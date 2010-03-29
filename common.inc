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
 * Returns true if the given cache file is available
 * @param - age default of 7200=(2 hours in seconds)
 */
function cache_available($file, $age=7200)
{
	if( $_GET['nocache'] == '1' )
		return false;

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
function cache_create($file, $report)
{
	$fp = fopen($file, 'w');

	if( $report )
	{
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

	fclose($fp);
}

/**
 * Dumps the cache file out
 */
function cache_dump($file, $found)
{
	if( is_readable($file) )
	{
		$val = '0';
		if( $found )
			$val = '1';
		print file_get_contents($file);
		print "cache.found=$val\n";
	}
	else
	{
		print "err.msg=No ski report data found: $file\n";
	}
}

/**
 * Creates a hash map with standard property names needed for resorts
 * @param readable - the readable name of the location
 * @param lat_lon - an array(0=lat, 1=lon)
 * @param info - the "location.info" property
 */
function resort_props($readable, $lat_lon, $info='')
{
	return array('readable'=>$readable, 'lat_lon'=>$lat_lon, 'info'=>$info);
}

/**
 * Ensures the given set of report props includes the given location
 */
function resort_assert_location($resort_props, $loc)
{
	if( ! array_key_exists($loc, $resort_props) )
	{
		print "err.msg=invalid location: $loc\n";
		exit(1);
	}
}

function resort_get_readable_location($resorts, $loc)
{
	return $resorts[$loc]['readable'];
}

/**
 * performs the reverse of resort_get_readable_location. It actually checks
 * to see if the readable location is *anywhere* in $readable_location
 */
function resort_get_location($resorts, $readable_location)
{
	$keys = array_keys($resorts);
	for($i = 0; $i < count($keys); $i++)
	{
		$key = $keys[$i];
		if( strstr($readable_location, $resorts[$key]['readable']) )
			return $key;
	}

	print "err.msg=invalid readable location: $readable_location\n";
	exit(1);
}

function resort_get_info_url($resorts, $loc)
{
	return $resorts[$loc]['info'];
}

function resort_set_weather($resorts, $loc, $props)
{
	list($lat, $lon) = $resorts[$loc]['lat_lon'];
	Weather::set_props($lat, $lon, &$props);
}

?>
