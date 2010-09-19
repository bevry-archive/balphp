<?php
/**
 * Balupton's Resource Library (balPHP)
 * Copyright (C) 2008-2009 Benjamin Arthur Lupton
 * http://www.balupton.com/
 *
 * This file is part of Balupton's Resource Library (balPHP).
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Balupton's Resource Library (balPHP).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package balphp
 * @subpackage bal
 * @version 0.2.0-final, December 9, 2009
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008-2009, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */
class Bal_Basic {
	
	public function __get ( $key ) {
		$getter = 'get'.str_replace(' ','',ucwords(str_replace('_',' ',$key)));
		if ( method_exists($this, $getter) ) {
			return $this->$getter($key);
		} elseif ( property_exists($this, $key) ) {
			return $this->$key;
		} else {
			throw new Exception('Unkown property: '.$key);
		}
	}
	
	public function __set ( $key, $value ) {
		$setter = 'set'.str_replace(' ','',ucwords(str_replace('_',' ',$key)));
		if ( method_exists($this, $setter) ) {
			$this->$setter($value);
		} elseif ( property_exists($this, $key) ) {
			$this->$key = $value;
		} else {
			throw new Exception('Unkown property: '.$key);
		}
		return $this;
	}
	
	public function set ( $key, $value = null ) {
		if ( $value === null && is_array($key) ) {
			foreach ( $key as $_key => $_value ) {
				$this->set($_key, $_value);
			}
		} else {
			$this->$key = $value;
		}
		return $this;
	}
	
	public function __construct($data = null){
		if ( is_array($data) ) {
			$this->set($data);
		}
		return $this;
	}
}