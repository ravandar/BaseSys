<?php namespace AliKhaleghi\BaseSys\Authentication;

use Myth\Auth\Config\Auth as AuthConfig;
use CodeIgniter\Router\Exceptions\RedirectException;
use AliKhaleghi\BaseSys\Entities\User as baseUser;
use Myth\Auth\Entities\User;
use Myth\Auth\Exceptions\AuthException;
use Myth\Auth\Password;
use Myth\Auth\Authentication\AuthenticatorInterface;
use Myth\Auth\Authentication\AuthenticationBase;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class JWTAuthenticator extends AuthenticationBase implements AuthenticatorInterface
{

    /**
     * @var AuthConfig
     */
    protected $config;

    /**
     * @var JWT Token
     */
    public $token;

    /**
     * @var JWT Token expiration timestamp
     */
    public $expire;
    
    

    public function __construct($config)
    {
        $this->config = $config;
        helper("basesys");
    }
    public function id() {
        return $this->user->id ?? null;
    }
    /**
     * Attempts to validate the credentials and log a user in.
     *
     * @param array $credentials
     * @param bool  $remember Should we remember the user (if enabled)
     *
     * @return bool
     */
    public function attempt(array $credentials, bool $remember = null): bool
    {
        $this->user = $this->validate($credentials, true);

        if (empty($this->user))
        {
            // Always record a login attempt, whether success or not.
            $ipAddress = service('request')->getIPAddress();
            $this->recordLoginAttempt($credentials['email'] ?? $credentials['phone'], $ipAddress, $this->user->id ?? null, false);

            $this->user = null;
            return false;
        }

        if ($this->user->isBanned())
        {
            // Always record a login attempt, whether success or not.
            $ipAddress = service('request')->getIPAddress();
            $this->recordLoginAttempt($credentials['email'] ?? $credentials['phone'], $ipAddress, $this->user->id ?? null, false);

            $this->error = lang('Auth.userIsBanned');

            $this->user = null;
            return false;
        }

        if(false) // if (! $this->user->isActivated())
        {
            // Always record a login attempt, whether success or not.
            $ipAddress = service('request')->getIPAddress();
            $this->recordLoginAttempt($credentials['email'] ?? $credentials['phone'], $ipAddress, $this->user->id ?? null, false);

            $param = http_build_query([
                'login' => urlencode($credentials['email'] ?? $credentials['phone'])
            ]);

            $this->error = lang('Auth.notActivated') .' '. anchor(route_to('resend-activate-account').'?'.$param, lang('Auth.activationResend'));

            $this->user = null;
            return false;
        }

        return $this->login($this->user, $remember);
    }


    /**
     * Checks to see if the user is logged in.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    { 

        $headers = apache_request_headers(); 
        $remember = @$headers['remember'];
        
        $user  = jwt_user();
        $this->rememberToken = $remember;
        $this->user = $user;
        return $user instanceof User;
    }
    
    /**
     * Checks to see if the user is logged in or not.
     *
     * @return bool
     */
    public function check(): bool
    {
        if ($this->isLoggedIn())
        {
            // Do we need to force the user to reset their password?
            if ($this->user && $this->user->force_pass_reset)
            {
                throw new RedirectException(route_to('reset-password') .'?token='.$this->user->reset_hash);
            }

            return true;
        }

        // Remember Me Token 
        $remember = $this->rememberToken;
        
        if (empty($remember))
        {
            return false;
        }
        
        [$selector, $validator] = explode(':', $remember);
        $validator = hash('sha256', $validator);

        $token = $this->loginModel->getRememberToken($selector);


        if (empty($token))
        {
            return false;
        }

        if (! hash_equals($token->hashedValidator, $validator))
        {
            return false;
        }

        // Yay! We were remembered!
        $user = $this->userModel->find($token->user_id);
        if (empty($user))
        {
            return false;
        }
        
        $basedUser = new \AliKhaleghi\BaseSys\Entities\User;
        $basedUser->fill(['id'=>$user->id, ...(array)$user]);
        // set current instance user
        $this->user = $basedUser;

        $this->login($user);

        // We only want our remember me tokens to be valid
        // for a single use.
        $this->remembered = $this->refreshRemember($user->id, $selector);

        return true;
    }

    /**
     * Sets a new validator for this user/selector. This allows
     * a one-time use of remember-me tokens, but still allows
     * a user to be remembered on multiple browsers/devices.
     *
     * @param int    $userID
     * @param string $selector
     */
    public function refreshRemember(int $userID, string $selector)
    {
        $existing = $this->loginModel->getRememberToken($selector);

        // No matching record? Shouldn't happen, but remember the user now.
        if (empty($existing))
        {
            return $this->rememberUser($userID);
        }

        // Update the validator in the database and the session
        $validator = bin2hex(random_bytes(20));

        $this->loginModel->updateRememberValidator($selector, $validator);


        return [
            'token'     => $selector.':'.$validator, 
            'expires'   => (new \DateTime)->modify('+' . config('Auth')->rememberLength . ' seconds')->format('Y-m-d H:i:s'), 
        ];
 
    }

    /**
     * Checks the user's credentials to see if they could authenticate.
     * Unlike `attempt()`, will not log the user into the system.
     *
     * @param array $credentials
     * @param bool  $returnUser
     *
     * @return bool|User
     */
    public function validate(array $credentials, bool $returnUser=false)
    {
        $this->userModel = new \AliKhaleghi\BaseSys\Models\UserModel;
        // Can't validate without a password.
        if (empty($credentials['password']) || count($credentials) < 2)
        {
            return false;
        }

        // Only allowed 1 additional credential other than password
        $password = $credentials['password'];
        unset($credentials['password']);

        if (count($credentials) > 1)
        {
            throw AuthException::forTooManyCredentials();
        }

        // Ensure that the fields are allowed validation fields
        if (! in_array(key($credentials), $this->config->validFields))
        {
            throw AuthException::forInvalidFields(key($credentials));
        }

        // Can we find a user with those credentials?
        $user = $this->userModel->where($credentials)->first();

        if (! $user)
        {
            $this->error = lang('Auth.badAttempt');
            return false;
        }
        // Now, try matching the passwords.
        if (! Password::verify($password, $user->password_hash))
        {
            $this->error = lang('Auth.invalidPassword');
            return false;
        }

        // Check to see if the password needs to be rehashed.
        // This would be due to the hash algorithm or hash
        // cost changing since the last time that a user
        // logged in.
        if (Password::needsRehash($user->password_hash, $this->config->hashAlgorithm))
        {
            $user->password = $password;
            $this->userModel->save($user);
        }

        return $returnUser
            ? $user
            : true;
    }

    /**
     * Generates a timing-attack safe remember me token
     * and stores the necessary info in the db and a cookie.
     *
     * @see https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
     *
     * @param int $userID
     *
     * @throws \Exception
     */
    public function rememberUser(int $userID)
    {
        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(20));
        $expires   = date('Y-m-d H:i:s', time() + $this->config->rememberLength);

        $token = $selector.':'.$validator;

        // Store it in the database
        $this->loginModel->rememberUser($userID, $selector, hash('sha256', $validator), $expires);
 

        return [
            'token'     => $token, 
            'expires'   => $expires, 
        ];
    }

    public function login(User $user = null, bool $remember = false): bool
    {
        $JWT = new JWT;
        $request = \Config\Services::request();

        // get private key
        $key = service("settings")->get("JWT.PrivateKey");

        $iat = time(); // current timestamp value
        
        $nbf = $iat;
        // Remember me will only extend to 3 days, otherwises 1 hour of access is granted 
        $exp = $iat + ($remember ? (86400 * 3) : 3600 );//;3600;

        $payload = array(
            "iss" => "nambartar.com",
            "aud" => "nambartar.ir",
            "iat" => $iat, // issued at
            "nbf" => $nbf, // not before in seconds
            "exp" => $exp, // expire time in seconds
            "data" => $user->id,
        );
        
        // Remember the user ??
        $this->remembered = $remember
                                    ? $this->rememberUser($user->id)
                                    : false;

        $token = $JWT::encode($payload, $key, 'HS256');

        $this->token = $token;
        $this->expire = $exp;

        $agent = $request->getUserAgent();

        if ($agent->isBrowser()) {
            $currentAgent = $agent->getBrowser() . ' ' . $agent->getVersion();
        } elseif ($agent->isRobot()) {
            $currentAgent = $agent->getRobot();
        } elseif ($agent->isMobile()) {
            $currentAgent = $agent->getMobile();
        } else {
            $currentAgent = 'ناشناس';
        }
        
        
        $data = [ 
            'agent' => $currentAgent,
            'platform'  => $agent->getPlatform()
        ];

        $baseUser = new baseUser;
        $baseUser->fill(json_decode(json_encode($user), true));

        // move some data around
        $baseUser->recordLoginSession($request->getIPAddress(), $token, $data );
        
        // trigger log in
        \CodeIgniter\Events\Events::trigger('jwt_logged', $user);
        
        return true;
    }

    /**
     * Logs a user out of the system.
     * TODO: Actually logout and deautorize token
     */
    public function logout()
    {

        // Handle user-specific tasks
        if ($user = jwt_user()) { 

            $this->loginModel->purgeRememberTokens($user->id);

            // Trigger logout event
            \CodeIgniter\Events\Events::trigger('logout', $user);

            $this->user = null;
            return true;
        }
        return false;
    }
}
