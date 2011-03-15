<?php
/*
 * Copyright (c) 2011 Andy Doan, Dan Walkes
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

require_once('me.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_me_get();
	$resort = resort_get_location($resorts, $location);

	$cache_file = 'me_'.$location.'.txt';
	$found_cache = cache_available($resort,$cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);
	log_hit('me_report.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$report = get_report($resort);
	if( $report )
		cache_create($resort, $cache_file, $report);
}

function get_report($resort)
{
	$report = array();
	$contents = file_get_contents($resort->fresh_source_url);

	$report['snow.units'] = 'inches';

	if( preg_match("/report-title\"><strong>\s+(.*?)\s+</", $contents, $matches) )
		$report['date']  = $matches[1];

	$report['trails.open'] = find_int("/Trails Open(.*?)(\d+)/", $contents, 2);
	$report['lifts.open'] = find_int("/Lifts Open(.*?)(\d+)/", $contents, 2);
	if( preg_match("/Primary Surface(.*?):(.*?)</", $contents, $matches) )
		$report['snow.conditions'] = strip_tags($matches[2]);
	$report['snow.total'] = find_int("/Average Base Depth(.*?)(\d+)/", $contents, 2);

	$snow_new = find_int("/New Snow(.*?)(\d+)/", $contents, 2);
	$snow_week = find_int("/Past 7 Days Snow(.*?)(\d+)/", $contents, 2);
	$report['snow.fresh'] = $snow_new;
	$report['snow.daily'] = "Fresh($snow_new) Week($snow_week)";

	return $report;
}

?>
