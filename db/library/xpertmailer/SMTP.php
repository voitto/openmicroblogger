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

if (!class_exists('MIME')) require_once library_path().'xpertmailer'.DIRECTORY_SEPARATOR . 'MIME.php';

if (version_compare(phpversion(), '5', '>=')) {
  if (!class_exists('SMTP5')) require_once library_path().'xpertmailer'.DIRECTORY_SEPARATOR . 'PHP5' . DIRECTORY_SEPARATOR . 'SMTP5.php';
  class SMTP extends SMTP5 { }
} else {
  if (!class_exists('SMTP4')) require_once library_path().'xpertmailer'.DIRECTORY_SEPARATOR . 'PHP4' . DIRECTORY_SEPARATOR . 'SMTP4.php';
  class SMTP extends SMTP4 { }
}

?>