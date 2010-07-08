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
function cache_create($resort, $file, $report)
{
	$fp = fopen($file, 'w');

	if( $report )
	{
		$report['location'] = $resort->name;
		$report['location.info'] = $resort->info;

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

function create_loc($name, $url_base, $code, $lat=0, $lon=0, $info_url='',
			$data='')
{
	return (object)array('name'=>$name, 'url_base'=>$url_base, 'code'=>$code,
				'info'=>$info_url, 'lat'=>$lat, 'lon'=>$lon,
				'data'=>$data);
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

?>
