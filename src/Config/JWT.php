<?php
namespace AliKhaleghi\BaseSys\Config;
use CodeIgniter\Config\BaseConfig; 

class JWT extends BaseConfig
{

    /**
     * --------------------------------------------------------------------
     * JWT Validation Leeway
     * --------------------------------------------------------------------
     * 
     * You can add a leeway to account for when there is a clock skew times between
     * the signing and verifying servers. It is recommended that this leeway should
     * not be bigger than a few minutes.
     *
     * ? Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
     *
     * @var int
     */
    public $Leeway = 60;

    /**
     * --------------------------------------------------------------------
     * JSON Web Token Private Token
     * --------------------------------------------------------------------
     *
     * A Secure string to encode tokens with.
     *
     * @var string
     */
    public $PrivateKey = "MyVeryPrivateAndSafeCode";

    /**
     * --------------------------------------------------------------------
     * Logout on IP Change
     * --------------------------------------------------------------------
     *
     * Keep the session strictly valid only for the logged IP Address
     *
     * @var bool
     */
    public $IPLocked = TRUE;

    /**
     * --------------------------------------------------------------------
     * Allowed JSON Web Tokens
     * --------------------------------------------------------------------
     *
     * Number of sessions a user can log in with
     *
     * @var bool
     */
    public $AllowedTokens = 1;

    /**
	 * --------------------------------------------------------------------
	 * Reset Time
	 * --------------------------------------------------------------------
	 *
	 * The amount of time that a password reset-token is valid for,
	 * in seconds.
	 *
	 * @var int
	 */
	public $resetTime = 3600;
    
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
    public ?string $requireActivation = 'AliKhaleghi\BaseSys\Authentication\Activators\EmailActivator';

	/**
	 * --------------------------------------------------------------------
	 * Allow Password Reset or not 
	 * --------------------------------------------------------------------
	 *
	 * When FALSE, users will have the option to reset their password
	 * via the specified Resetter.
	 *
	 * @var bool|null Name of the ResetterInterface class
	 */
	public ?bool $resetPasswordDisabled = FALSE;

	/**
	 * --------------------------------------------------------------------
	 * Allow Password Reset 
	 * --------------------------------------------------------------------
	 *
	 * When enabled, users will have the option to reset their password
	 * via the specified Resetter. Default setting is email.
	 *
	 * @var string|null Name of the ResetterInterface class
	 */
	public ?string $emailResetter = 'AliKhaleghi\BaseSys\Authentication\Resetters\EmailResetter';

	/**
	 * --------------------------------------------------------------------
	 * Allow Password Reset 
	 * --------------------------------------------------------------------
	 *
	 * When enabled, users will have the option to reset their password
	 * via the specified Resetter. Default setting is email.
	 *
	 * @var string|null Name of the ResetterInterface class
	 */
	public ?string $SMSResetter = 'AliKhaleghi\BaseSys\Authentication\Resetters\SMSResetter';

	/**
	 * --------------------------------------------------------------------
	 * Activator classes
	 * --------------------------------------------------------------------
	 *
	 * Available activators with config settings
	 *
	 * @var array
	 */
	public array $userActivators = [
		'AliKhaleghi\BaseSys\Authentication\Activators\EmailActivator' => [
			'fromEmail' => null,
			'fromName' => null,
		],
		'AliKhaleghi\BaseSys\Authentication\Activators\SMSActivator' => [
            // No Config Required
		],
	];

	/**
	 * --------------------------------------------------------------------
	 * Resetter Classes
	 * --------------------------------------------------------------------
	 *
	 * Available resetters with config settings
	 *
	 * @var array
	 */
	public array $userResetters = [
		'AliKhaleghi\BaseSys\Authentication\Resetters\EmailResetter' => [
			'fromEmail' => null,
			'fromName' => null,
		],
		'AliKhaleghi\BaseSys\Authentication\Resetters\SMSResetter' => [
			'token' 			=> null,
			'outgoing_number' 	=> null,
		],
	];
}