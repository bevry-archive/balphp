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
class Bal_Log extends Zend_Log {
	
    const INSERT	= 8;
    const SAVE		= 9;
    const DELETE	= 10;
    const SUCCESS	= 10;

	protected $Writer = null;
	
	public function __construct ( ) {
		$this->Writer = new Zend_Log_Writer_Mock();
		$this->addWriter($this->Writer);
		$Formatter_Rich = new Zend_Log_Formatter_Simple('hello %message%' . PHP_EOL);
		$this->Writer->setFormatter($Formatter_Rich);
		# Priorities
		$this->addPriority('INSERT',	Bal_Log::INSERT);
		$this->addPriority('SAVE',		Bal_Log::SAVE);
		$this->addPriority('DELETE',	Bal_Log::DELETE);
		$this->addPriority('SUCCESS',	Bal_Log::SUCCESS);
	}

	public static function getInstance ( ) {
		return Zend_Registry::get('Log');
	}
	
	public function getEvents ( ) {
		return $this->Writer->events;
	}
	
	public function render ( ) {
		return '<pre>'.var_export($this->getEvents(),true).'</pre>';
	}
	
	public function log ( $message, $code = null, array $extras = null ) {
		if ( $code === null ) $code = Zend_Log::INFO;
		foreach ( $extras as $key => $value ) {
			parent::setEventItem($key,$value);
		}
		parent::log($message, $code);
		foreach ( $extras as $key => $value ) {
			parent::setEventItem($key,null);
		}
	}
	
}
