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
 * Returns true if the given cache file is available
 * @param - age default of 7200=(2 hours in seconds)
 */
function cache_available($file, $age=7200)
{
	if( $_GET['nocache'] == '1' )
		return false;

	$file = '.cache/'.$file;
	if( is_readable($file))
	{
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

			$keys = array_keys($report);
			for($i = 0; $i < count($keys); $i++)
			{
				$key = $keys[$i];
				fwrite($fp, $key.' = '.$report[$key]."\n");
			}
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
