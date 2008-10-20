<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *                                                                                         *
 *  XPertMailer is a PHP Mail Class that can send and read messages in MIME format.        *
 *  This file is part of the XPertMailer package (http://xpertmailer.sourceforge.net/)     *
 *  Copyright (C) 2007 Tanase Laurentiu Iulian                                             *
 *                                                                                         *
 *  This library is free software; you can redistribute it and/or modify it under the      *
 *  terms of the GNU Lesser General Public License as published by the Free Software       *
 *  Foundation; either version 2.1 of the License, or (at your option) any later version.  *
 *                                                                                         *
 *  This library is distributed in the hope that it will be useful, but WITHOUT ANY        *
 *  WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A        *
 *  PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.        *
 *                                                                                         *
 *  You should have received a copy of the GNU Lesser General Public License along with    *
 *  this library; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, *
 *  Fifth Floor, Boston, MA 02110-1301, USA                                                *
 *                                                                                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

if (!class_exists('FUNC4')) require_once library_path().'xpertmailer'.DIRECTORY_SEPARATOR . 'FUNC4.php';

$_RESULT = array();

class POP34 {

  var $CRLF = "\r\n";
  var $PORT = 110;
  var $TOUT = 30;
  var $COUT = 5;
  var $BLEN = 1024;

  function _ok($conn = null, &$resp, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    if (!is_resource($conn)) return FUNC4::trace($debug, 'invalid resource connection', 1);
    else {
      $_pop3 = new POP34;
      $ret = true;
      do {
        if ($result = fgets($conn, $_pop3->BLEN)) {
          $resp[] = $result;
          if (substr($result, 0, 3) != '+OK') {
            $ret = false;
            break;
          }
        } else {
          $resp[] = 'can not read';
          $ret = false;
          break;
        }
      } while ($result[3] == '-');
      return $ret;
    }
  }

  function connect($host = null, $user = null, $pass = null, $port = null, $vssl = null, $tout = null, $context = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    $_pop3 = new POP34;
    if ($port == null) $port = $_pop3->PORT;
    if ($tout == null) $tout = $_pop3->TOUT;
    $err = array();
    if (!is_string($host)) $err[] = 'invalid host type';
    else {
      if (!(trim($host) != '' && (FUNC4::is_ipv4($host) || FUNC4::is_hostname($host, true, $debug)))) $err[] = 'invalid host value';
    }
    if (!is_string($user)) $err[] = 'invalid username type';
    else if (($user = FUNC4::str_clear($user)) == '') $err[] = 'invalid username value';
    if (!is_string($pass)) $err[] = 'invalid password type';
    else if (($pass = FUNC4::str_clear($pass)) == '') $err[] = 'invalid password value';
    if (!(is_int($port) && $port > 0)) $err[] = 'invalid port value';
    if ($vssl != null) {
      if (!is_string($vssl)) $err[] = 'invalid ssl version type';
      else {
        $vssl = strtolower($vssl);
        if (!($vssl == 'tls' || $vssl == 'ssl' || $vssl == 'sslv2' || $vssl == 'sslv3')) $err[] = 'invalid ssl version value';
      }
    }
    if (!(is_int($tout) && $tout > 0)) $err[] = 'invalid timeout value';
    if ($context != null && !is_resource($context)) $err[] = 'invalid context type';
    if (count($err) > 0) FUNC4::trace($debug, implode(', ', $err));
    else {
      $_pop3 = new POP34;
      $ret = false;
      $prt = ($vssl == null) ? 'tcp' : $vssl;
      $conn = ($context == null) ? fsockopen($prt.'://'.$host, $port, $errno, $errstr, $tout) : fsockopen($prt.'://'.$host, $port, $errno, $errstr, $tout, $context);
      if (!$conn) $_RESULT[401] = $errstr;
      else if (!socket_set_timeout($conn, $_pop3->COUT)) $_RESULT[402] = 'could not set socket timeout';
      else if (!POP34::_ok($conn, $resp, $debug)) $_RESULT[403] = $resp;
      else $ret = POP34::auth($conn, $user, $pass, $debug);
      if (!$ret) {
        if (is_resource($conn)) @fclose($conn);
        $conn = false;
      }
      return $conn;
    }
  }

  function auth($conn = null, $user = null, $pass = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    $err = array();
    if (!is_resource($conn)) $err[] = 'invalid resource connection';
    if (!is_string($user)) $err[] = 'invalid username type';
    else if (($user = FUNC4::str_clear($user)) == '') $err[] = 'invalid username value';
    if (!is_string($pass)) $err[] = 'invalid password type';
    else if (($pass = FUNC4::str_clear($pass)) == '') $err[] = 'invalid password value';
    if (count($err) > 0) FUNC4::trace($debug, implode(', ', $err));
    else {
      $_pop3 = new POP34;
      $ret = false;
      if (!fwrite($conn, 'USER '.$user.$_pop3->CRLF)) $_RESULT[404] = 'can not write';
      else if (!POP34::_ok($conn, $resp, $debug)) $_RESULT[405] = $resp;
      else if (!fwrite($conn, 'PASS '.$pass.$_pop3->CRLF)) $_RESULT[405] = 'can not write';
      else if (!POP34::_ok($conn, $resp, $debug)) $_RESULT[406] = $resp;
      else {
        $_RESULT[407] = $resp;
        $ret = true;
      }
      return $ret;
    }
  }

  function disconnect($conn = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    if (!is_resource($conn)) FUNC4::trace($debug, 'invalid resource connection', 1);
    else {
      $_pop3 = new POP34;
      if (!fwrite($conn, 'QUIT'.$_pop3->CRLF)) $_RESULT[437] = 'can not write';
      else if (!POP34::_ok($conn,  $resp, $debug)) $_RESULT[438] = $resp;
      else $_RESULT[439] = $resp;
      return @fclose($conn);
    }
  }

  function pnoop($conn = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    if (!is_resource($conn)) FUNC4::trace($debug, 'invalid resource connection');
    else {
      $_pop3 = new POP34;
      $ret = false;
      if (!fwrite($conn, 'NOOP'.$_pop3->CRLF)) $_RESULT[408] = 'can not write';
      else if (!POP34::_ok($conn,  $resp, $debug)) $_RESULT[409] = $resp;
      else {
        $_RESULT[410] = $resp;
        $ret = true;
      }
      return $ret;
    }
  }

  function prset($conn = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    if (!is_resource($conn)) FUNC4::trace($debug, 'invalid resource connection');
    else {
      $_pop3 = new POP34;
      $ret = false;
      if (!fwrite($conn, 'RSET'.$_pop3->CRLF)) $_RESULT[411] = 'can not write';
      else if (!POP34::_ok($conn,  $resp, $debug)) $_RESULT[412] = $resp;
      else {
        $_RESULT[413] = $resp;
        $ret = true;
      }
      return $ret;
    }
  }

  function pquit($conn = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    if (!is_resource($conn)) FUNC4::trace($debug, 'invalid resource connection');
    else {
      $_pop3 = new POP34;
      $ret = false;
      if (!fwrite($conn, 'QUIT'.$_pop3->CRLF)) $_RESULT[414] = 'can not write';
      else if (!POP34::_ok($conn,  $resp, $debug)) $_RESULT[415] = $resp;
      else {
        $_RESULT[416] = $resp;
        $ret = true;
      }
      return $ret;
    }
  }

  function pstat($conn = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    if (!is_resource($conn)) FUNC4::trace($debug, 'invalid resource connection');
    else {
      $_pop3 = new POP34;
      $ret = false;
      if (!fwrite($conn, 'STAT'.$_pop3->CRLF)) $_RESULT[417] = 'can not write';
      else if (!POP34::_ok($conn,  $resp, $debug)) $_RESULT[418] = $resp;
      else {
        if (count($exp = explode(' ', substr($resp[0], 4, -strlen($_pop3->CRLF)))) == 2) {
          $val1 = intval($exp[0]);
          $val2 = intval($exp[1]);
          if (strval($val1) === $exp[0] && strval($val2) === $exp[1]) {
            $ret = array($val1 => $val2);
            $_RESULT[421] = $resp;
          } else $_RESULT[420] = $resp;
        } else $_RESULT[419] = $resp;
      }
      return $ret;
    }
  }

  function pdele($conn = null, $msg = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    $err = array();
    if (!is_resource($conn)) $err[] = 'invalid resource connection';
    if (!(is_int($msg) && $msg > 0)) $err[] = 'invalid message number';
    if (count($err) > 0) FUNC4::trace($debug, implode(', ', $err));
    else {
      $_pop3 = new POP34;
      $ret = false;
      if (!fwrite($conn, 'DELE '.$msg.$_pop3->CRLF)) $_RESULT[422] = 'can not write';
      else if (!POP34::_ok($conn,  $resp, $debug)) $_RESULT[423] = $resp;
      else {
        $_RESULT[424] = $resp;
        $ret = true;
      }
      return $ret;
    }
  }

  function pretr($conn = null, $msg = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    $err = array();
    if (!is_resource($conn)) $err[] = 'invalid resource connection';
    if (!(is_int($msg) && $msg > 0)) $err[] = 'invalid message number';
    if (count($err) > 0) FUNC4::trace($debug, implode(', ', $err));
    else {
      $_pop3 = new POP34;
      $ret = false;
      if (!fwrite($conn, 'RETR '.$msg.$_pop3->CRLF)) $_RESULT[425] = 'can not write';
      else if (!POP34::_ok($conn,  $resp, $debug)) $_RESULT[426] = $resp;
      else {
        $ret = '';
        do {
          if ($res = fgets($conn, $_pop3->BLEN)) $ret .= $res;
          else {
            $_RESULT[427] = 'can not read';
            $ret = false;
            break;
          }
        } while ($res != '.'.$_pop3->CRLF);
        if ($ret) {
          $ret = substr($ret, 0, -strlen($_pop3->CRLF.'.'.$_pop3->CRLF));
          $_RESULT[428] = $resp;
        }
      }
      return $ret;
    }
  }

  function plist($conn = null, $msg = null, $debug = null) {
    if (!FUNC4::is_debug($debug)) $debug = debug_backtrace();
    global $_RESULT;
    $_RESULT = array();
    $err = array();
    if (!is_resource($conn)) $err[] = 'invalid resource connection';
    if ($msg == null) $msg = 0;
    if (!(is_int($msg) && $msg >= 0)) $err[] = 'invalid message number';
    if (count($err) > 0) FUNC4::trace($debug, implode(', ', $err));
    else {
      $_pop3 = new POP34;
      $ret = false;
      $num = ($msg > 0) ? true : false;
      if (!fwrite($conn, 'LIST'.($num ? ' '.$msg : '').$_pop3->CRLF)) $_RESULT[429] = 'can not write';
      else if (!POP34::_ok($conn,  $resp, $debug)) $_RESULT[430] = $resp;
      else {
        if ($num) {
          if (count($exp = explode(' ', substr($resp[0], 4, -strlen($_pop3->CRLF)))) == 2) {
            $val1 = intval($exp[0]);
            $val2 = intval($exp[1]);
            if (strval($val1) === $exp[0] && strval($val2) === $exp[1]) {
              $ret = array($val1 => $val2);
              $_RESULT[433] = $resp;
            } else $_RESULT[432] = $resp;
          } else $_RESULT[431] = $resp;
        } else {
          do {
            if ($res = fgets($conn, $_pop3->BLEN)) {
              if (count($exp = explode(' ', substr($res, 0, -strlen($_pop3->CRLF)))) == 2) {
                $val1 = intval($exp[0]);
                $val2 = intval($exp[1]);
                if (strval($val1) === $exp[0] && strval($val2) === $exp[1]) {
                  $ret[$val1] = $val2;
                  $_RESULT[436] = $resp;
                }
              } else if ($res[0] != '.') {
                $_RESULT[435] = $res;
                $ret = false;
                break;
              }
            } else {
              $_RESULT[434] = 'can not read';
              $ret = false;
              break;
            }
          } while ($res[0] != '.');
        }
      }
      return $ret;
    }
  }

}

?>