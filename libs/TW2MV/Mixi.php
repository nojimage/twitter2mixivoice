<?php
require_once 'Client.php';
/**
 * TW2MV_Mixi
 *
 * mixiの処理
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
class TW2MV_Mixi extends TW2MV_Client
{
    /**
     *
     * @var string
     */
    static $HTTP_URI = 'http://mixi.jp/';

    /**
     *
     * @var string
     */
    static $HTTPS_URI = 'https://mixi.jp/';

    protected $response_encoding = 'eucjp-win';

    /**
     * Login to mixi
     *
     * @param $email
     * @param $password
     * @return unknown_type
     */
    public function login($email, $password)
    {
        $next_url = '/home.pl';
        $datas = compact('email', 'password', 'next_url');
        $this->post_request(self::$HTTP_URI . 'login.pl', $datas); // FIXME: to https
    }

}
