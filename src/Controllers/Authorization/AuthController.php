<?php

namespace AliKhaleghi\BaseSys\Controllers\Authorization;

use CodeIgniter\Controller;
use CodeIgniter\Session\Session;
use Myth\Auth\Config\Auth as AuthConfig;
use AliKhaleghi\BaseSys\Entities\User;

use CodeIgniter\API\ResponseTrait;

class AuthController extends Controller
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

    //--------------------------------------------------------------------
    // Login/out
    //--------------------------------------------------------------------

    public function verify()
    {
        $valid = validate_token();
        if(!$valid)
        return $this->fail([] , 409);
        $response = [
            'token' => @$valid->token
        ];
        return $this->respond($response, 200);
    }
    /**
     * Displays the login form, or redirects
     * the user to their destination/home if
     * they are already logged in.
     */
    public function login()
    {

		$rules = [
			'email'	=> 'valid_email|required',
			'password' => 'required',
		];
        if($this->request->getGet("phone")) {
            
			$rules['phone_number']	= [
                'label' => 'شماره تلفن',
                'rules' => [
                    'required'
                ]
            ];
        }
		if ($this->config->validFields == ['email'])
		{
			$rules['phone_number'] .= '|valid_email';
		}

		if (! $this->validate($rules))
		{
            $response = [
                'OK'     => false,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response , 409);
		}

		$email = $this->request->getVar('email');
		$password = $this->request->getVar('password');
		$remember = (bool)$this->request->getVar('remember');

		// Determine credential type
		$type = 'email';

		// Try to log them in...
		if (! $this->auth->attempt([$type => $email, 'password' => $password], $remember))
		{
            $response = [
                'OK'     => false,
                'error'  => $this->auth->error() ?? lang('Auth.badAttempt'),
                'message' => 'Invalid Information',[$type => $email, 'password' => $password]
            ];
            return $this->fail($response , 409);
		}

		// Is the user being forced to reset their password?
		if ($this->auth->user()->force_pass_reset === true)
		{

            // Try to log them in...
            if (! $this->auth->attempt([$type => $email, 'password' => $password], $remember))
            {
                $response = [
                    'OK'     => true,
                    'message'=> 'نیاز به ثبت گذرواژه جدید',
                    'redirect' => route_to('reset-password') .'?token='. $this->auth->user()->reset_hash
                ];
                return $this->respond($response , 200);
            }
		} 
        $response = [
            'user'          => $this->auth->user()->publicInfo(false, true),
            "token"         => $this->auth->token,
            "remembered"    => $this->auth->remembered,
            "token_type"    => "bearer",
            "expires_in"    => $this->auth->expire 
        ];
        return $this->respond($response, 200);
    }
 
    /**
     * Log the user out.
     */
    public function logout()
    {
        if ($this->auth->check()) {
            if($this->auth->logout()) {
                
                $response = [
                    'message'=> 'شما با موفقیت از حساب خود خارج شدید.',
                    'redirect' => base_url('authentication/login') 
                ];
                return $this->respond($response , 200);
            }
        }
        return $this->fail('درخواست شما قابل اجرا نمی باشد');
    } 
    //--------------------------------------------------------------------
    // Register
    //--------------------------------------------------------------------

    /**
     * Displays the user registration page.
     */
    public function register()
    { 
        // check if already logged in.
        if ($this->auth->check()) {
            // return redirect()->back();
        }

        // Check if registration is allowed
        if (! $this->config->allowRegistration) {
            // return redirect()->back()->withInput()->with('error', lang('Auth.registerDisabled'));
        }

        return $this->fail(1, 200);
        // return $this->_render($this->config->views['register'], ['config' => $this->config]);
    }

    /**
     * Attempt to register a new user.
     */
    public function attemptRegister()
    {
        // Check if registration is allowed
        if (! $this->config->allowRegistration) {
            return $this->fail(lang('JWT.registerDisabled')); 
        }

        $users = model(\AliKhaleghi\BaseSys\Models\UserModel::class);

        // Validate basics first since some password rules rely on these fields
        $rules = config('Validation')->registrationRules ?? [
            'username' => 'required|alpha_numeric_space|min_length[3]|max_length[30]|is_unique[users.username]',
            'email'    => 'required|valid_email|is_unique[users.email]',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());  
        }

        // Validate passwords since they can only be validated properly here
        $rules = [
            'password'     => 'required|strong_password',
            'pass_confirm' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return $this->fail($this->validator->getErrors());  
        }

        // Save the user
        $allowedPostFields = array_merge(['password'], $this->config->validFields, $this->config->personalFields);
        $user              = new User($this->request->getVar($allowedPostFields));


        service("settings")->get("JWT.requireActivation") === null ?
            $user->activate() // Activate Account
        :
            $user->generateActivateHash(); // Generate Activation Code

        // Ensure default group gets assigned if set
        if (! empty($this->config->defaultUserGroup)) {
            $users = $users->withGroup($this->config->defaultUserGroup);
        }

        if (! $users->save($user)) {
            return $this->fail($users->errors());  
        }

        if (service("settings")->get("JWT.requireActivation") !== null) {
            //
            $activator = service('JWTactivator');
            
            // Send Activation Email or SMS
            $sent      = $activator->send($user);

            if (! $sent) {
                return $this->fail(($activator->error() ?? lang('Auth.unknownError')));
            }

            $response = [
                'message'   => lang('Auth.activationSuccess'),
                'redirect'  => base_url('authentication/login') 
            ];
            return $this->respond($response , 200);
            // Success! 
        }

        $response = [
            'message'   => lang('Auth.registerSuccess'),
            'redirect'  => base_url('authentication/login') 
        ];
        return $this->respond($response , 200);
    }
}
