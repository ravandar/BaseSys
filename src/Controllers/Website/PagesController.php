<?php

namespace AliKhaleghi\BaseSys\Controllers\Website;

use CodeIgniter\Controller;
use CodeIgniter\Session\Session;
use Myth\Auth\Config\Auth as AuthConfig;
use Myth\Auth\Entities\User;
use CodeIgniter\API\ResponseTrait;

class PagesController extends Controller
{
    protected $auth;

    /**
     * @var AuthConfig
     */
    protected $config;

    use ResponseTrait;

    public function __construct()
    {
    }
    
    // ----------------------------------------------------------------------------

    /**
     * Attempt to Recover Password
     * 
     * @method GET
     * 
     * @param str   $via            Optional, default: email
     * @param str   $recover        Email or Phone number to recover password for.
     * 
     * @return Response
     */
    public function getConfig()
    {
        $section = $this->request->getGet("section");
        sleep(2);
        $response = [
            'data' => [
                'title'         => service("settings")->get("Website.title", $section),
                'description'   => service("settings")->get("Website.description", $section),
                'keywords'      => service("settings")->get("Website.keywords", $section),
            ]
        ];

        return $this->respond($response , 200); 
    }
    
}
