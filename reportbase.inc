<?php

abstract class ReportBase
{
	public static function run_cgi($class) {
		if( array_key_exists('HTTP_ENV_VARS', $GLOBALS) ) {
			header( "Content-Type: text/plain" );
			if( array_key_exists('location', $_GET) ) {
				$obj = new $class();
				$obj->run($_GET['location']);
			} else {
				print "err.msg=no location specified\n";
			}
		}
	}

	abstract function run($location);
	abstract function get_report($resort);

	protected function write_report($resort, $cache_file)
	{
		$report = $this->get_report($resort);
		if( $report )
			cache_create($resort, $cache_file, $report);
	}

}

?>
