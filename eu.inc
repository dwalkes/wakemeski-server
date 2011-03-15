<?php

require_once('common.inc');

$eu_base = 'eu_report.php';

function fr_url($name)
{
	return 'http://www.j2ski.mobi/france/'.$name;
}

function sp_url($name)
{
	return 'http://www.j2ski.mobi/spain/'.$name;
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
			create_loc('Chamonix',         $eu_base,
			           'fchm', 45.923056,  6.869722,
			           fr_url('chamonix_snow-report.html'),
			           'http://www.chamonix.com'),
			create_loc('La Clusaz',        $eu_base,
			           'flcl', 45.905852,  6.425792,
			           fr_url('la_clusaz_snow-report.html'),
			           'http://www.laclusaz.com/hiver-winter11/'),
			create_loc('Les Contamines',   $eu_base,
			           'flct', 45.8225, 6.73,
			           fr_url('les_contamines_snow-report.html'),
			           'http://www.lescontamines.com/conditions-ski.html'),
			create_loc('Tignes',           $eu_base,
			           'ftig', 45.4699822, 6.9076373,
			           fr_url('tignes_snow-report.html'),
			           'http://www.tignes.co.uk/'),
			create_loc('Les Deux Alpes',   $eu_base,
			           'flda', 45.007553,  6.121801,
			           fr_url('les_deux_alpes_snow-report.html'),
			           'http://www.2alpes.com/'),
			create_loc('St Gervais Mont-Blanc',   $eu_base,
			           'fstg', 45.893333,  6.713889,
			           fr_url('st_gervais_mont_blanc_snow-report.html'),
			           'http://www.saintgervais.com/en/alpine-and-sports/snow-conditions.html'),
		));
}

function region_create_spain()
{
	global $eu_base;
	return create_region("Spain",
		array(
			create_loc('Cerler',           $eu_base,
			           'spce', 42.593333,  0.538333,
			           sp_url('cerler_snow-report.html'),
			           'http://www.cerler.com/'),
			create_loc('Baqueira-Beret',   $eu_base,
			           'spbb', 42.699479,  0.934358,
			           sp_url('baqueira_beret_snow-report.html'),
			           'http://www.baqueira.es/'),
			create_loc('Sierra Nevada',    $eu_base,
			           'spsn', 37.092963,  -3.395211,
			           sp_url('sierra_nevada_snow-report.html'),
			           'http://www.sierranevada.co.uk/'),
			create_loc('Boí Taüll',        $eu_base,
			           'spbt', 42.477778,  0.870833,
			           sp_url('boi_taull_snow-report.html'),
			           'http://www.boitaullresort.com/'),
			create_loc('Espot Esqui',      $eu_base,
			           'spee', 42.550556,  1.080833,
			           sp_url('espot_esqui_snow-report.html'),
			           'http://www.granpallars.com/estacion.php?id=espot'),
			create_loc('Formigal',         $eu_base,
			           'spfo', 42.773894,  -0.363947,
			           sp_url('formigal_snow-report.html'),
			           'http://www.formigal.com/'),
			create_loc('Port Del Comte',   $eu_base,
			           'sppc', 42.172778,  1.561944,
			           sp_url('port_del_comte_snow-report.html'),
			           'http://www.portdelcomte.net/'),
			create_loc('Candanchú',        $eu_base,
			           'spca', 42.788056,  -0.527778,
			           sp_url('candanchu_snow-report.html'),
			           'http://www.candanchu.com/'),
			create_loc('Panticosa',        $eu_base,
			           'sppa', 42.702989, -0.274594,
			           sp_url('panticosa_snow-report.html'),
			           'http://www.panticosa-loslagos.com/'),
			create_loc('Astún',            $eu_base,
			           'spas', 42.809722,  -0.506111,
			           sp_url('astun_snow-report.html'),
			           'http://www.astun.com/'),
			create_loc('Leitariegos',      $eu_base,
			           'sple', 42.993319,  -6.412455,
			           sp_url('leitariegos_snow-report.html'),
			           'http://www.leitariegos.net/'),
			create_loc('Manzaneda',        $eu_base,
			           'spma', 42.309444,  -7.233333,
			           sp_url('manzaneda_snow-report.html'),
			           'http://www.manzaneda.com/'),
			create_loc('San Isidro',       $eu_base,
			           'spsi', 43.041303,  -5.319981,
			           sp_url('san_isidro_snow-report.html'),
			           'http://www.san-isidro.net/'),
			create_loc('Alto Campoo',      $eu_base,
			           'spac', 43.017989,  -4.213475,
			           sp_url('alto_campoo_snow-report.html'),
			           'http://www.altocampoo.com/'),
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
		region_create_spain()->locations,
		region_create_switzerland()->locations
	);
}

?>
