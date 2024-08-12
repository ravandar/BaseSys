<?php namespace AliKhaleghi\BaseSys;
/**
 * Serial Validation
 * 
 * PHP version 8
 *
 * @category CodeIgniter4
 * @package  AliKhaleghi\BaseSys
 * 
 * @author   Ali Khaleghi <awli.khaleghi@gmail.com>
 * @license  <Private>
 */
class SerialValidation{


	/**
	 * Base Sys Version
	 * 
	 * ? Requires Additional Work
	 * @var $serial
	 */
	public static $version = '1.2';

	// ---------------------------------------------------------------

	/**
	 * Serial Number
	 * 
	 * ? Requires Additional Work
	 * @var $serial
	 */
	private static $serial = '';

	// ---------------------------------------------------------------

	/**
	 * 
	 * 
	 * TODO: 	Locate Serial
	 * ? Check for local serial 
	 * @return null
	 */
	public function __contruct() {

	}

	// ---------------------------------------------------------------

	/**
	 * Static Method validated
	 * ? Whether or not this package has already been validated.
	 * 
	 * @param 	null
	 * @return 	(bool)
	 */
	public static function validated()
	{
		
	}

	// ---------------------------------------------------------------

	/**
	 * Locate the serial number
	 *	! Default serial is stored inside `./serial.pk` 
	 * 
	 * @param 	null
	 * @return	mixed
	 * TODO: 	Requires to look into the database for correct value
	 */
	private static function locateSerial()
	{
		$file = @file_get_contents("serial.pk");
		if($file) {
			return $file;
		}

		return null;
	} 
}