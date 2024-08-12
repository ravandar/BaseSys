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
class EmailActivator extends BaseActivator implements ActivatorInterface
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

        $settings = $this->getActivatorSettings();

        $sent = $email->setFrom($settings->fromEmail ?? $config->fromEmail, $settings->fromName ?? $config->fromName)
            ->setTo($user->email)
            ->setSubject(lang('JWT.activationSubject'))
            ->setMessage(lang('JWT.activationEmail',[ $user->activate_hash]))
            // ->setMessage(view($this->config->views['emailActivation'], ['hash' => $user->activate_hash]))
            ->setMailType('html')
            ->send();

        if (! $sent) {
            $this->error = lang('Auth.errorSendingActivation', [$user->email]);

            return false;
        }

        return true;
    }
}
