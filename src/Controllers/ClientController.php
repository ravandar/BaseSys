<?php
namespace AliKhaleghi\BaseSys\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;


class ClientController extends Controller
{
    use ResponseTrait;

    protected $user;

    protected $auth;

    /**
     * @var ApiConfig
     */
    protected $config;

    public function __construct()
    {
        helper("basesys");
    }

    // -------------------------------------------------------------

    /**
     * Get User Data
     * 
     * @method GET
     * 
     * @return (Response)
     */
    public function getUserData() {
        $user = jwt_user();
        if($user instanceof \AliKhaleghi\BaseSys\Entities\User) {
            
            $response = [
                'status' => 200,
                'message' => 'Successful Request',
                'user'  => $user->publicInfo(false, true)
            ];
            return $this->respond($response, 200);
        }
        $response = [
            'OK'     => false,
            'message' => 'خطا'
        ];
        return $this->fail($response , 401); // logout the user
    }

    // -------------------------------------------------------------

    /**
     * Get Login Records
     * 
     * @method GET
     * 
     * @return (Response)
     */
    public function getLoginRecords() {
        $user = jwt_user();
        if($user instanceof \AliKhaleghi\BaseSys\Entities\User) {
            
            $response = [
                'status'    => 200, 
                'records'   => $user->getMeta("LoginSession", TRUE)
            ];
            return $this->respond($response, 200);
        }
        $response = [
            'OK'     => false,
            'message' => 'خطا'
        ];
        return $this->fail($response , 401); // logout the user
        
    }


    // -------------------------------------------------------------

    /**
     * Get Balance for current User
     * 
     * @method GET
     * 
     * @return (Response)
     */
    public function getBalance() {
        if(!$this->user) return $this->userNotSet();
    }

    // -------------------------------------------------------------

    /**
     * Get Balance for current User
     * 
     * @method POST
     * 
     * @return (Response)
     */
    public function getTransactions() {
        
    }

    // -------------------------------------------------------------

    /**
     * Request Checkout
     * 
     * @method POST
     * 
     * @return (Response)
     */
    public function requestCheckout() {
        
    }
}
