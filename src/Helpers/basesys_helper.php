<?php 
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
/**
 * Base Helper
 */
if(!function_exists("core_version")) {
    function core_version() {
        return 1;
    }
}

// --------------------------------------------

/**
 * Get Current User 
 * Based on JWT token
 */
if(!function_exists('jwt_user'))
{
    function jwt_user(){
        $Users = new \AliKhaleghi\BaseSys\Models\UserModel;

        $request = \Config\Services::request();
        // API Configurations
        // $config = config("Api");
        
        // get private key
        $key = service("settings")->get("JWT.PrivateKey");

        $headers = apache_request_headers();
        $token = @$headers['Authorization'] ?: @$headers['authorization'];

        // prevent error using @
        if(!empty($token)) $token = @explode(" ", $token)[1];
         
        // check if token is null or empty means invalid Bearer authorization header
        if(is_null($token) || empty($token)) {
            return false;
        }   
 
        $JWT = new JWT;
        $JWT::$leeway = service("settings")->get("JWT.Leeway");
        
        try {
            $decoded = $JWT::decode($token, new Key($key, 'HS256'));
            return $Users->where('id', $decoded->data)->first();
        }
        catch(\ExpiredException $e) {
            return false;
            // check for remember me tokenss
        }
        catch(\UnexpectedValueException $e){
            return false;
        }     
    }
}
// --------------------------------------------

/**
 * Validate Current Token
 */
if(!function_exists('validate_token'))
{
    function validate_token(){
        $Users = new \AliKhaleghi\BaseSys\Models\UserModel;

        $request = \Config\Services::request();
        // API Configurations
        // $config = config("Api");
        
        // get private key
        $key = service("settings")->get("JWT.PrivateKey");

        $headers = apache_request_headers();
        $token = @$headers['Authorization'] ?: @$headers['authorization'];

        // prevent error using @
        if(!empty($token)) $token = @explode(" ", $token)[1];
         
        // check if token is null or empty means invalid Bearer authorization header
        if(is_null($token) || empty($token)) {
            return false;
        }   
 
        $JWT = new JWT;
        $JWT::$leeway = service("settings")->get("JWT.Leeway");
        
        try {
            $decoded = $JWT::decode($token, new Key($key, 'HS256'));
            return (object)[
                'decoded'   => $decoded,
                'token'     => $token
            ];
        }
        catch(\ExpiredException $e) {
            return false;
            // check for remember me tokenss
        }
        catch(\UnexpectedValueException $e){
            return false;
        }     
    }
}