<?php
/**
 * Basic Functions
 *
 * PHP versions 5
 *
 * Copyright 2009, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @version    1.0
 * @author     nojimage <nojimage at gmail.com>
 * @copyright  2009 nojimage
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://php-tips.com/
 * @package    tw2mv
 * @subpackage tw2mv.libs
 * @since      File available since Release 2.0 beta1
 * @modifiedby nojimage <nojimage at gmail.com>
 *
 */

if (!defined('DEBUG')) {
    /**
     * Debug flag
     * @var int 0:product 1:debug
     */
    define('DEBUG', 0);
}

/**
 * Debug Print
 * 
 * @param $var
 */
function debug($var)
{

    if (!DEBUG) { return; }

    $trace = debug_backtrace();
    echo 'DEBUG IN ' . $trace[0]['file'] . ' (' . $trace[0]['line'] . ')' . "\n";
    print_r($var);
    echo "\n";
}

/**
 * return same value
 *
 * @param $var
 * @return mixed
 */
function ref($var)
{
    return $var;
}

/**
 * Camelize
 *
 * @param $string
 * @return string
 */
function camelize($string)
{
    return str_replace(" ", "", ucwords(str_replace("_", " ", $string)));
}

/**
 * underscore
 * 
 * @param $string
 * @return string
 */
function underscore($string)
{
    return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $string));
}