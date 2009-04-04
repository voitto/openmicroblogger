<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.6.0 -- 22-October-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @package dbscript
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   */

  /**
   * Cookie
   * 
   * An md5 encrypted cookie, which times out after $expiration seconds.
   * After you authenticate a user, set a cookie to securely and
   * transparently propagate the user's id.
   * Set $cookie->key to a unique string before set() and validate().
   * 
   * Usage:
   * <code>
   * function set_cookie() {
   *   $cookie = new Cookie();
   *   $cookie->userid = "foobar";
   *   $cookie->set();
   * }
   *
   * function check_cookie() {
   *  $cookie = new Cookie();
   *  if ($cookie->validate()) {
   *   print "cookie is good";
   *  } else {
   *   print "cookie is not good";
   *  }
   * }
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/cookie}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @return object
   * @version 0.6.0 -- 22-October-2008
   * @todo support for clients with cookies-disabled
   */

class Cookie {
  var $created;
  var $userid;
  var $version;
  var $mode = 'cfb';
  var $key = 'foobar';
  var $cookiename = 'auth';
  var $myversion = '1';
  var $expiration = '86400';
  var $warning = '300';
  var $glue = '|';
  var $validated = false;
 
  function Cookie() {
    global $prefix;
    $this->cookiename = $prefix.$this->cookiename;
    if (array_key_exists($this->cookiename, $_COOKIE)) {
      $buffer = $this->_unpackage($_COOKIE[$this->cookiename]);
    } else {
      return false;
    }
  }
 
  function get_rnd_iv($iv_len){
    $iv = '';
    while ($iv_len-- > 0) {
      $iv .= chr(mt_rand() & 0xff);
    }
    return $iv;
  }
  function md5_encrypt($plain_text, $password, $iv_len = 16){
    $plain_text .= "\x13";
    $n = strlen($plain_text);
    if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
    $i = 0;
    $enc_text = $this->get_rnd_iv($iv_len);
    $iv = substr($password ^ $enc_text, 0, 512);
    while ($i < $n) {
      $block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
      $enc_text .= $block;
      $iv = substr($block . $iv, 0, 512) ^ $password;
      $i += 16;
    }
    return base64_encode($enc_text);
  }
  function md5_decrypt($enc_text, $password, $iv_len = 16){
    $enc_text = base64_decode($enc_text);
    $n = strlen($enc_text);
    $i = $iv_len;
    $plain_text = '';
    $iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
    while ($i < $n) {
      $block = substr($enc_text, $i, 16);
      $plain_text .= $block ^ pack('H*', md5($iv));
      $iv = substr($block . $iv, 0, 512) ^ $password;
      $i += 16;
    }
    return preg_replace('/\\x13\\x00*$/', '', $plain_text);
  }
  function validate() {
    if (!$this->version || !$this->created || !$this->userid) {
      $validated = false;
    }
    if ($this->version != $this->myversion) {
      $validated = false;
    }
    if (time() - $this->created > $this->expiration) {
      
      // ERROR cookie expired
      $validated = false;
    } elseif ($this->userid) {
      if (time() - $this->created > 500) {
        $this->_reissue();
        $this->set();
      }
      $validated = true;
    }
    return $validated;
  }
  function _unpackage($cookie) {
    $buffer = $this->_decrypt($cookie);
    list($this->version,$this->created,$this->userid) =explode($this->glue,$buffer);
    if ($this->version != $this->myversion || !$this->created ||!$this->userid) {
    }
  }
  function set_cookie($Name, $Value = '', $MaxAge = 0, $Path = '', $Domain = '', $Secure = false, $HTTPOnly = false) {
    header('Set-Cookie: ' . rawurlencode($Name) . '=' . rawurlencode($Value)
                        . (empty($MaxAge) ? '' : '; Max-Age=' . $MaxAge)
                        . (empty($Path)   ? '' : '; path=' . $Path)
                        . (empty($Domain) ? '' : '; domain=' . $Domain)
                        . (!$Secure       ? '' : '; secure')
                        . (!$HTTPOnly     ? '' : '; HttpOnly'), false);
  }
  function _package() {
    $parts = array($this->myversion,time(),$this->userid);
    $cookie = implode($this->glue,$parts);
    return $this->_encrypt($cookie);
  }
  function set( $exp = NULL, $seed = NULL ) {
    if ($exp == NULL)
      $exp = $this->expiration;
    if ($seed == NULL)
      $seed = $this->key;
    global $request;
    $cookie = $this->_package();
    $this->set_cookie($this->cookiename,$cookie,$exp,$request->path,$request->domain);
  }
  function logout() {
    $this->userid=0;
    global $request;
    $cookie = $this->_package();
    $this->set_cookie($this->cookiename,$cookie,0,$request->path,$request->domain);
    unset($_COOKIE[$this->cookiename]);
  }
  function _decrypt($crypttext) {
    $plaintext = $this->md5_decrypt($crypttext, $this->key);
    return $plaintext;
  }
  function _encrypt($plaintext) {
    $crypttext = $this->md5_encrypt($plaintext, $this->key);
    return $crypttext;
  }
  function _reissue() {
    $this->created = time();
  }
}

?>