<?php

abstract class ReportBase
{
	public static function run_cgi($class) {
		if( array_key_exists('HTTP_ENV_VARS', $GLOBALS) ) {
			header( "Content-Type: text/plain" );
			if( array_key_exists('location', $_GET) ) {
				$obj = new $class();
				$obj::run($_GET['location']);
			} else {
				print "err.msg=no location specified\n";
			}
		}
	}

	public abstract function run($location);
}

?>
