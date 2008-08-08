<?php

/**
 * Convert Type
 *
 * Converts any string into its appropriate native type.
 *
 * Example:
 * - 'two' => 'two'
 * - '2' => 2
 * - '2.0' => 2.0
 * - 'true' => true
 * - 'false' => false
 *
 * @access public
 * @author Gary Court <gcourt@gmail.com>
 * @param string $var String to convert.
 * @return mixed The converted string.
 * @version 1.0
 */

function convert_type($var) 
{
  if (is_string($var)) {
    if (is_numeric($var)) {
      if(strpos($var, '.') !== false)
        return (float)$var;
      else
        return (int)$var;
    }
  
    if( $var == "true" )  return true;
    if( $var == "false" ) return false;
  }
  
  return $var;
}

/**
 * Array Trim
 *
 * Recurses through an array, calling trim() on any strings.
 *
 * @access public
 * @author Gary Court <gcourt@gmail.com>
 * @param array $arr Array to trim.
 * @param string $charlist Parameter to pass to trim().
 * @version 1.0
 */

function array_trim($arr, $charlist = " \t\n\r\0\x0B")
{
  for ($i = 0; $i < count($arr); $i++) {
    if (is_string($arr[$i]))
      $arr[$i] = trim($arr[$i], $charlist);
    elseif (is_array($arr[$i]))
      $arr[$i] = array_trim($arr[$i], $charlist);
  }
  return $arr;
}

/**
 * Merge Sort
 *
 * Uses the merge sort algorithm on $array, and compares array elements 
 * using the function named in $cmp_function.
 *
 * @access public
 * @author Gary Court <gcourt@gmail.com>
 * @param array $array The array to be sorted
 * @param string $cmp_function The name of the function to use to compare two array elements. If null, uses 'strcmp'.
 * @version 1.0
 */

function mergesort(&$array, $cmp_function = 'strcmp') {
  // Arrays of size < 2 require no action.
  if (count($array) < 2) return;
  
  // Split the array in half
  $halfway = count($array) / 2;
  $array1 = array_slice($array, 0, $halfway);
  $array2 = array_slice($array, $halfway);
  
  // Recurse to sort the two halves
  mergesort($array1, $cmp_function);
  mergesort($array2, $cmp_function);
  
  // If all of $array1 is <= all of $array2, just append them.
  if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
    $array = array_merge($array1, $array2);
    return;
  }
  
  // Merge the two sorted arrays into a single sorted array
  $array = array();
  $ptr1 = $ptr2 = 0;
  while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
    if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1)
      $array[] = $array1[$ptr1++];
    else
      $array[] = $array2[$ptr2++];
  }
  
  // Merge the remainder
  while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
  while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];
}


class HTTP_Negotiate
{
  /**
   * HTTP Content Negotiation
   * 
   * Using the content negotiation algorithm specified in 
   * {@link http://cidr-report.org/ietf/all-ids/draft-ietf-http-v11-spec-00.txt draft-ietf-http-v11-spec-00}, 
   * will return the most appropriate variants (may be more then one that
   * works) based on the provided request headers. This function is based
   * off of {@link http://search.cpan.org/dist/libwww-perl/lib/HTTP/Negotiate.pm libwww-perl, HTTP::Negotiate, choose()}.
   *
   * Usage:
   * <code>
   *  $variants = array(
   *    array(
   *      id => 'var1',
   *      qs => 1.000,
   *      type => 'text/html',
   *      encoding => null,
   *      charset => 'iso-8859-1',
   *      language => 'en',
   *      size => 3000
   *    ),
   *    array(
   *      id => 'var2',
   *      qs => 1.000,
   *      type => 'application/xhtml+xml',
   *      encoding => null,
   *      charset => 'iso-8859-1',
   *      language => 'en',
   *      size => 3000
   *    ),
   *  );
   *  
   *  $request_headers = array(
   *    HTTP_ACCEPT => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,{@*}*;q=0.5',
   *    HTTP_ACCEPT_LANGUAGE => 'en-us,en;q=0.5',
   *    HTTP_ACCEPT_CHARSET => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7', 
   *    HTTP_ACCEPT_ENCODING => 'gzip,deflate'
   *  );
   *  
   *  $results = HTTP_Negotiate::choose($variants, $request_headers);
   *  assertTrue(count($results) == 1 && $results[0]['id'] == 'var2');
   * </code>
   *
   * More information on accept headers can be found at 
   * {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html}
   *
   * @access public
   * @author Gary Court <gcourt@gmail.com>
   * @param array $variants Array of array of strings which contain the supported parameters of each variant (supported keys: id, qs, type, encoding, charset, language, size)
   * @param array $request_headers Array of strings which contain the request header (supported keys: HTTP_ACCEPT, HTTP_ACCEPT_LANGUAGE, HTTP_ACCEPT_CHARSET, HTTP_ACCEPT_ENCODING). If null, $_SERVER is used.
   * @return array The acceptable variants (from $variants) based on the request headers. May return more then one acceptable variant (in original order) or may return null (if no acceptable variant was found). 
   * @static
   * @version 1.0
   * @todo Support for parameters in variant->type.
   */
   
  function choose($variants, $request_headers = null) 
  {


    //check arguments
    if (!is_array($variants))
      return false;
    if ($request_headers === null)
      $request_headers = $_SERVER;
    elseif (!is_array($request_headers))
      return false;

    //parse all accept values
    $request = array();
    $request_header_keys = array_keys($request_headers);
    foreach ($request_header_keys as $request_header_key) {
      $accept_type = null;
      if (strpos($request_header_key, 'HTTP_ACCEPT_') !== false)
        $accept_type = strtolower(substr($request_header_key, strlen('HTTP_ACCEPT_')));
      elseif ($request_header_key == 'HTTP_ACCEPT')
        $accept_type = 'type';
      
      if ($accept_type) {
        $request[$accept_type] = array();
        $accept_variants = array_trim(explode(',', $request_headers[$request_header_key]));
        foreach ($accept_variants as $accept_variant) {
          if ($accept_variant) {
            $accept_variant_parameters = array_trim(explode(';', $accept_variant));
            $request[$accept_type][$accept_variant_parameters[0]] = array();
            for ($i = 1; $i < count($accept_variant_parameters); $i++) {
              if (strpos($accept_variant_parameters[$i], '=') !== false) {
                $accept_variant_parameter_values = array_trim(explode('=', $accept_variant_parameters[$i]));
                $accept_variant_parameter_values[1] = convert_type($accept_variant_parameter_values[1]);
                
                if ($accept_variant_parameter_values[0] == 'q') {
                  if ($accept_variant_parameter_values[1] > 1.0)
                    $accept_variant_parameter_values[1] = 1.0;
                  elseif ($accept_variant_parameter_values[1] < 0.0)
                    $accept_variant_parameter_values[1] = 0.0;
                }
                if ($accept_variant_parameter_values[0] == 'mxb' && $accept_variant_parameter_values[1] < 0)
                  $accept_variant_parameter_values[1] = 0;
                
                $request[$accept_type][$accept_variant_parameters[0]][$accept_variant_parameter_values[0]] = $accept_variant_parameter_values[1];
              }
            }
            if (!isset($request[$accept_type][$accept_variant_parameters[0]]['q']))
              $request[$accept_type][$accept_variant_parameters[0]]['q'] = 1.0;
          }
        }
      }
    }
    
    //determine if at least one variant specifies a language
    $language_variant_specified = false;
    foreach ($variants as $variant)
      if (isset($variant['language'])) {
        $language_variant_specified = true;
        break;
      }
    
    //determine the best variant for the request
    $results = array();
    foreach ($variants as $variant) {
      //calculate qs
      if (!isset($variant['qs']) || !is_numeric($variant['qs'])) 
        $qs = 1.0;
      else
        $qs = (float)convert_type($variant['qs']);
      
      //calculate qe
      if (!isset($request['encoding']))
        $qe = 1.0;
      elseif (!isset($variant['encoding']) || !count($request['encoding']))
        $qe = 1.0;
      elseif (array_key_exists($variant['encoding'], $request['encoding']))
        $qe = (float)$request['encoding'][$variant['encoding']]['q'];
      elseif ($variant['encoding'] == 'identity')
        $qe = 1.0;
      elseif (isset($request['encoding']['*']))
        $qe = (float)$request['encoding']['*']['q'];
      else
        $qe = 0.0;
      
      // ---------

      // hack by Brian
       
      // changed !count(... to !isset($request['charset'])

      // ---------
      
      //calculate qc
      if (!(isset($request['charset'])))
        $qc = 1.0;
      elseif (!isset($variant['charset']) || $variant['charset'] == 'US-ASCII' || !isset($request['charset']))
        $qc = 1.0;
      elseif (array_key_exists($variant['charset'], $request['charset']))
        $qc = (float)$request['charset'][$variant['charset']]['q'];
      elseif (isset($request['charset']['*']))
        $qc = (float)$request['charset']['*']['q'];
      else
        $qc = 0.0;
      
      //calculate ql
      if (!(isset($request['language'])))
        $ql = 1.0;
      elseif (!$language_variant_specified || !count($request['language']))
        $ql = 1.0;
      elseif (!isset($variant['language']))
        $ql = 0.5;
      elseif (array_key_exists($variant['language'], $request['language']))
        $ql = (float)$request['language'][$variant['language']]['q'];
      elseif (array_key_exists(substr($variant['language'], 0, 2), $request['language']))
        $ql = (float)$request['language'][substr($variant['language'], 0, 2)]['q'];
      elseif (isset($request['language']['*']))
        $ql = (float)$request['language']['*']['q'];
      else
        $ql = 0.001;
      
      //calculate q & mxb
      $mxb = null;

      // ---------

      // hack by Brian below added (6) !(isset...
      // to prevent warnings on strict php setups

      // ---------
      
      if (!(isset($request['type'])))
        $q = 0.0;
      elseif (!isset($variant['type']))
        $q = 0.0;
      elseif (!count($request['type']))
        $q = 1.0;
      elseif (array_key_exists($variant['type'], $request['type'])) {
        if (!(isset($request['type'][$variant['type']]['q'])))
          $request['type'][$variant['type']]['q'] = $q;
        $q = (float)$request['type'][$variant['type']]['q'];
        if (!(isset($request['type'][$variant['type']]['mxb'])))
          $request['type'][$variant['type']]['mxb'] = $mxb;
        $mxb = $request['type'][$variant['type']]['mxb'];
      }
      elseif (array_key_exists(strtok($variant['type'], '/').'/*', $request['type'])) {
        if (!(isset($request['type'][strtok($variant['type'], '/').'/*']['q'])))
          $request['type'][strtok($variant['type'], '/').'/*']['q'] = $q;
        $q = (float)$request['type'][strtok($variant['type'], '/').'/*']['q'];
        if (!(isset($request['type'][strtok($variant['type'], '/').'/*']['mxb'])))
          $request['type'][strtok($variant['type'], '/').'/*']['mxb'] = $mxb;
        $mxb = $request['type'][strtok($variant['type'], '/').'/*']['mxb'];
      }
      elseif (array_key_exists('*/*', $request['type'])) {
        if (!(isset($request['type']['*/*']['q'])))
          $request['type']['*/*']['q'] = $q;
        $q = (float)$request['type']['*/*']['q'];
        if (!(isset($request['type']['*/*']['mxb'])))
          $request['type']['*/*']['mxb'] = $mxb;
        $mxb = $request['type']['*/*']['mxb'];
      }
      else
        $q = 0.0;
      
      //calculate bs
      $bs = $variant['size'];
      
      //calculate Q
      if ($mxb === null || $bs === null || $mxb >= $bs)
        $Q = $qs*$qe*$qc*$ql*$q;
      else
        $Q = 0.0;
      
      //keep track of the highest Q values
      $variant['Q'] = $Q;
      if (!count($results) || $variant['Q'] > $results[0]['Q'])
        $results = array($variant);
      elseif ($variant['Q'] == $results[0]['Q'])
        array_push($results, $variant);
    }
    
    //sort results (which all have same Q) by smallest filesize, ascending
    if (!function_exists('compareVariants')) {
      function compareVariants($a, $b) {
        if ($a['Q'] == $b['Q']) {
          if (isset($a['size'])) {
            if (isset($b['size'])) {
              if ($a['size'] == $b['size'])
                return 0;
              return ($a['size'] < $b['size'] ? -1 : 1);
            }
            return -1;
          }
          if (isset($b['size']))
            return 1;
          return 0;
        }
        return ($a['Q'] < $b['Q'] ? 1 : -1);
      }
    }
    mergesort($results, 'compareVariants');
    
    //return variants ordered by best choice
    return $results;
  }
}

?>