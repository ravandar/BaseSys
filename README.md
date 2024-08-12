# Myth:Auth

Flexible, Powerful, Secure API package for CodeIgniter 4.

## Project Notice

Things are supposed to work on CI4.2+

## Requirements

- PHP 8.1+
- CodeIgniter 4.2+

## Features

Authentication/Authorization

- Login/Register/Account Recovery
- User Panel
-- Dashboard
-- User Profile
--- Account Details Modification
--- Password Modification
--- Contact Info Modification

- Admin Panel
-- Dashboard
-- User Management
--- User Modification
--- User Group Management
---- User Group Add/Delete


## Installation

Installation is best done via Composer. Assuming Composer is installed globally, you may use
the following command: 
```shell
    > composer require AliKhaleghi/BaseSys
```
This will add the latest stable release of **AliKhaleghi/BaseSys** as a module to your project.

Ensure your database is setup correctly, then run the Auth migrations: 
```shell
    > php spark migrate -all  
```

# Manual Labor

You will need to add some more configuration to get things working.
Open or Create the file `/app/config/Auth.php`.
In here we will need to add JSON Web Token library to Myth/Auth Authentication configuration.
find the variable `$authenticationLibs` and add the following element to the array.
`'jwt' => 'AliKhaleghi\BaseSys\Authentication\JWTAuthenticator', // JSON Web Token Authenticator Class`
`
/**
    * --------------------------------------------------------------------
    * Libraries
    * --------------------------------------------------------------------
    *
    * @var array
    */
public $authenticationLibs = [
    'local' => 'Myth\Auth\Authentication\LocalAuthenticator',
    'jwt' => 'AliKhaleghi\BaseSys\Authentication\JWTAuthenticator', // JSON Web Token Authenticator Class
];
`

## Filters

Please note that in order to keep the API End Points secure you will need to implant the following code
to your `App\Config\Filters.php` 
```php
public array $filters = [
    'jwt_logged_in' => ['before' => ['api/client/*']],
    'jwt_admin' => ['before' => ['api/client/*']],
];
```

## Registration Requirements
In order to get any sort of activation before allowing new users to log in,
you will need tou update your `App\Config\Auth.php` configuration as follows:
```php
/**
 * --------------------------------------------------------------------
 * Require Confirmation Registration via Email
 * --------------------------------------------------------------------
 *
 * When enabled, every registered user will receive an email message
 * with an activation link to confirm the account.
 *
 *  Validation Via Email: AliKhaleghi\BaseSys\Authentication\Activators\EmailActivator
 *  Validation Via SMS: AliKhaleghi\BaseSys\Authentication\Activators\SMSActivator
 *
 * @var string|null Name of the ActivatorInterface class
 */
public $requireActivation = 'AliKhaleghi\BaseSys\Authentication\Activators\EmailActivator';
```
if above value is not set yet registration requires activation, `EmailActivator` will be used as default.