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

require_once('nm.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_nm_get();
	$resort = resort_get_location($resorts, $location);

	$resort->fresh_source_url = "http://skinewmexico.com/snow_reports/feed.rss";
		
	$cache_file = 'nm_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('nm_report.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$report = get_report($resort);
	if( $report )
		cache_create($resort, $cache_file, $report);
}

function get_report($resort)
{
	$contents = file_get_contents($resort->fresh_source_url);

	//each location is in an <item> tag
	$locations = preg_split("/<item>/", $contents);
	//the first item is header junk we can ignore
	array_shift($locations);

	$reports = array();
	for($i = 0; $i < count($locations); $i++)
	{
		$report = get_report_props($locations[$i]);
		/*
		 * This report changed in 2010 to use "Angel Fire Resort" and
		 * list "x-c" (cross country?) after Enchanted Forest.  Also uses "Taos Ski Valley" instead
		 * of Taos.  Use a strpos check instead of a == to handle these cases
		 */
		$pos = strpos($report['location'],$resort->name);
		if( $pos !== false )
			return $report;
	}
}

function get_report_props($body)
{
	$data = array();
	preg_match_all("/<h1>(.*)<\/h1/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['location'] = $matches[1][0][0];

	preg_match_all("/<pubDate>(.*)<\/pubDate>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['date'] = $matches[1][0][0];

	$data['snow.fresh'] = find_int("/New Natural Snow Last 48 Hours: <b>(\d+)/", $body);

	$data['snow.daily'] = 'n/a';
	if( $data['snow.fresh'] != 'n/a' )
		$data['snow.daily'] = 'Fresh('.$data['snow.fresh'].')';

	$data['snow.units'] = 'inches';

	$data['snow.total'] = find_int("/Base Snow Depth \(inches\): <b>(\d+)&quot;/", $body);

	$data['trails.open'] = find_int("/Trails Open: <b>(\d+)/", $body);
	$data['lifts.open']  = find_int("/Lifts Open: <b>(\d+)/", $body);

	preg_match_all("/Surface Cond&#58; (.*?)<\/title>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.conditions'] = $matches[1][0][0];

	//NOTE: The comment (for Sandia Peak) is multi. So the regex is missing it
	//      We combine things as one line so it will find it
	$body = str_replace("\n", " ", $body);	
	preg_match_all("/<p>Comments:\s+<b>(.*?)<\/b>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['location.comments'] = trim($matches[1][0][0]);

	return $data;
}

?>
