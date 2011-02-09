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

require_once('nh.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_nh_get();
	$resort = resort_get_location($resorts, $location);

	$cache_file = 'nh_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('nh_report.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$report = get_report($resort);
	if( $report )
		cache_create($resort, $cache_file, $report);
}

function get_report($resort)
{
	$resort->fresh_source_url = 'http://www.skinh.com/skirss.cfm?id='.$resort->data;
	$contents = file_get_contents($resort->fresh_source_url);

	$report = get_report_props($contents);
	return $report;
}

function get_report_props($body)
{
	$data = array();

	$data['snow.units'] = 'inches';

	preg_match("/<description>(.*)Conditions as of (.*)&lt/", $body, $matches);
	$data['date'] = $matches[2];

	$data['snow.fresh'] = find_int("/New Snow:\s+(\d+)/", $body);
	$data['snow.daily'] = 'Fresh('.$data['snow.fresh'].')';

	preg_match("/Average Base:\s+(\d+)-(\d+)/", $body, $matches);
	$data['snow.total'] = $matches[1].' '.$matches[2];

	preg_match("/Trails Open:\s+(\d+)\s+of\s+(\d+)/", $body, $matches);
	$data['trails.open'] = $matches[1];
	$data['trails.total'] = $matches[2]; 

	preg_match("/Lifts Open:\s+(\d+)\s+of\s+(\d+)/", $body, $matches);
	$data['lifts.open'] = $matches[1];
	$data['lifts.total'] = $matches[2]; 

	if( preg_match("/Surface Condition:(.*)Legend\"&gt;(.*)&lt;\/a/", $body, $matches) )
		$data['snow.conditions'] = $matches[2];

	return $data;
}

?>
