<?php
/**
 * mixiボイス to Twitter
 *
 * PHP versions 5
 *
 * Copyright 2009, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * @filesource
 * @version 　0.2
 * @author    nojimage <nojimage at gmail.com>
 * @copyright 2009 nojimage
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link 　   http://php-tips.com/php/2009/10/mixivoice2twitter
 * @since  　 File available since Release 0.1
 *
 * = 機能
 * * mixiボイスで発言した内容をtwitterに投稿します。
 * * mixiボイスの返信をtwitterにダイレクトメッセージで送ります。
 *
 * = 使い方
 * account.mixi.txtに、mixiのログイン情報、twitterのログイン情報を記入します。
 * 初回時は過去25件のデータを取得し、twitterに投稿します。
 * 次回以降は、mixiボイスの最終投稿時間を記憶しているので、そこからの発言を処理します。
 * {mixi_email}.datというファイルに、最終取得分のステータスIDが記入されています。
 *
 * = 注意事項
 * accounts.mixi.txtをWeb公開ディレクトリ等、他者から見れる場所に設置しないでください。
 * 
 * twitter2mixivoice(tw2mv)と併用する場合は、mixi側に再投稿しないよう、
 * MV2TW_Twitter::$comment_suffixにあるハッシュタグをtw2mvの除外ハッシュタグとして
 * 指定しておいてください。（設定のない場合、投稿がループする可能性があります。）
 *
 */
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
mb_internal_encoding('UTF-8');

/**
 * アカウントファイルのフルパス
 *
 * [注意] Webで公開されている場所にはおかないでください。
 * デフォルトは、このファイルと同階層の「accounts.mixi.txt」というファイルです。
 * @var string
 */
define('ACCOUNTS_FILE', dirname(__FILE__) . DS . 'accounts.mixi.txt');

/**
 * ステータスIDファイル格納ディレクトリ
 *
 * デフォルトは、このファイルと同階層にステータスIDファイルを作成していきます。
 * @var string
 */
define('STAT_DIR', dirname(__FILE__) . DS );

/**
 * Twitter2Mixivoiceで投稿したときの末尾文字
 * 
 * TW2MV_Twitter::$comment_suffixを変更している方は
 * 合わせて変更してください。
 * （発言フィルタリングで除外するために使用。）
 *
 * @var string
 */
define('TW2MV_SUFFIX', '[tw2mv]');

/**
 * 初回起動用フラグ
 * trueにしておくと、最終取得日の更新のみでtwitterへの投稿を行いません。
 *
 * @var bool
 */
define('ONLY_FETCH', false);

// pear include
set_include_path(dirname(__FILE__) . DS . 'pear' . PS . get_include_path());
require_once('HTTP/Request.php');

// 処理開始
$mv2tw = new MV2TW(ACCOUNTS_FILE);
$mv2tw->start();
exit(0);


// == Classes =============================================
/**
 *
 *
 * @author  nojimage
 * @package mv2tw
 *
 */
class MV2TW
{
    public $account_file = './accounts.mixi.txt';

    public $accounts = array();

    public function __construct($account_file = null)
    {
        if (is_file($account_file)) {
            $this->load($account_file);
        }
    }

    /**
     * アカウントファイルの解析
     *
     * @param $account_file
     * @return array
     */
    public function load($account_file = null)
    {
        if (is_file($account_file)) {
            $this->account_file = $account_file;
        }

        $lines = file($this->account_file);
        $this->accounts = array();

        foreach ($lines as $line)
        {
            $line = trim($line);
            if (preg_match('/^#/', $line)) {
                continue;
            }

            // アカウントデータの取得
            $account = preg_split('/[\s]+/', $line);
            if (count($account) >= 4) {
                $this->accounts[] = array(
                    'mixi_email' => $account[0], 'mixi_password' => $account[1],
                    'twitter_username' => $account[2], 'twitter_password' => $account[3],
                    'filter' => array_slice($account, 4));
            }
        }

        return $this->accounts;
    }

    /**
     * 投稿処理の開始
     *
     */
    public function start()
    {
        foreach ($this->accounts as $account)
        {
            $mixi = new MV2TW_Mixi($account['mixi_email'], $account['mixi_password'], STAT_DIR);

            $my_voices = $mixi->get_my_voice();
            $replays   = $mixi->get_replay();
             
            if ((!empty($my_voices) || !empty($replays)) && !ONLY_FETCH) {

                $twitter = new MV2TW_Twitter($account['twitter_username'], $account['twitter_password']);

                // 通常ポスト
                foreach ($my_voices as $message) {
                    if ($this->filter($account['filter'], $message)) {
                        $twitter->post($message);
                    }
                }

                // ダイレクトメッセージ
                foreach ($replays as $message) {
                    if ($this->filter($account['filter'], $message)) {
                        $twitter->dm($account['twitter_username'], $message);
                    }
                }
            }

        }
    }

    /**
     * メッセージがフィルター条件に合致するかチェックします
     *
     * @param $filters
     * @param $message
     * @return boolean
     */
    public function filter($filters, $message)
    {
        $denys  = array(TW2MV_SUFFIX);
        $allows = array();

        foreach ($filters as $filter)
        {
            if (preg_match('/!#.+/u', $filter)) {
                // 否定条件
                $denys[] = substr($filter, 1);
            } else {
                // 肯定条件
                $allows[] = $filter;
            }
        }

        // 否定条件の評価
        foreach ($denys as $filter)
        {
            if (mb_strpos($message, $filter) !== FALSE) {
                // 文字列が存在する場合
                return false;
            }
        }

        if (empty($allows)) {
            // 肯定フィルターがなければ
            return true;
        }

        $result = false;
        foreach ($allows as $filter) {
            if (mb_strpos($message, $filter) !== FALSE) {
                // 文字列が存在する場合
                $result = true;
                break;
            }
        }

        return $result;
    }
}

/**
 *
 *
 * @author  nojimage
 * @package tw2mv
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
     * Cookies
     * @var array
     */
    protected $cookies = array();

    /**
     *
     */
    public function __construct()
    {
        $this->http = new HTTP_Request();
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
}

/**
 *
 *
 * @author  nojimage
 * @package mv2tw
 *
 */
class MV2TW_Mixi extends TW2MV_Client
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

    /**
     * ステータスファイルの格納先
     *
     * @var string
     */
    private $stat_file_dir = './';

    protected $response_encoding = 'eucjp-win';

    public $voice_post_key = '';

    /**
     * メールアドレス
     * @var string
     */
    public $email = '';

    /**
     *
     * @param $email
     * @param $password
     */
    public function __construct($email = null, $password = null, $stat_file_dir = null)
    {
        parent::__construct();

        if (!empty($stat_file_dir)) {
            $this->stat_file_dir = $stat_file_dir;
        }


        if (!empty($email) && !empty($password)) {
            $this->login($email, $password);
        }
    }

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

        $this->email = $email;
    }

    /**
     * 自分の発言の取得
     * @return array
     */
    public function get_my_voice()
    {
        $result = array();

        // ページを解析
        $page = $this->get_request(self::$HTTP_URI . 'list_echo.pl');
        $messages = $this->parse_voice_page($page);
        if (empty($messages)) {
            return $result;
        }
        $myid = $this->parse_myid($page);


        // 最終取得時間を取得
        $last_post_time = $this->get_last_post_time('my_voice');

        foreach ($messages as $msg) {
            if (!empty($msg['to_id'])) {
                // 誰かへの返信の場合除外
                continue;
            }

            if ($msg['post_time'] <= $last_post_time) {
                // 最終取得より過去
                continue;
            }

            $result[] = $msg['message'];
        }

        // 最終取得時刻を書き込み
        $this->save_last_post_time($messages[0]['post_time'], 'my_voice');

        return array_reverse($result); // 古いものから順に並べる
    }

    /**
     * 自分への返信の取得
     * @return array
     */
    public function get_replay()
    {
        $result = array();

        // ページを解析
        $page = $this->get_request(self::$HTTP_URI . 'res_echo.pl');
        $messages = $this->parse_voice_page($page);
        if (empty($messages)) {
            return $result;
        }
        $myid = $this->parse_myid($page);

        // 最終取得時間を取得
        $last_post_time = $this->get_last_post_time('replay');

        foreach ($messages as $msg) {
            if (empty($msg['to_id']) || $msg['to_id'] != $myid) {
                // 自分への返信ではない場合除外
                continue;
            }

            if ($msg['post_time'] <= $last_post_time) {
                // 最終取得より過去
                continue;
            }

            $result[] =  $msg['from'] . ': ' . $msg['message'];
        }

        // 最終取得時刻を書き込み
        $this->save_last_post_time($messages[0]['post_time'], 'replay');

        return array_reverse($result); // 古いものから順に並べる
    }
    /**
     * 投稿キーの取得
     *
     * @param $page string
     */
    public function parse_post_key($page)
    {
        if (preg_match('!<input.*?(?: name="post_key").*? value="(.*?)".*?/>|<input.*? value="(.*?)".*?(?: name="post_key").*?/>!u', $page, $matches)) {
            $this->voice_post_key = $matches[1];
        }
    }

    /**
     * 自分のIDを取得する
     *
     * @param $page
     * @return string
     */
    public function parse_myid($page)
    {
        if (preg_match('!http://mixi.jp/add_diary.pl\?id=([\d]+)!', $page, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * mixiボイスの各ページを解析してメッセージを取得
     * @param $page string
     * @return array
     */
    public function parse_voice_page($page)
    {
        $result = array();
        if (preg_match_all('!<td class="comment">(.*)<span>.*post_time=([0-9]+)!', $page, $matches, PREG_SET_ORDER)) {
            for ($i = 0; $i < count($matches); $i++)
            {
                $to_id = null;
                $to    = null;
                $msg   = $matches[$i][1];
                if ( preg_match('!^<a href="view_echo.pl\?from=nickname&id=([\d]+)&.*?&gt;&gt;(.*?)</a>(.*)$!', $msg, $idmatch) ) {
                    // 発言先
                    $to_id = $idmatch[1];
                    $to   = $idmatch[2];
                    $msg  = $idmatch[3];
                }
                $message = array('to_id' => $to_id, 'to' => $to, 'message' => strip_tags($msg), 'post_time' => $matches[$i][2]);
                $result[] = $message;
            }
        }

        // 発言元の取得
        if (preg_match_all('!<td class="nickname"><a href="list_echo.pl\?id=([\d]+)">(.*?)</a>!s', $page, $matches, PREG_SET_ORDER)) {
            for ($i = 0; $i < count($matches); $i++)
            {
                $result[$i]['from_id'] = $matches[$i][1];
                $result[$i]['from']   = trim($matches[$i][2]);
            }
        }

        return $result;
    }

    /**
     * 最終取得時間格納ファイル名の取得
     *
     * @param string $type
     * @param string $email
     * @return string
     */
    public function get_stat_filename($type = 'my_voice', $email = null)
    {
        if (empty($email)) {
            $email = $this->email;
        }
        $this->stat_file_dir = preg_replace('!/+$!', '', $this->stat_file_dir);
        $email = str_replace(array('@', '.', '+', '%'), array('_at_', '_', '_', '_'), $email);
        return $this->stat_file_dir . DS . $email . '.' . $type . '.dat';
    }

    /**
     * 最終更新時間の取得
     * @param string $type
     * @param string $email
     * @return string last posttime
     */
    public function get_last_post_time($type = 'my_voice', $email = null)
    {
        $filepath = $this->get_stat_filename($type, $email);
        if (is_file($filepath)) {
            return file_get_contents($filepath);
        }
        return null;
    }

    /**
     * 最終更新時間の保存
     * @param string $value
     * @param string $type
     * @param string $email
     * @return bool
     */
    public function save_last_post_time($value, $type = 'my_voice', $email = null)
    {
        $filepath = $this->get_stat_filename($type, $email);
        if ($fh = fopen($filepath, 'w')) {
            fwrite($fh, $value);
            fclose($fh);
        }
        return is_file($filepath);
    }
}

/**
 *
 *
 * @author  nojimage
 * @package tw2mv
 *
 */
class MV2TW_Twitter extends TW2MV_Client
{
    /**
     *
     * @var string
     */
    static $HTTP_URI = 'http://twitter.com/';

    /**
     *
     * @var string
     */
    protected $username = '';

    /**
     *
     * @var string
     */
    protected $password = '';

    /**
     * 発言の末尾に付加するメッセージ
     *
     * @var string
     */
    static $comment_suffix = ' #mv2tw';

    /**
     *
     * @var string
     */
    public $status = array();

    /**
     *
     * @param $username
     * @param $password
     * @param $stat_file_dir
     * @return unknown_type
     */
    public function __construct($username, $password = null, $stat_file_dir = null)
    {
        parent::__construct();

        if (!empty($username)) {
            $this->username = $username;
        }

        if (!empty($password)) {
            $this->password = $password;
        }
    }

    /**
     * Twitterへポスト
     * @param $message
     * @return bool
     */
    public function post($message)
    {
        // 140字に丸める
        $message = $this->make_message($message);
        
        $datas = array('status' => $message);

        $this->http->setBasicAuth($this->username, $this->password);
        $result = $this->_json_decode($this->post_request(self::$HTTP_URI . 'statuses/update.json', $datas, true));
        
        return empty($result->error);
    }

    /**
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function dm($to, $message)
    {
        // 140字に丸める
        $message = $this->make_message($message);
        
        $datas = array('user' => $to, 'text' => $message);
        
        $this->http->setBasicAuth($this->username, $this->password);
        $result = $this->_json_decode($this->post_request(self::$HTTP_URI . 'direct_messages/new.json', $datas, true));
        
        return empty($result->error);
        
    }

    /**
     * 140字に丸める
     * 
     * @param string $message
     * @return string
     */
    public function make_message($message)
    {
        $max_length = 140 - mb_strlen(self::$comment_suffix);
        
        if (mb_strlen($message) > $max_length) {
            // 140字を超える場合
            $message = preg_replace('!^(.{' . ($max_length - mb_strlen('...')) . '})(.*)$!u', '$1...', $message);
        }

        $message .= self::$comment_suffix;
        
        return $message;
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

}