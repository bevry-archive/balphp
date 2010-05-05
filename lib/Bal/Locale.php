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
class Bal_Locale {
	
	# ========================
	# VARIABLES
	
	/** Zend_Locale Dependency */
	public $Zend_Locale;
	
	/** Zend_Translate Dependency */
	public $Zend_Translate;
	
	/** Zend_Currency Dependency */
	public $Zend_Currency;
	
	/** Session Dependency. Such that we can store which locale we should use. */
	public $Session;
	
	/** The timezone we are localed to */
	public $timezone = null;
	/** The date format we are localed to for datetimes */
	public $format_datetime = Zend_Date::DATETIME;
	/** The date format we are localed to for dates */
	public $format_date = Zend_Date::DATES;
	/** The date format we are localed to for times */
	public $format_time = Zend_Date::TIMES;
	
	/** Where our il8n file is located */
	protected $file;
	/** Which locale to use when our detailed locale fails */
	protected $map = array(
		'en' => 'en_GB',
		'ar' => 'ar_SD'
	);
	/** Where our il8n files are located */
	protected $il8n_path;
	
	# ========================
	# CONSTRUCTORS
	
	/**
	 * Setup our Locale
	 * @param string $locale	which locale to use
	 * @param string $currency	which currency to use
	 * @param string $timezone	which timezone to use
	 * @return
	 */
	public function __construct ( $locale = null, $currency = null, $timezone = null ) {
		# Prepare options in case we were sent an array
		if ( is_array($locale) ) {
			$localeConfig = $locale; $locale = null;
			if ( !empty($localeConfig['locale']) )		$locale   = $localeConfig['locale'];
			if ( !empty($localeConfig['currency']) )	$currency = $localeConfig['currency'];
			if ( !empty($localeConfig['timezone']) )	$timezone = $localeConfig['timezone'];
			unset($localeConfig);
		}
		
		# Prepare Dependencies
		$this->Session = new Zend_Session_Namespace('Application');
		$this->Zend_Locale = new Zend_Locale();
		$this->il8n_path = IL8N_PATH;
		
		# Detect the Locale
		if ( $this->Session->locale ) {
			Zend_Locale::setDefault($this->Session->locale);
		} elseif ( $locale !== null ) {
			Zend_Locale::setDefault($locale);
		}
		
		# Check the Locale exists
		$file = null;
		$locales = Zend_Locale::getOrder(Zend_Locale::ZFDEFAULT);
		foreach ( $locales as $locale => $weight ) {
			# Check Locale
			if ( $this->setLocale($locale) ) {
				break;
			}
		}
		
		# Apply the Locale Preference
		$this->Session->locale = $locale = $this->getFullLocale();
		
		# Apply the Zend_Locale Dependency
	    Zend_Registry::set('Zend_Locale', $this->Zend_Locale);
		
		# Apply the Zend_Translate Dependency
	    $this->Zend_Translate = new Zend_Translate('array', $this->file, $locale);
	    Zend_Registry::set('Zend_Translate', $this->Zend_Translate);
		
		# Apply the Zend_Currency Dependency
	    $this->Zend_Currency = new Zend_Currency($this->getFullLocale(), $currency);
	    Zend_Registry::set('Zend_Currency', $this->Zend_Currency);
		
		# Apply the Locale to the Registry
	    Zend_Registry::set('Locale', $this);
		
		# Apply the Formats
		$this->format_date = strclean(Zend_Locale_Format::getDateFormat($locale));
		$this->format_time = strclean(Zend_Locale_Format::getTimeFormat($locale));
		$this->format_datetime = strclean(Zend_Locale_Format::getDateTimeFormat($locale));
		
		# Apply the Timezone
		if ( $timezone ) $this->timezone = $timezone;
		
		# Chain
		return $this;
	}
	
	/**
	 * Get the Locale Instance
	 * @return Bal_Locale
	 */
	public static function getInstance ( ) {
		return Zend_Registry::get('Locale');
	}
	
	# ========================
	# LOCALES
	
	/**
	 * Clear our Locale Preference
	 * @return Bal_Locale
	 */
	public function clearLocale() {
		# Clear
		$this->Session->locale = null;
		
		# Chain
		return $this;
	}
	
	/**
	 * Set our Locale Preference
	 * @param string $locale
	 * @return boolean
	 */
	public function setLocale($locale){
		# Check Locale
        $language = explode('_', $locale); $language = $language[0];
		if ( ($file = $this->hasFile($locale)) || ($file = $this->hasFile($language)) ) {
			$this->file = $file;
			$this->Zend_Locale->setLocale($locale);
			Zend_Locale::setDefault($locale);
			$this->Session->locale = $locale;
			return true;
		}
		# Done
		return false;
	}
	
	/**
	 * Get a detailed locale from a simple locale
	 * @param string $locale
	 * @return string
	 */
	public function getFullLocale($locale = null){
		# Default
		if ( $locale === null ) $locale = $this->getLocale();
		
		# Check if already detailed
		if ( strpos($locale, '_') !== false ) return $locale;
		
		# Return detailed
		return $this->map[$locale];
	}
	
	/**
	 * Get the current locale
	 * @return string
	 */
	public function getLocale(){
		return $this->Zend_Locale->toString();
	}
	
	/**
	 * Get the current language
	 * @return string
	 */
	public function getLanguage(){
		return $this->Zend_Locale->getLanguage();
	}
	
	/**
	 * Get the current region
	 * @return string
	 */
	public function getRegion(){
		return $this->Zend_Locale->getRegion();
	}
	
	/**
	 * Get the file for a locale
	 * @param string $locale [optional]
	 * @return string
	 */
	public function getFile ( $locale = null ) {
		# Default
		if ( $locale === null ) $locale = $this->getLocale();
		
		# Determine
		$file = $this->il8n_path . DIRECTORY_SEPARATOR . $locale . '.php';
		
		# Return
		return $file;
	}
	
	/**
	 * Does the file exist for a locale
	 * @param string $locale [optional]
	 * @return boolean
	 */
	public function hasFile ( $locale = null ) {
		# Default
		if ( $locale === null ) $locale = $this->getLocale();
		
		# Determine
		$file = $this->getFile($locale);
		$file = file_exists($file) ? $file : false;
		
		# Return
		return $file;
	}
	
	/**
	 * Are we of this locale or language?
	 * @param string $locale
	 * @return boolean
	 */
	public function is ( $locale ) {
		return $locale === $this->Zend_Locale->toString() || $locale === $this->Zend_Locale->getLanguage();
	}

	# ========================
	# CURRENCY
	
	/**
	 * Get the current currency symbol
	 * @return string
	 */
	public function currencySymbol ( ) {
		return $this->Zend_Currency->getSymbol();
	}
	
	/**
	 * Get the amount within a currency format
	 * @param mixed $amount
	 * @return string
	 */
	public function currency ( $amount ) {
		$amount = floatval($amount);
		return $this->Zend_Currency->toCurrency($amount);
	}
	
	# ========================
	# TRANSLATION
	
	/**
	 * Get the translation list
	 * Refer to the Zend_Locale documentation for more
	 * http://files.zend.com/help/Zend-Framework/zend.locale.functions.html
	 * @return mixed
	 */
	public function translationList ( $type ) {
		return $this->Zend_Locale->getTranslationList($type);
	}
	
	/**
	 * Get a translation of a particular type
	 * Refer to the Zend_Locale documentation for more
	 * http://files.zend.com/help/Zend-Framework/zend.locale.functions.html
	 * @return mixed
	 */
	public function translation ( $text, $type, $locale = null ) {
		return $this->Zend_Locale->getTranslation($text, $type, $locale);
	}
	
	/**
	 * Get the list of supported languages determined from the files in our il8n path
	 * @return array
	 */
	public function languages ( ) {
		$files = scan_dir($this->il8n_path);
		$languages = array();
		foreach ( $files as $file ) {
			$language = substr($file, 0, strrpos($file, '.'));
			$languages[$language] = $this->language($language);
		}
		return $languages;
	}
	
	/**
	 * Translate a language name
	 * @param string $lang
	 * @return string
	 */
	public function language ( $lang ) {
		return $this->translation($lang, 'language');
	}
	
	
	/**
	 * Translate some text with default
	 * @param string $text
	 * @param array $info
	 * @param string $default
	 * @return string
	 */
	public function translate_default ( $text, array $info = array(), $default = null ) {
		$translation = $this->translate($text, $info);
		if ( $translation === $text && $default !== null ) {
			$translation = $default;
		}
		return $translation;
	}
	
	/**
	 * Translate some text
	 * @param string $text
	 * @param mixed ...
	 * @return string
	 */
	public function translate ( ) {
		# Prepare Arrguments
		$numargs = func_num_args();
		$args = func_get_args();
		$data = array();
		
		# Check Arg TYpes
		if ( $numargs === 2 && is_array($args[1]) ) {
			# We were passed the data as the second argument
			$text = $args[0];
			$data = $args[1];
			if ( is_object($data) ) {
				$data = $data->toArray();
			}
			
			# Translate the text
			$text = $this->Zend_Translate->_($text);
			
			# Insert the arguments
			$text = populate($text, $data);
		}
		elseif ( $numargs === 1 && is_array($args[0]) ) {
			# We were passed the data with the translation text
			$text = $args[0][0];
			$data = $args[0];
			
			# Recurse correctly
			$text = $this->translate($text, $data);
		}
		else {
			# We were passed the data as arguments
			$data = $args;
			$text = array_shift($data);
			
			# Recurse correctly
			$text = $this->translate($text, $data);
		}
		
		# Run it through an advanced translation
		if ( false )
		$text = preg_replace(
			'/([^\\\\])?\\{([^\\}]+)\\}/ie',
			'preg_unescape("${1}") . $this->_translate_advanced(preg_unescape("${2}"))',
			$text
		);
		
		# Return translation
    	return $text;
	}
	
	/**
	 * Perform an advanced series of translations on the text
	 * @throws Bal_Exception
	 * @param string $test - will look like "currency:2.00,N/A" or "currency:,N/A"
	 * @return string
	 */
	protected function _translate_advanced ( $text ) {
		# Prepare
		$result = '';
		
		# Find everything upto a unescaped comma, several times
		$matches = array();
		$matches_n = preg_match_all('/([^\\\\]+),?/i', $text, $matches); // find blocks seperated by a unescaped comma
		
		baldump($text, $matches);
		# Check
		if ( $matches_n === 0 ) {
			throw new Bal_Exception('Translate advanced did not find what it was looking for..');
		}
		
		# Cycle
		foreach ( $matches as $match ) {
			# Prepare
			$match_str = $match[1]; // the stuff between the ()
			
			# Find the translator if it exists - will look like "currency:2.00" or "2.00" or "Eg\\: Hello"
			$parts = array();
			$parts_n = preg_match('/^[^\\\\]+:?/i', $match, $parts); // find the first block before a unescaped :
			
			# Check
			if ( $parts_n === 0 ) {
				throw new Bal_Exception('Translate advanced did not find what it was looking for..');
			}
	
			# Fetch the translator
			$translator = 'translate';
			if ( count($parts) === 3 ) {
				$translator = $parts[1];
				$match = $parts[2];
			}
			
			# Apply the translator
			$result = $this->$translator($match);
		}
		
		# Return result
		return $result;
	}
	
	# ========================
	# DATETIMES
	
	
	/**
	 * Get a Zend_Date object according to a timestamp and timezone
	 * @param timestamp $timestamp
	 * @param timezone $timezone
	 * @return Zend_Date
	 */
	public function getDate ($timestamp = null, $timezone = null) {
		# Ensure Timestamp
		if ( $timestamp ) $timestamp = ensure_timestamp($timestamp);
		
		# Get the Date for Timestamp
		$Date = new Zend_Date($timestamp);
		
		# Adjust the Timezone
		if ( $timezone ) {
			$Date->setTimezone($timezone);
		} elseif ( $this->timezone ) {
			$Date->setTimezone($this->timezone);
		}
		
		# Return the Date
		return $Date;
	}
	
	/**
	 * Get the translation of a month name
	 * @param string $month
	 * @return string
	 */
	public function month ( $month ) {
		# Prepare the Date
		$Date = $this->getDate();
		
		# Set the Month
		$Date->setMonth($month);
		
		# Get the Month
		$month = $Date->get(Zend_Date::MONTH_NAME);
		
		# Return the Month
		return $month;
	}
	
	/**
	 * Get the translation for a timestamp
	 * @param timestamp $timestamp
	 * @param string $format_datetime
	 * @param string $locale
	 * @return string
	 */
	public function timestamp ( $timestamp, $format_datetime = null, $locale = null ) {
		# Alias for Datetime
		return $this->datetime($timestamp,$format_datetime,$locale);
	}
	
	/**
	 * Get the translation for a datetime
	 * @param timestamp $timestamp
	 * @param string $format_datetime
	 * @param string $locale
	 * @return string
	 */
	public function datetime ( $timestamp, $format_datetime = null, $locale = null ) {
		# Get the Date for the Timestamp
		$Date = $this->getDate($timestamp);
		
		# Prepare format
		if ( $format_datetime === null ) $format_datetime = $this->format_datetime;
		
		# Translate according to format
		$datetime = $Date->get($format_datetime, $locale);
		
		# Return translation
		return $datetime;
	}
	
	/**
	 * Get the translation for a date and time
	 * @param timestamp $timestamp
	 * @param string $format_date
	 * @param string $format_time
	 * @param string $locale
	 * @return string
	 */
	public function dateandtime ( $timestamp, $format_date = null, $format_time = null, $locale = null ) {
		return $this->date($timestamp, $format_date, $locale).' '.$this->date($timestamp, $format_time, $locale);
	}
	
	/**
	 * Get the translation for a date
	 * @param timestamp $timestamp
	 * @param string $format_date
	 * @param string $locale
	 * @return string
	 */
	public function date ( $timestamp, $format_date = null, $locale = null ) {
		$Date = $this->getDate($timestamp);
		if ( $format_date === null ) $format_date = $this->format_date;
		return $Date->get($format_date, $locale);
	}
	
	/**
	 * Get the translation for a time
	 * @param timestamp $timestamp
	 * @param string $format_time
	 * @param string $locale
	 * @return string
	 */
	public function time ( $timestamp, $format_time = null, $locale = null ) {
		$Date = $this->getDate($timestamp);
		if ( $format_time === null ) $format_time = $this->format_time;
		return $Date->get($format_time, $locale);
	}
	
	/**
	 * Get the translation for a timestamp in timeago format
	 * @param timestamp $timestamp
	 * @return string
	 */
	public function timeago ( $timestamp ) {
		# http://www.php.net/manual/en/function.time.php#89415
	    $periods	= array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
	    $lengths	= array(60,60,24,7,4.35,12,10);
	    $now		= time();
	    $timestamp	= strtotime($timestamp);
	    # is it future date or past date
	    if($now > $timestamp) {
	        $difference	= $now - $timestamp;
	        $tense		= 'ago';
	       
	    } else {
	        $difference	= $timestamp - $now;
	        $tense		= 'from now';
	    }
	    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
	        $difference /= $lengths[$j];
	    }
	    $difference = round($difference);
	    if($difference != 1) {
	    	$periods[$j].= 's';
	    }
	    return $this->translate('time-ago', $difference, $periods[$j], $tense);
	}
	
	# ========================
	# FILES
	
	/**
	 * Converts a filesize from bytes to human and translate into the locale
	 * @version 2, July 13, 2009
	 * @since 1.2, April 27, 2008
	 * @param int	$filesize						in bytes
	 * @param int	$round_up_after [optional]		round up after this value, so with 0.1 it turns 110 KB into 0.11 MB
	 * @return string Eg. "5.0 MB"
	 */
	public function filesize ( $filesize, $round_up_after = 0.100, $round_after = 2 ) {
		# Define our file size levels
		$levels = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		
		# Perform upsclaing
		$level = 0; $size = $filesize;
		while ( ($new_size = $size / 1024) >= $round_up_after ) {
			$size = $new_size;
			++$level;
		} $filesize = strval($size);
		
		# Format
		if ( $filesize >= $round_after ) $filesize = round($filesize);
		$determined_size = $this->number($filesize, array('precision' => 2));
		$determined_level = $levels[$level];
		
		# Translate
		return $this->translate('%s '.$determined_level, $this->number($determined_size));
	}
	
	# ========================
	# TYPES
	# http://files.zend.com/help/Zend-Framework/zend.locale.functions.html
	
	/**
	 * Translate a number value
	 * @param number $number
	 * @param array $options
	 * @return string
	 */
	public function number ( $number, $options = array() ) {
		if ( $number === null || $number === '' ) return $number;
		return Zend_Locale_Format::getNumber($number, $options);
	}
	
	/**
	 * Translate a integer
	 * @param number $number
	 * @param array $options
	 * @return string
	 */
	public function integer ( $number, $options = array() ) {
		if ( $number === null || $number === '' ) return $number;
		$number = intval($number);
		return $this->number($number,$options);
	}
	
	/**
	 * Translate a decimal
	 * @param number $number
	 * @param array $options
	 * @return string
	 */
	public function decimal ( $number ) {
		$result = Zend_Locale_Format::toNumber($number, array(
			'precision' => 2,
			'number_format' => $this->translation(null, 'DecimalNumber', $this->getLocale()),
			'locale' => $this->getLocale()
		));
		return $result;
	}
	
	/**
	 * Translate a percent
	 * @param number $number
	 * @param array $options
	 * @return string
	 */
	public function percent ( $number ) {
		$result = Zend_Locale_Format::toNumber($number, array(
			'precision' => 2,
			'number_format' => $this->translation(null, 'PercentNumber', $this->getLocale()),
			'locale' => $this->getLocale()
		));
		return $result;
	}
	
	/**
	 * Translate a decimal percent
	 * @param number $number
	 * @param array $options
	 * @return string
	 */
	public function percentdecimal ( $number) {
		return $this->percent($number*100);
	}
	
	/**
	 * Translate a float
	 * @param number $number
	 * @param array $options
	 * @return string
	 */
	public function float ( $number, $options = array()  ) {
		return $this->number($number,$options);
	}
	
	/**
	 * Translate a string
	 * @param number $number
	 * @param array $options
	 * @return string
	 */
	public function string ( ) {
		$args = func_get_args();
		return call_user_func_array(array($this,'translate'), $args);
	}
}
