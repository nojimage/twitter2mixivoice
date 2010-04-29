<?php
/**
 * TW2MV
 *
 * メインクラス
 *
 * PHP versions 5
 *
 * Copyright 2009, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.2
 * @author     nojimage <nojimage at gmail.com>
 * @copyright  2010 nojimage
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://php-tips.com/
 * @package    tw2mv
 * @subpackage tw2mv.libs
 * @since      File available since Release 2.0 beta1
 * @modifiedby nojimage <nojimage at gmail.com>
 *
 */
class TW2MV
{

    /**
     * 実行
     *
     * @param array $config_file
     * @param array $options
     */
    static function start($config_file = null, $options = null)
    {
        if (empty($config_file)) {
            $config_file = CONFIG_DIR . 'tw2mv.ini.php';
        }

        // 設定読み込み
        require_once('TW2MV' . DS . 'Configure.php');
        $config = new TW2MV_Configure($config_file, $options);

        debug($config);

        if ($config->core_tw2mv) {
            // Twitter -> mixi voiceを起動
            self::twitter2mixivoice($config);
        }

        if ($config->core_mv2tw) {
            // mixi voice -> Twitterを起動
            self::mixivoice2twitter($config);
        }
    }

    /**
     * Twitter -> mixi voice
     * @param TW2MV_Configure $config
     */
    static function twitter2mixivoice($config)
    {
        require_once 'Twitter.php';
        require_once 'Mixi_Voice.php';

        $twitter = new TW2MV_Twitter($config);

        // Twitterからステータスを取得
        $messages = $twitter->getMessages();

        if (!empty($messages)) {

            $mixi_voice = new TW2MV_Mixi_Voice($config);

            // 古いものから投稿
            $messages = array_reverse($messages);

            foreach ($messages as $message)
            {
                $mixi_voice->post($message);
            }
        }

    }

    /**
     * mixi voice -> Twitter
     * @param TW2MV_Configure $config
     */
    static function mixivoice2twitter($config)
    {
        require_once 'Twitter.php';
        require_once 'Mixi_Voice.php';

        $mixi_voice = new TW2MV_Mixi_Voice($config);

        if ($config->mixi_voice_myvoice_parse) {
            // Mixiボイスから発言を取得
            $messages = $mixi_voice->getMessages();

            if (!empty($messages)) {
                $twitter = new TW2MV_Twitter($config);

                // 古いものから投稿
                $messages = array_reverse($messages);

                foreach ($messages as $message)
                {
                    $twitter->post($message);
                }
            }

        }

        if ($config->mixi_voice_reply_parse) {
            // Mixiボイスから返信を取得
            $messages = $mixi_voice->getReplyMessages();

            if (!empty($messages)) {
                $twitter = new TW2MV_Twitter($config);

                // 古いものから投稿
                $messages = array_reverse($messages);

                foreach ($messages as $message)
                {
                    $twitter->direct_message($message, $config->twitter_username);
                }
            }
        }
    }

    /**
     * コマンドラインオプションのセット
     *
     * @param $argc
     * @param $argv
     * @return Console_CommandLine_Result
     */
    static function parse_options($argc, $argv)
    {
        require_once 'Console/CommandLine.php';
        $parser = new Console_CommandLine(array(
            'description' => 'twitterとmixiボイス間でメッセージの転送を行うスクリプトです。',
            'version' => '2.1.0'));

        require_once('TW2MV' . DS . 'Configure.php');
        // オプションのセット
        TW2MV_Configure::set_commandline_parser($parser);

        // 引数の追加
        $parser->addArgument('config_files', array(
            'optional'    => true,
            'multiple'    => true,
            'description' => '設定ファイル'));

        // パーサの実行
        try {
            $result = $parser->parse($argc, $argv);
            return $result;

        } catch (Exception $e) {
            $parser->displayError($e->getMessage());

        }
    }
}