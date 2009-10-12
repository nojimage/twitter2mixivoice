<?php
/**
 * Path setting & include
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

mb_internal_encoding('UTF-8');

if (!defined('DS')) {
    /**
     * DIRECTORY_SEPARATOR alias
     * @var string
     */
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('PS')) {
    /**
     * PATH_SEPARATOR alias
     * @var string
     */
    define('PS', PATH_SEPARATOR);
}

/**
 * Bot Root dir
 * @var string
 */
define('ROOT', dirname(dirname(__FILE__)));

/**
 * Core lib files dir
 * @var string
 */
define('LIBS', dirname(__FILE__));

/**
 * TW2MV lib files dir
 * @var string
 */
define('TW2MV', LIBS . DS . 'TW2MV');

/**
 * Config files dir
 * @var string
 */
define('CONFIG_DIR', ROOT . DS . 'config' . DS);

/**
 * 
 * @var string
 */
define('EXTLIBS', ROOT . DS . 'extlibs' . DS);

/**
 * 
 * @var string
 */
define('EXTLIBS_PEAR', EXTLIBS . DS . 'pear' . DS);

// -- set include 
set_include_path(LIBS . PS . TW2MV. PS . EXTLIBS . PS . EXTLIBS_PEAR . PS . get_include_path());

// -- load libs
require_once('basic.php');