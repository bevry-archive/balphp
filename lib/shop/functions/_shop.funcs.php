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
 * @subpackage shop
 * @version 0.1.0-final, April 21, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once(dirname(__FILE__).'/../../core/functions/_general.funcs.php');

if ( function_compare('newsletter_subscribe', 1, true, __FILE__, __LINE__) )
{	/**
	 * Subscribe a user to the newsletter table
	 * 
	 * @version 1
	 * 
	 * @todo figure out what the hell this does
	 * 
	 */
	function newsletter_subscribe ( $address, $table )
	{
		$table .= '_subscribers';
		$address = addslashes($address);
		
		$region = defined('REGION_CODE') ? REGION_CODE : 'UNK';
		
		$key = md5(time());
		$req_time = time();
		$mysql_query =
		'SELECT * FROM `'.$table.'` WHERE `address` = \''.$address.'\' LIMIT 1';
		$mysql_result = mysql_query($mysql_query);
		$mysql_num_rows = mysql_num_rows($mysql_result);
		if ( $mysql_num_rows == 0 )
		{
			$mysql_query =
			'INSERT INTO `'.$table.'` (`address`, `userkey`, `confirmed`, `last_sub_req_date`, `bounce_count`, `region`) VALUES '."('$address', '$key', 1, '$req_time', 0, '$region')";
			mysql_query($mysql_query);
			$mysql_error = mysql_error();
			if ( $mysql_error )
				return false;
		}
		
		return true;
	}
}

if ( function_compare('newsletter_unsubscribe', 1, true, __FILE__, __LINE__) )
{	/**
	 * Unsubscribe a user from the newsletter table
	 * 
	 * @version 1
	 * 
	 * @todo figure out what the hell this does
	 * 
	 */
	function newsletter_unsubscribe ( $address, $table )
	{
		$table .= '_subscribers';
		$address = addslashes($address);
		
		$mysql_query =
		'DELETE FROM `'.$table.'` WHERE `address` = \''.$address.'\' ';
		mysql_query($mysql_query);
		$mysql_error = mysql_error();
		if ( $mysql_error )
			return false;
		
		return true;
	}
}

if ( function_compare('newsletter_resubscribe', 1, true, __FILE__, __LINE__) )
{	/**
	 * Resubscribe a user to the newsletter table
	 * 
	 * @version 1
	 * 
	 * @todo figure out what the hell this does
	 * 
	 */
	function newsletter_resubscribe ( $old_address, $address, $table )
	{
		$table .= '_subscribers';
		$address = addslashes($address);
		$old_address = addslashes($old_address);
		
		$mysql_query =
		'UPDATE `'.$table.'` SET `address` = \''.$address.'\' WHERE `address` = \''.$old_address.'\'';
		mysql_query($mysql_query);
		$mysql_error = mysql_error();
		if ( $mysql_error )
			return false;
		
		return true;
	}
}
