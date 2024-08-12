<?php
namespace AliKhaleghi\BaseSys\Controllers\Admin;
use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;
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
class System extends Controller
{
	use ResponseTrait;

	public function __construct()
	{

	} 

	// ---------------------------------------------------------------------------

	/**
	 * Get Config
	 * 
	 * @method  PUT
	 * @param   (str)     $key      Config Class.Key to delete
	 * @param   (str)     $value    New Value
	 * 
	 * @return Response
	 */
	public function getConfig() {
		$context	= $this->request->getVar("context");
		$key		= $this->request->getGet("key");
		try {
			return $this->respond([
				'status'    => 'Ok',
				'result'    => [
					'value'  => service('settings')->get($key, $context),
				]
			], 200);
		} catch (\Throwable $th) {
			return $this->fail($th->getMessage());
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * Set Config 
	 * 
	 * @method  PUT
	 * @param   (str)     $key      Config Class.Key to delete
	 * @param   (str)     $value    New Value
	 * 
	 * @return Response
	 */
	public function setConfig() {
		$context	= $this->request->getVar("context");
		$key		= $this->request->getVar("key");
		$value		= $this->request->getVar("value");

		if($key && $value) {
			
			try {
				service('settings')->set($key, ($value === 'false' ? false : ( $value === 'true' ? true : $value)), $context);
				return $this->respond([
					'status'    => 'Ok',
					'result'    => [
						'value'  => service('settings')->get($key, $context),
					]
				], 200);
			} catch (\Throwable $th) {
				return $this->fail($th->getMessage());
			}
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * Delete Config 
	 * 
	 * @method  PUT
	 * @param   (str)     $key      Config Class.Key to delete
	 * 
	 * @return Response
	 */
	public function deleteConfig() {
		$key  = $this->request->getVar("key");

		try {
			service('settings')->forget($key);
			return $this->respond([
				'status'    => 'Ok'
			], 200);
		} catch (\Throwable $th) {
			return $this->fail($th->getMessage());
		}
	}
}
