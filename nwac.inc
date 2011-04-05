<?php

require_once('common.inc');

function region_create_or()
{
	$nwc_base = 'nwac_report.php';

	return create_region("Oregon",
		array(
			create_loc('Ski Bowl Ski Area, Government Camp', $nwc_base,
			           'OSOGVT', 45.297155, -121.756492,
			           'http://www.skibowl.com',
				   'skibowlgovtcamp'),
			create_loc('Mt Hood Meadows', $nwc_base,
			           'OSOMHM', 45.33185, -121.664631,
			           'http://www.skihood.com',
				   'mthoodmeadows'),
			create_loc('Timberline',      $nwc_base,
			           'OSOTIM', 45.331111, -121.711642,
			           'http://www.timberlinelodge.com',
				   'timberlinebase'),
		));
}

function region_create_wa()
{
	$nwc_base = 'nwac_report.php';

	return create_region("Washington",
		array(
			create_loc('Alpental',        $nwc_base,
			           'OSOALP', 47.44333, -121.42833,
			           'http://www.summitatsnoqualmie.com',
				   'alpental'),
			create_loc('Crystal Mountain',$nwc_base,
			           'OSOCMT', 46.92833, -121.50333,
			           'http://www.skicrystal.com/',
				   'crystalskiarea'),
			create_loc('Hurricane Ridge', $nwc_base,
			           'OSOHUR', 47.975, -123.51667,
			           'http://www.hurricaneridge.com',
				   'hurricaneridge'),
			create_loc('Mission Ridge',   $nwc_base,
			           'OSOMSR', 47.29194, -120.39778,
			           'www.missionridge.com',
				   'missionridge'),
			create_loc('Mt Baker',        $nwc_base,
			           'OSOMTB', 48.857322, -121.660143,
			           'www.mtbaker.us',
				   'mtbakerskiarea'),
			create_loc('Stevens Pass',    $nwc_base,
			           'OSOSK9', 47.75, -121.09,
			           'http://www.stevenspass.com',
				   'stevensskiarea'),
			create_loc('Snoqualmie Pass', $nwc_base,
			           'OSOSNO', 47.42222, -121.41,
			           'http://www.summitatsnoqualmie.com',
				   'snoqualmiepass'),
			create_loc('White Pass',      $nwc_base,
			           'OSOWPS', 46.63556, -121.38639,
			           'www.skiwhitepass.com',
				   'whitepass'),
		));
}

function resorts_nwac_get()
{
	return array_merge(
		region_create_or()->locations,
		region_create_wa()->locations
	);
}

/**
 * The NWAC website changed their URL's for telemetry data. In order to
 * help ensure backward compatibility we are just adding a lookup table
 * from the old->new and keeping the old location codes
 */
function nwac_telemetry_url($resort)
{
	$code = $resort->data;
	return 'http://www.nwac.us/weatherdata/'.$code.'/now';
}

