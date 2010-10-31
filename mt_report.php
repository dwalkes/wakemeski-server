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

require_once('mt.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_mt_get();
	$resort = resort_get_location($resorts, $location);
	$resort->fresh_source_url = "http://feeds.visitmt.com/rss/?feedid=15";

	$cache_file = 'mt_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

function write_report($resort, $cache_file)
{
	$report = get_report($resort);
	$resort->info = $report['location.info'];
	if( $report )
		cache_create($resort, $cache_file, $report);
}


/**
 * Grabs the RSS feed turns the values for the given report into a hash of:
 *  loc => {snow.daily='24hr, 48hr', snow.total="total", ..}
 */
function get_report($resort)
{
	//note the content is technically XML, but its a pretty loose form.
	//its actually easier to break up with regular expressions than dealing
	//with it as a DOM
	$contents = file_get_contents($resort->fresh_source_url);

	//make everything one line for the regular expressions to work
	$contents = str_replace("\n", "\t",  $contents);

	//each location is in an <item> tag
	$locations = preg_split("/<item>/", $contents);
	//the first item is header junk we can ignore
	array_shift($locations);

	$reports = array();
	for($i = 0; $i < count($locations); $i++)
	{
		$r = get_report_props($resort, $locations[$i]);
		if( $r )
		{
			if( $report )
			{
				//we've hit a duplicate report, make sure we use the latest
				$t1 = strtotime($r['date']);
				$t2 = strtotime($report['date']);
				if( $t2 > $t1 )
					continue;
			}
			$report = $r;
		}
	}

	return $report;
}

function get_report_props($resort, $body)
{
	$data = array();
	preg_match_all("/<title>(.*)<\/title>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$title = $matches[1][0][0];
	if( !strstr($title, $resort->name) )
		return null;

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

?>
