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

if ( !function_exists('function_compare') || function_compare('function_compare', 3, true, __FILE__, __LINE__) ) {

	/**
	 * Compares the versions of a function
	 *
	 * @version 3, April 23, 2008
	 *
	 * @param	string	$function_name			function name
	 * @param	string	$version__attempting	function version
	 * @param	string	$set					are we about to set the function?
	 * @param	string	$file__attempting		__FILE__
	 * @param	string	$line__attempting		__LINE__
	 *
	 * @return	bool
	 */
	function function_compare ( $function_name, $version__attempting, $set = true, $file__attempting, $line__attempting ) { /*
		 * Changelog
		 *
		 * v3, April 23, 2008
		 * - Rewrote, added file and line, and now uses trigger_error
		 *
		 * v2 - 24/12/2006
		 * - Made it so it floors version numbers, if the number is the same then we don't worry.
		 * - Eg. If 2.0 was compared with 2.1 then no problem would happen, if 3 was compared with 2 or vice versa we do have a problem
		 *
		 * v1 - 29/07/2006
		 */
		
		/*
		 * Usage
		 *
		 * Returns:
		 * true		;	function has not been set yet
		 * false	;	functions are the same version
		 * NULL		;	functions are not the same version
		 */
		
		// Prepare
		$version__var = $function_name . '__version';
		global $$version__var;
		$file__var = $function_name . '__file';
		global $$file__var;
		$line__var = $function_name . '__line';
		global $$line__var;
		
		//
		$version__existing = !empty($$version__var) ? $$version__var : NULL;
		$file__existing = !empty($$file__var) ? $$file__var : NULL;
		$line__existing = !empty($$line__var) ? $$line__var : NULL;
		
		// Checks
		$function_exists = function_exists($function_name);
		
		// Logic
		if ( $function_exists ) { // The function exists
			

			if ( $version__existing !== $version__attempting ) { // Funcions are not the exact same version
				if ( $set ) { // We want to set the function, but we can't as it is already defined
					// so error
					$error_level = ($version__existing === NULL) ? E_USER_ERROR : // don't know
(floor($version__existing) !== floor($version__attempting)) ? E_USER_ERROR : // different major
E_USER_WARNING;// same major, different minors
/*($version__existing > $version__attempting)
								?	E_USER_NOTICE		// existing is a newer version
								:	E_USER_WARNING		// existing is a older version*/
					
					$error_message = '<strong>Conflicting function versions</strong><br />' . "\r\n" . '&nbsp;function name: <strong>[' . $function_name . ']</strong><br />' . "\r\n" . '&nbsp;existing version: [' . $version__existing . '] in <strong>' . $file__existing . '</strong> on line <strong>' . $line__existing . '</strong><br />' . "\r\n" . '&nbsp;attempting version: [' . $version__attempting . '] in <strong>' . $file__attempting . '</strong> on line <strong>' . $line__attempting . '</strong><br />' . "\r\n";
					trigger_error($error_message, $error_level);
				}
				// Return false
				return false;
			}
			
			// Function is the same version
			return $set ? false : true;
		} else { // Function does not exist
			

			if ( $set ) { // Set the variable
				$$version__var = $version__attempting;
				$$file__var = $file__attempting;
				$$line__var = $line__attempting;
			}
			
			return true;
		}
	}

	/**
	 * Alias for function_compare
	 *
	 * @see function_compare
	 */
	function class_compare ( $function_name, $version__attempting, $set = true, $file__attempting, $line__attempting ) {
		return function_compare($function_name, $version__attempting, $set, $file__attempting, $line__attempting);
	}
}


if ( function_compare('baldebug', 1, true, __FILE__, __LINE__) ) {
	function baldebug ( ) {
		$args = func_get_args(); if ( sizeof($args) === 1 ) $args = $args[0];
		echo '<pre>'.var_export($args,true).'</pre>';
	}
}


if ( function_compare('baldump', 1, true, __FILE__, __LINE__) ) {
	function baldump ( ) {
		$args = func_get_args(); if ( sizeof($args) === 1 ) $args = $args[0];
		echo '<pre>'; var_dump($args); echo '</pre>';
	}
}

?>