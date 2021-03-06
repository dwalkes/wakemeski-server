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

require_once('co.inc');
require_once('reportbase.inc');

class COReport extends ReportBase
{
	public function run($location)
	{
		$resorts = resorts_co_get();
		$resort = resort_get_location($resorts, $location);

		$resort->fresh_source_url = "http://feeds.feedburner.com/snowreport";

		$cache_file = 'co_'.$location.'.txt';
		$found_cache = cache_available($resort,$cache_file);
		if( !$found_cache )
		{
			$this->write_report($resort, $cache_file);
		}

		cache_dump($cache_file, $found_cache);

		log_hit('co_report.php', $location, $found_cache);
	}

	function get_report($resort)
	{
		$xml = self::get_location_report($resort);
		if( $xml )
			return self::get_report_props($xml);
		return array();
	}

	/**
	 * Takes the report's XML node and parse the information out into a hashtable
	 */
	static function get_report_props($report)
	{
		$props = array();
		$data = $report->getElementsByTagName('description')->item(0)->nodeValue;

		$props['snow.daily'] = 'n/a';
		$props['snow.fresh'] = 'n/a';

		$day = find_int("/New Snow Last 24 hours: (\d+)/", $data);
		$yesterday = find_int("/New Snow Last 48 hours: (\d+)/", $data);

		if( $day != 'n/a' )
		{
			$props['snow.daily'] = "Fresh($day)";
			$props['snow.fresh'] = $day;
		}

		if( $yesterday != 'n/a' && $day != 'n/a' )
			$props['snow.daily'] .= " 48hr($yesterday)";
		else if( $yesterday != 'n/a' )
			$props['snow.daily'] = "48hr($yesterday)";

		$props['snow.units'] = 'inches';

		$props['snow.total'] = find_int("/Mid Mountain Depth: (\d+)/", $data);

		preg_match_all("/Lifts Open: (\d+)\/(\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
		if($matches[1][0][0])
		{
			$props['lifts.open'] = $matches[1][0][0];
			$props['lifts.total'] = $matches[2][0][0];
		}
		else
		{
			preg_match_all("/Lifts Open: (\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
			if($matches[1][0][0])
				$props['lifts.open'] = $matches[1][0][0];
		}

		preg_match_all("/Surface Conditions: (.*?)<br/", $data, $matches, PREG_OFFSET_CAPTURE);
		if( $matches[1][0][0] )
			$props['snow.conditions'] = $matches[1][0][0];

		preg_match_all("/Comments:\s+(.*)/", $data, $matches, PREG_OFFSET_CAPTURE);
		if( $matches[1][0][0] )
		{
			$props['location.comments'] = strip_tags($matches[1][0][0]);
			//ensure we don't just give an empty comment
			if( $props['location.comments'] == '--' )
				unset($props['location.comments']);
		}

		$date = $report->getElementsByTagName('pubDate')->item(0)->nodeValue;
		$date = strtotime($date);
		$props['date'] = date("h:ia M j", $date);

		$props['details.url'] = $report->getElementsByTagName('link')->item(0)->nodeValue;

		return $props;
	}

	/**
	 * Returns the XML node containing the report for a given location or false
	 * if one is not found
	 */
	static function get_location_report($resort)
	{
		$dom = self::get_report_xml($resort);

		$items = $dom->getElementsByTagName('item');
		for ($i = 0; $i < $items->length; $i++)
		{
			$title_node = $items->item($i)->getElementsByTagName('title')->item(0);
			$title = trim($title_node->firstChild->nodeValue);
			$name = $resort->name;
			if(preg_match("/$name/", $title) )
			{
			    return $items->item($i);
			}
		}

		return false;
	}

	static function get_report_xml($resort)
	{
		$xml = file_get_contents($resort->fresh_source_url);
		$sxe = simplexml_load_string($xml);
		return dom_import_simplexml($sxe);
	}
}
$report_class = 'COReport';
ReportBase::run_cgi($report_class);
?>
