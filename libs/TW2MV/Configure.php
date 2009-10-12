<?php
/**
 * TW2MV_Configure
 *
 * 設定ファイルの処理
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
class TW2MV_Configure
{
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
     *
     * @param $config_file
     */
    function __construct($config_file)
    {
        $this->load($config_file);
    }

    /**
     * 設定ファイルの読み込み
     * @param $config_file
     * @return array
     */
    function load($config_file)
    {
        if (!is_file($config_file)) {
            debug('config file not found.');
            return false;
        }

        $config  = parse_ini_file($config_file, true);

        if (empty($config)) {
            debug('config parse error.');
            return false;
        }

        foreach (array_keys($config) as $section)
        {
            foreach (array_keys($config[$section]) as $key)
            {
                $this->{strtolower($section) . '_' . str_replace('.', '_', $key)} = $config[$section][$key];
            }
        }

        $this->config = $config;

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
}