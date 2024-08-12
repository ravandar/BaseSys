<?php

namespace AliKhaleghi\BaseSys\Authentication\Resetters;

use Config\Email;
use Myth\Auth\Entities\User;
use Myth\Auth\Authentication\Resetters\ResetterInterface;

/**
 * Class EmailActivator
 *
 * Sends an activation email to user.
 */
class SMSResetter extends BaseResetter implements ResetterInterface
{
    /**
     * Sends an activation email
     *
     * @param User $user
     */
    public function send(?User $user = null): bool
    {
        $this->error = lang("JWT.errorSendingResetSMS");
        return false;
    }
}
