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
require_once("xmlobj.php");
require_once("logging.php");

class XMLStream {
	var $socket;
	var $parser;
	var $buffer;
	var $xml_depth = 0;
	var $host;
	var $port;
	var $stream_start = '<stream>';
	var $stream_end = '</stream';
	var $disconnected = false;
	var $sent_disconnect = False;
	var $ns_map = array();
	var $current_ns = array();
	var $xmlobj = Null;
	var $nshandlers = array();
	var $idhandlers = array();
	var $eventhandlers = array();
	var $lastid = 0;
	var $default_ns;
	var $until = '';
	var $until_happened = False;
	var $until_payload = array();
	var $log;
	var $reconnect = True;
	var $been_reset = False;
	var $is_server;

	function XMLStream($host=Null, $port=Null, $log=False, $loglevel=Null, $is_server=False) {
		$this->reconnect = !$is_server;
		$this->is_server = $is_server;
		$this->host = $host;
		$this->port = $port;
		$this->setupParser();
		$this->log = new Logging($log, $loglevel);
	}

	function getId() {
		$this->lastid++;
		return $this->lastid;
	}

	function addIdHandler($id, $pointer, $obj=Null) {
		$this->idhandlers[$id] = array($pointer, $obj);
	}

	function addHandler($name, $ns, $pointer, $obj=Null, $depth=1) {
		$this->nshandlers[] = array($name,$ns,$pointer,$obj, $depth);
	}

	function addEventHandler($name, $pointer, $obj) {
		$this->eventhanders[] = array($name, $pointer, $obj);
	}

	function connect($persistent=False, $sendinit=True) {
		$this->disconnected = False;
		$this->sent_disconnect = False;
		if($persistent) {
			$conflag = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
		} else {
			$conflag = STREAM_CLIENT_CONNECT;
		}
		$this->log->log("Connecting to tcp://{$this->host}:{$this->port}");
		$this->socket = stream_socket_client("tcp://{$this->host}:{$this->port}", $flags=$conflag);
		if(!$this->socket) {
			$this->log->log("Could not connect.", LOGGING_ERROR);
			$this->disconnected = True;
		}
		stream_set_blocking($this->socket, 1);
		if($sendinit) $this->send($this->stream_start);
	}

	function apply_socket($socket) {
		$this->socket = $socket;
	}

	function process() {
		$updated = '';
		while(!$this->disconnect) {
			$read = array($this->socket);
			$write = NULL;
			$except = NULL;
			$updated = stream_select($read, $write, $except, 1);
			if ($updated > 0) {
				$buff = @fread($this->socket, 1024);
				if(!$buff) { 
					if($this->reconnect) {
						$this->doReconnect();
					} else {
						fclose($this->socket);
						return False;
					}
				}
				$this->log->log("RECV: $buff", LOGGING_VERBOSE);
				xml_parse($this->parser, $buff, False);
			}
		}
	}

	function read() {
		$buff = @fread($this->socket, 1024);
		if(!$buff) { 
			if($this->reconnect) {
				$this->doReconnect();
			} else {
				fclose($this->socket);
				return False;
			}
		}
		$this->log->log("RECV: $buff", LOGGING_VERBOSE);
		xml_parse($this->parser, $buff, False);
	}

	function processTime($timeout=-1) {
		$start = time();
		$updated = '';
		while(!$this->disconnected and ($timeout == -1 or time() - $start < $timeout)) {
			$read = array($this->socket);
			$write = NULL;
			$except = NULL;
			$updated = stream_select($read, $write, $except, 1);
			if ($updated > 0) {
				$buff = @fread($this->socket, 1024);
				if(!$buff) { 
					if($this->reconnect) {
						$this->doReconnect();
					} else {
						fclose($this->socket);
						return False;
					}
				}
				$this->log->log("RECV: $buff", LOGGING_VERBOSE);
				xml_parse($this->parser, $buff, False);
			}
		}
	}

	function processUntil($event, $timeout=-1) {
		$start = time();
		if(!is_array($event)) $event = array($event);
		$this->until[] = $event;
		end($this->until);
		$event_key = key($this->until);
		reset($this->until);
		$updated = '';
		while(!$this->disconnected and $this->until[$event_key] and (time() - $start < $timeout or $timeout == -1)) {
			$read = array($this->socket);
			$write = NULL;
			$except = NULL;
			$updated = stream_select($read, $write, $except, 1);
			if ($updated > 0) {
				$buff = @fread($this->socket, 1024);
				if(!$buff) { 
					if($this->reconnect) {
						$this->doReconnect();
					} else {
						fclose($this->socket);
						return False;
					}
				}
				$this->log->log("RECV: $buff", LOGGING_VERBOSE);
				xml_parse($this->parser, $buff, False);
			}
		}
		if(array_key_exists($event_key, $this->until_payload)) {
			$payload = $this->until_payload[$event_key];
		} else {
			$payload = array();
		}
		unset($this->until_payload[$event_key]);
		return $payload;
	}

	function startXML($parser, $name, $attr) {
		if($this->been_reset) {
			$this->been_reset = False;
			$this->xml_depth = 0;
		}
		$this->xml_depth++;
		if(array_key_exists('XMLNS', $attr)) {
			$this->current_ns[$this->xml_depth] = $attr['XMLNS'];
		} else {
			$this->current_ns[$this->xml_depth] = $this->current_ns[$this->xml_depth - 1];
			if(!$this->current_ns[$this->xml_depth]) $this->current_ns[$this->xml_depth] = $this->default_ns;
		}
		$ns = $this->current_ns[$this->xml_depth];
		foreach($attr as $key => $value) {
			if(strstr($key, ":")) {
				$key = explode(':', $key);
				$key = $key[1];
				$this->ns_map[$key] = $value;
			}
		}
		if(!strstr($name, ":") === False)
		{
			$name = explode(':', $name);
			$ns = $this->ns_map[$name[0]];
			$name = $name[1];
		}
		$obj = new XMLObj($name, $ns, $attr);
		if($this->xml_depth > 1)
			$this->xmlobj[$this->xml_depth - 1]->subs[] = $obj;
		$this->xmlobj[$this->xml_depth] = $obj;
	}

	function endXML($parser, $name) {
		#$this->log->log("Ending $name", LOGGING_DEBUG);
		#print "$name\n";
		if($this->been_reset) {
			$this->been_reset = False;
			$this->xml_depth = 0;
		}
		$this->xml_depth--;
		if($this->xml_depth == 1) {
			#clean-up old objects
			$found = False;
			foreach($this->nshandlers as $handler) {
				if($handler[4] != 1 and $this->xmlobj[2]->hassub($handler[0])) {
					$searchxml = $this->xmlobj[2]->sub($handler[0]);
				} elseif(is_array($this->xmlobj) and array_key_exists(2, $this->xmlobj)) {
					$searchxml = $this->xmlobj[2];
				}
				if($searchxml !== Null and $searchxml->name == $handler[0] and ($searchxml->ns == $handler[1] or (!$handler[1] and $searchxml->ns == $this->default_ns))) {
					if($handler[3] === Null) $handler[3] = $this;
					$this->log->log("Calling {$handler[2]}", LOGGING_DEBUG);
					call_user_method($handler[2], $handler[3], $this->xmlobj[2]);
				}
			}
			foreach($this->idhandlers as $id => $handler) {
				if(array_key_exists('id', $this->xmlobj[2]->attrs) and $this->xmlobj[2]->attrs['id'] == $id) {
					if($handler[1] === Null) $handler[1] = $this;
					call_user_method($handler[0], $handler[1], $this->xmlobj[2]);
					#id handlers are only used once
					unset($this->idhandlers[$id]);
					break;
				}
			}
			if(is_array($this->xmlobj)) {
				$this->xmlobj = array_slice($this->xmlobj, 0, 1);
				$this->xmlobj[0]->subs = Null;
			}
			unset($this->xmlobj[2]);
		}
		if($this->xml_depth == 0 and !$this->been_reset) {
			if(!$this->disconnected) {
				if(!$this->sent_disconnect) {
					$this->send($this->stream_end);
				}
				$this->disconnected = True;
				$this->sent_disconnect = True;
				fclose($this->socket);
				if($this->reconnect) {
					$this->doReconnect();
				}
			}
			$this->event('end_stream');
		}
	}

	function doReconnect() {
		if(!$this->is_server) {
			$this->log->log("Reconnecting...", LOGGING_WARNING);
			$this->connect(False, False);
			$this->reset();
		}
	}

	function disconnect() {
		$this->reconnect = False;
		$this->send($this->stream_end);
		$this->sent_disconnect = True;
		$this->processUntil('end_stream', 5);
		$this->disconnected = True;
	}

	function event($name, $payload=Null) {
		$this->log->log("EVENT: $name", LOGGING_DEBUG);
		foreach($this->eventhandlers as $handler) {
			if($name == $handler[0]) {
				if($handler[2] === Null) $handler[2] = $this;
				call_user_method($handler[1], $handler[2], $payload);
			}
		}
		foreach($this->until as $key => $until) {
			if(is_array($until)) {
				if(in_array($name, $until)) {
					$this->until_payload[$key][] = array($name, $payload);
					$this->until[$key] = False;
				}
			}
		}
	}

	function charXML($parser, $data) {
		if(array_key_exists($this->xml_depth, $this->xmlobj))
			$this->xmlobj[$this->xml_depth]->data .= $data;
	}

	function send($msg) {
		#socket_write($this->socket, $msg);
		$this->log->log("SENT: $msg", LOGGING_VERBOSE);
		@fwrite($this->socket, $msg);
	}

	function reset() {
		$this->xml_depth = 0;
		unset($this->xmlobj);
		$this->xmlobj = array();
		$this->setupParser();
		if(!$this->is_server) {
			$this->send($this->stream_start);
		}
		$this->been_reset = True;
	}

	function setupParser() {
		$this->parser = xml_parser_create('UTF-8');
		xml_parser_set_option($this->parser,XML_OPTION_SKIP_WHITE,1);
		xml_parser_set_option($this->parser,XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'startXML', 'endXML');
		xml_set_character_data_handler($this->parser, 'charXML');
	}
}

