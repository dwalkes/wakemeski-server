<?php
/*
 * Copyright (c) 2008 doanac@gmail.com
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once('weather.inc');

/**
 * Set to true to turn on debug logging
 */
$debug_support = false;

/**
 * Log a debug message if debug support is configured
 * @param $string
 */
function debug_log($string)
{
	global $debug_support;
	if( $debug_support )
	{
		$log_handle = fopen('.cache/debug_log.txt','a');
		if($log_handle != false)
		{
			fwrite($log_handle,$string."\n");
			fclose($log_handle);
		}
	}
}


/**
 * Uses the regular expression to find a value. if the value isn't found
 * the value "n/a" will be returned.
 *
 * matchidx can be used if the regex has more than one matching pattern
 */
function find_int($regex, $text, $matchidx=1)
{
	if( !preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE) )
		return 'n/a';

	if( $matches[$matchidx][0][0] === FALSE )
		return 'n/a';

	return $matches[$matchidx][0][0];
}

/**
 * Does a two-phase grep of the data to find in the report
 * Finds any general value
 */
function grep_grep($exp1, $exp2, $data)
{
	if( !preg_match_all($exp1, $data, $m1, PREG_OFFSET_CAPTURE) )
		return false;

	if( !preg_match_all($exp2, $m1[1][0][0], $m2, PREG_OFFSET_CAPTURE) )
		return false;

	return trim($m2[1][0][0]);
}

/**
* Does a two-phase grep of the data to find in the report
* finds an integer value (sets to n/a if not found)
*/
function grep_grep_int($exp1, $exp2, $data)
{
	if( !preg_match_all($exp1, $data, $m1, PREG_OFFSET_CAPTURE) )
		return false;

	return find_int($exp2,$m1[1][0][0]);
}

/**
* @return true if an integer value was found (including an integer
* value of 0
*/
function int_found($int)
{
	return $int != 'n/a';
}

/**
* @return a timestamp representing the most recent data found for the resort or
 *  FALSE if no timestamp could be found
 */
function get_report_timestamp($filename)
{
	return filemtime($filename.".report-date");
}

/**
 * Returns true if the given cache file is available
 * @param resort
 * @param cache file name
 * @param - age default of 7200=(2 hours in seconds)
 */
function cache_available($resort,$file, $age=7200)
{
	if( $_GET['nocache'] == '1' )
		return false;
	$date_format="F j, Y, g:i a";

	$file = '.cache/'.$file;
	if( is_readable($file))
	{
		// Attempt to adjust age if report timestamp indicates yesterday's date
		$report_ts = get_report_timestamp($file);
		debug_log('report ' . $resort->name . ' timestamp ' . date($date_format,$report_ts));
		if( $report_ts != FALSE )
		{
			// current time in GMT
			$gmtime = (int)gmdate('U');
			// sunrise time in GMT at this resort's location
			$sunrise_time = date_sunrise($gmtime,SUNFUNCS_RET_TIMESTAMP,$resort->lat,$resort->lon);
			debug_log('gmtime '.date($date_format,$gmtime));
			debug_log('sunrise_time ts'.$sunrise_time. ' date ' .date($date_format,$sunrise_time) . ' lat '. $resort->lat . ' lon ' . $resort->lon);
			/**
			* Is it before sunrise time in this resort location?
			*/
			if( $gmtime < $sunrise_time )
			{
				$report_time_array=getdate($report_ts);
				$gmtime_array=getdate($gmtime);
				debug_log('report_time_array '. implode(',',$report_time_array) . 'gmtime_array ' . implode(',',$gmtime_array));
				/**
				 * if it's yesterdays report and we are waiting for the new one... decrease the
				 * polling interval from the default to make sure we get the most recent report available.
				 */
				if( (($report_time_array['wday']+1)%7) == $gmtime_array['wday'] )
				{
					if( $age > 10*60 )
					{
						debug_log('cap polling rate at 10 minutes');
						// cap the polling rate at 10 minutes if we're waiting for new ski data
						$age = 10*60;
					}
				}

			}
		}
		//get modification time stamp. If its less than
		//age, use that copy
		$mod = filemtime($file);
		if( time() - $mod < $age )
			return true;
	}
	return false;
}

/**
 * Creates the cache file with the report information
 */
function cache_create($resort, $file, $report)
{
	$file = '.cache/'.$file;

	//open in append mode, since opening in write mode would truncate
	//file even if we didn't have the lock
	$fp = fopen($file, 'a');

	if( $fp && flock($fp, LOCK_EX) )
	{
		ftruncate($fp, 0) ; // erase the contents such as 'w+'
		if( $report )
		{
			$report['location'] = $resort->name;
			$report['location.info'] = $resort->info;
			$report['fresh.source.url']=$resort->fresh_source_url;

			if( !isset($report['weather.url']) )
				Weather::set_props($resort->lat, $resort->lon, &$report);

			if( !isset($report['location.latitude']) && $resort->lat >0)
			{
				$report['location.latitude'] = $resort->lat;
				$report['location.longitude'] = $resort->lon;
			}

			$keys = array_keys($report);
			for($i = 0; $i < count($keys); $i++)
			{
				$key = $keys[$i];
				fwrite($fp, $key.' = '.$report[$key]."\n");
			}
			/*
			* Create a cache file with modification time equal to the report date
			*/
			touch($file.".report-date", strtotime($report['date']));
		}
		else
		{
			fwrite($fp, "err.msg=No ski report data found\n");
		}

		flock($fp, LOCK_UN);
		fclose($fp);
	}
}

/**
 * Dumps the cache file out
 */
function cache_dump($file, $found)
{
	$file = '.cache/'.$file;
	$fp = fopen($file, 'r');

	if( $fp && flock($fp, LOCK_SH) )
	{
		while( $line = fgets($fp) )
			print $line;

		flock($fp, LOCK_UN);
		fclose($fp);

		$val = '0';
		if( $found )
			$val = '1';
		print "cache.found=$val\n";
	}
	else
	{
		print "err.msg=No ski report data found: $file\n";
	}
}

function create_loc($name, $url_base, $code, $lat=0, $lon=0, $info_url='',
			$data='')
{
	return (object)array('name'=>$name, 'url_base'=>$url_base, 'code'=>$code,
				'info'=>$info_url, 'lat'=>$lat, 'lon'=>$lon,
				'data'=>$data,
				/*
				 * Allocate a member for storing fresh source URL info.  Will
				 * be populated by each individual script if different than info_url
				 */
				'fresh_source_url'=> $info_url);
}

function create_region($name, $locations)
{
	return (object)array('name'=>$name, 'locations'=>$locations);
}

/**
 * Returns the resort information for the given location code or exits the
 * program with an error
 */
function resort_get_location($resorts, $code)
{
	foreach( $resorts as $resort )
	{
		if( $resort->code == $code )
			return $resort;
	}

	print "err.msg=invalid location: $code\n";
	exit(1);
}

/**
 * Logs access to a particular page by a particular user so that we can
 * understand how people are using this application.
 */
function log_hit($page, $location='null', $found_cache=false)
{
	$file = './hits_log';

	$id = 'n/a';
	if( isset($_GET['id']) )
		$id = $_GET['id'];

	if( $found_cache )
		$found_cache = 'cached';
	else
		$found_cache = 'uncached';

	//open in append mode, since opening in write mode would truncate
	//file even if we didn't have the lock
	$fp = fopen($file, 'a');

	if( $fp && flock($fp, LOCK_EX) )
	{
		$time = $_SERVER['REQUEST_TIME'];

		fwrite($fp, "$id\t$time\t$page\t$location\t$found_cache\n");

		flock($fp, LOCK_UN);
		fclose($fp);
	}
}

?>
