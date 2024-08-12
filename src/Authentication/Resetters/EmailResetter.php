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
class EmailResetter extends BaseResetter implements ResetterInterface
{
    /**
     * Sends an activation email
     *
     * @param User $user
     */
    public function send(?User $user = null): bool
    {
        $email  = service('email');
        $config = new Email();

        $settings = $this->getResetterSettings();

        $sent = $email->setFrom($settings->fromEmail ?? $config->fromEmail, $settings->fromName ?? $config->fromName)
            ->setTo($user->email)
            ->setSubject(lang('JWT.forgotSubject'))
            ->setMessage(lang('JWT.forgotEmail',[ $user->activate_hash]))
            ->setMailType('html')
        ->send();

        if (! $sent) {
            $this->error = lang('JWT.errorEmailSent', [$user->email]);

            return false;
        }

        return true;
    }
}
