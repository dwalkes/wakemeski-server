<?php
/*
 * Copyright (c) 2008 nombre.usario@gmail.com
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

	header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	//first validate the location:
	if(!getReadableLocation($location))
	{
		print "err.msg=invalid location: $location\n";
		exit(1);
	}

	$found_cache = have_cache($location);
	if( !$found_cache )
	{
		write_report($location);
	}

	$cache_file = "ut_$location.txt";
	if( is_readable($cache_file) )
	{
		print file_get_contents("ut_$location.txt");
		print "cache.found=$found_cache\n";
	}
	else
	{
		print "err.msg=No ski report data found\n";
	}


function have_cache($location)
{
	$file = "ut_$location.txt";
	if( is_readable($file))
	{
		//get modification time stamp. If its less than
		//120 minutes old, use that copy
		$mod = filemtime($file);
		if( time() - $mod < 7200 ) //=60*120 = 120 minutes
		{
			return 1;
		}
	}
	return 0;
}

function write_report($loc)
{
	$report = get_report($loc);
	if( $report )
	{
		$fp = fopen("ut_$loc.txt", "w");

		$keys = array_keys($report);
		for($j = 0; $j < count($keys); $j++)
		{
			$key = $keys[$j];
			fwrite($fp, $key.' = '.$report[$key]."\n");
		}

		list($lat, $lon) = get_lat_lon($loc);
		list($icon, $url) = Weather::get_report($lat, $lon);
		fwrite($fp, "location.latitude=$lat\n");
		fwrite($fp, "location.longitude=$lon\n");
		fwrite($fp, "weather.url=$url\n");
		fwrite($fp, "weather.icon=$icon\n");

		fclose($fp);
	}
}

function get_url($loc)
{
	if( $loc == 'ATA') $val = 'alta_ski_area';
	else if( $loc == 'BVR') $val = 'beaver_mountain';
	else if( $loc == 'BHR') $val = 'brian_head_resort';
	else if( $loc == 'BRT') $val = 'brighton_ski_resort';
	else if( $loc == 'CNY') $val = 'the_canyons';
	else if( $loc == 'DVR') $val = 'deer_valley_resort';
	else if( $loc == 'PCM') $val = 'park_city_mountain_resort';
	else if( $loc == 'POW') $val = 'powder_mountain_resort';
	else if( $loc == 'SBN') $val = 'snowbasin';
	else if( $loc == 'SBD') $val = 'snowbird_ski_and_summer_resort';
	else if( $loc == 'SOL') $val = 'solitude_mountain_resort';
	else if( $loc == 'SUN') $val = 'sundance_resort';
	else if( $loc == 'WLF') $val = 'wolf_creek_utah_ski_resort';

	return 'http://www.skiutah.com/winter/members/'.$val.'/resort';
}

function get_report_contents($url)
{
	$contents = file_get_contents($url);

	$idx1 = strpos($contents, "v-ski_resort_snow_report");
	$idx2 = strpos($contents, "#v-ski_resort_snow_report");
	if( $idx1 === false || $idx2 === false)
	{
		print "err.msg=report format changed. server update required\n";
		exit(1);
	}

	return substr($contents, $idx1, $idx2-$idx1);
}

function get_report($loc)
{
	$url = get_url($loc);
	$contents = get_report_contents($url);

	$data = array();
	$data['location'] = getReadableLocation($loc);

	$data['location.info'] = $url;

	preg_match_all("/Updated: <span>(.*)<\/span/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['date'] = $matches[1][0][0];

	preg_match_all("/Snow Last 24<\/th><td>(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['snow.daily'] = "Fresh(".$matches[1][0][0].")";

	preg_match_all("/Snow Last 48<\/th><td>(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['snow.daily'] .= " 48hr(".$matches[1][0][0].")";

	preg_match_all("/Base Depth<\/th><td>(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['snow.total'] = $matches[1][0][0];

	preg_match_all("/<span>(\d+)<\/span>\/<span>(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['trails.open'] = $matches[1][0][0];
	$data['trails.total'] = $matches[2][0][0];
	$data['lifts.open'] = $matches[1][1][0];
	$data['lifts.total'] = $matches[2][1][0];

	return $data;
}


/**
 * Turns a 3 digit code like ATA into Alta
 */
function getReadableLocation($loc)
{
	if( $loc == 'ATA') return 'Alta';
	if( $loc == 'BVR') return 'Beaver Mountain';
	if( $loc == 'BHR') return 'Brian Head';
	if( $loc == 'BRT') return 'Brighton';
	if( $loc == 'CNY') return 'The Canyons';
	if( $loc == 'DVR') return 'Deer Valley';
	if( $loc == 'PCM') return 'Park City';
	if( $loc == 'POW') return 'Powder Mountain';
	if( $loc == 'SBN') return 'Snowbasin';
	if( $loc == 'SBD') return 'Snowbird';
	if( $loc == 'SOL') return 'Solitude';
	if( $loc == 'SUN') return 'Sundance';
	if( $loc == 'WLF') return 'Wolf Creek';
}

//turns something like "Alta" into ATA
function getLocation($resort)
{
	if( strstr($resort, "Alta") ) return "ATA";
	if( strstr($resort, "Beaver Mountain") ) return "BVR";
	if( strstr($resort, "Brian Head") ) return "BHR";
	if( strstr($resort, "Brighton") ) return "BRT";
	if( strstr($resort, "The Canyons") ) return "CNY";
	if( strstr($resort, "Deer Valley") ) return "DVR";
	if( strstr($resort, "Park City") ) return "PCM";
	if( strstr($resort, "Powder Mountain") ) return "POW";
	if( strstr($resort, "Snowbasin") ) return "SBN";
	if( strstr($resort, "Snowbird") ) return "SBD";
	if( strstr($resort, "Solitude") ) return "SOL";
	if( strstr($resort, "Sundance") ) return "SUN";
	if( strstr($resort, "Wolf Creek") ) return "WLF";
}

function get_lat_lon($loc)
{
	if( $loc == 'ATA')
		return array(40.57972, -111.6375);
	if( $loc == 'BVR')
		return array(41.96833, -111.54083);
	if( $loc == 'BHR')
		return array(37.69194, -112.83722);
	if( $loc == 'BRT')
		return array(40.6, -111.58278 );
	if( $loc == 'CNY')
		return array(40.685257, -111.556375);
	if( $loc == 'DVR')
		return array(40.63139, -111.47861 );
	if( $loc == 'PCM')
		return array(40.64361, -111.50417 );
	if( $loc == 'POW')
		return array(41.37778, -111.77111);
	if( $loc == 'SBN')
		return array(41.21194, -111.85111);
	if( $loc == 'SBD')
		return array(40.578052, -111.666755 );
	if( $loc == 'SOL')
		return array(40.62556, -111.59444 );
	if( $loc == 'SUN')
		return array(40.38583, -111.58083 );
	if( $loc == 'WLF')
		return array(40.47667, -111.02361 );
}

?>
