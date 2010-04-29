<?php
/**
 * TW2MV_Configure
 *
 * 設定ファイルの処理
 *
 * PHP versions 5
 *
 * Copyright 2010, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.3
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
class TW2MV_Configure
{
    /**
     *
     * @var string
     * @since version 2.1.0
     */
    public $config_file = '';

    /**
     *
     * @var array
     */
    public $config;

    /**
     * mixiボイス から Twitterの転送を行うか
     * @var bool
     */
    public $core_mv2tw = true;

    /**
     * Twitter から mixiボイスの転送を行うか
     * @var bool
     */
    public $core_tw2mv = true;

    /**
     * データ格納先ディレクトリ
     * @var string
     */
    public $core_datas = 'datas';

    /**
     * 実際の投稿は行わず、IDの取得のみを行う
     * @var bool
     */
    public $core_fetch_only = false;

    /**
     * 実行時にパスワードを暗号化して設定ファイルを書き換えるか
     * @var bool
     */
    public $core_password_crypt = true;

    public $mixi_email = '';
    public $mixi_password = '';
    public $mixi_voice_post_interval = 2;

    /**
     * mixiボイスに投稿するとき末尾に付加される文字列
     * @var string
     */
    public $mixi_voice_message_suffix = ' [tw2mv]';
    public $mixi_voice_myvoice_parse = true;
    public $mixi_voice_reply_parse = true;

    /**
     * メッセージ取得時、mixiボイスの発言から他のユーザへの返信を除外するか
     * @var string
     */
    public $mixi_voice_exclude_reply = true;

    /**
     * mixiボイスのメッセージ取得時、除外するキーワード
     * @var string
     */
    public $mixi_voice_filter_denys = array();

    /**
     * mixiボイスのメッセージ取得時、許可するキーワード
     * @var string
     */
    public $mixi_voice_filter_allows = array();

    public $twitter_username = '';
    public $twitter_password = '';
    public $twitter_max_status = 20;

    /**
     * twitterに投稿するとき末尾に付加される文字列
     * @var string
     */
    public $twitter_message_suffix = ' #mv2tw';

    /**
     * メッセージ取得時、twitterの発言から他のユーザへの返信を除外するか
     * @var string
     */
    public $twitter_exclude_reply = true;

    /**
     * twitterのメッセージ取得時、除外するキーワード
     * @var string
     */
    public $twitter_filter_denys = array();

    /**
     * twitterのメッセージ取得時、許可するキーワード
     * @var string
     */
    public $twitter_filter_allows = array();

    /**
     * OAuth Consumer Key
     * @var string
     * @since version 2.1.0
     */
    public $twitter_oauth_consumer_key = 'sKLeVx7IpK3rKByLn218w';

    /**
     * OAuth Consumer Secret Key
     * @var string
     * @since version 2.1.0
     */
    public $twitter_oauth_consumer_secret = 'DSwg6gZyPoh3frNBXGY7lzuxU2X8DYh9nrj0';

    /**
     * OAuth Access Token
     * @var string
     * @since version 2.1.0
     */
    public $twitter_oauth_access_token = '';

    /**
     * OAuth Access Token Secret
     * @var string
     * @since version 2.1.0
     */
    public $twitter_oauth_access_token_secret = '';

    /**
     * パスワードが暗号化されている場合に付与される文字列
     * @var string
     */
    static $crypted_prefix = '==CRYPT=?';

    /**
     *
     * @param string $config_file
     * @param array  $options
     */
    function __construct($config_file, $options = null)
    {
        $this->load($config_file, $options);
    }

    /**
     * 設定ファイルの読み込み
     *
     * @param string $config_file
     * @param array  $options
     * @return array
     */
    function load($config_file, $options = null)
    {
        if (!is_file($config_file)) {

            // 相対指定の場合は、CONFIG_DIRディレクトリからも走査
            if (strpos($config_file, DS) !== NULL) {
                $config_file = CONFIG_DIR . $config_file;
                debug($config_file);
            }

            // それでも無いなら終了
            if (!is_file($config_file)) {
                debug('config file not found.');
                return false;
            }
        }

        $config  = parse_ini_file($config_file, true);

        if (empty($config)) {
            debug('config parse error.');
            return false;
        }

        foreach (array_keys($config) as $section)
        {

            if (in_array(strtolower($section), array('core', 'mixi', 'mixi_voice', 'twitter'))) {
                // 2.0.4以前のオプション設定
                foreach (array_keys($config[$section]) as $key)
                {
                    $key_name = strtolower($section) . '_' . str_replace('.', '_', $key);
                    if (in_array($key_name, get_class_vars(__CLASS__))) {
                        // ファイルからのオプションをセット
                        $this->{$key_name} = $config[$section][$key];
                    }
                }
            } else {
                // 2.0.5以降のオプション設定
                $key_name = str_replace('.', '_', strtolower($section));
                if (in_array($key_name, get_class_vars(__CLASS__))) {
                    // ファイルからのオプションをセット
                    $this->{$key_name} = $config[$section];
                }

            }

        }

        // -- パスワードを暗号化する
        $this->_secure($config_file);

        foreach (get_object_vars($this) as $key => $val) {
            // コマンドラインからのオプションをセット
            if (!is_null($options[$key])) {
                $this->{$key} = $options[$key];
            }
        }

        $this->config = $config;

        $this->config_file = $config_file;

        return get_object_vars($this);
    }

    /**
     * ステータスファイルの格納先
     * @return string
     */
    function get_stat_dir()
    {
        $datas_dir = str_replace(DS . DS, DS, ROOT . DS . $this->core_datas . DS);

        $stat_dir = $datas_dir . md5($this->twitter_username . '_to_' . $this->mixi_email) . DS;

        // ディレクトリが存在しなければ作成
        if (!is_dir($stat_dir)) {
            mkdir($stat_dir, 0700);
        }

        return $stat_dir;
    }

    /**
     * コマンドラインオプションをセット
     *
     * @param Console_CommandLine $parser
     */
    static function set_commandline_parser($parser)
    {
        $parser->addOption('core_mv2tw', array(
            'long_name'   => '--disable-mv2tw',
            'action'      => 'StoreFalse',
            'description' => 'mixiボイスからtwitterへの転送を行わない'));

        $parser->addOption('core_tw2mv', array(
            'long_name'   => '--disable-tw2mv',
            'action'      => 'StoreFalse',
            'description' => 'twitterからmixiボイスへの転送を行わない'));

        $parser->addOption('core_fetch_only', array(
            'long_name'   => '--fetch-only',
            'action'      => 'StoreTrue',
            'description' => '実際の投稿は行わず、IDの取得のみを行う'));
    }

    /**
     * パスワードを暗号化します。
     *
     * @param $cofig_file
     */
    protected function _secure($config_file = null)
    {
        $file_update = false;

        foreach (array('mixi_password', 'twitter_password') as $key)
        {
            // -- 既に暗号化されていないかチェック
            if (strpos($this->{$key}, self::$crypted_prefix) === 0) {
                // 暗号化済み
                continue;
            }

            // 暗号化する
            $this->{$key} = self::encrypt($this->{$key});
            $file_update  = true;

        }

        if (!$file_update || !$this->core_password_crypt || empty($config_file) || !is_file($config_file)) {
            // 設定ファイルの書き換えを行わない
            return;
        }

        // 設定ファイルを書き換え
        $lines = file($config_file);
        $type  = '';
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/^\[(.*)\]/', $lines[$i], $matches)) {
                $type = $matches[1];
            } else if (preg_match('/^password\s*=\s*/', $lines[$i], $matches) && !empty($type)) {
                $lines[$i] = 'password="' . $this->{strtolower($type) . '_password'} . '"' . "\n";
            } else if (preg_match('/^((mixi|twitter)[\._])password\s*=\s*/', $lines[$i], $matches)) {
                $lines[$i] = $matches[1].'password="' . $this->{$matches[2] . '_password'} . '"' . "\n";
            }
        }

        // 書き出し
        if ($fh = fopen($config_file, 'a')) {
            if (flock($fh, LOCK_EX)) {
                ftruncate($fh, 0);
                foreach ($lines as $line) {
                    fwrite($fh, $line);
                }
                flock($fh, LOCK_UN);
            }
            fclose($fh);
        }

        return;

    }

    /**
     * 値を暗号化して返します
     *
     * @param string $value
     * @param string $key
     * @return string
     */
    static function encrypt($value, $key = null)
    {
        if (is_null($key)) {
            $key = self::get_secure_key();
        }

        require_once('Crypt/Blowfish.php');
        $crypt = Crypt_Blowfish::factory('cbc', $key, substr(sha1($key), 0, 8));
        $cipher = $crypt->encrypt($value);
        $cipher = base64_encode($cipher);
        return self::$crypted_prefix . $cipher;
    }

    /**
     * 値を複合化して返します
     *
     * @param string $cipher
     * @param string $key
     * @return string
     */
    static function decrypt($cipher, $key = null)
    {
        if (is_null($key)) {
            $key = self::get_secure_key();
        }

        require_once('Crypt/Blowfish.php');
        $crypt = Crypt_Blowfish::factory('cbc', $key, substr(sha1($key), 0, 8));
        $cipher = substr($cipher, strlen(self::$crypted_prefix));
        $cipher = base64_decode($cipher);
        $value = $crypt->decrypt($cipher);

        return rtrim($value, "\0");
    }

    /**
     * 暗号化用のキーを取得する
     * 
     * @return string
     */
    static function get_secure_key()
    {
        $key_file = CONFIG_DIR . 'secret_key.php';
        return (is_file($key_file)) ? sha1(file_get_contents($key_file)) : 'gre#jTG%EihogNu04t6uXewR@lglew';
    }

    /**
     * Access Tokenを保存
     * 
     * @param $oauth_token
     * @param $oauth_token_secret
     * @since version 2.1.0
     */
    function saveTwitterAccessToken($oauth_token, $oauth_token_secret)
    {

        $this->twitter_oauth_access_token = $oauth_token;
        $this->twitter_oauth_access_token_secret = $oauth_token_secret;

        // 設定ファイル読み込み
        $file_contents = file_get_contents($this->config_file);

        if (preg_match('/^(twitter.oauth_access_token)\s*=\s*".*?"/i', $file_contents)) {

            $file_contents = preg_replace('/^(twitter.oauth_access_token)\s*=\s*".*?"/i',  '$1 = "'. $oauth_token . '"', $file_contents);

        } else {

            $file_contents .= "\n";
            $file_contents .= 'twitter.oauth_access_token = "' . $oauth_token . '"';

        }

        if (preg_match('/^(twitter.oauth_access_token_secret)\s*=\s*".*?"/i', $file_contents)) {

            $file_contents = preg_replace('/^(twitter.oauth_access_token_secret)\s*=\s*".*?"/i',  '$1 = "'. $oauth_token_secret . '"', $file_contents);

        } else {

            $file_contents .= "\n";
            $file_contents .= 'twitter.oauth_access_token_secret = "' . $oauth_token_secret . '"';

        }

        // 書き出し
        if ($fh = fopen($this->config_file, 'a')) {
            if (flock($fh, LOCK_EX)) {
                ftruncate($fh, 0);
                fwrite($fh, $file_contents);
                flock($fh, LOCK_UN);
            }
            fclose($fh);
        }

    }
}