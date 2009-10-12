<?php
require_once 'HTTP' . DS . 'Request.php';
/**
 * TW2MV_Client
 *
 * PHP versions 5
 *
 * Copyright 2009, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.0
 * @author     nojimage <nojimage at gmail.com>
 * @copyright  2009 nojimage
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://php-tips.com/
 * @package    tw2mv
 * @subpackage tw2mv.libs.TW2MV
 * @since      File available since Release 2.0 beta1
 * @modifiedby nojimage <nojimage at gmail.com>
 *
 */
class TW2MV_Client
{
    static $HTTP_URI = '';

    static $HTTPS_URI = '';

    /**
     * @var string
     */
    protected $response_encoding = 'UTF-8';

    /**
     * HTTP_Request
     * @var HTTP_Request
     */
    protected $http;

    /**
     * TW2MV_Configure
     * @var TW2MV_Configure
     */
    public $config;

    /**
     * Cookies
     * @var array
     */
    protected $cookies = array();

    /**
     *
     * @param TW2MV_Configure
     */
    public function __construct($config)
    {
        $this->http = new HTTP_Request();
        $this->config = $config;
    }

    /**
     * POST Request
     *
     * @param $url
     * @param $datas
     * @param $saveBody
     * @return string
     */
    public function post_request($url, $datas = array(), $saveBody = true)
    {
        $this->http->setURL($url);
        $this->http->setMethod(HTTP_REQUEST_METHOD_POST);
        foreach ($datas as $key => $val) {
            $this->http->addPostData($key, mb_convert_encoding($val, $this->response_encoding, 'UTF-8'));
        }
        if (!empty($this->cookies)) {
            foreach ($this->cookies as $cookie) {
                $this->http->addCookie($cookie['name'], $cookie['value']);
            }
        }
        $this->http->sendRequest($saveBody);
        if (count($this->http->getResponseCookies())) {
            $this->cookies = $this->http->getResponseCookies();
        }

        return mb_convert_encoding($this->http->getResponseBody(), 'UTF-8', $this->response_encoding);
    }

    /**
     * GET Request
     *
     * @param $url
     * @param $datas
     * @param $saveBody
     * @return string
     */
    public function get_request($url, $datas = array(), $saveBody = true)
    {
        $this->http->setURL($url);
        $this->http->setMethod(HTTP_REQUEST_METHOD_GET);
        foreach ($datas as $key => $val) {
            $this->http->addQueryString($key, mb_convert_encoding($val, $this->response_encoding, 'UTF-8'), true);
        }
        if (!empty($this->cookies)) {
            foreach ($this->cookies as $cookie) {
                $this->http->addCookie($cookie['name'], $cookie['value']);
            }
        }
        $this->http->sendRequest($saveBody);
        if (count($this->http->getResponseCookies())) {
            $this->cookies = $this->http->getResponseCookies();
        }

        return mb_convert_encoding($this->http->getResponseBody(), 'UTF-8', $this->response_encoding);
    }

    /**
     * json decode
     *
     * @param $value
     * @return array
     */
    protected function _json_decode($value)
    {
        if (function_exists('json_decode')) {
            return json_decode($value);
        }

        // use pear library
        require_once 'Services' . DS . 'JSON.php';
        $json = new Services_JSON();
        return $json->decode($value);
    }

    /**
     *
     * @param string $message
     * @param array $denys
     * @param array $allows
     * @return bool
     */
    protected function _post_filter($message, $denys = null, $allows = null)
    {
        // 否定フィルター
        if (!empty($denys)) {
            foreach ($denys as $filter)
            {
                if (mb_strpos($message, $filter) !== FALSE) {
                    // 文字列が存在する場合
                    return false;
                }
            }
        }

        // 肯定フィルター
        if (!empty($allows)) {
            $is_allow = false;
            foreach ($allows as $filter)
            {
                if (mb_strpos($message, $filter) !== FALSE) {
                    // 文字列が存在する場合
                    $is_allow = true;
                    break;
                }
            }
            if (!$is_allow) {
                return false;
            }
        }
        
        return true;
    }
}