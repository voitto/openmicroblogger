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
require_once("xmlstream.php");
require_once("logging.php");

class XMPP extends XMLStream {
	var $server;
	var $user;
	var $password;
	var $resource;
	var $fulljid;
	var $authed;

	function XMPP($host, $port, $user, $password, $resource, $server=Null, $printlog=False, $loglevel=Null) {
		$this->XMLStream($host, $port, $printlog, $loglevel);
		$this->user = $user;
		$this->password = $password;
		$this->resource = $resource;
		if(!$server) $server = $host;
		$this->stream_start = '<stream:stream to="' . $server . '" xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client" version="1.0">';
		$this->stream_end = '</stream:stream>';
		$this->addHandler('features', 'http://etherx.jabber.org/streams', 'features_handler');
		$this->addHandler('success', 'urn:ietf:params:xml:ns:xmpp-sasl', 'sasl_success_handler');
		$this->addHandler('failure', 'urn:ietf:params:xml:ns:xmpp-sasl', 'sasl_failure_handler');
		$this->addHandler('proceed', 'urn:ietf:params:xml:ns:xmpp-tls', 'tls_proceed_handler');
		$this->default_ns = 'jabber:client';
		$this->addHandler('message', 'jabber:client', 'message_handler');
		$this->addHandler('presence', 'jabber:client', 'presence_handler');
		$this->authed = False;
		$this->use_encryption = True;
	}

	function message_handler($xml) {
		$payload['type'] = $xml->attrs['type'];
		if(!$paytload['type']) $payload['type'] = 'chat';
		$payload['from'] = $xml->attrs['from'];
		$payload['body'] = $xml->sub('body')->data;
		$this->log->log("Message: {$xml->sub('body')->data}", LOGGING_DEBUG);
		$this->event('message', $payload);
	}

	function message($to, $body, $type='chat', $subject=Null) {
		$to = htmlspecialchars($to);
		$body = htmlspecialchars($body);
		$subject = htmlspecialchars($subject);
		$out = "<message from='{$this->fulljid}' to='$to' type='$type'>";
		if($subject) $out .= "<subject>$subject</subject>";
		$out .= "<body>$body</body></message>";
		$this->send($out);
	}

	function presence($status=Null, $show='available', $to=Null) {
		$type = '';
		$to = htmlspecialchars($to);
		$status = htmlspecialchars($status);
		if($show == 'unavailable') $type = 'unavailable';
		$out = "<presence";
		if($to) $out .= " to='$to'";
		if($type) $out .= " type='$type'";
		if($show == 'available' and !$status) {
			$out .= "/>";
		} else {
			$out .= ">";
			if($show != 'available') $out .= "<show>$show</show>";
			if($status) $out .= "<status>$status</status>";
			$out .= "</presence>";
		}
		$this->send($out);
	}

	function presence_handler($xml) {
		$payload['type'] = (isset($xml->attrs['type'])) ? $xml->attrs['type'] : 'available';
		$payload['show'] = (isset($xml->sub('show')->data)) ? $xml->sub('show')->data : $payload['type'];
		$payload['from'] = $xml->attrs['from'];
		$payload['status'] = (isset($xml->sub('status')->data)) ? $xml->sub('status')->data : '';
		$this->log->log("Presence: {$payload['from']} [{$payload['show']}] {$payload['status']}", LOGGING_DEBUG);
		$this->event('presence', $payload);
	}

	function features_handler($xml) {
		if($xml->hassub('starttls') and $this->use_encryption) {
			$this->send("<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'><required /></starttls>");
		} elseif($xml->hassub('bind')) {
			$id = $this->getId();
			$this->addIdHandler($id, 'resource_bind_handler');
			$this->send("<iq xmlns=\"jabber:client\" type=\"set\" id=\"$id\"><bind xmlns=\"urn:ietf:params:xml:ns:xmpp-bind\"><resource>{$this->resource}</resource></bind></iq>");
		} else {
			$this->log->log("Attempting Auth...");
			$this->send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" . base64_encode("\x00" . $this->user . "\x00" . $this->password) . "</auth>");
		}
	}

	function sasl_success_handler($xml) {
		$this->log->log("Auth success!");
		$this->authed = True;
		$this->reset();
	}
	
	function sasl_failure_handler($xml) {
		$this->log->log("Auth failed!", LOGGING_ERROR);
		$this->disconnect();
	}

	function resource_bind_handler($xml) {
		if($xml->attrs['type'] == 'result') {
			$this->log->log("Bound to " . $xml->sub('bind')->sub('jid')->data);
			$this->fulljid = $xml->sub('bind')->sub('jid')->data;
		}
		$id = $this->getId();
		$this->addIdHandler($id, 'session_start_handler');
		$this->send("<iq xmlns='jabber:client' type='set' id='$id'><session xmlns='urn:ietf:params:xml:ns:xmpp-session' /></iq>");
	}

	function session_start_handler($xml) {
		$this->log->log("Session started");
		$this->event('session_start');
	}

	function tls_proceed_handler($xml) {
		$this->log->log("Starting TLS encryption");
		stream_socket_enable_crypto($this->socket, True, STREAM_CRYPTO_METHOD_SSLv23_CLIENT);
		$this->reset();
	}
}
?>
