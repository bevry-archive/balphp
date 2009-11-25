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

if ( function_compare('image_resize_dimensions', 8.1, true, __FILE__, __LINE__) ) {

	/**
	 * Resizes an image dimensions
	 * @version 8.1, November 11, 2009
	 * @param int $args[$width_original]
	 * @param int $args[$height_original]
	 * @param int $args[$width_desired]
	 * @param int $args[$height_desired]
	 * @param string|'area' $args[$resize_mode] optional
	 * @return array|false the array of new dimensions (and image info), or error
	 */
	function image_resize_dimensions ( $args ) {
		/*
		 * Changelog
		 *
		 * v8.1, November 11, 2009
		 * - Now uses trigger_error instead of outputting errors to screen
		 *
		 * v8, December 02, 2007
		 * - Cleaned by using math instead of logic
		 * - Restructured the code
		 * - Re-organised variable names
		 *
		 * v7, 20/07/2007
		 * - Cleaned
		 *
		 * v6,
		 * - Added cropping
		 *
		 * v5, 12/08/2006
		 * - Changed to use args
		 */
		
		/*
		The 'exact' resize mode, will resize things to the exact limit.
		If a width or height is 0, the appropriate value will be calculated by ratio
		Results with a 800x600 limit:
			*x*			->	800x600
		Results with a 0x600 limit:
			1280x1024	->	750x600
			1900x1200	->	950x600
			96x48		->	1200x600
			1000x500	->	1200x600
		Results with a 800x0 limit:
			1280x1024	->	800x640
			1900x1200	->	800x505
			96x48		->	800x400
			1000x500	->	800x400
		*/
		
		/*
		The 'area' resize mode, will resize things to fit within the area.
		If a width or height is 0, the appropriate value will be calculated by ratio
		Results with a 800x600 limit:
			1280x1024	->	750x600
			1900x1200	->	950x600	->  800x505
			96x48		->	96x48		no change
			1000x500	->	800x400 = '800x'.(800/100)*500
		Results with a 0x600 limit:
			1280x1024	->	750x600
			1900x1200	->	950x600
			96x48		->	96x48		no change
			1000x500	->	1000x500	no change
		Results with a 800x0 limit:
			1280x1024	->	800x640
			1900x1200	->	950x600	->	800x505
			96x48		->	96x48		no change
			1000x500	->	800x400	= '800x'.(800/1000)*500
		*/
		
		// ---------
		$image = $x_original = $y_original = $x_old = $y_old = $resize_mode = $width_original = $width_old = $width_desired = $width_new = $height_original = $height_old = $height_desired = $height_new = null;
		extract($args);
		
		// ---------
		if ( is_null($width_original) && !is_null($width_old) ) {
			$width_original = $width_old;
			$width_old = null;
		}
		if ( is_null($height_original) && !is_null($height_old) ) {
			$height_original = $height_old;
			$height_old = null;
		}
		
		//
		if ( is_null($width_original) && is_null($height_original) && !is_null($image) ) { // Get from image
			$image = image_read($image);
			$width_original = imagesx($image);
			$height_original = imagesy($image);
		}
		
		//
		if ( empty($width_original) || empty($height_original) ) { //
			trigger_error('no original dimensions specified', E_USER_WARNING);
			return false;
		}
		
		// ---------
		if ( is_null($width_desired) && !is_null($width_new) ) {
			$width_desired = $width_new;
			$width_new = null;
		}
		if ( is_null($height_desired) && !is_null($height_new) ) {
			$height_desired = $height_new;
			$height_new = null;
		}
		
		//
		if ( is_null($width_desired) || is_null($height_desired) ) { // Don't do any resizing
			trigger_error('no desired dimensions specified', E_USER_NOTICE);
			// return array( 'width' => $width_original, 'height' => $height_original );
		}
		
		// ---------
		if ( is_null($resize_mode) ) {
			$resize_mode = 'area';
		} elseif ( $resize_mode === 'none' ) { // Don't do any resizing
			trigger_error('$resize_mode === \'none\'', E_USER_NOTICE);
			// return array( 'width' => $width_original, 'height' => $height_original );
		} elseif ( !in_array($resize_mode, array('area', 'crop', 'exact', true)) ) { //
			trigger_error('Passed $resize_mode is not valid: ' . var_export(compact('resize_mode'), true), E_USER_WARNING);
			return false;
		}
		
		// ---------
		if ( is_null($x_original) && !is_null($x_old) ) {
			$x_original = $x_old;
			unset($x_old);
		}
		if ( is_null($y_original) && !is_null($y_old) ) {
			$y_original = $y_old;
			unset($y_old);
		}
		if ( is_null($x_original) )
			$x_original = 0;
		if ( is_null($y_original) )
			$y_original = 0;
			
		// ---------
		// Let's force integer values
		$width_original = intval($width_original);
		$height_original = intval($height_original);
		$width_desired = intval($width_desired);
		$height_desired = intval($height_desired);
		
		// ---------
		// Set proportions
		if ( $height_original !== 0 )
			$proportion_wh = $width_original / $height_original;
		if ( $width_original !== 0 )
			$proportion_hw = $height_original / $width_original;
		
		if ( $height_desired !== 0 )
			$proportion_wh_desired = $width_desired / $height_desired;
		if ( $width_desired !== 0 )
			$proportion_hw_desired = $height_desired / $width_desired;
			
		// ---------
		// Set cutoms
		$x_new = $x_original;
		$y_new = $y_original;
		$canvas_width = $canvas_height = null;
		
		// ---------
		$width_new = $width_original;
		$height_new = $height_original;
		
		// ---------
		// Do resize
		if ( $height_desired === 0 && $width_desired === 0 ) {
			// Nothing to do
		} elseif ( $height_desired === 0 && $width_desired !== 0 ) {
			// We don't care about the height
			$width_new = $width_desired;
			if ( $resize_mode !== 'exact' ) {
				// h = w*(h/w)
				$height_new = $width_desired * $proportion_hw;
			}
		} elseif ( $height_desired !== 0 && $width_desired === 0 ) {
			// We don't care about the width
			if ( $resize_mode !== 'exact' ) {
				 // w = h*(w/h)
				$width_new = $height_desired * $proportion_wh;
			}
			$height_new = $height_desired;
		} else {
			// We care about both

			if ( $resize_mode === 'exact' || /* no upscaling */ ($width_original <= $width_desired && $height_original <= $height_desired) ) { // Nothing to do
			} elseif ( $resize_mode === 'area' ) { // Proportion to fit inside
				

				// Pick which option
				if ( $proportion_wh <= $proportion_wh_desired ) { // Option 1: wh
					// Height would of overflowed
					$width_new = $height_desired * $proportion_wh; // w = h*(w/h)
					$height_new = $height_desired;
				} else // if ( $proportion_hw <= $proportion_hw_desired )
{ // Option 2: hw
					// Width would of overflowed
					$width_new = $width_desired;
					$height_new = $width_desired * $proportion_hw; // h = w*(h/w)
				}
			
			} elseif ( $resize_mode === 'crop' ) { // Proportion to occupy
				

				// Pick which option
				if ( $proportion_wh <= $proportion_wh_desired ) { // Option 2: hw
					// Height will overflow
					$width_new = $width_desired;
					$height_new = $width_desired * $proportion_hw; // h = w*(h/w)
					// Set custom
					$y_new = -($height_new - $height_desired) / 2;
				} else // if ( $proportion_hw <= $proportion_hw_desired )
{ // Option 1: hw
					// Width will overflow
					$width_new = $height_desired * $proportion_wh; // w = h*(w/h)
					$height_new = $height_desired;
					// Set custom
					$x_new = -($width_new - $width_desired) / 2;
				}
				
				// Set canvas
				$canvas_width = $width_desired;
				$canvas_height = $height_desired;
			
			}
		
		}
		
		// ---------
		// Set custom if they have not been set already
		if ( $canvas_width === null )
			$canvas_width = $width_new;
		if ( $canvas_height === null )
			$canvas_height = $height_new;
			
		// ---------
		// Compat
		$width_old = $width_original;
		$height_old = $height_original;
		$x_old = $x_original;
		$y_old = $y_original;
		
		// ---------
		// Return
		$return = compact('width_original', 'height_original', 'width_old', 'height_old', 'width_desired', 'height_desired', 'width_new', 'height_new', 'canvas_width', 'canvas_height', 'x_original', 'y_original', 'x_old', 'y_old', 'x_new', 'y_new');
		// echo '<--'; var_dump($return); echo '-->';
		return $return;
	}
}

if ( function_compare('image_dimensions', 1, true, __FILE__, __LINE__) ) {
	/**
	 * Gets a image's dimensions
	 * @version 1, November 11, 2009
	 */
	function image_dimensions ( $image_path ) {
		$image = image_read($image_path,false);
		if ( !$image ) return $image;
		$width = imagesx($image);
		$height = imagesy($image);
		$dimensions = compact('width','height');
		return $dimensions;
	}
}

if ( !function_exists('exif_imagetype') && function_compare('exif_imagetype', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Create the exif_imagetype if it does not already exist
	 * @version 1, April 21, 2008
	 * @author none
	 * @copyright none
	 * @license none
	 */
	function exif_imagetype ( $image_location ) {
		$image_size = getimagesize($image_location);
		if ( !$image_size )
			return $image_size;
		$image_type = $image_size[2];
		return $image_type;
	}
}

if ( !function_exists('image_type_to_extension') && function_compare('image_type_to_extension', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Create the image_type_to_extension if it does not already exist
	 * @version 1.1, November 11, 2009
	 * @since 1, July 20, 2007
	 */
	function image_type_to_extension ( $type, $include_dot = true ) {
		/*
		 * Changelog
		 * v1,  November 11, 2009
		 * - cleaned
		 * v1, July 20, 2007
		 * - created
		 */
		// Prepare
		global $IMAGE_CODE_TYPE, $IMAGE_TYPE_EXT;
		
		// Find extnesion
		if ( empty($IMAGE_TYPE_EXT[$type]) )
			return false;
		$ext = $IMAGE_TYPE_EXT[$type];
		
		// Present
		if ( $include_dot )
			$ext = '.' . $ext;
			
		// Done
		return $ext;
	}
}

if ( function_compare('define_image_vars', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Define the image vars: types, extensions, read/write types, and supported types.
	 * It is intended to be run once, any other runs are pointless.
	 * @version 1.1, November 11, 2009
	 * @since 1, July 20, 2007
	 */
	function define_image_vars ( ) {
		/*
		 * Changlog
		 * v1.1, November 11, 2009
		 * - Cleaned
		 */
		global $IMAGE_TYPE_EXTS, $IMAGE_TYPE_EXT, $IMAGE_TYPE_READFUNCTION, $IMAGE_TYPE_WRITEFUNCTION, $IMAGE_CODE_TYPE, $IMAGE_SUPPORTED_READ_TYPES, $IMAGE_SUPPORTED_READ_EXTENSIONS, $IMAGE_SUPPORTED_READ_TYPES_EXTENSIONS, $IMAGE_SUPPORTED_WRITE_TYPES, $IMAGE_SUPPORTED_WRITE_EXTENSIONS, $IMAGE_SUPPORTED_WRITE_TYPES_EXTENSIONS, $IMAGE_SUPPORTED_TYPES, $IMAGE_SUPPORTED_EXTENSIONS, $IMAGE_SUPPORTED_TYPES_EXTENSIONS;
		if ( isset($IMAGE_TYPE_EXTS) )
			return true;
		
		$IMAGE_TYPE_EXTS = array(IMAGETYPE_GIF => array('gif'), IMAGETYPE_JPEG => array('jpeg', 'jpg', 'jpe'), IMAGETYPE_JPEG2000 => array('jpeg', 'jpg', 'jpe'), IMAGETYPE_PNG => array('png'), IMAGETYPE_PSD => array('psd'), IMAGETYPE_BMP => array('bmp'), IMAGETYPE_TIFF_II => array('tiff'), IMAGETYPE_TIFF_MM => array('tiff'), IMAGETYPE_JPC => array('jpc'), IMAGETYPE_JP2 => array('jp2'), IMAGETYPE_JPX => array('jpx'), IMAGETYPE_JB2 => array('jb2'), IMAGETYPE_SWF => array('swf'), IMAGETYPE_SWC => array('swc'), IMAGETYPE_IFF => array('iff'), IMAGETYPE_WBMP => array('wbmp'), IMAGETYPE_XBM => array('xbm'));
		$IMAGE_TYPE_EXT = array();
		
		$IMAGE_SUPPORTED_READ_TYPES = array();
		$IMAGE_SUPPORTED_READ_EXTENSIONS = array();
		$IMAGE_SUPPORTED_READ_TYPES_EXTENSIONS = array();
		
		$IMAGE_SUPPORTED_WRITE_TYPES = array();
		$IMAGE_SUPPORTED_WRITE_EXTENSIONS = array();
		$IMAGE_SUPPORTED_WRITE_TYPES_EXTENSIONS = array();
		
		$IMAGE_SUPPORTED_TYPES = array();
		$IMAGE_SUPPORTED_EXTENSIONS = array();
		$IMAGE_SUPPORTED_TYPES_EXTENSIONS = array();
		
		$IMAGE_TYPE_READFUNCTION = array();
		$IMAGE_TYPE_WRITEFUNCTION = array();
		
		foreach ( $IMAGE_TYPE_EXTS as $image_type => $image_extensions ) {
			// Check type
			foreach ( $image_extensions as $image_extension ) {
				// Check read
				$image_read_function = 'imagecreatefrom' . $image_extension;
				$image_supported_read = function_exists($image_read_function);
				if ( $image_supported_read ) {
					// Supported
					$IMAGE_SUPPORTED_READ_TYPES[] = $image_type;
					$IMAGE_SUPPORTED_READ_EXTENSIONS += $image_extensions;
					$IMAGE_SUPPORTED_READ_TYPES_EXTENSIONS[$image_type] = $image_extensions;
				}
				// check write
				$image_write_function = 'image' . $image_extension;
				$image_supported_write = function_exists($image_write_function);
				if ( $image_supported_write ) {
					// Supported
					$IMAGE_SUPPORTED_WRITE_TYPES[] = $image_type;
					$IMAGE_SUPPORTED_WRITE_EXTENSIONS += $image_extensions;
					$IMAGE_SUPPORTED_WRITE_TYPES_EXTENSIONS[$image_type] = $image_extensions;
				}
				// Check overall
				if ( $image_supported_read && $image_supported_write ) {
					// Supported
					$IMAGE_TYPE_EXT[$image_type] = $image_extension;
					$IMAGE_SUPPORTED_TYPES[] = $image_type;
					$IMAGE_SUPPORTED_EXTENSIONS += $image_extensions;
					$IMAGE_SUPPORTED_TYPES_EXTENSIONS[$image_type] = $image_extensions;
					$IMAGE_TYPE_WRITEFUNCTION[$image_type] = $image_write_function;
					$IMAGE_TYPE_READFUNCTION[$image_type] = $image_read_function;
					// Done we work
					break;
				}
				// Continue
			}
		}
		
		// Done
		return true;
	}
	define_image_vars();
}

if ( function_compare('image_read_function', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Return the name of the function used to read the image based upon it's type.
	 * @version 2, November 11, 2009
	 * @since 1, July 20, 2007
	 * @param string $type
	 * @return string
	 */
	function image_read_function ( $type ) {
		global $IMAGE_TYPE_READFUNCTION;
		if ( empty($IMAGE_TYPE_READFUNCTION[$type]) )
			return false;
		return $IMAGE_TYPE_READFUNCTION[$type];
	}
}

if ( function_compare('image_write_function', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Return the name of the function used to write the image based upon it's $type.
	 * @version 2, November 11, 2009
	 * @since 1, July 20, 2007
	 * @param string $type
	 * @return string
	 */
	function image_write_function ( $type ) {
		global $IMAGE_TYPE_WRITEFUNCTION;
		if ( empty($IMAGE_TYPE_WRITEFUNCTION[$type]) )
			return false;
		return $IMAGE_TYPE_WRITEFUNCTION[$type];
	}
}


if ( function_compare('image_memory_adjust', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Adjusts the memory limits so that we can hopefully read the image
	 * @version 1, November 26, 2009
	 * @since 1, November 26, 2009
	 * @param string $type
	 * @return string
	 */
	function image_memory_adjust ( $filename ) {
		// http://www.php.net/manual/en/function.imagecreatefromjpeg.php#64155
		
		# Prepare
	    $imageInfo = getimagesize($filename);
	    $MB = pow(1024,2);		// number of bytes in 1M
	    $K64 = pow(2,16);		// number of bytes in 64K
	    $TWEAKFACTOR = 1.8;		// Or whatever works for you
	    
	    # Calculate
	    $pixels = $imageInfo[0]*$imageInfo[1];
	    $memory = $pixels * $imageInfo['bits'] * ($imageInfo['channels'] / 8) + $K64;
	    $memoryExtra = round($memory * $TWEAKFACTOR);
	    
	    # Limits
		$memoryHave = memory_get_usage();
	    $memoryLimitMB = intval(ini_get('memory_limit'));
	    if ( !$memoryLimitMB ) $memoryLimitMB = 8;
	    $memoryLimit = $memoryLimitMB * $MB;
	    
	    # Check
	    $memoryNeeded = $memoryHave+$memoryExtra;
	    if ( $memoryNeeded > $memoryLimit) {
	    	$memoryDifference = $memoryNeeded-$memoryLimit;
	    	$memoryLimitNewMB = $memoryLimitMB + ceil($memoryDifference/$MB);
	    	$memoryLimitNew = $memoryLimitNewMB * $MB;
			ini_set('memory_limit', $memoryLimitNewMB . 'M');
			var_dump(compact('memoryNeeded','memoryHave','memoryExtra','memoryDifference','memoryLimitNewMB', 'memoryLimitNew', 'memoryLimit', 'memoryLimitMB'));
			var_dump(intval(ini_get('memory_limit')));
			if ( intval(ini_get('memory_limit')) === $memoryLimitNewMB ) {
				return true; // Adjusted
			} else {
				return false; // Can't adjust
			}
	    } else {
	        return true; // No need to adjust
	    }
	}
	
}

if ( function_compare('image_read', 4.1, true, __FILE__, __LINE__) ) {

	/**
	 * Read a image location, string, or resource, and return the image resource, and optionally it's creation information as well.
	 * @version 4.1, November 11, 2009
	 * @since 4, July 20, 2007
	 * @param array $args
	 * @param boolean $return_info if true, then an array is returned containing the image resource coupled with creation information.
	 * @return resource|array
	 */
	function image_read ( $args, $return_info = true ) {
		/*
		 * Changelog
		 * Version 4.1, November 11, 2007
		 * - Cleaned
		 * Version 4, July 20, 2007
		 * - Cleaned
		 * Version 3, August 12, 2006
		 * - Changed it to use $args
		 */
		
		// Set defaults
		$image_type = $image = null;
		
		// Extract
		if ( gettype($args) === 'array' )
			extract($args);
		else
			$image = $args;
			
		// Check image
		if ( empty($image) ) {
			trigger_error('$image is empty: ' . var_export(compact('image', 'args'), true), E_USER_WARNING);
			return false;
		}
		
		// Get the type of the image
		$type = gettype($image);
		
		// Do stuff
		switch ( $type ) {
			case 'string' :
				// Try and create it
				$result = @imagecreatefromstring($image);
				if ( $result ) {
					 // The image is a binary string
					$image = $result;
				}
				// Check if it is a file
				elseif ( is_file($image) ) {
					// Get the image type
					$image_type = exif_imagetype($image);
					if ( !$image_type ) { // Error
						trigger_error('$image did not point to a valid image: ' . var_export(compact('image'), true), E_USER_WARNING);
						return false;
					}
					
					// Check if image is supported
					$image_read_function = image_read_function($image_type);
					if ( !$image_read_function ) { // Error
						trigger_error('Unsupported image type: ' . var_export(compact('image_type','image'), true), E_USER_WARNING);
						return false;
					}
					
					// Adjust memory for the image
					if ( !image_memory_adjust($image) ) { // Error
						trigger_error('Image requires more resources than those available: ' . var_export(compact('image'), true), E_USER_WARNING);
						return false;
					}
					
					// Read the image
					$image = call_user_func($image_read_function, $image);
					if ( !$image ) { // Error
						trigger_error('Failed to the read the image: ' . var_export(compact('image'), true), E_USER_WARNING);
						return false;
					}
					break;
				}
				
			case 'resource' :
				// Check if it is already a image
				if ( @imagesx($image) ) { // We have a valid image
					break;
				}
			
			default :
				// Error
				trigger_error('Could not determine the image: ' . var_export(compact('image', 'image_type'), true), E_USER_NOTICE);
				$image = false;
				break;
		}
		
		# Return
		if ( $return_info ) {
			if ( empty($image_type) ) {
				return compact('image');
			} else {
				return compact('image', 'image_type');
			}
		}
		
		# Return
		return $image;
	}
}

if ( function_compare('image_resize', 3, true, __FILE__, __LINE__) ) {

	/**
	 * Resize an image resource.
	 *
	 * @version 3, July 20, 2007
	 *
	 * @param array $args
	 * @return resource|false resized image resource, or error
	 */
	function image_resize ( $args ) { /*
		 * Changelog
		 *
		 * Version 3, July 20, 2007
		 * - Cleaned
		 *
		 * Version 2, August 12, 2006
		 * - Changed it to use $args
		 */
		
		// Set default variables
		$image = $width_old = $height_old = $width_new = $height_new = $canvas_width = $canvas_height = $canvas_size = null;
		
		$x_old = $y_old = $x_new = $y_new = 0;
		
		// Exract user
		extract($args);
		
		// Read image
		$image = image_read($image,false);
		if ( empty($image) ) { // error
			trigger_error('no image was specified', E_USER_WARNING);
			return false;
		}
		
		// Check new dimensions
		if ( empty($width_new) || empty($height_new) ) { // error
			trigger_error('Desired/new dimensions not found', E_USER_WARNING);
			return false;
		}
		
		// Do old dimensions
		if ( empty($width_old) && empty($height_old) ) { // Get the old dimensions from the image
			$width_old = imagesx($image);
			$height_old = imagesy($image);
		}
		
		// Do canvas dimensions
		if ( empty($canvas_width) && empty($canvas_height) ) { // Set default
			$canvas_width = $width_new;
			$canvas_height = $height_new;
		}
		
		// Let's force integer values
		$width_old = intval($width_old);
		$height_old = intval($height_old);
		$width_new = intval($width_new);
		$height_new = intval($height_new);
		$canvas_width = intval($canvas_width);
		$canvas_height = intval($canvas_height);
		
		// Create the new image
		$image_new = imagecreatetruecolor($canvas_width, $canvas_height);
		
		// Resample the image
		$image_result = imagecopyresampled(
			/* the new image */
			$image_new,
			/* the old image to update */
			$image,
			/* the new positions */
			$x_new, $y_new,
			/* the old positions */
			$x_old, $y_old,
			/* the new dimensions */
			$width_new, $height_new,
			/* the old dimensions */
			$width_old, $height_old);
		
		// Check
		if ( !$image_result ) { // ERROR
			trigger_error('the image failed to resample', E_USER_WARNING);
			return false;
		}
		
		// return
		return $image_new;
	}
}

if ( function_compare('image_write', 1.1, true, __FILE__, __LINE__) ) {

	/**
	 * Return the binary output of a image resource
	 * @version 1.1, November 11, 2009
	 * @since 1, July 20, 2007
	 * @param mixed $args[$image] verified by {@link read_image}
	 * @return string|false binary output, or error
	 * @todo needs a rewrite to it's flow is a logical stream
	 */
	function image_write ( $args, $return_info = true ) { /*
		 * Changelog
		 * Version 1.1, November 11, 2009
		 * - Cleaned
		 * Version 1, 20/07/2007
		 * - Created
		 */
		// Set default variables
		$image = $image_type = $location = null;
		
		$quality = 95;
		
		// Exract user
		extract($args);
		
		// If the image is a file
		if ( gettype($image) === 'string' && is_file($image) ) {
			// Get the image type
			$image_type = exif_imagetype($image);
			if ( !$image_type ) { // Error
				trigger_error('$image did not point to a valid image: ' . var_export(compact('image'), true), E_USER_WARNING);
				return false;
			}
			// Check Image
			$image = image_read($image, false);
		} else {
			// Check image type
			if ( empty($image_type) ) { // error
				trigger_error('No image_type was specified', E_USER_WARNING);
				return false;
			}
		}
		
		// Check image
		if ( empty($image) ) { // error
			trigger_error('No image was specified', E_USER_WARNING);
			return false;
		}
		
		// Check if image is supported
		$image_write_function = image_write_function($image_type);
		if ( !$image_write_function ) { // Error
			trigger_error('Unsupported image type: ' . var_export(compact('image_type'), true), E_USER_WARNING);
			return false;
		}
		
		// Read the image
		ob_start();
		$image = call_user_func($image_write_function, $image, $location, $quality);
		$result = ob_get_contents();
		$error = strstr($result, '</b>:');
		ob_end_clean();
		if ( !$image || $error ) { // Error
			trigger_error('Failed to the write the image: ' . var_export(compact('image','error'), true), E_USER_WARNING);
			return false;
		}
		
		# Swap
		$image = $result;
		
		# Return
		if ( $return_info ) {
			return compact('image', 'image_type');
		}
		
		# Return
		return $image;
	}
}

if ( function_compare('image_compress', 4.1, true, __FILE__, __LINE__) ) {

	/**
	 * Compress our image resource to a specified quality or acceptable file size.
	 * @version 4.1, November 11, 2009
	 * @since 4, July 20, 2007
	 * @param resource|string $args[$image] image filename {@link image_read}
	 * @param string $args[$image_type] optional if $image is a resource, then the type must be specified
	 * @param int|95 $args[$quality] optional quality to start on
	 * @param int|0 $args[$max_filesize] optional the max filesize the result must be
	 * @return string|false binary output, or error
	 * @todo needs a clean
	 */
	function image_compress ( $args, $return_info = false ) { /*
		 * Changelog
		 * Version 4.1, November 11, 2009
		 * - Cleaned
		 * Version 4, July 20, 2007
		 * - Cleaned
		 * Version 3, 12/08/2006
		 * - Changed it to use $args
		 * - Changed it to a 95% compression with a 5% decrease
		 */
		
		// Set default variables
		$image = $image_type = null;
		
		$quality = 95;
		$max_filesize = 0;
		
		$max_size = null;
		
		// Exract args
		if ( is_array($args) )
			extract($args);
		else {
			$image = $args;
		}
		
		// Read image + info
		$result = image_read($image, true);
		if ( !$result )
			return $result;
			
		// Update info
		extract($result);
		
		// Check Image
		if ( empty($image) ) { // error
			trigger_error('No image was specified', E_USER_WARNING);
			return false;
		}
	
		// Check Image
		if ( empty($image_type) ) { // error
			trigger_error('No image type was specified', E_USER_WARNING);
			return false;
		}
		
		// Max filesize
		if ( !empty($max_size) )
			$max_filesize = $max_size; // backwards compat
		

		// Do compression
		$location = null;
		$result = image_write(compact('image', 'location', 'quality', 'image_type', 'image_extension'),false);
		if ( !$result ) { // Writing the image failed
			trigger_error('Writing the image failed', E_USER_WARNING);
			return false;
		}
		
		if ( $max_filesize != 0 ) { // Max filesize is now bytes!
			while ( $quality >= 5 && strlen($result) /* current filesize */ > $max_filesize ) {
				$result = image_write(compact('image', 'location', 'quality', 'image_type', 'image_extension'),false);
				$quality -= 5;
			}
		}
		
		# Swap
		$image = $result;
		
		# Return
		if ( $return_info ) {
			return compact('image', 'image_type');
		}
		
		# Return
		return $image;
	}
}

if ( function_compare('image_remake', 9.2, true, __FILE__, __LINE__) ) {

	/**
	 * Remake our image to the way we want, it is a combination of all the other image functions.
	 *
	 * @version 9.2, November 11, 2009
	 * @since 9.1, December 02, 2007
	 *
	 * @param resource|string $args[$image] image filename {@link image_read}
	 * @param string $args[$image_type] optional if $image is a resource, then the type must be specified
	 *
	 * @param int $args[$width_new] optional {@link image_resize_dimensions}
	 * @param int $args[$height_new] optional {@link image_resize_dimensions}
	 * @param string $args[$resize_mode] optional {@link image_resize_dimensions}
	 *
	 * @param int|95 $args[$quality] optional quality to start on
	 * @param int|0 $args[$max_filesize] optional the max filesize the result must be
	 *
	 * @return string|false binary output, or error
	 * @todo needs a clean
	 */
	function image_remake ( $args ) { /*
		 * Changelog
		 *
		 * Version 9.2, November 11, 2009
		 * - Cleaned
		 *
		 * Version 9.1, December 02, 2007
		 * - Added error checking on write
		 *
		 * Version 9, July 20, 2007
		 * - Cleaned
		 *
		 * Version 8, August 12, 2006
		 * - Changed it to use $args
		 * - Changed it to have a 95% compression
		 */
		
		// Set default variables
		$image = $image_type = null;
		
		$width_new = $height_new = 0;
		$resize_mode = 'area';
		$quality = 95;
		$max_filesize = 0;
		$size = $width_old = $height_old = $max_size = null;
		
		// Exract args
		extract($args);
		
		// Read image + info
		$result = image_read($image, true);
		if ( !$result )
			return $result;
			
		// Update info
		extract($result);
		
		// Check Image
		if ( empty($image) ) { // error
			trigger_error('No image was specified', E_USER_WARNING);
			return false;
		}
		
		// Check new dimensions
		if ( !array_key_exists('width_new',$args) || !array_key_exists('height_new',$args) ) { // error
			trigger_error('Desired/new dimensions were not found', E_USER_WARNING);
			return false;
		}
		
		// Do old dimensions
		if ( empty($width_old) && empty($height_old) ) { // Get the old dimensions from the image
			$width_old = imagesx($image);
			$height_old = imagesy($image);
		}
		
		// Let's force integer values
		$width_old = intval($width_old);
		$height_old = intval($height_old);
		$width_new = intval($width_new);
		$height_new = intval($height_new);
		
		// Max filesize
		if ( !empty($size) )
			$max_filesize = $size; // backwards compat
		if ( !empty($max_size) )
			$max_filesize = $max_size; // backwards compat
		

		# REMAKE THE IMAGE
		

		// Get new resize stuff
		$result = image_resize_dimensions(compact('image', 'resize_mode', 'width_old', 'height_old', 'width_new', 'height_new'));
		if ( !$result )
			return $result;
			
		// Update variables
		extract($result);
		
		// Resize the image
		$image_resize = compact('image', 'width_old', 'height_old', 'width_new', 'height_new', 'canvas_width', 'canvas_height', 'x_old', 'y_old', 'x_new', 'y_new');
		$image = image_resize($image_resize);
		if ( !$image )
			return $image;
			
		// Compress the image
		$image_compress = compact('image', 'max_filesize', 'quality', 'image_type', 'image_extension');
		$image = image_compress($image_compress,false);
		if ( !$image )
			return $image;
			
		// Return the image
		return $image;
	}
}

?>