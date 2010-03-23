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

require_once('weather.inc');

	header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	//first validate the location:
	if(!get_location_info($location))
	{
		print "err.msg=invalid location: $location\n";
		exit(1);
	}

	$found_cache = have_cache($location);
	if( !$found_cache )
	{
		write_report($location);
	}

	print file_get_contents("eu_$location.txt");
	print "cache.found=$found_cache\n";

function have_cache($location)
{
	$file = "eu_$location.txt";
	if( is_readable($file))
	{
		//get modification time stamp. If its less than
		//120 minutes old, use that copy
		$mod = filemtime($file);
		if( time() - $mod < 7200 ) //=60*120 = 120 minutes
		{
			return 1;
		}
	}
	return 0;
}

function write_report($loc)
{
	$fp = fopen("eu_$loc.txt", 'w');

	fwrite($fp, "location = $loc\n");

	list($readable, $url) = get_location_info($loc);
	$report = get_report($url);
	if( $report )
	{
		$props = get_report_props($url, $report);
		$keys = array_keys($props);
		for($i = 0; $i < count($keys); $i++)
		{
			$key = $keys[$i];
			fwrite($fp, $key.' = '.$props[$key]."\n");
		}
		fwrite($fp, "location.info=$url\n");

/*TODO, change get_location_info to return this
		fwrite($fp, "location.latitude=$lat\n");
		fwrite($fp, "location.longitude=$lon\n");
*/
	}
	else
	{
		fwrite($fp, "err.msg=No ski report data found\n");
	}

	fclose($fp);
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
    }
    return $matches[1][0][0];
}

/**
 * Turns a 4 digit code like fchm and returns a tuple
 * (Chamonix, http://www.j2ski.mobi/france/chamonix_snow-report.html)
 */
function get_location_info($loc)
{
//switzerland
	if( $loc == 'seng')
		return array('Engelberg', 'http://www.j2ski.mobi/switzerland/engelberg_snow-report.html');
	if( $loc == 'sand' )
		return array('Andermatt', 'http://www.j2ski.mobi/switzerland/andermatt_snow-report.html');
	if( $loc == 'sanz' )
		return array('Anzère', 'http://www.j2ski.mobi/switzerland/anzere_snow-report.html');
	if( $loc == 'slax' )
		return array('Laax', 'http://www.j2ski.mobi/switzerland/laax_snow-report.html');
	if( $loc == 'sflf' )
		return array('Flims-Laax-Falera', 'http://www.j2ski.mobi/switzerland/flims_laax_falera_snow-report.html');
	if( $loc == 'sssf' )
		return array('Saas-Fee', 'http://www.j2ski.mobi/switzerland/saas_fee_snow-report.html');
	if( $loc == 'slkd' )
		return array('Leukerbad', 'http://www.j2ski.mobi/switzerland/leukerbad_snow-report.html');
	if( $loc == 'smur' )
		return array('Mürren', 'http://www.j2ski.mobi/switzerland/murren_snow-report.html');
	if( $loc == 'spot' )
		return array('Pontresina', 'http://www.j2ski.mobi/switzerland/pontresina_snow-report.html');
	if( $loc == 'sslv' )
		return array('Silvaplana', 'http://www.j2ski.mobi/switzerland/silvaplana_snow-report.html');
	if( $loc == 'sstm' )
		return array('St. Moritz', 'http://www.j2ski.mobi/switzerland/st_moritz_snow-report.html');
	if( $loc == 'szrm' )
		return array('Zermatt', 'http://www.j2ski.mobi/switzerland/zermatt_snow-report.html');
	if( $loc == 'smhg' )
		return array('Meiringen - Hasliberg', 'http://www.j2ski.mobi/switzerland/meiringen_hasliberg_snow-report.html');
	if( $loc == 'snen' )
		return array('Nendaz', 'http://www.j2ski.mobi/switzerland/nendaz_snow-report.html');
	if( $loc == 'svey' )
		return array('Veysonnaz', 'http://www.j2ski.mobi/switzerland/veysonnaz_snow-report.html');
	if( $loc == 'scrm' )
		return array('Crans-Montana', 'http://www.j2ski.mobi/switzerland/crans_montana_snow-report.html');
	if( $loc == 'sflm' )
		return array('Flumserberg', 'http://www.j2ski.mobi/switzerland/flumserberg_snow-report.html');
	if( $loc == 'sgrm' )
		return array('Grimentz', 'http://www.j2ski.mobi/switzerland/grimentz_snow-report.html');
	if( $loc == 'slnv' )
		return array('Lenzerheide - Valbella', 'http://www.j2ski.mobi/switzerland/lenzerheide_valbella_snow-report.html');
	if( $loc == 'sldb' )
		return array('Les Diablerets', 'http://www.j2ski.mobi/switzerland/les_diablerets_snow-report.html');
	if( $loc == 'stlc' )
		return array('Thyon les Collons', 'http://www.j2ski.mobi/switzerland/thyon_les_collons_snow-report.html');
	if( $loc == 'sgst' )
		return array('Gstaad', 'http://www.j2ski.mobi/switzerland/gstaad_snow-report.html');
	if( $loc == 'sdsr' )
		return array('Disentis Sedrun', 'http://www.j2ski.mobi/switzerland/disentis_sedrun_snow-report.html');
	if( $loc == 'sverb' )
		return array('Verbier', 'http://www.j2ski.mobi/switzerland/verbier_snow-report.html');
	if( $loc == 'sltz' )
		return array('La Tzoumaz', 'http://www.j2ski.mobi/switzerland/la_tzoumaz_snow-report.html');
	if( $loc == 'salt' )
		return array('Aletsch', 'http://www.j2ski.mobi/switzerland/aletsch_snow-report.html');
	if( $loc == 'sdav' )
		return array('Davos', 'http://www.j2ski.mobi/switzerland/davos_snow-report.html');
	if( $loc == 'smrg' )
		return array('Morgins', 'http://www.j2ski.mobi/switzerland/morgins_snow-report.html');
	if( $loc == 'sasa' )
		return array('Arosa', 'http://www.j2ski.mobi/switzerland/arosa_snow-report.html');
	if( $loc == 'skls' )
		return array('Klosters', 'http://www.j2ski.mobi/switzerland/klosters_snow-report.html');
	if( $loc == 'smin' )
		return array('Minschuns', 'http://www.j2ski.mobi/switzerland/minschuns_snow-report.html');
	if( $loc == 'ssam' )
		return array('Samnaun', 'http://www.j2ski.mobi/switzerland/samnaun_snow-report.html');
	if( $loc == 'ssed' )
		return array('Sedrun', 'http://www.j2ski.mobi/switzerland/sedrun_snow-report.html');
	if( $loc == 'sstl' )
		return array('St-Luc / Chandolin', 'http://www.j2ski.mobi/switzerland/st_luc_chandolin_snow-report.html');
	if( $loc == 'sadl' )
		return array('Adelboden', 'http://www.j2ski.mobi/switzerland/adelboden_snow-report.html');
	if( $loc == 'sevo' )
		return array('Evolène', 'http://www.j2ski.mobi/switzerland/evolene_snow-report.html');
	if( $loc == 'swen' )
		return array('Wengen', 'http://www.j2ski.mobi/switzerland/wengen_snow-report.html');
	if( $loc == 'skan' )
		return array('Kandersteg', 'http://www.j2ski.mobi/switzerland/kandersteg_snow-report.html');
	if( $loc == 'sgrn' )
		return array('Grindelwald', 'http://www.j2ski.mobi/switzerland/grindelwald_snow-report.html');
	if( $loc == 'svgn' )
		return array('Villars - Gryon', 'http://www.j2ski.mobi/switzerland/villars_gryon_snow-report.html');
	if( $loc == 'sbwd' )
		return array('Braunwald', 'http://www.j2ski.mobi/switzerland/braunwald_snow-report.html');

//france
	if( $loc == 'fchm')
		return array('Chamonix', 'http://www.j2ski.mobi/france/chamonix_snow-report.html');

	return null;
}
/*
Missing Swiss resorts:
Champéry, Leysin, Zinal, Grächen, Savognin
Scuol, Château d'Oex, Lenk, Arolla, Bruson, Riederalp
Les Crosets, Ovronnaz, Charmey, Les Mosses, Rougemont
Saas Grund, Saas Almagell, Flims, Falera, Brig, Torgon
*/
?>
