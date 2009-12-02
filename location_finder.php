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

	header( "Content-Type: text/plain" );

	$URL_BASE = 'http://bettykrocks.com/skireport';

	$region = $_GET['region'];
	if( !$region )
		show_regions();
	else
		show_locations($region);

function show_regions()
{
    print "Colorado\n";
	print "New Mexico\n";
	print "Montana\n";
	print "Oregon\n";
	print "Utah\n";
	print "Washington\n";
}

function show_locations($region)
{
	global $URL_BASE;

	if( $region == "Washington")
	{
		print "Alpental = $URL_BASE/nwac_report.php?location=OSOALP\n";
		print "Crystal Mountain = $URL_BASE/nwac_report.php?location=OSOCMT\n";
		print "Hurricane Ridge  = $URL_BASE/nwac_report.php?location=OSOHUR\n";
		print "Mission Ridge = $URL_BASE/nwac_report.php?location=OSOMSR\n";
		print "Mt Baker = $URL_BASE/nwac_report.php?location=OSOMTB\n";
		print "Stevens Pass = $URL_BASE/nwac_report.php?location=OSOSK9\n";
		print "Snoqualmie Pass = $URL_BASE/nwac_report.php?location=OSOSNO\n";
		print "White Pass = $URL_BASE/nwac_report.php?location=OSOWPS\n";
	}
	else if( $region == "Oregon")
	{
		print "Ski Bowl Ski Area, Government Camp = $URL_BASE/nwac_report.php?location=OSOGVT\n";
		print "Mt Hood Meadows  = $URL_BASE/nwac_report.php?location=OSOMHM\n";
		print "Timberline = $URL_BASE/nwac_report.php?location=OSOTIM\n";
	}
	else if ( $region == "Utah" )
	{
		print "Alta = $URL_BASE/utah_report.php?location=ATA\n";
		print "Beaver Mountain = $URL_BASE/utah_report.php?location=BVR\n";
		print "Brian Head = $URL_BASE/utah_report.php?location=BHR\n";
		print "Brighton = $URL_BASE/utah_report.php?location=BRT\n";
		print "The Canyons = $URL_BASE/utah_report.php?location=CNY\n";
		print "Deer Valley = $URL_BASE/utah_report.php?location=DVR\n";
		print "Park City = $URL_BASE/utah_report.php?location=PCM\n";
		print "Powder Mountain = $URL_BASE/utah_report.php?location=POW\n";
		print "Snowbasin = $URL_BASE/utah_report.php?location=SBN\n";
		print "Sunbird = $URL_BASE/utah_report.php?location=SBD\n";
		print "Solitude = $URL_BASE/utah_report.php?location=SOL\n";
		print "Sundance = $URL_BASE/utah_report.php?location=SUN\n";
		print "Wolf Creek = $URL_BASE/utah_report.php?location=WLF\n";
	}
	else if ( $region == "New Mexico" )
	{
		print "Angel Fire = $URL_BASE/nm_report.php?location=AF\n";
		print "Enchanted Forest = $URL_BASE/nm_report.php?location=EF\n";
		print "Pajarito Mountain = $URL_BASE/nm_report.php?location=PM\n";
		print "Red River = $URL_BASE/nm_report.php?location=RR\n";
		print "Sandia Peak = $URL_BASE/nm_report.php?location=SP\n";
		print "Sipapu = $URL_BASE/nm_report.php?location=SI\n";
		print "Ski Apache = $URL_BASE/nm_report.php?location=SA\n";
		print "Ski Santa Fe = $URL_BASE/nm_report.php?location=SF\n";
		print "Taos = $URL_BASE/nm_report.php?location=TS\n";
		print "Valles Caldera Nordic = $URL_BASE/nm_report.php?location=VC\n";
	}
    else if ( $region == "Colorado" )
	{
		print "Arapahoe Basin = $URL_BASE/co_report.php?location=AB\n";
        print "Aspen Highlands = $URL_BASE/co_report.php?location=AH\n";
        print "Aspen Mountain = $URL_BASE/co_report.php?location=AM\n";
        print "Buttermilk = $URL_BASE/co_report.php?location=BM\n";
        print "Copper Mountain = $URL_BASE/co_report.php?location=CM\n";
        print "Crested Butte = $URL_BASE/co_report.php?location=CB\n";
        print "Echo Mountain = $URL_BASE/co_report.php?location=EM\n";
        print "Eldora = $URL_BASE/co_report.php?location=EL\n";
        print "Howelsen = $URL_BASE/co_report.php?location=HW\n";
        print "Loveland = $URL_BASE/co_report.php?location=LV\n";
        print "Monarch Mountain = $URL_BASE/co_report.php?location=MM\n";
        print "Powderhorn = $URL_BASE/co_report.php?location=PH\n";
        print "Purgatory = $URL_BASE/co_report.php?location=PG\n";
        print "Silverton Mountain = $URL_BASE/co_report.php?location=SM\n";
        print "Ski Cooper = $URL_BASE/co_report.php?location=SC\n";
        print "Snowmass = $URL_BASE/co_report.php?location=SN\n";
        print "Sol Vista Basin = $URL_BASE/co_report.php?location=SV\n";
        print "Steamboat = $URL_BASE/co_report.php?location=ST\n";
        print "Sunlight = $URL_BASE/co_report.php?location=SL\n";
        print "Telluride = $URL_BASE/co_report.php?location=TD\n";
        print "Winter Park = $URL_BASE/co_report.php?location=WP\n";
        print "Wolf Creek = $URL_BASE/co_report.php?location=WC\n";

        print "Vail = $URL_BASE/co_report2.php?location=VA\n";
        print "Beaver Creek = $URL_BASE/co_report2.php?location=BC\n";
        print "Keystone = $URL_BASE/co_report2.php?location=KS\n";
        print "Breckenridge = $URL_BASE/co_report2.php?location=BK\n";
    }
    else if( $region == "Montana" )
    {
		print "Turner Mountain = $URL_BASE/mt_report.php?location=TR\n";
		print "Maverick Mountain= $URL_BASE/mt_report.php?location=MM\n";
		print "Lost Trail Powder Mountain= $URL_BASE/mt_report.php?location=LM\n";
		print "Bear Paw Ski Bowl= $URL_BASE/mt_report.php?location=BP\n";
		print "Lookout Pass= $URL_BASE/mt_report.php?location=LP\n";
		print "Discovery= $URL_BASE/mt_report.php?location=DS\n";
		print "Blacktail Mountain= $URL_BASE/mt_report.php?location=BT\n";
		print "Big Sky= $URL_BASE/mt_report.php?location=BS\n";
		print "Great Divide= $URL_BASE/mt_report.php?location=GD\n";
		print "Showdown= $URL_BASE/mt_report.php?location=SD\n";
		print "Teton Pass= $URL_BASE/mt_report.php?location=TP\n";
		print "Whitefish= $URL_BASE/mt_report.php?location=WF\n";
		print "Red Lodge= $URL_BASE/mt_report.php?location=RL\n";
		print "Bridger Bowl= $URL_BASE/mt_report.php?location=BB\n";
		print "Montana Snowbowl= $URL_BASE/mt_report.php?location=MS\n";
		print "Moonlight Basin= $URL_BASE/mt_report.php?location=MB\n";		
    }
}
?>
