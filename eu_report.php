<?php
/*
 * Copyright (c) 2008 nombre.usario@gmail.com
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *	  notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *	  notice, this list of conditions and the following disclaimer in the
 *	  documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *	  derived from this software without specific prior written permission.
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

require_once('eu.inc');
require_once('reportbase.inc');

class EUReport extends ReportBase
{
	public function run($location)
	{
		$resorts = resorts_eu_get();
		$resort = resort_get_location($resorts, $location);

		if( $resort->data )
			$resort->info = $resort->data;

		$cache_file = 'eu_'.$location.'.txt';
		$found_cache = cache_available($resort,$cache_file);
		if( !$found_cache )
		{
			$this->write_report($resort, $cache_file);
		}

		cache_dump($cache_file, $found_cache);

		log_hit('eu_report.php', $location, $found_cache);
	}

	function get_report($resort)
	{
		$report = self::download($resort->fresh_source_url);
		if( $report )
		{
			$props = self::get_report_props($resort->fresh_source_url, $report);

			//override what we may have parsed from the resort
			if( $resort->lat > 0 )
			{
				$props['location.latitude'] = $resort->lat;
				$props['location.longitude'] = $resort->lon;
			}
			return $props;
		}
	}

	/**
	 * Takes the report's XML node and parse the information out into a hashtable
	 */
	static function get_report_props($url, $report)
	{
		$props = array();

		preg_match_all("/<th>Upper<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
		$upper = $matches[1][0][0];
		preg_match_all("/<th>Lower<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
		$lower = $matches[1][0][0];
		$props['snow.total'] = "Lower ($lower), Upper($upper)";

		$num_matches = preg_match_all("/<th>Fresh Snow<\/th><td>(\d+)/", $report, $matches, PREG_OFFSET_CAPTURE);

		$fresh = $matches[1][0][0];

		if( $num_matches == 0 )
		{
			// if no matches were found looking for ints, look for a single - instead.
			// This means 0 fresh snow in this report.
			$num_matches = preg_match_all("/<th>Fresh Snow<\/th><td>-/", $report, $matches, PREG_OFFSET_CAPTURE);
			if( $num_matches > 0 )
			{
				$fresh = "0";
			}
		}

		$props['snow.daily'] = "Fresh(".$fresh."cm)";
		$props['snow.fresh'] = $fresh;
		$props['snow.units'] = 'cm';

		// Lifts open is reported as %
		if( preg_match_all("/<th>Lifts Open<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE) )
			$props['lifts.percent.open'] = $matches[1][0][0];

		//some places report "Pistes Open". Some do "area open"
		if( preg_match_all("/<th>Pistes Open<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE) )
			$props['trails.open'] = $matches[1][0][0];
		if( preg_match_all("/<th>Area Open<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE) )
			$props['trails.percent.open'] = $matches[1][0][0];

		preg_match_all("/<th>Snow<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
		$props['snow.conditions'] = $matches[1][0][0];

		preg_match_all("/reported (.*?)\.<\/p>/", $report, $matches, PREG_OFFSET_CAPTURE);
		$props['date'] = date('d M', strtotime($matches[1][0][0]));

		if( preg_match("/property=\"og:latitude\" content=\"(.*?)\"/", $report, $matches))
			$props['location.latitude'] = $matches[1];
		if( preg_match("/property=\"og:longitude\" content=\"(.*?)\"/", $report, $matches))
			$props['location.longitude'] = $matches[1];

		preg_match_all("/<p><a href=\"(.*?)\.html/", $report, $matches, PREG_OFFSET_CAPTURE);
		$page = $matches[1][0][0].".html";
		//this gives us chamonix_snow-forecast.html, now pull the base from the
		//report's url
		$idx = strrpos($url, '/');
		$base = substr($url, 0, $idx);
		$props['weather.url'] = $base."/".$page;

		self::get_weather(&$props);

		return $props;
	}

	function download($url)
	{
		return file_get_contents($url);
	}

	//parses the europe description and turns it into what we have coded
	//in the android client (which was based on weather.gov)
	static function get_icon($condition)
	{
		switch(strtolower($condition))
		{
			case 'sunny/clear':
			case 'clear skies':
				return 'skc';
			case 'fair': //cloud with sun
				return 'sct';
			case 'light snow':
				return 'mix';
			case 'snow':
			case 'heavy snow':
				return 'blizzard';
			case 'cloudy':
				return 'ovc';
			case 'partly cloudy': //little sun most cloud
				return 'bkn';
			case 'patchy light rain':
			case 'light rain':
			case 'rain':
				return 'ra';
		}
		return "unknown";
	}

	static function get_prop_array($weather, $prop)
	{
		if( preg_match_all("/<th>$prop<\/th><td.*?>(.*?)<\/td><td.*?>(.*?)<\/td><td.*?>(.*?)<\/td><td.*?>(.*?)<\/td><\/tr>/", $weather, $matches) )
		{
			$props[0] = $matches[1][0];
			$props[1] = $matches[2][0];
			$props[2] = $matches[3][0];
			$props[3] = $matches[4][0];

			return $props;
		}
	}

	static function get_temp_array($weather, $prop)
	{
		$props = self::get_prop_array($weather, $prop);
		if( $props )
		{
			for($i = 0; $i < count($props); $i++ )
				$props[$i] = str_replace("&deg;", "", $props[$i]);
			return $props;
		}
	}

	static function get_snow_array($weather, $prop)
	{
		$props = self::get_prop_array($weather, $prop);
		if( $props )
		{
			for($i = 0; $i < count($props); $i++ ) {
				$props[$i] = str_replace("-", "0", $props[$i]);
				$props[$i] = str_replace("cm", "", $props[$i]);
			}
			return $props;
		}
	}

	static function generate_forecast($min, $max,
				   $wind, $wind_dir,
				   $snow_low, $snow_high, $snow_level)
	{
		$forecast = "Low of $min with a high $max. ";
		if( $wind > 0 )
			$forecast .= "Winds blowing around $wind km/h from the $wind_dir. ";

		if( $snow_high > 0 )
			$forecast .= "New snow accumulation of $snow_low to $snow_high cm at $snow_level meters possible.";
		return strip_tags($forecast);
	}

	static function get_weather($props)
	{
		$weather = self::download($props['weather.url']);

		preg_match_all("/<th>4-Day Snow Forecast<\/th><th>(.*?)<\/th><th>(.*?)<\/th><th>(.*?)<\/th><th>(.*?)<\/th><\/tr>/", $weather, $matches);
		$day[0] = $matches[1][0];
		$day[1] = $matches[2][0];
		$day[2] = $matches[3][0];
		$day[3] = $matches[4][0];

		$min = self::get_temp_array($weather, "Min");
		$max = self::get_temp_array($weather, "Max");
		$wind_dir = self::get_prop_array($weather, "Wind Dir");
		$wind = self::get_prop_array($weather, "Wind km\/h");

		$snow_high = self::get_snow_array($weather, "Snow Hi");
		$snow_low = self::get_snow_array($weather, "Snow Lo");
		$snow_level = self::get_snow_array($weather, "Snow to");

		for($i = 0; $i < 4; $i++)
		{
			$props['weather.forecast.when.'.$i] = $day[$i];
			$props['weather.forecast.when-exact.'.$i] = strtotime($day[$i]);
			$props['weather.forecast.desc.'.$i] =
				self::generate_forecast($min[$i], $max[$i], $wind[$i], $wind_dir[$i], $snow_low[$i], $snow_high[$i], $snow_level[$i]);
		}

		preg_match_all("/<img alt=\"(.*?)\"/", $weather, $matches);
		$props['weather.icon'] = self::get_icon($matches[1][0]);
	}
}
$report_class = 'EUReport';
ReportBase::run_cgi($report_class);
?>
