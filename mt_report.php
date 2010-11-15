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
	$resort->fresh_source_url = "http://wintermt.com/skiareas/conditions/snow.asp?id=".$resort->data;

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
	if( $report )
		cache_create($resort, $cache_file, $report);
}

/**
 * Does a two-phase grep of the data to find in the report
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
 * Grabs the RSS feed turns the values for the given report into a hash of:
 *  loc => {snow.daily='24hr, 48hr', snow.total="total", ..}
 */
function get_report($resort)
{
	$contents = file_get_contents($resort->fresh_source_url);

	//make everything one line for the regular expressions to work
	$contents = str_replace("\r\n", " ", $contents);

	$date = grep_grep("/Date of Report:\s+<\/b><\/td>(.*?)<\/td>/", "/(\d+\/\d+\/\d+)/", $contents);
	$time = grep_grep("/Time Reported:\s+<\/b><\/td>(.*?)<\/td>/", "/(\d+:\d+:\d+)/", $contents);

	$new = grep_grep("/Snow In Last 24 hrs:<\/b><\/td>(.*?)<\/td>/", "/(\d+)\"/", $contents);
	$night = grep_grep("/New Snow Overnight:\s+<\/b><\/td>(.*?)<\/td>/", "/(\d+)\"/", $contents);

	$surface = grep_grep("/<b>Surface:\s+<\/b><\/td>(.*?)<\/tr>/", "/>(.*?)<\/td>/", $contents);
	$temp = grep_grep("/Temperature:\s+<\/b><\/td>(.*?)<\/tr>/", "/>\s+(\d+)/", $contents);

	$d_top = grep_grep("/Summit Depth:\s+<\/b><\/td>(.*?)<\/td>/", "/(\d+)/", $contents);
	$d_low = grep_grep("/Lower Mountain Depth:\s+<\/b><\/td>(.*?)<\/td>/", "/(\d+)/", $contents);
	$lifts = grep_grep("/Lifts Open:\s+<\/b><\/td>(.*?)<\/td>/", "/(\d+)/", $contents);

	$report = array();
	$report['date'] = $time." ".$date;

	if($night)
	{
		$report['snow.fresh'] = $night;
		$report['snow.daily'] = "Fresh($night) ";
	}
	if($new)
		$report['snow.daily'] .= "24hr($new)"; 
	$report['snow.units'] = 'inches';

	if($d_low)
		$report['snow.total'] = $d_low." ";
	if($d_top)
		$report['snow.total'] .= $d_top;

	if($temp)
		$report['temp.readings'] = $temp;

	if($surface)
		$report['snow.conditions'] = $surface;

	if($lifts)
		$report['lifts.open'] = $lifts;

	return $report;
}

?>
