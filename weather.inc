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

class Weather
{
	static function _get_xml_dom($lat, $lon)
	{
		$now = time();
		$tomorrow = $now + (24 * 60 * 60);
		$start = date('Y-m-d', $now);
		$end = date('Y-m-d', $tomorrow);

		$url = "http://forecast.weather.gov/MapClick.php?FcstType=dwml&lat=$lat&lon=$lon";
		$xml = file_get_contents($url);

		$sxe = simplexml_load_string($xml);
		return dom_import_simplexml($sxe);
	}

	/**
	 * Searches through the DOM for the time-layout node with a layout-key
	 * node equal to $layout
	 */
	private static function find_time_layout($dom, $layout)
	{
		$nodes = $dom->getElementsByTagName('time-layout');
		foreach( $nodes as $node )
		{
			$val = $node->getElementsByTagName('layout-key')->item(0)->textContent;
			if( $val == $layout )
				return $node;
		}
	}

	//returns a list($icon, $url, $forecast)
	// forecast is an array of (array("when"=>"Tonight", "desc"=>"forecast"))
	static function get_report($lat, $lon)
	{
		$dom = Weather::_get_xml_dom($lat, $lon);

		//get the weather report URL
		$node = $dom->getElementsByTagName('moreWeatherInformation')->item(0);
		$url = $node->firstChild->nodeValue;

		//get the icon for the weather description
		$node = $dom->getElementsByTagName('icon-link')->item(0);
		$icon = $node->firstChild->nodeValue;

		//get the "worded forecast", we'll use the first three
		$worded = $dom->getElementsByTagName('wordedForecast')->item(0);
		$descriptions = $worded->getElementsByTagName('text');

		//get the "when's" for the forecast
		$layout = $worded->getAttribute('time-layout');
		$node = Weather::find_time_layout($dom, $layout);
		$times = $node->getElementsByTagName('start-valid-time');

		$forecast = array();

		for( $i = 0; $i < 4; $i++ )
		{
			$forecast[$i]['when'] = $times->item($i)->getAttribute('period-name');
			$range = $times->item($i)->textContent;
			//strtotime is actually flexible enough to convert this into a 
			//time in seconds
			$forecast[$i]['when-exact'] = strtotime($range);
			$forecast[$i]['desc'] = $descriptions->item($i)->textContent;
		}

		return array($icon, $url, $forecast);
	}

	/**
	 * Sets gets the weather user get_report and sets the standard report
	 * properties weather.url, weather.icon, location.latitude,
	 * location.longitude in $props array
	 */
	static function set_props($lat, $lon, $props)
	{
		list($icon, $url, $forecast) = Weather::get_report($lat, $lon);

		$props['location.latitude'] = $lat;
		$props['location.longitude'] = $lon;
		$props['weather.url'] = $url;
		$props['weather.icon']= $icon;

		for($i = 0; $i < count($forecast); $i++)
		{
			$props["weather.forecast.when.$i"] = $forecast[$i]['when'];
			$props["weather.forecast.when-exact.$i"] = $forecast[$i]['when-exact'];
			$props["weather.forecast.desc.$i"] = $forecast[$i]['desc'];
		}
	}
}
