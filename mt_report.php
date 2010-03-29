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

require_once('common.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = build_resorts_table();

	resort_assert_location($resorts, $location);

	$cache_file = 'mt_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($location);
	}

	cache_dump($cache_file, $found_cache);

function write_report($loc)
{
    global $cache_file, $resorts;

	$reports = get_reports($resorts);
	$report = $reports[$loc];
	if( $report )
	{
		resort_set_weather($resorts, $loc, &$report);

		cache_create($cache_file, $report);
	}
	else
	{
		print("err.msg=No ski report data found\n");
	}
}

/**
 * Grabs the RSS feed turns the values for the given report into a hash of:
 *  loc => {snow.daily='24hr, 48hr', snow.total="total", ..}
 */
function get_reports($resorts)
{
	//note the content is technically XML, but its a pretty loose form.
	//its actually easier to break up with regular expressions than dealing
	//with it as a DOM
	$contents = file_get_contents("http://feeds.visitmt.com/rss/?feedid=15");

	//make everything one line for the regular expressions to work
	$contents = str_replace("\n", "\t",  $contents);

	//each location is in an <item> tag
	$locations = preg_split("/<item>/", $contents);
	//the first item is header junk we can ignore
	array_shift($locations);

	$reports = array();
	for($i = 0; $i < count($locations); $i++)
	{
		$report = get_report($resorts, $locations[$i]);
		$loc = resort_get_location($resorts, $report['location']);
		if( $reports[$loc] )
		{
			//we've hit a duplicate report, make sure we use the latest
			$t1 = strtotime($report['date']);
			$t2 = strtotime($reports[$loc]['date']);
			if( $t2 > $t1 )
				continue;
		}
		$reports[$loc] = $report;
	}

	return $reports;
}

function get_report($resorts, $body)
{
	$data = array();
	preg_match_all("/<title>(.*)<\/title>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$loc = resort_get_location($resorts, $matches[1][0][0]);
	$data['location'] = resort_get_readable_location($resorts, $loc);

	preg_match_all("/<link>(.*)<\/link>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['location.info'] = $matches[1][0][0];

	preg_match_all("/<pubDate>(.*)<\/pubDate>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['date'] = $matches[1][0][0];

	preg_match_all("/<STRONG>New Snow:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(.*?)<\/TD>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$new = $matches[1][0][0];
	preg_match_all("/Snow in Last 24 Hours<\/STRONG><\/TD><TD ALIGN='RIGHT'>(\d+)<\/TD>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$day = $matches[1][0][0];
	if( !$new ) $new = 0;
	if( !$day ) $day = 0;
	$data['snow.daily'] = "Fresh($new) 24hr($day)";
	$data['snow.fresh'] = $new;
	$data['snow.units'] = 'inches';

	preg_match_all("/<STRONG>Snow Depth:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(\d+)<\/TD><\/TR>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.total'] = $matches[1][0][0];
	else
		$data['snow.total'] = '--';

	preg_match_all("/Tempurature:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(.*?)<\/TD>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( !$matches[1][0][0] )
		preg_match_all("/Temperature:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(.*?)<\/TD>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['temp.readings'] = $matches[1][0][0];


	preg_match_all("/Surface Conditions:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(.*?)<\/TD><\/TR>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.conditions'] = $matches[1][0][0];

	return $data;
}

function build_resorts_table()
{
	$resorts['TR'] = resort_props('Turner Mountain',            array(48.604996, -115.630793));
	$resorts['MM'] = resort_props('Maverick Mountain',          array(45.434924, -113.1294876));
	$resorts['LM'] = resort_props('Lost Trail Powder Mountain', array(45.692912, -113.95166));
	$resorts['BP'] = resort_props('Bear Paw Ski Bowl',          array(48.164456, -109.670357));
	$resorts['LP'] = resort_props('Lookout Pass',               array(47.456233, -115.696404));
	$resorts['DS'] = resort_props('Discovery',                  array(46.248783, -113.239448));
	$resorts['BT'] = resort_props('Blacktail Mountain',         array(48.014908, -114.369712));
	$resorts['BS'] = resort_props('Big Sky',                    array(45.284,    -111.402151));
	$resorts['GD'] = resort_props('Great Divide',               array(46.752688, -112.312891));
	$resorts['SD'] = resort_props('Showdown',                   array(46.838133, -110.698483));
	$resorts['TP'] = resort_props('Teton Pass',                 array(47.928807, -112.805196));
	$resorts['WF'] = resort_props('Whitefish',                  array(48.484887, -114.353367));
	$resorts['RL'] = resort_props('Red Lodge',                  array(45.190749, -109.336372));
	$resorts['BB'] = resort_props('Bridger Bowl',               array(45.817659, -110.8958));
	$resorts['MS'] = resort_props('Montana Snowbowl',           array(47.013869, -113.999649));
	$resorts['MB'] = resort_props('Moonlight Basin',            array(45.311904, -111.436659));

	return $resorts;
}

?>
