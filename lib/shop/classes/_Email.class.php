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

class Email extends ShopObject
{
	var $content = '';
	
	// ===========================
	
	function Email ( $row = NULL, $perform_action = true )
	{	
		// Finish Construction
		return $this->ShopObject('emails', $row, $perform_action);
	}
	
	// ===========================
	
	function load ( $add_log_on_success = false, $only_unset = false )
	{	// We get a row based on $this->row['id']
		$result = parent::load($add_log_on_success, $only_unset);
		if ( $result )
		{
			$this->content = $this->get('body');
		}
		
		return $result;
	}
	
	function replace_arguments ( $arguments )
	{
		$content = $this->content;
		
		$keys = array_keys($arguments);
		$values = array_values($arguments);
		for ( $i = 0, $n = sizeof($keys); $i < $n; $i++ )
		{
			$key = $keys[$i];
			$value = $values[$i];
			
			$find = '%'.$key.'%';
			$content = str_replace($find, $value, $content);
		}
		
		$this->content = $content;
		
		return true;
	}
	
	function send ( $send_to, $send_from )
	{
		$subject = $this->get('subject', 'raw');
		$body = $this->content;
		$headers = 'X-Mailer: PHP/'.phpversion();
		if ( !empty($send_from) )
			$headers .=
				"\r\n".'From: '.$send_from."\r\n".
				'Reply-To: '.$send_from;
		
		$result = mail($send_to, $subject, $body, $headers);
		
		if ( $result )
		{
			$this->Log->add(
				// TYPE
					'success',
				// TITLE
					'Successfully sent the "'.$subject.'" email to "'.$send_to.'"',
				// DESCRIPTION
					'',
				// DETAILS
					'$id: ['.				var_export($this->id, true)				.']'."\r\n",
					'$send_to: ['.			var_export($send_to, true)				.']'."\r\n",
					'$subject: ['.			var_export($subject, true)				.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)			."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// MAKE FRIENDLY
					true
			);
		}
		else
		{	// Error
			$this->Log->add(
				// TYPE
					'error',
				// TITLE
					'Could not send the email',
				// DESCRIPTION
					'',
				// DETAILS
					'$id: ['.				var_export($this->id, true)				.']'."\r\n",
					'$send_to: ['.			var_export($send_to, true)				.']'."\r\n",
					'$subject: ['.			var_export($subject, true)				.']'."\r\n",
					'$body: ['.				var_export($body, true)					.']'."\r\n",
					'$headers: ['.			var_export($headers, true)				.']',
				// WHERE
					'Class: '.				get_class_heirachy($this, true)			."\r\n".
					'Filename: '.			basename(__FILE__)						."\r\n".
					'File: '.				__FILE__								."\r\n".
					'Function: '.			__FUNCTION__							."\r\n".
					'Line: '.				__LINE__,
				// MAKE FRIENDLY
					true
			);
		}
		
		return $result;
	}
}
