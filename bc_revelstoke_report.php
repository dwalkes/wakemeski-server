<?php
/*
 * Copyright (c) 2010 Andy Doan, Dan Walkes
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

require_once('bc.inc');
require_once('reportbase.inc');

class BCReportRev extends ReportBase
{
	public function run($location)
	{
		$resorts = resorts_bc_get();
		$resort = resort_get_location($resorts, $location);

		$resort->fresh_source_url = $resort->data;

		$cache_file = 'bc_'.$location.'.txt';
		$found_cache = cache_available($resort,$cache_file);
		if( !$found_cache )
		{
			$this->write_report($resort, $cache_file);
		}

		cache_dump($cache_file, $found_cache);

		log_hit('bc_revelstoke_report.php', $location, $found_cache);
	}

	function get_report($resort)
	{
		$props = array();

		$report = self::download($resort);

		$props['snow.units'] = 'cm';

		$props['details.url'] = $resort->fresh_source_url;

		preg_match("/Timestamp: (.*?)</", $report, $matches);
		$props['date'] = $matches[1];

		preg_match_all("/emph'>(.*?)\s+cm/", $report, $matches);

		$props['snow.fresh'] = $matches[1][0];
		$props['snow.daily'] = "Hourly(".$matches[1][1].") 24hr(".$matches[1][0].")";

		$props['snow.total'] = $matches[1][2];

		preg_match("/'giant'>(.*?)° C/", $report, $matches);
		$props['temp.readings'] = $matches[1];

		//TODO? $props['trails.open'] = $matches[2];
		//TODO? $props['trails.total'] = $matches[3];

		self::get_weather_props(&$props);

		return $props;
	}

	static function get_weather_props($props)
	{
		$props['weather.url'] = "http://www.weatheroffice.gc.ca/city/pages/bc-65_metric_e.html";
		$report = self::get_weather_report($props);

		self::get_weather_icon($report, &$props);

		//remove the contents up to the actual weather so that we can get
		//the proper list:
		$report = strstr($report, "<div class=\"fdetails\">");

		preg_match_all("/<dt>(.*?)<\/dt>\s+<dd>(.*?)<\/dd>/", $report, $matches);

		for($i = 0; $i < 3; $i++)
		{
			$props['weather.forecast.when.'.$i] = $matches[1][$i];
			$props['weather.forecast.desc.'.$i] = $matches[2][$i];
		}
	}

	static function get_weather_icon($report, $props)
	{
		if(preg_match("/id=\"currentimg\" src=\"\/weathericons\/(.*?).gif/", $report, $matches) )
			$props['weather.icon'] = bc_get_weather_icon($matches[1]);
	}

	static function get_weather_report($props)
	{
		$contents = file_get_contents($props['weather.url']);

		//its easier to parse if one line
		return str_replace("\n", "\t", $contents);
	}

	static function download($resort)
	{
		$contents = file_get_contents($resort->fresh_source_url);
		$idx = strpos($contents, "Timestamp:");
		if( $idx !== false )
		{
			$idx2 = strpos($contents, "</div>", $idx);
			$contents = substr($contents, $idx, $idx2-$idx);
		}
		return $contents;
	}
}
$report_class = 'BCReportRev';
ReportBase::run_cgi($report_class);
?>
