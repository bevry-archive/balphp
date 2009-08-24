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

class Serial extends ShopObject
{
	
	// ===========================
	
	function Serial ( $row = NULL, $perform_action = true )
	{	
		// Finish Construction
		return $this->ShopObject('serials', $row, $perform_action);
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
			case 'serial':
				$value = $this->generate_serial_part($this->get('formula_1'));
				$value .= '-'.$this->generate_serial_part($this->get('formula_2'));
				$value .= '-'.$this->generate_serial_part($this->get('formula_3'));
				$value .= '-'.$this->generate_serial_part($this->get('formula_4'));
				break;
			
			default:
				$value = parent::get($column_nickname, $display, $default, $get_title);
				break;
		}
		
		return $value;
	}
	
	// ===========================
	
	function generate_serial_part ( $number )
	{	
		if ( $number === 0 )
			return NULL;
		
		$min = ceil(1000/$number);
		$max = floor(9999/$number);
		
		while ( ($multiplier = rand($min, $max)) % 2 != 0 )
		{ }
		
		$result = $number*$multiplier;
	
		return $result;
	}
}

?>