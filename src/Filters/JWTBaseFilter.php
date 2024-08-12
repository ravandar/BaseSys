<?php

namespace AliKhaleghi\BaseSys\Filters;

use AliKhaleghi\BaseSys\Config\Auth as AuthConfig;

abstract class JWTBaseFilter
{
    /**
     * Landing Route
     */
    protected $landingRoute;

    /**
     * Reserved Routes
     */
    protected $reservedRoutes;

    /**
     * Authenticate
     */
    protected $authenticate;

    /**
     * Authorize
     */
    protected $authorize;

    /**
     * Constructor
     */
    public function __construct()
    {

        // Load the Auth config, for constructor only!!!
        $config = config(ApiConfig::class);

        // Load the routes

        // Load the authenticate service
        $this->authenticate = service('authentication', 'jwt');


        // Load the authorize service
        $this->authorize = service('authorization');
        
        // Load the helper
        if (! function_exists('logged_in')) {
            helper('auth');
        }
    }
    

    /**
     * Authorization Failed
     */
    protected function failed(int $code = 401) {
        
        $response = service('response');
        $response->setStatusCode($code);

        return $response; 
    }
}
