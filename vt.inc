<?php

require_once('common.inc');

function region_create_vt()
{
	return create_region("Vermont",
		array(
			create_loc('Bolton Valley',      'vt_report.php',
			           'BV',                 44.421127,-72.849637,
			           'http://www.boltonvalley.com/'),
			create_loc('Bromley Mountain',   'vt_report.php',
			           'BM',                 43.213125,-72.934738,
			           'http://www.bromley.com'),
			create_loc('Burke Mountain',     'vt_report.php',
			           'BU',                 44.58766,-71.916636,
			           'http://www.skiburke.com'),
			create_loc('Cochran\'s Ski Area','vt_report.php',
			           'CS',                 44.396693,-72.981967,
			           'http://www.cochranskiarea.com/'),
			create_loc('Jay Peak',           'vt_report.php',
			           'JP',                 44.928399,-72.501765,
			           'http://www.jaypeakresort.com'),
			create_loc('Killington Resort',  'vt_report.php',
			           'KR',                 43.619818, -72.802685,
			           'http://www.killington.com/winter/index.html'),
			create_loc('Mad River Glen',     'vt_report.php',
			           'MG',                 44.200833, -72.924444,
			           'http://www.madriverglen.com'),
			create_loc('Magic Mountain',     'vt_report.php',
			           'MM',                 43.201118, -72.773249,
			           'http://www.magicmtn.com'),
			create_loc('Middlebury College', 'vt_report.php',
			           'MC',                 43.94, -72.96,
			           'http://www.middleburysnowbowl.com'),
			create_loc('Mount Snow',         'vt_report.php',
			           'MS',                 42.967999,-72.892842,
			           'http://www.mountsnow.com'),
			create_loc('Northeast Slopes',  'vt_report.php',
			           'NS',                 44.072946,-72.250732,
			           'http://www.northeastslopes.org/'),
			create_loc('Okemo Mountain Resort', 'vt_report.php',
			           'OM',                 43.401622,-72.716235,
			           'http://www.okemo.com'),
			create_loc('Pico Mountain',      'vt_report.php',
			           'PM',                 43.661869,-72.842964,
			           'http://www.picomountain.com'),
			create_loc('Smugglers\' Notch',  'vt_report.php',
			           'SN',                 44.572346,-72.776767,
			           'http://www.smuggs.com'),
			create_loc('Stowe',              'vt_report.php',
			           'ST',                 44.531944, -72.7875,
			           'http://www.stowe.com'),
			create_loc('Stratton',           'vt_report.php',
			           'SR',                 43.113973,-72.905889,
			           'http://www.stratton.com'),
			create_loc('Sugarbush',          'vt_report.php',
			           'SU',                 44.137222,-72.906667,
			           'http://www.sugarbush.com'),
			create_loc('Suicide Six',        'vt_report.php',
			           'SS',                 43.622945,-72.519515,
			           'http://suicide6.com/'),
		));
}

function resorts_vt_get()
{
	return region_create_vt()->locations;
}
