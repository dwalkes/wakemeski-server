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

require_once('ut.inc');
require_once('reportbase.inc');

class UTReport extends ReportBase
{
	public function run($location)
	{
		$resorts = resorts_ut_get();
		$resort = resort_get_location($resorts, $location);

		$resort->fresh_source_url = $resort->info;

		$cache_file = 'ut_'.$location.'.txt';
		$found_cache = cache_available($resort,$cache_file);
		if( !$found_cache )
		{
			$this->write_report($resort, $cache_file);
		}

		cache_dump($cache_file, $found_cache);
	}

	static function get_report_contents($url)
	{
		$contents = file_get_contents($url);

		$idx1 = strpos($contents, "<div class=\"snow_total-data");
		$idx2 = strpos($contents, "<p class=\"more_information\">");

		if( $idx1 === false || $idx2 === false)
		{
			print "err.msg=report format changed. server update required\n";
			/*
			 *  return an empty string instead of exiting, that way we still write
			 *  additional common report detail in cache_create
			 */
			return "";
		}

		$contents = substr($contents, $idx1, $idx2-$idx1);
		//the report has fields we grep for that span lines, so remove
		//EOL's so regex's will work easily
		return str_replace("\n", "\t", $contents);
	}

	function get_report($resort)
	{
		$contents = self::get_report_contents($resort->fresh_source_url);

		$data = array();

		preg_match_all("/Updated: <span>(.*?)<\/span>/", $contents, $matches, PREG_OFFSET_CAPTURE);
		$data['date'] = $matches[1][0][0];

		$data['snow.units'] = 'inches';
		preg_match_all("/last_24 snow_data\">(.*?)value\">(.*?)<\/span>/", $contents, $matches, PREG_OFFSET_CAPTURE);
		$data['snow.daily'] = "Fresh(".$matches[2][0][0].")";
		$data['snow.fresh'] = $matches[2][0][0];

		preg_match_all("/last_48 snow_data\">(.*?)value\">(.*?)<\/span>/", $contents, $matches, PREG_OFFSET_CAPTURE);
		$data['snow.daily'] .= " 48hr(".$matches[2][0][0].")";

		preg_match_all("/base_depth snow_data\">(.*?)value\">(.*?)<\/span>/", $contents, $matches, PREG_OFFSET_CAPTURE);
		$data['snow.total'] = $matches[2][0][0];

		preg_match_all("/Runs Open:(.*?)value\">(\d+)\/(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
		$data['trails.open'] = $matches[2][0][0];
		$data['trails.total'] = $matches[3][0][0];
		preg_match_all("/Lifts Open:(.*?)value\">(\d+)\/(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
		$data['lifts.open'] = $matches[2][0][0];
		$data['lifts.total'] = $matches[3][0][0];

		preg_match_all("/Comments:(.*?)value\">(.*?)<\//", $contents, $matches, PREG_OFFSET_CAPTURE);
		$data['location.comments'] = $matches[2][0][0];
		return $data;
	}
}
$report_class = 'UTReport';
ReportBase::run_cgi($report_class);
?>
