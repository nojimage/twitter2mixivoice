<?php
/**
 * TW2MV_Message
 *
 * 共通メッセージクラス
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
class TW2MV_Message
{
    public $id;
    public $message;
    public $from;
    public $from_id;
    public $to;
    public $to_id;
    public $source;

    /**
     * 指定文字数に丸める
     *
     * @param int length
     * @param string $suffix
     * @return string
     */
    public function make_message($length, $suffix)
    {
        $max_length = $length - mb_strlen($suffix);

        $message = $this->message;
        
        if (mb_strlen($message) > $max_length) {
            // 140字を超える場合
            $message = preg_replace('!^(.{' . ($max_length - mb_strlen('...')) . '})(.*)$!u', '$1...', $message);
        }

        $message .= $suffix;

        return $message;
    }
}
