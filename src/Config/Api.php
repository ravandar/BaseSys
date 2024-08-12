<?php

namespace AliKhaleghi\BaseSys\Config;

use CodeIgniter\Config\BaseConfig;

class Api extends BaseConfig
{

    /**
     * --------------------------------------------------------------------
     * Automate Routing
     * --------------------------------------------------------------------
     *
     * Route Automation works on available Controllers, it will treats your Controller directory as API Controller
     * and will create routes to them through /api/[controller]/[method]?[query]&[query1]
     *
     * @var bool
     */
    public $routeAutomation = FALSE;

    /**
     * --------------------------------------------------------------------
     * Base of Admin and Client API End Points
     * --------------------------------------------------------------------
     * 
     *  path to all of your incoming requests.
     *  Links to routes would be base_url(Api->$baseRoutes['admin'].'/*')
     *
     * @var array
     */
    public $endpointGroups = [
        'admin'     => 'w/admin',
        'client'    => 'w/client',
        'auth'      => 'authorization/api',
    ];
    
    public const AdminEndpointFilters = [
        'before' => [
            'w/admin/*'
        ]
    ];
    
    public const ClientEndpointFilters = [
        'before' => [
            'w/client/*'
        ]
    ];

    /**
     * --------------------------------------------------------------------
     * Reserved Routes
     * --------------------------------------------------------------------
     *
     * 
     *
     * @var bool
     */
    public $reservedRoutes = [
        'login'                   => 'login',
        'logout'                  => 'logout',
        'register'                => 'register',
        'activate-account'        => 'activate-account',
        'resend-activate-account' => 'resend-activate-account',
        'forgot'                  => 'forgot',
        'reset-password'          => 'reset-password',
    ];
}
