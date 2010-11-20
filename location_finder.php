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

require('co.inc');
require('id.inc');
require('me.inc');
require('mt.inc');
require('nm.inc');
require('nv.inc');
require('nwac.inc');
require('ut.inc');
require('eu.inc');

$regions = array(
	region_create_co(),
	region_create_id(),
	region_create_me(),
	region_create_mt(),
	region_create_nm(),
	region_create_nv(),
	region_create_or(),
	region_create_ut(),
	region_create_wa(),
	region_create_france(),
	region_create_spain(),
	region_create_switzerland()
);

header( "Content-Type: text/plain" );

	if( isset($_GET['showall']) )
	{
		show_all();
	}
	else
	{
		$region = $_GET['region'];
		if( !$region )
			show_regions();
		else
			show_locations($region);
	}

function show_regions()
{
	global $regions;
	foreach($regions as $region)
	{
		print $region->name."\n";
	}

	log_hit('location_finder.php', "show_regions", false);
}

function show_locations($region_name)
{
	global $regions;
	foreach($regions as $region)
	{
		if( $region->name == $region_name )
		{
			foreach($region->locations as $loc)
			{
				print "$loc->name = $loc->url_base?location=$loc->code\n";
			}
			break;
		}
	}

	log_hit('location_finder.php', $region_name, false);
}

function show_all()
{
	global $regions;
	foreach($regions as $region)
	{
		print "REGION = $region->name\n";
		foreach($region->locations as $loc)
			print "\t$loc->code = $loc->name\n";
	}
}

?>
