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

require_once('common.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = build_resorts_table();

	resort_assert_location($resorts, $location);

	$cache_file = 'eu_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resorts, $location, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

function write_report($resorts, $loc, $cache_file)
{
	$readable = resort_get_readable_location($resorts, $loc);
	$url = resort_get_info_url($resorts, $loc);

	$report = get_report($url);
	if( $report )
	{
		$props = get_report_props($url, $report);
		$props['location'] = $loc;
		$props['location.info'] = $url;

/*TODO, change get_location_info to return this
		fwrite($fp, "location.latitude=$lat\n");
		fwrite($fp, "location.longitude=$lon\n");
*/
		cache_create($cache_file, $props);
	}
	else
	{
		print("err.msg=No ski report data found\n");
	}
}

/**
 * Takes the report's XML node and parse the information out into a hashtable
 */
function get_report_props($url, $report)
{
	$props = array();

	preg_match_all("/<th>Upper<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$upper = $matches[1][0][0];
	preg_match_all("/<th>Lower<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$lower = $matches[1][0][0];
	$props['snow.total'] = "Lower ($lower), Upper($upper)";

	preg_match_all("/<th>Fresh Snow<\/th><td>(\d+)/", $report, $matches, PREG_OFFSET_CAPTURE);
	$props['snow.daily'] = "Fresh(".$matches[1][0][0]."cm)";
	$props['snow.fresh'] = $matches[1][0][0];
	$props['snow.units'] = 'cm';

	preg_match_all("/<th>Area Open<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$props['trails.total'] = $matches[1][0][0];

	preg_match_all("/<th>Conditions<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$props['snow.conditions'] = $matches[1][0][0];

	preg_match_all("/<th>Reported<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$props['date'] = date('d M Y', strtotime($matches[1][0][0]));

	preg_match_all("/<p><a href=\"(.*?)\.html/", $report, $matches, PREG_OFFSET_CAPTURE);
	$page = $matches[1][0][0].".html";
	//this gives us chamonix_snow-forecast.html, now pull the base from the
	//report's url
	$idx = strrpos($url, '/');
	$base = substr($url, 0, $idx);
	$props['weather.url'] = $base."/".$page;

	$props['weather.icon'] = get_weather_icon($props['weather.url']);

	return $props;
}

function get_report($url)
{
	return file_get_contents($url);
}

function get_weather_icon($url)
{
	$weather = get_report($url);
	preg_match_all("/<img alt=\"(.*?)\"/", $weather, $matches, PREG_OFFSET_CAPTURE);

	switch(strtolower($matches[1][0][0]))
	{
		case 'sunny/clear':
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
		case 'light rain':
		case 'rain':
			return 'ra';
	}
	return $matches[1][0][0];
}

function build_resorts_table()
{
//switzerland
	$resorts['seng'] = resort_props('Engelberg',             array(), 'http://www.j2ski.mobi/switzerland/engelberg_snow-report.html');
	$resorts['sand'] = resort_props('Andermatt',             array(), 'http://www.j2ski.mobi/switzerland/andermatt_snow-report.html');
	$resorts['sanz'] = resort_props('Anzère',                array(), 'http://www.j2ski.mobi/switzerland/anzere_snow-report.html');
	$resorts['slax'] = resort_props('Laax',                  array(), 'http://www.j2ski.mobi/switzerland/laax_snow-report.html');
	$resorts['sflf'] = resort_props('Flims-Laax-Falera',     array(), 'http://www.j2ski.mobi/switzerland/flims_laax_falera_snow-report.html');
	$resorts['sssf'] = resort_props('Saas-Fee',              array(), 'http://www.j2ski.mobi/switzerland/saas_fee_snow-report.html');
	$resorts['slkd'] = resort_props('Leukerbad',             array(), 'http://www.j2ski.mobi/switzerland/leukerbad_snow-report.html');
	$resorts['smur'] = resort_props('Mürren',                array(), 'http://www.j2ski.mobi/switzerland/murren_snow-report.html');
	$resorts['spot'] = resort_props('Pontresina',            array(), 'http://www.j2ski.mobi/switzerland/pontresina_snow-report.html');
	$resorts['sslv'] = resort_props('Silvaplana',            array(), 'http://www.j2ski.mobi/switzerland/silvaplana_snow-report.html');
	$resorts['sstm'] = resort_props('St. Moritz',            array(), 'http://www.j2ski.mobi/switzerland/st_moritz_snow-report.html');
	$resorts['szrm'] = resort_props('Zermatt',               array(), 'http://www.j2ski.mobi/switzerland/zermatt_snow-report.html');
	$resorts['smhg'] = resort_props('Meiringen - Hasliberg', array(), 'http://www.j2ski.mobi/switzerland/meiringen_hasliberg_snow-report.html');
	$resorts['snen'] = resort_props('Nendaz',                array(), 'http://www.j2ski.mobi/switzerland/nendaz_snow-report.html');
	$resorts['svey'] = resort_props('Veysonnaz',             array(), 'http://www.j2ski.mobi/switzerland/veysonnaz_snow-report.html');
	$resorts['scrm'] = resort_props('Crans-Montana',         array(), 'http://www.j2ski.mobi/switzerland/crans_montana_snow-report.html');
	$resorts['sflm'] = resort_props('Flumserberg',           array(), 'http://www.j2ski.mobi/switzerland/flumserberg_snow-report.html');
	$resorts['sgrm'] = resort_props('Grimentz',              array(), 'http://www.j2ski.mobi/switzerland/grimentz_snow-report.html');
	$resorts['slnv'] = resort_props('Lenzerheide - Valbella',array(), 'http://www.j2ski.mobi/switzerland/lenzerheide_valbella_snow-report.html');
	$resorts['sldb'] = resort_props('Les Diablerets',        array(), 'http://www.j2ski.mobi/switzerland/les_diablerets_snow-report.html');
	$resorts['stlc'] = resort_props('Thyon les Collons',     array(), 'http://www.j2ski.mobi/switzerland/thyon_les_collons_snow-report.html');
	$resorts['sgst'] = resort_props('Gstaad',                array(), 'http://www.j2ski.mobi/switzerland/gstaad_snow-report.html');
	$resorts['sdsr'] = resort_props('Disentis Sedrun',       array(), 'http://www.j2ski.mobi/switzerland/disentis_sedrun_snow-report.html');
	$resorts['sverb'] = resort_props('Verbier',              array(), 'http://www.j2ski.mobi/switzerland/verbier_snow-report.html');
	$resorts['sltz'] = resort_props('La Tzoumaz',            array(), 'http://www.j2ski.mobi/switzerland/la_tzoumaz_snow-report.html');
	$resorts['salt'] = resort_props('Aletsch',               array(), 'http://www.j2ski.mobi/switzerland/aletsch_snow-report.html');
	$resorts['sdav'] = resort_props('Davos',                 array(), 'http://www.j2ski.mobi/switzerland/davos_snow-report.html');
	$resorts['smrg'] = resort_props('Morgins',               array(), 'http://www.j2ski.mobi/switzerland/morgins_snow-report.html');
	$resorts['sasa'] = resort_props('Arosa',                 array(), 'http://www.j2ski.mobi/switzerland/arosa_snow-report.html');
	$resorts['skls'] = resort_props('Klosters',              array(), 'http://www.j2ski.mobi/switzerland/klosters_snow-report.html');
	$resorts['smin'] = resort_props('Minschuns',             array(), 'http://www.j2ski.mobi/switzerland/minschuns_snow-report.html');
	$resorts['ssam'] = resort_props('Samnaun',               array(), 'http://www.j2ski.mobi/switzerland/samnaun_snow-report.html');
	$resorts['ssed'] = resort_props('Sedrun',                array(), 'http://www.j2ski.mobi/switzerland/sedrun_snow-report.html');
	$resorts['sstl'] = resort_props('St-Luc / Chandolin',    array(), 'http://www.j2ski.mobi/switzerland/st_luc_chandolin_snow-report.html');
	$resorts['sadl'] = resort_props('Adelboden',             array(), 'http://www.j2ski.mobi/switzerland/adelboden_snow-report.html');
	$resorts['sevo'] = resort_props('Evolène',               array(), 'http://www.j2ski.mobi/switzerland/evolene_snow-report.html');
	$resorts['swen'] = resort_props('Wengen',                array(), 'http://www.j2ski.mobi/switzerland/wengen_snow-report.html');
	$resorts['skan'] = resort_props('Kandersteg',            array(), 'http://www.j2ski.mobi/switzerland/kandersteg_snow-report.html');
	$resorts['sgrn'] = resort_props('Grindelwald',           array(), 'http://www.j2ski.mobi/switzerland/grindelwald_snow-report.html');
	$resorts['svgn'] = resort_props('Villars - Gryon',       array(), 'http://www.j2ski.mobi/switzerland/villars_gryon_snow-report.html');
	$resorts['sbwd'] = resort_props('Braunwald',             array(), 'http://www.j2ski.mobi/switzerland/braunwald_snow-report.html');

//france
	$resorts['fchm'] = resort_props('Chamonix',              array(), 'http://www.j2ski.mobi/france/chamonix_snow-report.html');

	return $resorts;
}

/*
Missing Swiss resorts:
Champéry, Leysin, Zinal, Grächen, Savognin
Scuol, Château d'Oex, Lenk, Arolla, Bruson, Riederalp
Les Crosets, Ovronnaz, Charmey, Les Mosses, Rougemont
Saas Grund, Saas Almagell, Flims, Falera, Brig, Torgon
*/
?>
