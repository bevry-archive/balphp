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

require_once (dirname(__FILE__) . '/_general.funcs.php');

if ( !function_exists('json_decode') ) {

	/**
	 * Convert a JSON string to a PHP object or array
	 *
	 * @version 1, June 08, 2008
	 *
	 * @param string		$content
	 * @param boolean		$assoc		if true, associative array is returned instead of object
	 *
	 * @return object|array
	 */
	function json_decode ( $content, $assoc = false ) {
		require_once (dirname(__FILE__) . '/../../pear/json.php');
		if ( $assoc ) {
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		} else {
			$json = new Services_JSON();
		}
		return $json->decode($content);
	}
}

if ( !function_exists('json_encode') ) {

	/**
	 * Convert a PHP object or array to a JSON string
	 *
	 * @version 1, June 08, 2008
	 *
	 * @param string		$content
	 *
	 * @return string
	 */
	function json_encode ( $content ) {
		require_once (dirname(__FILE__) . '/../../pear/json.php');
		$json = new Services_JSON();
		return $json->encode($content);
	}
}

?>
