<?php

namespace AliKhaleghi\BaseSys\Controllers\Authorization;

use CodeIgniter\Controller;
use CodeIgniter\Session\Session;
use Myth\Auth\Config\Auth as AuthConfig;
use Myth\Auth\Entities\User;
use CodeIgniter\API\ResponseTrait;

class RecoveryController extends Controller
{
    protected $auth;

    /**
     * @var AuthConfig
     */
    protected $config;

    use ResponseTrait;

    public function __construct()
    {

        $this->config = config('Auth');
        $this->auth   = service('authentication', 'jwt');
    }
    
    // ----------------------------------------------------------------------------

    /**
     * Attempt to Recover Password
     * 
     * @method POST
     * 
     * @param str   $via            Optional, default: email
     * @param str   $recover        Email or Phone number to recover password for.
     * 
     * @return Response
     */
    public function attemptForgot()
    {
        $resetters = service("settings")->get("JWT.activeResetters");

        if ((bool)service("settings")->get("JWT.resetPasswordDisabled")) {
            return $this->fail(lang('Auth.forgotDisabled'));   
        }
        
        $rules = [
            'type' => [
                'label' => lang('Auth.recovery_type'),
                // 'rules' => 'required|valid_email',
                'rules' => 'required',
            ],
        ];
        switch ($this->request->getVar("type")) {
            case 'sms':
                $rules ['auth'] = [
                    'label' => lang("Auth.phoneNumber"),
                    'rules' => 'required'
                ];
                break;
            case 'email':
                $rules ['auth'] = [
                    'label' => lang("Auth.email"),
                    'rules' => 'required'
                ];
                break;
        }
        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());  
        }

        // User Input
        $value = $this->request->getVar('auth');

        $users = model(\AliKhaleghi\BaseSys\Models\UserModel::class);
        
        $user = $users->where('email', $value)->first();

        $user = !$user ? $users->where('phone', $value)->first() : $user;

        if (null === $user) {
            return $this->fail(lang('Auth.forgotNoUser'));  
        }

        /**
         * In order to generate JWT Based reset hash we need
         * to convert Myth:Auth User to BaseSys User
         * @var User    $based User
         */
        $basedUser = new \AliKhaleghi\BaseSys\Entities\User;
        $basedUser->fill(json_decode(json_encode($user), true));

        $type = $this->request->getVar("via");

        // Generate new JWT based reset hash (6digit code) and save it
        $basedUser->generateResetHash();
        $users->skipValidation()->save($basedUser);

        // Send the message
        $resetter = service('JWTresetter', null, $type);
        $sent     = $resetter->send($basedUser);

        if (! $sent) {
            return $this->fail($resetter->error() ?? lang('Auth.unknownError'));  
        }

        $response = [
            'message'   => lang('Auth.forgotEmailSent'),
        ];

        return $this->respond($response , 200); 
    }
    
    // ----------------------------------------------------------------------------

    /**
     * Attempt to Password Reset
     * 
     * @method POST
     * 
     * @param str   $via            Optional, default: email
     * @param str   $recover        Email or Phone number to recover password for.
     * 
     * @return Response
     */
    public function attemptReset()
    {
        if ((bool)service("settings")->get("JWT.resetPasswordDisabled")) {
            return $this->fail(lang('Auth.forgotDisabled'));   
        }

        $users = model(UserModel::class);

        // First things first - log the reset attempt.
        $users->logResetAttempt(
            $this->request->getVar('recover'),
            $this->request->getVar('token'),
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent()
        );

        $rules = [
            'token'        => 'required',
            'recover'     => 'required',
            // 'email'        => 'required|valid_email',
            'password'     => 'required|strong_password',
            'pass_confirm' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());  
        }

        $user = $users->where('email', $this->request->getVar('recover'))
            ->where('reset_hash', $this->request->getVar('token'))
            ->first();

        $user = !$user ? $users
            ->where('reset_hash', $this->request->getVar('token'))
            ->where('phone', $this->request->getVar('recover'))
            ->first() : $user;

        if (null === $user) {
            return $this->fail(lang('JWT.forgotNoUser'));   
        }

        // Reset token still valid?
        if (! empty($user->reset_expires) && time() > $user->reset_expires->getTimestamp()) {
            return $this->fail(lang('JWT.resetTokenExpired'));   
        }

        // Success! Save the new password, and cleanup the reset hash.
        $user->password         = $this->request->getVar('password');
        $user->reset_hash       = null;
        $user->reset_at         = date('Y-m-d H:i:s');
        $user->reset_expires    = null;
        $user->force_pass_reset = false;
        $users->save($user);

        $response = [
            'message'   => lang('JWT.resetSuccess'),
        ];

        return $this->respond($response , 200); 
    }
}
