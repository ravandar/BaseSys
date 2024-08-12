<?php

namespace AliKhaleghi\BaseSys\Config;

use CodeIgniter\Router\RouteCollection;
use AliKhaleghi\BaseSys\Config\Api as ApiConfig;

/** @var ApiConfig $endpointGroups */
try {
    $endpointGroups = service("settings")->get("Api.endpointGroups");
} catch(\CodeIgniter\Database\Exceptions\DatabaseException $e) {
    
    // defaults
    $endpointGroups = [
        'admin'     => 'w/admin',
        'client'    => 'w/client',
        'auth'      => 'authorization/api',
    ];
} catch (\Throwable $th) {
    
}
/** @var RouteCollection $routes */
// var_dump($endpointGroups['auth']);
// AliKhaleghi:BaseSys routes file.
$routes->group($endpointGroups['auth'], ['namespace' => 'AliKhaleghi\BaseSys\Controllers'], static function ($routes) {
    // Load the reserved routes from Api.php
    $config         = config(ApiConfig::class);
    $reservedRoutes = $config->reservedRoutes;

    $routes->add('basesys', 'ApiController::baseTest');

    // var_dump(new \AliKhaleghi\BaseSys\Controllers\ApiController);
    // Login/out
    $routes->get($reservedRoutes['login'], 'Authorization\AuthController::login');
    $routes->post('verify_token', 'Authorization\AuthController::verify');
    $routes->post($reservedRoutes['login'], 'Authorization\AuthController::login');
    $routes->get($reservedRoutes['logout'], 'Authorization\AuthController::logout');

    // Registration
    $routes->add($reservedRoutes['register'], 'Authorization\AuthController::register');
    $routes->post($reservedRoutes['register'], 'Authorization\AuthController::attemptRegister');

    // Activation
    $routes->get($reservedRoutes['activate-account'], 'ApiController::activateAccount', ['as' => $reservedRoutes['activate-account']]);
    $routes->get($reservedRoutes['resend-activate-account'], 'ApiController::resendActivateAccount', ['as' => $reservedRoutes['resend-activate-account']]);

    // Forgot/Resets
    // $routes->get($reservedRoutes['forgot'], 'Authorization\AuthController', ['as' => $reservedRoutes['forgot']]);

    $routes->post($reservedRoutes['forgot'], 'Authorization\RecoveryController::attemptForgot'); 
    $routes->post($reservedRoutes['reset-password'], 'Authorization\RecoveryController::attemptReset');
    
});

// AliKhaleghi:BaseSys client-side routes file.
$routes->group($endpointGroups['client'], [ ], static function ($routes) {

    $routes->group('user', [
        // BaseSys Controllers
        'namespace' => 'AliKhaleghi\BaseSys\Controllers',
        // User Only routes
        'filter'    => 'jwt_logged_in'
    ], static function ($routes) {
        
        $routes->get('info', 'ClientController::getUserData');
        $routes->get('logins', 'ClientController::getLoginRecords');
        
    });

    $routes->group('website', [
        // BaseSys Controllers
        'namespace' => 'AliKhaleghi\BaseSys\Controllers\Website', 
    ], static function ($routes) {
        $routes->get('pages/config', 'PagesController::getConfig');
    });
});

// AliKhaleghi:BaseSys routes file.
$routes->group($endpointGroups['admin'], [ 'filter' => 'jwt_permission:superadmin'], static function ($routes) {

    // ------------------------------------------

    /**
     * System Related Routes
     * 
     * @method mixed
     * @access  superadmin
     */
    $routes->group('system', [
        'namespace' => '\AliKhaleghi\BaseSys\Controllers\Admin',
        'filter' => 'jwt_permission:superadmin'
    ], static function ($routes) {
        
        $routes->get('config', 'System::getConfig');
        $routes->put('config', 'System::setConfig');
        $routes->delete('config', 'System::deleteConfig');
    });

    // ------------------------------------------

    /**
     * File Management Routes
     * 
     * @method mixed
     * @access  superadmin, content-manager
     */
    $routes->group('files', [
        'namespace' => '\AliKhaleghi\BaseSys\Controllers\Admin',
        'filter' => 'jwt_permission:superadmin'
    ], static function ($routes) {
        
        $routes->get("get", "Files::getFile");
        $routes->post("save", "Files::saveFile");
        $routes->post("upload", "Files::uploadFile");
        $routes->post("delete", "Files::deleteFile");
    }); 

    // ------------------------------------------

    /**
     * User Management Routes
     * 
     * @method mixed
     * @access  superadmin, user-manager
     */
    $routes->group('user', [
        'namespace' => '\AliKhaleghi\BaseSys\Controllers\Admin',
        'filter' => 'jwt_permission:superadmin'
    ], static function ($routes) {
        
        $routes->get('list', 'Users::getUsers');
        $routes->get('permissions', 'Groups::getUserPermissions');
        $routes->get('get', 'Users::getUser');
        $routes->post('modify', 'Users::modifyUser');
        $routes->delete('delete', 'Groups::deleteUserGroup');
    });

    // ------------------------------------------

    /**
     * User Group/Permission Management Routes
     * 
     * @method mixed
     * @access  superadmin
     */
    $routes->group('user/group', [
        'namespace' => '\AliKhaleghi\BaseSys\Controllers\Admin',
        'filter' => 'jwt_permission:superadmin'
    ], static function ($routes) {
        
        $routes->get('permission/list', 'Groups::getUserPermissions');
        $routes->put('permission/save', 'Groups::addPermission');
        $routes->get('list', 'Groups::getUserGroups');
        $routes->put('save', 'Groups::saveUserGroup');
        $routes->delete('delete', 'Groups::deleteUserGroup');
    });
});