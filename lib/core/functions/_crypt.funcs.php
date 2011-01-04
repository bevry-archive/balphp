<?php
/**
 * Balupton's Resource Library (balPHP)
 * Copyright (C) 2008 Benjamin Arthur Lupton
 * http://www.balupton.com/
 *
 * This file is part of Balupton's Resource Library (balPHP).
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Balupton's Resource Library (balPHP).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package balphp
 * @subpackage core
 * @version 0.1.1-final, November 11, 2009
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'_general.funcs.php');

// Config
if ( !defined('CRYPT_LOADED') ) {
	// Cryptography
	define('CRIPT_ENABLED', function_exists('mcrypt_module_open'));
	define('CRYPT_CIPHER',	defined('MCRYPT_RIJNDAEL_256') ? MCRYPT_RIJNDAEL_256 : false);
	define('CRYPT_MODE',	defined('MCRYPT_MODE_CBC') ? MCRYPT_MODE_CBC : false);
	define('CRYPT_SOURCE',	defined('MCRYPT_DEV_RANDOM') ? MCRYPT_DEV_RANDOM : false);
	define('CRYPT_KEY',		'F5Z3aDe4cQGeixEmS5XvhmG0cd');
	define('CRYPT_IV',		'QDFCMmMzRDRlNUY2ZzdIOAasd');
	define('CRYPT_SIZE',	'256');
	
	// Encoding
	define('CRYPT_ENCODER',	'base64_encode');
	define('CRYPT_DECODER',	'base64_decode');
	
	// Loaded
	define('CRYPT_LOADED',	true);
}

if ( function_compare('encode', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Encode a piece of data
	 *
	 * @version 1.1, April 21, 2008
	 *
	 * @param mixed		$data
	 * @param mixed		$CRYPT_ENCODER	the encoder function to use, defaults to CRYPT_ENCODER
	 *
	 * @return mixed
	 */
	function encode ( $data, $CRYPT_ENCODER = null ) {
		if ( !$CRYPT_ENCODER )
			$CRYPT_ENCODER = CRYPT_ENCODER;
		$result = $data;
		$result = trim($result);
		$result = call_user_func($CRYPT_ENCODER, $result);
		trim($result);
		return $result;
	}
}

if ( function_compare('decode', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Decode a piece of data
	 *
	 * @version 1.1, April 21, 2008
	 *
	 * @param mixed		$data
	 * @param mixed		$CRYPT_DECODER	the encoder function to use, defaults to CRYPT_ENCODER
	 *
	 * @return mixed
	 */
	function decode ( $data, $CRYPT_DECODER = null ) {
		if ( !$CRYPT_DECODER )
			$CRYPT_DECODER = CRYPT_DECODER;
		$result = $data;
		$result = trim($result);
		$result = call_user_func($CRYPT_DECODER, $result);
		trim($result);
		return $result;
	}
}

if ( function_compare('encrypt', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Encrypt a piece of data
	 *
	 * @version 1.1, April 21, 2008
	 *
	 * @param mixed		$data
	 * @param mixed		$CRYPT_ENCODER
	 * @param mixed		$CRYPT_DECODER
	 * @param mixed		$CRYPT_KEY
	 * @param mixed		$CRYPT_CIPHER
	 * @param mixed		$CRYPT_MODE
	 * @param mixed		$CRYPT_SOURCE
	 *
	 * @return mixed
	 */
	function encrypt ( $data, $CRYPT_ENCODER = null, $CRYPT_DECODER = null, $CRYPT_KEY = null, $CRYPT_CIPHER = null, $CRYPT_MODE = null, $CRYPT_SOURCE = null ) {
		if ( !$CRYPT_ENCODER )
			$CRYPT_ENCODER = CRYPT_ENCODER;
		if ( !$CRYPT_DECODER )
			$CRYPT_DECODER = CRYPT_DECODER;
		if ( !$CRYPT_KEY )
			$CRYPT_KEY = CRYPT_KEY;
		if ( !$CRYPT_CIPHER )
			$CRYPT_CIPHER = CRYPT_CIPHER;
		if ( !$CRYPT_MODE )
			$CRYPT_MODE = CRYPT_MODE;
		if ( !$CRYPT_SOURCE )
			$CRYPT_SOURCE = CRYPT_SOURCE;
		
		$key = call_user_func($CRYPT_DECODER, $CRYPT_KEY);
		
		$size = mcrypt_get_iv_size($CRYPT_CIPHER, $CRYPT_MODE);
		$iv = mcrypt_create_iv($size, $CRYPT_SOURCE);
		
		$result = mcrypt_encrypt($CRYPT_CIPHER, $key, $data, $CRYPT_MODE, $iv);
		
		$result = call_user_func($CRYPT_ENCODER, $result);
		
		return $result;
	}
}

if ( function_compare('decrypt', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Decode a piece of data
	 *
	 * @version 1.1, April 21, 2008
	 *
	 * @param mixed		$data
	 * @param mixed		$CRYPT_DECODER
	 * @param mixed		$CRYPT_KEY
	 * @param mixed		$CRYPT_CIPHER
	 * @param mixed		$CRYPT_MODE
	 * @param mixed		$CRYPT_SOURCE
	 *
	 * @return mixed
	 */
	function decrypt ( $data, $CRYPT_DECODER = null, $CRYPT_KEY = null, $CRYPT_CIPHER = null, $CRYPT_MODE = null, $CRYPT_SOURCE = null ) {
		if ( !$CRYPT_DECODER )
			$CRYPT_DECODER = CRYPT_DECODER;
		if ( !$CRYPT_KEY )
			$CRYPT_KEY = CRYPT_KEY;
		if ( !$CRYPT_CIPHER )
			$CRYPT_CIPHER = CRYPT_CIPHER;
		if ( !$CRYPT_MODE )
			$CRYPT_MODE = CRYPT_MODE;
		if ( !$CRYPT_SOURCE )
			$CRYPT_SOURCE = CRYPT_SOURCE;
		
		$data = call_user_func($CRYPT_DECODER, $data);
		$key = call_user_func($CRYPT_DECODER, $CRYPT_KEY);
		
		$size = mcrypt_get_iv_size($CRYPT_CIPHER, $CRYPT_MODE);
		$iv = mcrypt_create_iv($size, $CRYPT_SOURCE);
		
		$result = mcrypt_decrypt($CRYPT_CIPHER, $key, $data, $CRYPT_MODE, $iv);
		
		$result = trim($result);
		
		return $result;
	}
}
