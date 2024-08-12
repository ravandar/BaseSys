<?php

namespace AliKhaleghi\BaseSys\Authentication\Activators;

use Config\Email;
use Myth\Auth\Entities\User;
use Myth\Auth\Authentication\Activators\ActivatorInterface;

/**
 * Class EmailActivator
 *
 * Sends an activation email to user.
 */
class SMSActivator extends BaseActivator implements ActivatorInterface
{
    /**
     * Sends an activation email
     *
     * @param User $user
     */
    public function send(?User $user = null): bool
    {
        $this->error = lang('Auth.errorSendingActivationSMS', [$user->phone]);
        return false;
    }
}
