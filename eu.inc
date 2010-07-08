<?php

require_once('common.inc');

$eu_base = 'http://bettykrocks.com/skireport/eu_report.php';

function fr_url($name)
{
	return 'http://www.j2ski.mobi/france/'.$name;
}

function swiss_url($name)
{
	return 'http://www.j2ski.mobi/switzerland/'.$name;
}

function region_create_france()
{
	global $eu_base;
	return create_region("France",
		array(
			create_loc('Chamonix',        $eu_base,
			           'fchm', 0,0,
			           fr_url('chamonix_snow-report.html')),
		));
}

function region_create_switzerland()
{
	global $eu_base;
	return create_region("Switzerland",
		array(
			create_loc('Adelboden',        $eu_base,
			           'sadl', 0,0,
			           swiss_url('adelboden_snow-report.html')),
			create_loc('Aletsch',          $eu_base,
			           'salt', 0,0,
			           swiss_url('aletsch_snow-report.html')),
			create_loc('Andermatt',        $eu_base,
			           'sand', 0,0,
			           swiss_url('andermatt_snow-report.html')),
			create_loc('Anzère',           $eu_base,
			           'sanz', 0,0,
			           swiss_url('anzere_snow-report.html')),
			create_loc('Arosa',            $eu_base,
			           'sasa', 0,0,
			           swiss_url('arosa_snow-report.html')),
			create_loc('Braunwald',        $eu_base,
			           'sbwd', 0,0,
			           swiss_url('braunwald_snow-report.html')),
			create_loc('Crans-Montana',    $eu_base,
			           'scrm', 0,0,
			           swiss_url('crans_montana_snow-report.html')),
			create_loc('Davos',            $eu_base,
			           'sdav', 0,0,
			           swiss_url('davos_snow-report.html')),
			create_loc('Disentis Sedrun',  $eu_base,
			           'sdsr', 0,0,
			           swiss_url('disentis_sedrun_snow-report.html')),
			create_loc('Engelberg',        $eu_base,
			           'seng', 0,0,
			           swiss_url('engelberg_snow-report.html')),
			create_loc('Evolène',          $eu_base,
			           'sevo', 0,0,
			           swiss_url('evolene_snow-report.html')),
			create_loc('Flims-Laax-Falera', $eu_base,
			           'sflf', 0,0,
			           swiss_url('flims_laax_falera_snow-report.html')),
			create_loc('Flumserberg',      $eu_base,
			           'sflm', 0,0,
			           swiss_url('flumserberg_snow-report.html')),
			create_loc('Grimentz',         $eu_base,
			           'sgrm', 0,0,
			           swiss_url('grimentz_snow-report.html')),
			create_loc('Grindelwald',      $eu_base,
			           'sgrn', 0,0,
			           swiss_url('grindelwald_snow-report.html')),
			create_loc('Gstaad',           $eu_base,
			           'sgst', 0,0,
			           swiss_url('gstaad_snow-report.html')),
			create_loc('Kandersteg',       $eu_base,
			           'skan', 0,0,
			           swiss_url('kandersteg_snow-report.html')),
			create_loc('Klosters',         $eu_base,
			           'skls', 0,0,
			           swiss_url('klosters_snow-report.html')),
			create_loc('Laax',             $eu_base,
			           'slax', 0,0,
			           swiss_url('laax_snow-report.html')),
			create_loc('La Tzoumaz',       $eu_base,
			           'sltz', 0,0,
			           swiss_url('la_tzoumaz_snow-report.html')),
			create_loc('Lenzerheide - Valbella', $eu_base,
			           'slnv', 0,0,
			           swiss_url('lenzerheide_valbella_snow-report.html')),
			create_loc('Leukerbad',        $eu_base,
			           'slkd', 0,0,
			           swiss_url('leukerbad_snow-report.html')),
			create_loc('Les Diablerets',   $eu_base,
			           'sldb', 0,0,
			           swiss_url('les_diablerets_snow-report.html')),
			create_loc('Meiringen',        $eu_base,
			           'smhg', 0,0,
			           swiss_url('meiringen_hasliberg_snow-report.html')),
			create_loc('Minschuns',        $eu_base,
			           'smin', 0,0,
			           swiss_url('minschuns_snow-report.html')),
			create_loc('Morgins',          $eu_base,
			           'smrg', 0,0,
			           swiss_url('morgins_snow-report.html')),
			create_loc('Mürren',           $eu_base,
			           'smur', 0,0,
			           swiss_url('murren_snow-report.html')),
			create_loc('Nendaz',           $eu_base,
			           'snen', 0,0,
			           swiss_url('nendaz_snow-report.html')),
			create_loc('Pontresina',       $eu_base,
			           'spot', 0,0,
			           swiss_url('pontresina_snow-report.html')),
			create_loc('Saas-Fee',         $eu_base,
			           'sssf', 0,0,
			           swiss_url('saas_fee_snow-report.html')),
			create_loc('Samnaun',          $eu_base,
			           'ssam', 0,0,
			           swiss_url('samnaun_snow-report.html')),
			create_loc('Sedrun',           $eu_base,
			           'ssed', 0,0,
			           swiss_url('sedrun_snow-report.html')),
			create_loc('Silvaplana',       $eu_base,
			           'sslv', 0,0,
			           swiss_url('silvaplana_snow-report.html')),
			create_loc('St-Luc / Chandolin', $eu_base,
			           'sstl', 0,0,
			           swiss_url('st_luc_chandolin_snow-report.html')),
			create_loc('St. Moritz',       $eu_base,
			           'sstm', 0,0,
			           swiss_url('st_moritz_snow-report.html')),
			create_loc('Thyon les Collons', $eu_base,
			           'stlc', 0,0,
			           swiss_url('thyon_les_collons_snow-report.html')),
			create_loc('Verbier',         $eu_base,
			           'sverb', 0,0,
			           swiss_url('verbier_snow-report.html')),
			create_loc('Veysonnaz',        $eu_base,
			           'svey', 0,0,
			           swiss_url('veysonnaz_snow-report.html')),
			create_loc('Villars - Gryon',  $eu_base,
			           'svgn', 0,0,
			           swiss_url('villars_gryon_snow-report.html')),
			create_loc('Wengen',           $eu_base,
			           'swen', 0,0,
			           swiss_url('wengen_snow-report.html')),
			create_loc('Zermatt',          $eu_base,
			           'szrm', 0,0,
			           swiss_url('zermatt_snow-report.html')),
		));
}

/*
Missing Swiss resorts:
Champéry, Leysin, Zinal, Grächen, Savognin
Scuol, Château d'Oex, Lenk, Arolla, Bruson, Riederalp
Les Crosets, Ovronnaz, Charmey, Les Mosses, Rougemont
Saas Grund, Saas Almagell, Flims, Falera, Brig, Torgon
*/

function resorts_eu_get()
{
	return array_merge(
		region_create_france()->locations,
		region_create_switzerland()->locations
	);
}

?>
