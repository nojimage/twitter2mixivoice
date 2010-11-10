<?php
require_once 'Client.php';
require_once 'HTTP' . DS . 'OAuth' . DS . 'Consumer.php';
/**
 * TW2MV_Twitter
 *
 * Twitterの処理
 *
 * PHP versions 5
 *
 * Copyright 2010, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.5
 * @author     nojimage <nojimage at gmail.com>
 * @copyright  2010 nojimage
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://php-tips.com/
 * @package    tw2mv
 * @subpackage tw2mv.libs.TW2MV
 * @since      File available since Release 2.0 beta1
 * @modifiedby nojimage <nojimage at gmail.com>
 *
 */
class TW2MV_Twitter extends TW2MV_Client
{
    /**
     *
     * @var string
     */
    static $HTTP_URI = 'http://api.twitter.com/1/';

    /**
     * HTTP_OAuth_Consumer
     *
     * @var HTTP_OAuth_Consumer
     * @since version 2.1.0
     */
    protected $oauth;

    /**
     * xAuth request url
     *
     * @var string
     * @since version 2.1.0
     */
    static $XAUTH_ACCESS_TOKEN_REQUEST_URL = 'https://api.twitter.com/oauth/access_token';

    /**
     *
     * @param TW2MV_Configure
     * @since version 2.1.0
     */
    public function __construct($config)
    {
        parent::__construct($config);

        try {

            // OAuthリクエスト
            $consumer_request = new HTTP_OAuth_Consumer_Request();
            $consumer_request->accept($this->http);

            $this->oauth = new HTTP_OAuth_Consumer($this->config->twitter_oauth_consumer_key, $this->config->twitter_oauth_consumer_secret);
            $this->oauth->accept($consumer_request);

            if (empty($this->config->twitter_oauth_access_token) || empty($this->config->twitter_oauth_access_token_secret)) {

                // Access Tokenを取得
                $this->_getAccessToken();

            }

            // トークンをセット
            $this->oauth->setToken($this->config->twitter_oauth_access_token);
            $this->oauth->setTokenSecret($this->config->twitter_oauth_access_token_secret);

        } catch (Exception $e) {

            debug($e->getMessage());

        }

    }

    /**
     * Twitterから発言を取得
     * @return array
     */
    function getMessages()
    {
        $params = array('count' => $this->config->twitter_max_status);

        // 前回の最終ID取得
        if ($this->get_last_message_id()) {
            $params['since_id'] = $this->get_last_message_id();
        }

        // 発言を取得
        $result = $this->_json_decode($this->get_request(self::$HTTP_URI . 'statuses/user_timeline/' . $this->config->twitter_username . '.json', $params));

        // Messageオブジェクトに格納
        $result = $this->_parse($result);

        if (empty($result) || !is_array($result)) {
            return false;
        }

        // 発言の最終IDを取得
        $last_message_id = $result[0]->id;

        // フィルタリング
        $result = $this->_filter($result);

        // 最終IDを保存
        $this->save_last_message_id($last_message_id);

        return $result;
    }

    /**
     * Twitterに投稿
     *
     * @param TW2MV_Message   $message
     * @return bool
     */
    function post($message)
    {

        // TW2MVのサフィックスが含まれていないチェック
        if (mb_strpos($message->message, $this->config->mixi_voice_message_suffix) !== FALSE) {
            return false;
        }

        $datas = array('status' => $message->make_message(140, $this->config->twitter_message_suffix));

        if ($this->config->core_fetch_only) {

            debug($datas);

        } else {

            // 投稿
            $result = $this->_json_decode($this->post_request(self::$HTTP_URI . 'statuses/update.json', $datas, true));

        }

        if (!empty($result->error)) {

            debug($result->error);

        }

        return empty($result->error);
    }

    /**
     * ダイレクトメッセージを投稿
     *
     * @param TW2MV_Message   $message
     * @param string $to
     * @return bool
     */
    function direct_message($message, $to)
    {
        $datas = array('user' => $to, 'text' => $message->make_message(140, $this->config->twitter_message_suffix));

        // 投稿
        if ($this->config->core_fetch_only) {

            debug($datas);

        } else {

            $result = $this->_json_decode($this->post_request(self::$HTTP_URI . 'direct_messages/new.json', $datas, true));

        }

        return empty($result->error);
    }

    /**
     * Twitterの最終発言IDを取得
     * @return string
     */
    function get_last_message_id()
    {
        $stat_file = $this->config->get_stat_dir() . 'twitter.dat';

        if (!is_file($stat_file)) {
            return '';
        }

        return file_get_contents($stat_file);
    }

    /**
     * Twitterの最終発言IDを保存
     *
     * @param int $id
     */
    function save_last_message_id($id)
    {
        $stat_file = $this->config->get_stat_dir() . 'twitter.dat';

        if ($fh = fopen($stat_file, 'w')) {
            fwrite($fh, $id);
            fclose($fh);
        }

    }

    /**
     * TwitterのStatus戻り値をTW2MV_Messageに格納
     *
     * @param array $datas
     * @return array
     */
    protected function _parse($datas)
    {
        $messages = array();

        if (empty($datas) || !is_array($datas)) {
            return $messages;
        }

        require_once 'Message.php';

        foreach ($datas as $data)
        {
            $msg = new TW2MV_Message();
            $msg->id = $data->id_str;
            $msg->message = $data->text;
            $msg->from = $data->user->name;
            $msg->from_id = $data->user->id_str;
            $msg->to = $data->in_reply_to_screen_name;
            $msg->to_id = $data->in_reply_to_user_id;
            $msg->source = $data->source;

            $messages[] = $msg;
        }

        return $messages;
    }

    /**
     * メッセージをフィルタリング
     *
     * @param array $messages
     * @return array
     */
    protected function _filter($messages)
    {
        $passed = array();

        foreach ($messages as $message)
        {
            // 返信を省く
            if ($this->config->twitter_exclude_reply) {

                if ( (!$this->config->twitter_strict_replay_match && !empty($message->to) )
                || ($this->config->twitter_strict_replay_match && preg_match('/^@/u', $message->message)))  {

                    continue;

                }

            }

            // tw2mvから送信されたメッセージを省く
            if ($this->config->twitter_exclude_tw2mv && !empty($message->source) && preg_match('/tw2mv/', $message->source)) {

                continue;

            }

            // フィルタリング
            if ($this->_post_filter($message->message, $this->config->twitter_filter_denys, $this->config->twitter_filter_allows)) {

                $passed[] = $message;

            }

        }

        return $passed;
    }

    /**
     * OAuth Access Tokenの取得
     *
     * @since version 2.1.0
     */
    protected function _getAccessToken()
    {
        $params = array(
            'x_auth_mode' => 'client_auth',
            'x_auth_username' => $this->config->twitter_username, 
            'x_auth_password' => TW2MV_Configure::decrypt($this->config->twitter_password));
         
        $response = $this->oauth->sendRequest(self::$XAUTH_ACCESS_TOKEN_REQUEST_URL, $params, HTTP_Request2::METHOD_POST);

        if ($response->getStatus() !== 200) {
            throw new Exception($response->getBody(), $response->getStatus());
        }

        // レスポンスデータを解析
        $access_token_info = array();
        parse_str($response->getBody(), $access_token_info);

        // 設定ファイルへ保存
        $this->config->saveTwitterAccessToken($access_token_info['oauth_token'], $access_token_info['oauth_token_secret']);
    }

    /**
     * POST Request
     *
     * @param $url
     * @param $datas
     * @return string
     * @since version 2.1.0
     */
    public function post_request($url, $datas = array())
    {

        $body = '';

        try {

            $response = $this->oauth->sendRequest($url, $datas, HTTP_Request2::METHOD_POST);
            $body = $response->getBody();

        } catch (Exception $e) {

            debug($e->getMessage());

        }

        return $body;
    }
}