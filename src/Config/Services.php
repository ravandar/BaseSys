<?php

namespace AliKhaleghi\BaseSys\Config;

use CodeIgniter\Model;
use Config\Services as BaseService;

use Myth\Auth\Authentication\Activators\ActivatorInterface;
use Myth\Auth\Authentication\Activators\UserActivator;

use Myth\Auth\Authentication\Passwords\PasswordValidator;

use Myth\Auth\Authentication\Resetters\EmailResetter;

use Myth\Auth\Authentication\Resetters\ResetterInterface;
use Myth\Auth\Authorization\FlatAuthorization;
use Myth\Auth\Authorization\GroupModel;
use Myth\Auth\Authorization\PermissionModel;
use Myth\Auth\Config\Auth as AuthConfig;
use Myth\Auth\Models\LoginModel;
use Myth\Auth\Models\UserModel;

class Services extends BaseService
{
    /**
     * Returns an instance of the JWT Based Activator.
     */
    public static function JWTactivator(?AuthConfig $config = null, ?string $type = null, bool $getShared = true): ActivatorInterface
    {
        if ($getShared) {
            return self::getSharedInstance('JWTactivator', $config, $type);
        }

        $config ??= config(AuthConfig::class);
        $class = service("settings")->get("JWT.requireActivation") ?? UserActivator::class;
        /** @var class-string<ActivatorInterface> $class */
        return new $class($config);
    }

    /**
     * Returns an instance of the JWT Based Resetter.
     */
    public static function JWTresetter(?AuthConfig $config = null, ?string $type = null, bool $getShared = true): ResetterInterface
    {
        if ($getShared) {
            return self::getSharedInstance('JWTresetter', $config, $type);
        }
        
        $config ??= config(AuthConfig::class);
        
        switch ($type) {
            case 'sms':
                $class = service("settings")->get("JWT.SMSResetter");
                break;
            
            case 'email':
            default:
                $class = service("settings")->get("JWT.emailResetter");
                break;
        }

        /** @var class-string<ResetterInterface> $class */
        return new $class($config);
    }
}
