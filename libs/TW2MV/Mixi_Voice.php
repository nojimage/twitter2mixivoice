<?php
require_once 'Mixi.php';
/**
 * TW2MV_Mixi_Voice
 *
 * mixiボイスの処理
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
class TW2MV_Mixi_Voice extends TW2MV_Mixi
{
    /**
     * mixiボイス投稿用キー
     *
     * @var string
     */
    public $post_key;

    /**
     * mixiのユーザID
     * @var string
     */
    public $mixi_user_id;

    /**
     * mixiボイスから発言を取得
     * @return array
     */
    function getMessages()
    {
        if (empty($this->cookies)) {
            // ログイン
            $this->login($this->config->mixi_email, $this->config->mixi_password);
        }

        $result = array();

        // 前回の最終ID取得
        $last_message_id = $this->get_last_message_id('my_voice');

        // ページを解析
        $page = $this->get_request(self::$HTTP_URI . 'list_echo.pl');
        $result = $this->_parse($page, $last_message_id);

        if (empty($result) || !is_array($result)) {
            return false;
        }

        // 発言の最終IDを取得
        $last_message_id = $result[0]->id;

        // フィルタリング
        $result = $this->_filter($result);

        $this->save_last_message_id($last_message_id, 'my_voice');

        return $result;
    }

    /**
     * mixiボイスから返信を取得
     * @return array
     */
    function getReplyMessages()
    {
        if (empty($this->cookies)) {
            // ログイン
            $this->login($this->config->mixi_email, $this->config->mixi_password);
        }

        $result = array();

        // 前回の最終ID取得
        $last_message_id = $this->get_last_message_id('reply');

        // ページを解析
        $page = $this->get_request(self::$HTTP_URI . 'res_echo.pl');
        $result = $this->_parse($page, $last_message_id);

        if (empty($result) || !is_array($result)) {
            return false;
        }

        // 発言の最終IDを取得
        $last_message_id = $result[0]->id;

        // 自分自身のIDを取得
        $this->_parse_mixi_id($page);

        // フィルタリング
        $result = $this->_filter_reply($result);

        $this->save_last_message_id($last_message_id, 'reply');

        return $result;
    }

    /**
     * mixiボイスに投稿
     *
     * @param TW2MV_Message   $message
     * @return bool
     */
    function post($message)
    {
        // MV2TWのサフィックスが含まれていないチェック
        if (mb_strpos($message->message, $this->config->twitter_message_suffix) !== FALSE) {
            return false;
        }

        // ポストキーの取得
        if (empty($this->post_key)) {
            $this->get_post_key();
        }

        // メッセージの作成
        $datas = array('body' => $message->make_message(150, $this->config->mixi_voice_message_suffix),
            'post_key' => $this->post_key, 'redirect' => 'recent_echo');

        if ($this->config->core_fetch_only) {
            debug($datas);
        } else {
            $pages = $this->post_request(self::$HTTP_URI . 'add_echo.pl', $datas);
        }

        // wait
        sleep($this->config->mixi_voice_post_interval);

    }

    /**
     * 投稿キーの取得
     *
     * @return string
     */
    public function get_post_key()
    {
        if (empty($this->cookies)) {
            // ログイン
            $this->login($this->config->mixi_email, $this->config->mixi_password);
        }

        // ページデータを取得
        $page = $this->get_request(self::$HTTP_URI . 'recent_echo.pl');

        if (preg_match('!<input.*?(?: name="post_key").*? value="(.*?)".*?/>|<input.*? value="(.*?)".*?(?: name="post_key").*?/>!u', $page, $matches)) {
            $this->post_key = $matches[1];
        }

        return $this->post_key;
    }

    /**
     * mixiボイスの各ページを解析してメッセージを取得
     * @param string $page
     * @param string $last_id
     * @return array
     */
    protected function _parse($page, $last_id)
    {
        require_once 'Message.php';

        $result = array();
        if (preg_match_all('!<td class="comment">(.*)<span>.*post_time=([0-9]+)!', $page, $matches, PREG_SET_ORDER)) {

            for ($i = 0; $i < count($matches); $i++)
            {
                if ($matches[$i][2] <= $last_id) {
                    // post_timeが前回取得より古い
                    continue;
                }

                $to_id = null;
                $to    = null;
                $msg   = $matches[$i][1];
                if ( preg_match('!^<a href="view_echo.pl\?from=nickname&id=([\d]+)&.*?&gt;&gt;(.*?)</a>(.*)$!', $msg, $idmatch) ) {
                    // 発言先
                    $to_id = $idmatch[1];
                    $to    = $idmatch[2];
                    $msg   = $idmatch[3];
                }
                $message = new TW2MV_Message();
                $message->id = $matches[$i][2];
                $message->message = strip_tags($msg);
                $message->to_id = $to_id;
                $message->to = $to;
                $result[] = $message;
            }

        }

        // 発言元の取得
        if (preg_match_all('!<td class="nickname"><a href="list_echo.pl\?id=([\d]+)">(.*?)</a>!s', $page, $matches, PREG_SET_ORDER)) {
            for ($i = 0; $i < count($matches); $i++)
            {
                if ($matches[$i][2] <= $last_id) {
                    // post_timeが前回取得より古い
                    continue;
                }

                $result[$i]->from_id = $matches[$i][1];
                $result[$i]->from    = trim($matches[$i][2]);
            }
        }

        return $result;
    }

    /**
     * 自分のIDを取得する
     *
     * @param $page
     * @return string
     */
    protected function _parse_mixi_id($page)
    {
        if (preg_match('!http://mixi.jp/add_diary.pl\?id=([\d]+)!', $page, $matches)) {
            $this->mixi_user_id = $matches[1];
        }
        return $this->mixi_user_id;
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
            if ($this->config->mixi_voice_exclude_reply && !empty($message->to)) {
                continue;
            }

            // フィルタリング
            if ($this->_post_filter($message->message, $this->config->mixi_voice_filter_denys, $this->config->mixi_voice_filter_allows)) {
                $passed[] = $message;
            }

        }

        return $passed;
    }

    /**
     * メッセージをフィルタリング
     *
     * @param array $messages
     * @return array
     */
    protected function _filter_reply($messages)
    {
        $passed = array();

        foreach ($messages as $message)
        {

            if (empty($message->to_id) || $message->to_id != $this->mixi_user_id) {
                // 自分への返信ではない場合除外
                continue;
            }

            $message->message = $message->from . ': ' . $message->message;

            $passed[] = $message;

        }

        return $passed;
    }

    /**
     * 最終更新時間の取得
     * @param string $type
     * @return string last post_time
     */
    public function get_last_message_id($type = 'my_voice')
    {
        $stat_file = $this->config->get_stat_dir() . 'mixi_' . $type . '.dat';
        if (!is_file($stat_file)) {
            return '';
        }
        return file_get_contents($stat_file);
    }

    /**
     * Twitterの最終発言IDを保存
     *
     * @param int $id
     * @param string $type
     */
    function save_last_message_id($id, $type = 'my_voice')
    {
        $stat_file = $this->config->get_stat_dir() . 'mixi_' . $type . '.dat';

        if (!empty($id) && $fh = fopen($stat_file, 'w')) {
            fwrite($fh, $id);
            fclose($fh);
        }

    }
}