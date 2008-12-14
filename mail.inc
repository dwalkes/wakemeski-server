<?php

//NOTE: this doesn't really do proper parsing of a mail file, but its
//      good enough for the needs of this tool for Ski Utah emails

class Mail
{
	private static $MAILDIR = '/home/bettykro/mail/bettykrocks.com/skireport/new';

	// returns the most recent email from the given sender with
	// the given subject line regular expression
	// returns the body
	static function get_most_recent($from, $subject)
	{
		$output = shell_exec('ls -t '.Mail::$MAILDIR);
		$files = split("\n", $output);
		for($i = 0; $i < count($files); $i++)
		{
			$body = Mail::parse($from, $subject, Mail::$MAILDIR.'/'.$files[$i]);
			if( $body )
			{
				return $body;
			}
		}
		
		return '';
	}

	// returns $body if one matches the from and subject inputs
	private static function parse($from, $subject, $file)
	{
		$body = '';
		$boundary = '';
		
		$lines = Mail::get_file_as_lines($file);
		for($i = 0; $i < count($lines); $i++)
		{
			preg_match_all("/^Subject\s*:\s*(\S)/", $lines[$i], $matches, PREG_OFFSET_CAPTURE);
			if( count($matches[0]) )
			{
				$tmp = substr($lines[$i],$matches[1][0][1]);
				if( strpos($tmp, $subject) != FALSE )
					return '';
				continue;
			}
			
			preg_match_all("/^From\s*:\s*(\S)/", $lines[$i], $matches, PREG_OFFSET_CAPTURE);
			if( count($matches[0]) )
			{
				$tmp = substr($lines[$i],$matches[1][0][1]);
				if( $tmp != $from )
					return '';
				continue;
			}
			
			if( !$boundary )
			{
				preg_match_all("/boundary\s*=\s*(\S+);/", $lines[$i], $matches, PREG_OFFSET_CAPTURE);
				if( count($matches[0]) )
				{
					$boundary = $matches[1][0][0];
					continue;
				}
			}
	
			if( $boundary && preg_match("/^Content-Type\s*:\s*text\/plain;/", $lines[$i]) )
			{
				//we are to the body of the email
				while( $lines[++$i] != "--$boundary" && $i < count($lines) )
				{
					// a line ending with '=' means the next line should be
					// joined with it (ie no end of line)
					if( substr($lines[$i], -1) == '=' )
					{
						$body .= substr($lines[$i], 0, -1);
					}
					else
					{
						$body .= $lines[$i]."\n";
					}
				}
			}
		}
		
		return $body;
	}
	
	private static function get_file_as_lines($file)
	{ 
		return split("\n", file_get_contents($file));
	}
}

?>
