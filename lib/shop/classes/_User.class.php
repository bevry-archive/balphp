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

require_once(dirname(__FILE__).'/_ShopObject.class.php');
require_once(dirname(__FILE__).'/../functions/_shop.funcs.php');

class User extends ShopObject
{
	// Authorize
	var $authorize_create;
	var $authorize_delete;
	
	// ===========================
	
	function User ( $row = NULL, $perform_action = true )
	{	
		// Construct
		if ( empty($this->Shop) )
			$this->Shop = & $GLOBALS['Shop'];
		if ( empty($this->Log) )
			$this->Log = & $this->Shop->Log;
		
		// Authorize
		$this->authorize_create = !$this->Shop->admin;
		$this->authorize_delete = false;
		
		// Finish Construction
		return $this->ShopObject('users', $row, $perform_action);
	}
	
	// ===========================
	
	function authorized ( $action, $error = true )
	{
		return true;
	}
	
	// ===========================
	
	/* public */
	function get ( $column_nickname, $display = false, $default = '', $get_title = true )
	{	
		if ( !is_null($this->status) )
			$this->status = true;
		// We don't care about construction status
		
		switch ( $column_nickname )
		{
			case 'name':
				$value = $this->get('firstname', $display);
				$value .= ' '.$this->get('lastname', $display);
				break;
			
			default:
				$value = parent::get($column_nickname, $display, $default, $get_title);
				break;
		}
		
		return $value;
	}
	
	// ===========================
	
	/* public */
	function create ( $add_log_on_success = true )
	{	
		$result = parent::create($add_log_on_success);
		if ( !$this->status )
			return $result;
		
		#
		$new_email = $this->get('email');
		if ( $this->get('workshop') )
			newsletter_subscribe($new_email, 'workshop');
		if ( $this->get('newsletter') )
			newsletter_subscribe($new_email, 'mailinglist');
	
		return $result;
	}
	
	/* public */
	function update ( $add_log_on_success = true )
	{	
		# Check if we are deconstructed, if we are then fail
		if ( !$this->check_status(false, true, __FUNCTION__) )
			return NULL;
		$this->status = true;
		
		# Check that we are ready to update
		if ( empty($this->id) )
		{
			$this->status = false;
			return false;
		}
		
		#
		$old_email = $this->get('email');
		
		$result = parent::update($add_log_on_success);
		if ( !$this->status )
			return $result;
		
		$new_email = $this->get('email');
		
		if ( $old_email !== $new_email )
		{
			if ( $this->get('workshop') )
				newsletter_resubscribe($old_email, $new_email, 'workshop');
			if ( $this->get('newsletter') )
				newsletter_resubscribe($old_email, $new_email, 'mailinglist');
		}
		else
		{
			if ( $this->get('workshop') )
				newsletter_subscribe($new_email, 'workshop');
			if ( $this->get('newsletter') )
				newsletter_subscribe($new_email, 'mailinglist');
		}
		
		
		if ( !$this->get('workshop') )
			newsletter_unsubscribe($new_email, 'workshop');
		if ( !$this->get('newsletter') )
			newsletter_unsubscribe($new_email, 'mailinglist');
		
		return $result;
	}
	
	/* public */
	function delete ( $add_log_on_success = true )
	{
		# Check if we are deconstructed, if we are then fail
		if ( !$this->check_status(false, true, __FUNCTION__) )
			return NULL;
		$this->status = true;
		
		# Check that we are ready to delete
		if ( empty($this->id) )
		{
			$this->status = false;
			return false;
		}
		
		#
		$result = parent::update($add_log_on_success);
		if ( !$this->status )
			return $result;
		
		$new_email = $this->get('email');
		if ( $this->get('workshop') )
			newsletter_unsubscribe($new_email, 'workshop');
		if ( $this->get('newsletter') )
			newsletter_unsubscribe($new_email, 'mailinglist');
	
		return $result;
	}
	
	// ===========================

}
