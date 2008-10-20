<?php
/*
XMPPHP: The PHP XMPP Library
Copyright (C) 2008  Nathanael C. Fritz
This file is part of SleekXMPP.

XMPPHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

XMPPHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with XMPPHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('LOGGING_ERROR', 0);
define('LOGGING_WARNING', 1);
define('LOGGING_INFO', 2);
define('LOGGING_DEBUG', 3);
define('LOGGING_VERBOSE', 4);

class Logging {
	var $data = array();
	var $names = array();
	var $runlevel;
	var $printout;

	function Logging($printout = False, $runlevel=LOGGING_INFO) {
		$this->names = array('ERROR  ', 'WARNING', 'INFO   ', 'DEBUG  ', 'VERBOSE');
		$this->runlevel = $runlevel;
		$this->printout = $printout;
	}

	function log($msg, $runlevel=Null) {
		if($runlevel === Null) $runlevel = LOGGING_INFO;
		$data[] = array($this->runlevel, $msg);
		if($this->printout and $runlevel <= $this->runlevel) print "{$this->names[$runlevel]}: $msg\n";
	}

	function printout($clear=True, $runlevel=Null) {
		if(!$runlevel) $runlevel = $this->runlevel;
		foreach($this->data as $data) {
			if($runlevel <= $data[0]) print "{$this->names[$runlevel]}: $data[1]\n";
		}
		if($clear) $this->data = array();
	}
}

?>
