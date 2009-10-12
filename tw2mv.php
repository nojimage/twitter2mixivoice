<?php
/**
 * Twitter to mixiボイス
 *
 * PHP versions 5
 *
 * Copyright 2009, nojimage (http://php-tips.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @version   2.0 beta1
 * @author    nojimage <nojimage at gmail.com>
 * @copyright 2009 nojimage
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link    　http://php-tips.com/
 * @since   　File available since Release 2.0 beta
 *
 */

/**
 * Debug flag
 * @var int 0:product 1:debug
 */
define('DEBUG', 1);

// -- Load lib file
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'lib.php');

require_once('TW2MV.php');

TW2MV::start(CONFIG_DIR . 'tw2mv.ini.php');