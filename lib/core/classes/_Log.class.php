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
 * @version 0.1.0-final, April 21, 2008
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

/**
 * A Log class
 */
class Log
{	/*
	 ***
	 * ABOUT
	 **
	 * Class: Log Class
	 * Author: Benjamin "balupton" Lupton
	 * Version: 1.0.0.0-beta
	 * Release Date: Unreleased
	 *
	 ***
	 * CHANGLOG
	 ***
	 * 1.0.0.0 (18/12/2006)
	 * - Added $count, $types, display_type, display_item
	 *
	 ***
	 * SUMMARY
	 **
	 * The Log class offers a Logging system into your work.
	 *
	 **
	 */
	
	var $types = array(); /* array(
		type	=>	array(
			& some log,
		),
	);
	*/
	var $log = array();
	var $friendly_log = array();
	var $count = 0;
	var $friendly_count = 0;
	var $display = false;
	
	function clear ( )
	{
		$this->log = $this->friendly_log = $this->types = array();
		$this->count = $this->friendly_count = 0;
		$this->display = false;
	}
	
	/* public */
	function add ( $type, $title, $description = '', $details = '', $where = '', $make_friendly = false )
	{	// Adds a error to the log
		$type = strtolower($type);
		if ( $type == 'error' )
			$this->display = true;
		
		if ( empty($description) )
			$description = '';
		if ( !is_array($description) )
			$description = array($description, $description);
		
		switch ( true )
		{
			case $make_friendly || (empty($details) && empty($where)):
				// We have a log that is friendly
				$item = array( 'type' => $type, 'title' => $title, 'description' => $description );
				$this->friendly_log[] = $item;
				$this->friendly_count++;
				if ( !$make_friendly )
				{	// If we are log that is only friendly, and not detailed then lets stop here
					break;
				}
			
			default:
				$item = array( 'type' => $type, 'title' => $title, 'description' => $description, 'details' => $details, 'where' => $where );
				$this->log[] = $item;
				$this->types[$type][] = & $this->log[$this->count];
				$this->count++;
				break;
		}
		
		return true;
	}
	
	/* private */
	function display_item ( $item, $i = NULL, $return = false, $friendly = false )
	{
		/*
			Log [1]: Error
			 Title: Something went wrong
			 Description: Something did go wrong
			 Details: A detailed explanaion
			 Where: where it went wrong
		*/
		
		if ( !$friendly )
		{
			$r = 
				"\r\n".
				($item['type'] === 'error' ? '--------------'."\r\n" : '').
				'Log '.(is_null($i) ? '' : '['.$i.']').': '.	ucfirst($item['type'])."\r\n".
				' Title: '.			$this->prepare($item['title'], $friendly, 'title')."\r\n".
				( !empty( $item['description'] )
					?	' Description: '."\r\n".
						' '.$this->prepare($item['description'][1], $friendly, 'description')."\r\n"
					:	''
				).
				' Details: '."\r\n".
				'  '.$this->prepare($item['details'], $friendly, 'details')."\r\n".
				' Where: '."\r\n".
				'  '.$this->prepare($item['where'], $friendly, 'where')."\r\n";
		} else
		{
			ob_start();
			?><style type="text/css">
			.log .log_item
			{
				border:1px solid grey;
				padding:5px;
				background-color:#EEEEEE;
				margin:5px;
			}
			
			.log .log_item .title,
			.log .log_item .description
			{
				padding:0px;
				margin:0px;
			}
			.log .log_item .title
			{
				font-size:14px;
			}
			
			.log .log_item.success
			{
				color:green;
				border:1px solid green;
				background-color:#D5FFD5;
			}
			
			.log .log_item.error
			{
				color:red;
				border:1px solid red;
				background-color:#FFE1E1;
			}
			
			.log .log_item .unfriendly
			{
				padding:5px;
		border:1px solid red;
		background-color:#FFECEC;
	}</style><?php
			?><div class="log_item <?php echo $item['type']; ?>">
				<h4 class="title"><?php echo $this->prepare($item['title'], $friendly, 'title'); ?></h4>
				<?php
					echo
					( !empty( $item['description'] )
					? '<p class="description">'.$this->prepare($item['description'][0], $friendly, 'description').'</p>'
					: ''
					);
				?>
			</div><?php
			$r = ob_get_contents();
			ob_end_clean();
		}
		
		if ( !$return )
			echo $r;
		return $r;
	}
	
	function display_friendly ( $return = false )
	{	// Will display errors in a friendly way
		$r = '';
		
		$r .= '<div class="log">';
		
		if ( isset($this->types['error']) && sizeof($this->types['error']) )
		{	// Give an alert that an error has occured
			$r .= '<div class="log_item error"><h4 class="title">An error occurred during processing.</h4></div>';
		}

		for ( $i = 0, $n = $this->friendly_count; $i < $n; $i++ )
		{
			$item = $this->friendly_log[$i];
			$r .= $this->display_item($item, $i, true, true);
		}
		
		$r .= '</div>';
		
		if ( !$return )
			echo $r;
		return $r;
	}
	
	/* public */
	function display_type ( $type, $return = false )
	{	// Displays the log of a particular type
		if ( is_null($type) )
			return $this->display($return, $friendly);
		
		$type = strtolower($type);
		
		$r = '';
		
		if ( !empty($this->types[$type]) )
			$log = $this->types[$type];
		else
			$log = array();
		
		for ( $i = 0, $n = sizeof($log); $i < $n; $i++ )
		{
			$item = $log[$i];
			$r .= $this->display_item($item, $i, true);
		}
		
		if ( !$return )
			echo '<pre>'.$r.'</pre>';
		return $r;
	}
	
	/* public */
	function display ( $return = false, $exclude = array() )
	{	// Displays the log
		$r = '';
		
		for ( $i = 0, $n = $this->count; $i < $n; $i++ )
		{
			$item = $this->log[$i];
			
			if ( !in_array(strtolower($item['type']), $exclude) )
				$r .= $this->display_item($item, $i, true);
			
		}
		
		if ( !$return )
			echo '<pre>'.$r.'</pre>';
		return $r;
	}
	
	/* private */
	function prepare ( $msg, $friendly = false, $thing = NULL )
	{
		if ( $thing === 'title' )
		{
			if ( $friendly )
			{
				$msg = str_replace('[', '<em>', $msg);
				$msg = str_replace(']', '</em>', $msg);
			}
			return $msg;
		}
		
		$msg = str_replace("\r\n",	"\n",			$msg);
		$msg = str_replace("\n",	"\r\n",			$msg);
		if ( !$friendly )
		{
			$msg = str_replace("\r\n",		"\r\n".'  ',	$msg);
		} else
		{
			$msg = str_replace("\r\n",		"\r\n".'<br />  ',		$msg);
		}
		return $msg;
	}
	
}

?>