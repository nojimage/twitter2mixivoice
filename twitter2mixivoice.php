<?php
/**
 * $Id$
 */
/**
 * Twitter to mixiボイス
 *
 *　PHP versions 5.2
 *
 * @version 0.3b
 * @author  nojimage <nojimage at gmail.com>
 * @copyright 2009 nojimage
 * @license http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link    http://php-tips.com/php/2009/09/twitter2mixivoice
 * @since   File available since Release 0.1
 *
 * = 機能
 * * twitterの発言をmixiボイスにも投稿します。
 * * 但し、「@」で始まる発言は除外します。（返信等
 * * accounts.txtに複数のアカウントを記入することで、複数アカウントを処理できます。
 *
 * = 使い方
 * account.txtに、mixiのログイン情報、twitterのログイン情報を記入します。
 * 初回時は過去20件のデータを取得し、mixiボイスに投稿します。
 * 次回以降は、twitterのステータスIDを記憶しているので、そこからの発言を処理します。
 * {twitterアカウント}.datというファイルに、最終取得分のステータスIDが記入されています。
 * 
 * = 注意事項
 * accounts.txtをWeb公開ディレクトリ等、他者から見れる場所に設置しないでください。
 *
 */
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);

/**
 * アカウントファイルのフルパス
 *
 * [注意] Webで公開されている場所にはおかないでください。
 * デフォルトは、このファイルと同階層の「accounts.txt」というファイルです。
 * @var string
 */
define('ACCOUNTS_FILE', dirname(__FILE__) . DS . 'accounts.txt');

/**
 * ステータスIDファイル格納ディレクトリ
 *
 * デフォルトは、このファイルと同階層にステータスIDファイルを作成していきます。
 * @var string
 */
define('STAT_DIR', dirname(__FILE__) . DS );



// pear include
set_include_path(dirname(__FILE__) . DS . 'pear' . PS . get_include_path());
require_once('HTTP/Request.php');

// 処理開始
$tw2mv = new TW2MV(ACCOUNTS_FILE);
$tw2mv->start();
exit(0);


// == Classes =============================================
/**
 *
 *
 * @author nojimage
 *
 */
class TW2MV
{
    public $account_file = './accounts.txt';

    public $accounts = array();

    public function __construct($account_file = null)
    {
        if (is_file($account_file)) {
            $this->load($this->account_file);
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

            if (preg_match('/(.*?)[\s]+(.*?)[\s]+(.*?)[\s]+(.*?)$/', $line, $matches)) {
                $this->accounts[] = array(
                    'mixi_email' => $matches[1], 'mixi_password' => $matches[2],
                    'twitter_username' => $matches[3], 'twitter_password' => $matches[4]);
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
        foreach ($this->accounts as $account) {
            $twitter = new TW2MV_Twitter($account['twitter_username'], $account['twitter_password'], STAT_DIR);

            if (count($twitter->get_status())) {

                $mixi = new TW2MV_Mixi($account['mixi_email'], $account['mixi_password']);

                foreach ($twitter->status as $message) {
                    $mixi->post_voice( preg_replace("/\n/", '', $message) );
                }
            }
        }
    }
}

/**
 *
 *
 * @author nojimage
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
 * @author nojimage
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

    /**
     * ボイスの末尾に付加するメッセージ（10文字以内）
     *
     * @var string
     */
    static $comment_suffix = ' [tw2mv]';

    protected $response_encoding = 'eucjp-win';

    public $voice_post_key = '';

    /**
     *
     * @param $email
     * @param $password
     */
    public function __construct($email = null, $password = null)
    {
        parent::__construct();

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
    }

    /**
     * get Voice
     *
     * @return string
     */
    public function get_recent_voice()
    {
        $page = $this->get_request(self::$HTTP_URI . 'recent_echo.pl');
        $this->parse_post_key($page);
        return $page;
    }

    /**
     * 投稿キーの取得
     *
     * @param $page
     */
    public function parse_post_key($page)
    {
        if (preg_match('!<input.*?(?: name="post_key").*? value="(.*?)".*?/>|<input.*? value="(.*?)".*?(?: name="post_key").*?/>!u', $page, $matches)) {
            $this->voice_post_key = $matches[1];
        }
    }

    /**
     * Post to Voice
     *
     * @param $message
     * @return string
     */
    public function post_voice($message)
    {
        if (empty($this->voice_post_key)) {
            $this->get_recent_voice();
        }

        $data = $message . self::$comment_suffix;

        $datas = array('body' => $data,
            'post_key' => $this->voice_post_key, 'redirect' => 'recent_echo');

        $this->post_request(self::$HTTP_URI . 'add_echo.pl', $datas);

        // wait
        sleep(2);
    }
}

/**
 *
 *
 * @author nojimage
 *
 */
class TW2MV_Twitter extends TW2MV_Client
{
    /**
     *
     * @var string
     */
    static $HTTP_URI = 'http://twitter.com/';

    /**
     * ステータスファイルの格納先
     *
     * @var string
     */
    private $stat_file_dir = './';

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
    public function __construct($username, $password, $stat_file_dir = null)
    {
        parent::__construct();

        if (!empty($stat_file_dir)) {
            $this->stat_file_dir = $stat_file_dir;
        }

        if (!empty($username) && !empty($password)) {
            $this->login($username, $password);
        }
    }

    /**
     *
     * @param $username
     * @param $password
     */
    public function login($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        $this->http->setBasicAuth($this->username, $this->password);

        $result = json_decode($this->get_request(self::$HTTP_URI . 'account/verify_credentials.json'), true);
    }

    /**
     * 発言の取得
     *
     * @return array
     */
    public function get_status()
    {

        $params = array('count' => 20);

        // since_id取得
        if (is_file($this->get_stat_filename())) {
            $params['since_id'] = file_get_contents($this->get_stat_filename());
        }

        $result = json_decode($this->get_request(self::$HTTP_URI . 'statuses/user_timeline/' . $this->username . '.json', $params), true);

        if (empty($result)) {
            return false;
        }

        $result = array_reverse($result);
        $this->status = array();
        foreach ($result as $data) {
            if ( strpos($data['text'], '@') === 0 ) { continue; }
            $this->status[] = $data['text'];
        }

        $lastest_id = $data['id'];

        // save to stat
        if ($fh = fopen($this->get_stat_filename(), 'w')) {
            fwrite($fh, $lastest_id);
            fclose($fh);
        }

        return $this->status;
    }

    /**
     *
     * @return string
     */
    public function get_stat_filename()
    {
        $this->stat_file_dir = preg_replace('!/+$!', '', $this->stat_file_dir);
        return $this->stat_file_dir . DS . $this->username . '.dat';
    }

}