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

//NOTE: this doesn't really do proper parsing of a mail file, but its
//      good enough for the needs of this tool for Ski Utah emails

class Mail
{
	private static $MAILDIR = '/home/bettykro/mail/bettykrocks.com/skireport/new';

	// returns the most recent email from the given sender with
	// the given subject line regular expression
	// returns the body
	static function get_most_recent($from, $subject, $html=false)
	{
		$output = shell_exec('ls -t '.Mail::$MAILDIR);
		$files = split("\n", $output);
		for($i = 0; $i < count($files); $i++)
		{
			$body = Mail::parse($from, $subject, Mail::$MAILDIR.'/'.$files[$i], $html);
			if( $body )
			{
				return $body;
			}
		}

		return '';
	}

	// returns $body if one matches the from and subject inputs
	private static function parse($from, $subject, $file, $html)
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
				if( $subject && strpos($tmp, $subject) != FALSE )
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
					$boundary = str_replace('"', '', $boundary);
					continue;
				}
			}

			$pattern = "/^Content-Type\s*:\s*text\/plain;/";
			if( $html )
				$pattern = "/^Content-Type\s*:\s*text\/html;/";
            $match = preg_match($pattern, $lines[$i]);
			if( $boundary && $match )
			{
				//we are to the body of the multi-part email
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
            else if( $match )
            {
                //we are to the body of single part plain email
                while( ++$i < count($lines) )
				{
					$body .= $lines[$i]."\n";
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
