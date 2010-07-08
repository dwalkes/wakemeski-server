<?php

require_once('common.inc');

function region_create_nm()
{
	$nm_base = 'http://bettykrocks.com/skireport/mt_report.php';

	return create_region("New Mexico",
		array(
			create_loc('Angel Fire',         $nm_base,
			           'AF', 36.3903, -105.2875,
			           'http://www.angelfireresort.com/winter/mountain-snow-report.php'),
			create_loc('Enchanted Forest',   $nm_base,
			           'EF', 36.7063, -105.4053,
			           'http://www.enchantedforestxc.com/'),
			create_loc('Pajarito Mountain',  $nm_base,
			           'PM', 35.89519,-106.391785,
			           'http://www.skipajarito.com/conditions.php'),
			create_loc('Red River',          $nm_base,
			           'RR', 36.70859,-105.409924,
			           'http://redriverskiarea.com/page.php?pname=mountain/snow'),
			create_loc('Sandia Peak',        $nm_base,
			           'SP', 35.20783,-106.41354,
			           'http://www.sandiapeak.com/index.php?page=snow-report'),
			create_loc('Sipapu',             $nm_base,
			           'SI', 36.15359,-105.54824,
			           'http://www.sipapunm.com/index.php?option=com_snowreport&view=helloworld&Itemid=73'),
			create_loc('Ski Apache',         $nm_base,
			           'SA', 33.39745,-105.789198,
			           'http://www.skiapache.com/'),
			create_loc('Ski Santa Fe',       $nm_base,
			           'SF', 35.79679,-105.80166,
			           'http://skisantafe.com/index.php?page=snow-report'),
			create_loc('Taos',               $nm_base,
			           'TS', 36.35,   -105.27,
			           'http://www.skitaos.org/snow_reports/index'),
			create_loc('Valles Caldera Nordic', $nm_base,
			           'VC', 35.9,    -106.55,
			           'http://www.vallescaldera.gov/comevisit/skisnow/'),
		));
}

function resorts_nm_get()
{
	return region_create_nm()->locations;
}
