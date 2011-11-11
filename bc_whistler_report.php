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

class BCReportWhis extends ReportBase
{
	public function run($location)
	{
		$resorts = resorts_bc_get();
		$resort = resort_get_location($resorts, $location);

		$cache_file = 'bc_'.$location.'.txt';
		$found_cache = cache_available($resort,$cache_file);
		if( !$found_cache )
		{
			$this->write_report($resort, $cache_file);
		}

		cache_dump($cache_file, $found_cache);

		log_hit('bc_whistler_report.php', $location, $found_cache);
	}

	function get_report($resort)
	{
		$props = array();

		$report = self::download($resort);

		$props['snow.units'] = 'cm';

		preg_match("/Last Updated:\s+(.*?)</", $report, $matches);
		//this string can have odd padding in between words, so stript it off
		$fields = preg_split("/\s+/", $matches[1]);
		$props['date'] = implode(' ', $fields);

		preg_match("/lline\">(\d+) cm(.*?)(\d+) cm(.*?)(\d+) cm(.*?)(\d+) cm(.*?)(\d+)/", $report, $matches);
		$new  = $matches[1];
		$hr24 = $matches[3];
		$hr48 = $matches[5];
		$week = $matches[7];
		$base = $matches[9];

		$props['snow.fresh'] = $hr24;
		$props['snow.daily'] = "Fresh($new) 24hr($hr24) 48hr($hr48) week($week)";

		$props['snow.total'] = $base;

		preg_match("/Peak<(.*?)width=\"25%\">(.*?)&deg;/", $report, $matches);
		$tempPeak = $matches[2];
		preg_match("/Alpine<\/td><td>(.*?)&deg;/", $report, $matches);
		$tempAlpine = $matches[1];
		preg_match("/Village<\/td><td>(.*?)&deg;/", $report, $matches);
		$tempVillage = $matches[1];
		$props['temp.readings'] = $tempVillage.' '.$tempAlpine.' '.$tempPeak;

		self::get_weather_props(&$props);

		return $props;
	}

	// kind of a hack. we don't get exact timestamps so we hack something
	static function get_weather_exact($label)
	{
		if($label == "Tonight")
		{
			//get the next midnight
			return strtotime("12:00am") + (24*60*60);
		}
		else
		{
			// these will be days of week like Monday. strtotime
			// returns the time at midnight. We add 6 hours so that
			// it will look like "Monday 6:00 am" in the app
			return strtotime($label) + (6*60*60);
		}
	}

	static function get_weather_interval($idx, $label, $offset, $props, $contents)
	{
		$props['weather.forecast.when.'.$idx] = $label;
		$props['weather.forecast.when-exact.'.$idx] = self::get_weather_exact($label);

		//move to the offset for this forecast
		$contents = substr($contents, $offset);

		if($idx == 1)
		{
			preg_match("/weathericons\/(.*?).gif/", $contents, $matches);
			$props['weather.icon'] = bc_get_weather_icon($matches[1]);
		}

		preg_match("/title='(.*?)'>/", $contents, $matches);
		$props['weather.forecast.desc.'.$idx] = $matches[1];
	}

	static function get_weather_props($props)
	{
		//make sure this is Pacific time in case the PHP server is another timezone
		//otherwise the strtotime functions won't work for this
		date_default_timezone_set('America/Los_Angeles');

		$props['weather.url'] = "http://movement.whistlerblackcomb.com/cache/whistler_fx.php";
		$contents = file_get_contents($props['weather.url']);

		//get the text to parse
		$idx1 = strpos($contents, "<table width='100%' cellpadding='0' cellspacing='0' class='icons' >");
		$idx2 = strpos($contents, "</table>", $idx1);
		$contents = substr($contents, $idx1, $idx2-$idx1);

		//split this up based on each interval (ie Sunday, Monday, Tuesday)
		// keep the offset's so we can parse the actual report
		preg_match_all("/<span class='titleS'>(.*?)<\/span>/", $contents, $matches, PREG_OFFSET_CAPTURE);

		self::get_weather_interval(0, $matches[1][0][0], $matches[1][0][1], &$props, $contents);
		self::get_weather_interval(1, $matches[1][1][0], $matches[1][1][1], &$props, $contents);
		self::get_weather_interval(2, $matches[1][2][0], $matches[1][2][1], &$props, $contents);
		self::get_weather_interval(3, $matches[1][3][0], $matches[1][3][1], &$props, $contents);
	}

	static function download($resort)
	{
		$contents = file_get_contents($resort->fresh_source_url);

		//strip off some the junk we don't need
		$contents = strstr($contents, "Last Updated");
		$idx = strpos($contents, ">Grooming</div>");
		return(substr($contents, 0, $idx));
	}
}
$report_class = 'BCReportWhis';
ReportBase::run_cgi($report_class);
?>
