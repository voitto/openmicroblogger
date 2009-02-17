<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Search A9 OpenSearch compatible engines.
 * This is porting Perl modules WWW::OpenSearch.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Web Services
 * @package    Services_OpenSearch
 * @author     HIROSE Masaaki <hirose31@irori.org>
 * @copyright  2005 HIROSE Masaaki
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: OpenSearch.php,v 1.19 2006/01/29 13:47:32 hirose31 Exp $
 * @link       http://pear.php.net/package/Services_OpenSearch/
 */

require_once 'PEAR.php';
require_once 'HTTP/Request.php';
require_once 'XML/Unserializer.php';
require_once 'XML/RSS.php';

define('SERVICES_OPENSEARCH_VERSION',    '0.1.0');
define('SERVICES_OPENSEARCH_USER_AGENT', 'Services_OpenSearch/'.SERVICES_OPENSEARCH_VERSION);
// for fopen
ini_set('user_agent', SERVICES_OPENSEARCH_USER_AGENT);

/**
 * Class for accessing and retrieving information from OpenSearch 
 * compatible engines.
 *
 * @category   Web Services
 * @package Services_OpenSearch
 * @author  HIROSE Masaaki <hirose31@irori.org>
 * @uses    PEAR
 * @uses    HTTP_Request
 * @uses    XML_Unserializer
 * @uses    XML_RSS
 */
class Services_OpenSearch {
    /**
     * URI of OpenSearch Description Document.
     *
     * @access private
     * @var    string
     */
    var $_descriptionUrl;

    /**
     * Element names in OpenSearch Description Document.
     *
     * @access private
     * @var    array
     */
    var $_cols = array(
        'Url',
        'Format',
        'ShortName',
        'LongName',
        'Description',
        'Tags',
        'Image',
        'SampleSearch',
        'Developer',
        'Contact',
        'SyndicationRight',
        'AdultContent',
        );

    /**
     * Element values in OpenSearch Description Document.
     *
     * @access private
     * @var    array
     */
    var $_desc;

    /**
     * User-Agent for HTTP access.
     *
     * @access private
     * @var    string
     */
    var $_userAgent;

    /**
     * Constructor
     *
     * @access public
     * @param  string $url URI of OpenSearch Description Document
     * @param  array  $pager_param
     */
    function Services_OpenSearch($url = null, $pager_param = array()) {
        if (! is_null($url)) {
            $this->_descriptionUrl = $url;
        }
        if (! empty($pager_param)) {
            $this->pager_param = $pager_param;
        }

        $this->_desc = array();
        $this->_pager_default = array(
            'count'        => 10,
            'startIndex'   => 1,
            'startPage'    => 1,
            'totalResults' => -1,
            'itemsPerPage' => -1,
            );
        foreach ($this->_pager_default as $key => $default) {
            if (! isset($this->pager_param[$key])) {
                $this->pager_param[$key] = $default;
            }
        }
        $this->_userAgent = SERVICES_OPENSEARCH_USER_AGENT;
    }

    /**
     * Retrieves the version number of this class.
     *
     * @access public
     * @return string
     */
    function getVersion() {
        return SERVICES_OPENSEARCH_VERSION;
    }

    /**
     * Retrieves the User-Agent name.
     *
     * @access public
     * @return string
     */
    function getUserAgent() {
        return $this->_userAgent;
    }

    /**
     * Sets the User-Agent name.
     *
     * @access public
     * @return string
     */
    function setUserAgent($ua = null) {
        if (is_null($ua)) {
            $this->_userAgent = SERVICES_OPENSEARCH_USER_AGENT;
        } else {
            $this->_userAgent = $ua;
        }
    }

    /**
     * Retrieves the currently set URI of OpenSearch Description Document.
     *
     * @access public
     * @return string URI of OpenSearch Description Document
     */
    function getDescriptionUrl() {
        return $this->_descriptionUrl;
    }

    /**
     * Sets the URI of OpenSearch Description Document.
     *
     * @access public
     * @param  string $url URI of OpenSearch Description Document
     */
    function setDescriptionUrl($url) {
        if ($this->_descriptionUrl != $url) {
            $this->_desc = array();
        }
        $this->_descriptionUrl = $url;
    }

    /**
     * Retrieves the currently set entry count of per page.
     *
     * @access public
     * @return integer
     */
    function getCount() {
        return $this->pager_param['count'];
    }

    /**
     * Sets the entry count of per page.
     *
     * @access public
     * @param  integer $n entry count of per page. positive (non-zero) integer.
     */
    function setCount($n) {
        $this->pager_param['count'] = $n;
    }

    /**
     * Retrieves the currently set start index.
     *
     * @access public
     * @return integer
     */
    function getStartIndex() {
        return $this->pager_param['startIndex'];
    }

    /**
     * Sets the start index.
     *
     * @access public
     * @param  integer $n entry count of per page. positive (non-zero) integer.
     */
    function setStartIndex($n) {
        $this->pager_param['startIndex'] = $n;
    }

    /**
     * Retrieves the currently set start page.
     *
     * @access public
     * @return integer
     */
    function getStartPage() {
        return $this->pager_param['startPage'];
    }

    /**
     * Sets the start page.
     *
     * @access public
     * @param  integer $n entry count of per page. positive (non-zero) integer.
     */
    function setStartPage($n) {
        $this->pager_param['startPage'] = $n;
    }

    /**
     * Retrieves the total number of results.
     *
     * @access public
     * @return integer
     */
    function getTotalResults() {
        return $this->pager_param['totalResults'];
    }

    /**
     * Retrieves the number of items per page.
     *
     * @access public
     * @return integer
     */
    function getItemsPerPage() {
        return $this->pager_param['itemsPerPage'];
    }

    /**
     * Retrieves element value of OpenSearch Description Document.
     * If need, fetch and set element value.
     *
     * @access private
     * @param  string $name 
     * @return string
     */
    function _getDescription($name) {
        if (! isset($this->_descriptionUrl)) {
            return PEAR::raiseError("missing description URL");
        }
        if (! isset($this->_desc[$name])) {
            $ret = $this->_fetchDescription($this->_descriptionUrl);
            if (is_null($ret) || PEAR::isError($ret)) {
                return null;
            }
        }
        return isset($this->_desc[$name]) ? $this->_desc[$name] : null;
    }

    /**
     * Retrieves Url element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getUrl() {
        return $this->_getDescription('Url');
    }

    /**
     * Retrieves Format element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getFormat() {
        return $this->_getDescription('Format');
    }

    /**
     * Retrieves ShortName element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getShortName() {
        return $this->_getDescription('ShortName');
    }

    /**
     * Retrieves LongName element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getLongName() {
        return $this->_getDescription('LongName');
    }

    /**
     * Retrieves Description element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getDescription() {
        return $this->_getDescription('Description');
    }

    /**
     * Retrieves Tags element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getTags() {
        return $this->_getDescription('Tags');
    }

    /**
     * Retrieves getImage element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getImage() {
        return $this->_getDescription('Image');
    }

    /**
     * Retrieves SampleSearch element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getSampleSearch() {
        return $this->_getDescription('SampleSearch');
    }

    /**
     * Retrieves Developer element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getDeveloper() {
        return $this->_getDescription('Developer');
    }

    /**
     * Retrieves Contact element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getContact() {
        return $this->_getDescription('Contact');
    }

    /**
     * Retrieves SyndicationRight element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getSyndicationRight() {
        return $this->_getDescription('SyndicationRight');
    }

    /**
     * Retrieves AdultContent element value in OpenSearch Description Document.
     *
     * @access public
     * @return string 
     */
    function getAdultContent() {
        return $this->_getDescription('AdultContent');
    }

    /**
     * Fetch OpenSearch Description Document.
     *
     * @access private
     * @param  string $url URI of OpenSearch Description Document
     */
    function _fetchDescription($url) {
        $req = new HTTP_Request($url);
        $req->addHeader('User-Agent', SERVICES_OPENSEARCH_USER_AGENT);

        if (PEAR::isError($req->sendRequest())) {
            return null;
        }
        switch ($req->getResponseCode()) {
        case 200:
            break;
        default:
            return PEAR::raiseError('OpenSearch: return HTTP ' . $req->getResponseCode());
        }

        $data = $this->_parseDescription($req->getResponseBody());
        if (PEAR::isError($data)) {
            return null;
        }

        foreach ($data as $k => $v) {
            $this->_desc[$k] = $v;
        }

        return true;
    }

    /**
     * Parse OpenSearch Description Document.
     *
     * @access private
     * @param  string $xml OpenSearch Description Document data.
     * @return array
     */
    function _parseDescription($xml) {
        $parser = new XML_Unserializer(array('complexType' => 'array'));
        $parser->unserialize($xml, false);
        $doc = $parser->getUnserializedData();
        if (isset($doc->ErrorMsg)) {
            return PEAR::raiseError($doc->ErrorMsg);
        }
        $data = array();
        foreach ($this->_cols as $c) {
            if (isset($doc[$c])) {
                $data[$c] = $doc[$c];
            } else {
                $data[$c] = null;
            }
        }
        return $data;
    }

    /**
     * Search keyword on OpenSearch compatible engine.
     *
     * @access public
     * @param  string $query keyword
     * @return array
     */
    function search($query) {
        $url = $this->_setupQuery($query);
        // trigger_error($url);
        $rss = new XML_RSS($url);
        array_push($rss->channelTags, 'OPENSEARCH:TOTALRESULTS', 'OPENSEARCH:STARTINDEX', 'OPENSEARCH:ITEMSPERPAGE');
        $rss->parse();

        $ch = $rss->getChannelInfo();
        if (isset($ch['opensearch:totalresults'])) {
            $this->pager_param['totalResults'] = $ch['opensearch:totalresults'];
        }
        if (isset($ch['opensearch:itemsperpage'])) {
            $this->pager_param['itemsPerPage'] = $ch['opensearch:itemsperpage'];
        }
        if (isset($ch['opensearch:startindex'])) {
            $this->pager_param['startIndex'] = $ch['opensearch:startindex'];
        }

        return $rss->getItems();
    }

    /**
     * Setup search URL.
     *
     * @access private
     * @param  string $query keyword
     * @return string $url search URL
     */
    function _setupQuery($query) {
        $search  = array('{searchTerms}',
                         '{count}',
                         '{startIndex}',
                         '{startPage}',
            );
        $replace = array(urlencode($query),
                         $this->pager_param['count'],
                         $this->pager_param['startIndex'],
                         $this->pager_param['startPage'],
            );
        $url     = $this->getUrl();

        $url = str_replace($search, $replace, $url);
        return $url;
    }
}

/*
 Local Variables:
 mode: php
 tab-width: 4
 c-basic-offset: 4
 c-hanging-comment-ender-p: nil
 indent-tabs-mode: nil
 End:
*/
