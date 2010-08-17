<?php

define('STATE_STOP', 0);
define('STATE_START', 1);

define('STATE_TAG', 2);
define('STATE_OPENING_TAG', 3);
define('STATE_CLOSING_TAG', 4);
define('STATE_TAG_CLEANUP', 5);

define('STATE_ATTRIBUTE', 6);

define('STATE_PI', 7);
define('STATE_JASP', 8);
define('STATE_ESCAPE', 9);

class StartingState  {
	function parse(&$context) {
		$data = $context->scanUntilString('<');
		if ($data != '') {
			$context->handler_object_data->{$context->handler_method_data}($this, $data);
		}
		$context->IgnoreCharacter();
		return STATE_TAG;
	}
}

class TagState {
	function parse(&$context) {
		switch($context->ScanCharacter()) {
		case '/':
			return STATE_CLOSING_TAG;
			break;
		case '?':
			return STATE_PI;
			break;
		case '%':
			return STATE_JASP;
			break;
		case '!':
			return STATE_ESCAPE;
			break;
		default:
			$context->unscanCharacter();
			return STATE_OPENING_TAG;
		}
	}
}

class ClosingTagState {
	function parse(&$context) {
		$tag = $context->scanUntilString('>');
		if ($tag != '') {
			$context->handler_object_element->{$context->handler_method_closing}($this, $tag);
		}
		return STATE_TAG_CLEANUP;
	}
}

class OpeningTagState {

	var $attributes = array();

	function attributeHandler($attributename, $attributevalue) {
		$this->attributes[$attributename] = $attributevalue;
	}

	function parse(&$context) {
		$tag = $context->scanUntilCharacters("/> \n\r\t");
		if ($tag != '') {
			$this->attributes = array();
			$context->_parse(STATE_ATTRIBUTE);
			$context->handler_object_element->{$context->handler_method_opening}($this, $tag, $this->attributes);
		}
		return STATE_TAG_CLEANUP;
	}
}

class TagCleanupState {
	function parse(&$context) {
		$char = $context->scanCharacter();
		if ($char == '/') {
			$char = $context->scanCharacter();
			if ($char != '>') {
				$context->unscanCharacter();
			}
		}
		return STATE_START;
	}
}

class AttributeStartState {

	var $attribute_handler;
	
	function parse(&$context) {
		$context->ignoreWhitespace();
		$attributename = $context->scanUntilCharacters("=/> \n\r\t");
		if ($attributename == '') {
			return STATE_STOP;
		} else {
			$attributevalue = NULL;
			$context->ignoreWhitespace();
			$char = $context->scanCharacter();
			if ($char == '=') {
				$context->ignoreWhitespace();
				$char = $context->ScanCharacter();
				if ($char == '"') {
					$attributevalue= $context->scanUntilString('"');
					$context->IgnoreCharacter();
				} else if ($char == "'") {
					$attributevalue = $context->scanUntilString("'");
					$context->IgnoreCharacter();
				} else {
					$context->unscanCharacter();
					$attributevalue = $context->scanUntilCharacters("/> \n\r\t");
				}
			}
			$this->attribute_handler->attributeHandler($attributename, $attributevalue);
			return STATE_ATTRIBUTE;
		}
	}
}

class JaspState {
	function parse(&$context) {
		$text = $context->scanUntilString('%>');
		if ($text != '') {
			$context->handler_object_element->{$context->handler_method_jasp}($this, $text);
		}
		$context->IgnoreCharacter();
		$context->IgnoreCharacter();
		return STATE_START;
	}
}

class PiState {
	function parse(&$context) {
		$target = $context->scanUntilCharacters(" \n\r\t");
		$context->IgnoreCharacter();
		$data = $context->scanUntilString('?>');
		if ($data != '') {
			$context->handler_object_element->{$context->handler_method_pi}($this, $target, $data);
		}
		$context->IgnoreCharacter();
		$context->IgnoreCharacter();
		return STATE_START;
	}
}

class EscapeState {
	function parse(&$context) {
		$char = $context->ScanCharacter();
		if ($char == '-') {
			$char = $context->ScanCharacter();
			if ($char == '-') {
				$text = $context->scanUntilString('-->');
				$context->IgnoreCharacter();
				$context->IgnoreCharacter();
			} else {
				$context->unscanCharacter();
				$text = $context->scanUntilString('>');
			}
		} else {
			$text = $context->scanUntilString('>');
		}
		$context->IgnoreCharacter();
		if ($text != '') {
			$context->handler_object_element->{$context->handler_method_escape}($this, $text);
		}
		return STATE_START;
	}
}

class StateParser {
	var $rawtext;
	var $position;
	var $length;

	var $State = array();

	function unscanCharacter() {
		$this->position -= 1;  // $this->position--; is broken?
	}
	
	function ignoreCharacter() {
		$this->position += 1;
	}

	function scanCharacter() {
		if ($this->position < $this->length) {
			return $this->rawtext{$this->position++};
		}
	}
	
	function scanUntilCharacters($string) {
		$startpos = $this->position;
		while ($this->position < $this->length && strpos($string, $this->rawtext{$this->position}) === FALSE) {
			$this->position++;
		}
		return substr($this->rawtext, $startpos, $this->position - $startpos);
	}

	function scanUntilString($str) {
		$start = $this->position;
		$this->position = strpos($this->rawtext, $str, $start);
		if ($this->position === FALSE) {
			$this->position = $this->length;
		}
		return substr($this->rawtext, $start, $this->position - $start);
	}

	function ignoreWhitespace() {
		while ($this->position < $this->length && 
			strpos(" \n\r\t", $this->rawtext{$this->position}) !== FALSE) {
			$this->position++;
		}
	}

	function parse($test) {
		$this->rawtext = $test;
		$this->length = strlen($test);
		$this->position = 0;
		$this->_parse();
	}
	
	function _parse($state = STATE_START) {
		do {
			$state = $this->State[$state]->parse($this);
		} while ($state != STATE_STOP && $this->position < $this->length);
	}

}

class TrimDecorator {
	var $original_obj;
	var $original_method;
	
	function TrimDecorator(&$original_obj, $original_method) {
		$this->original_obj =& $original_obj;
		$this->original_method = $original_method;
	}
	
    function TrimData(&$parser, $data) {
    	$data = trim($data);
    	if ($data != '') {
			$this->original_obj->{$this->original_method}($this, $data);
		}
    }
}

class NullHandler {
	function DoNothing($text) {
	}
}

class HtmlParser extends StateParser {
	var $handler_object_element;
	var $handler_method_closing;
	var $handler_method_opening;

	var $handler_object_data;
	var $handler_method_data;

	var $handler_object_pi;
	var $handler_method_pi;

	var $handler_object_jasp;
	var $handler_method_jasp;

	var $handler_object_escape;
	var $handler_method_escape;
	
	var $handler_default;

	var $parser_options;
	
	function HtmlParser() {
		$nullhandler =& new NullHandler();
		$this->set_element_handler($nullhandler, 'DoNothing', 'DoNothing');
		$this->set_data_handler($nullhandler, 'DoNothing');
		$this->set_pi_handler($nullhandler, 'DoNothing');
		$this->set_jasp_handler($nullhandler, 'DoNothing');
		$this->set_escape_handler($nullhandler, 'DoNothing');
		
		$this->State[STATE_START] =& new StartingState();

		$this->State[STATE_CLOSING_TAG] =& new ClosingTagState();
		$this->State[STATE_TAG] =& new TagState();
		$this->State[STATE_OPENING_TAG] =& new OpeningTagState();
		$this->State[STATE_TAG_CLEANUP] =& new TagCleanupState();

		$this->State[STATE_ATTRIBUTE] =& new AttributeStartState();
		$this->State[STATE_ATTRIBUTE]->attribute_handler =& $this->State[STATE_OPENING_TAG];

		$this->State[STATE_PI] =& new PiState();
		$this->State[STATE_JASP] =& new JaspState();
		$this->State[STATE_ESCAPE] =& new EscapeState();

        $this->parser_options['trimDataNodes'] = FALSE;
	}
	
	function set_object(&$object) {
		$this->handler_default =& $object;
	}
	
	function set_option($name, $value) {
		$this->parser_options[$name] = $value;
	}

    function set_data_handler($data_method) {
    	$this->handler_object_data =& $this->handler_default;
    	$this->handler_method_data = $data_method;
    }
	
    function set_element_handler($opening_method, $closing_method) {
    	$this->handler_object_element =& $this->handler_default;
    	$this->handler_method_opening = $opening_method;
    	$this->handler_method_closing = $closing_method;
    }

    function set_pi_handler($pi_method) {
    	$this->handler_object_pi =& $this->handler_default;
    	$this->handler_method_pi = $pi_method;
    }

    function set_escape_handler($escape_method) {
    	$this->handler_object_escape =& $this->handler_default;
    	$this->handler_method_escape = $escape_method;
    }

    function set_jasp_handler ($jasp_method) {
    	$this->handler_object_jasp =& $this->handler_default;
    	$this->handler_method_jasp = $jasp_method;
    }

	function parse($data) {
		if ($this->parser_options['trimDataNodes']) {
			$decorator =& new TrimDecorator($this->handler_object_data, $this->handler_method_data);
			$this->handler_object_data =& $decorator;
			$this->handler_method_data = 'TrimData';
		}
		parent::parse($data);
	}

}


