<?php namespace AliKhaleghi\BaseSys\Entities;
use Morilog\Jalali\Jalalian;
use Myth\Auth\Entities\User as MythUser;

use Myth\Auth\Authorization\GroupModel;
use Myth\Auth\Authorization\PermissionModel;

class User extends MythUser
{
    /**
     * Default attributes.
     * @var array
     */
    protected $attributes = [
    	'firstname' => 'کاربر',
    	'lastname'  => 'مهمان',
    ];
	public $fullname;
	
	/**
	 * Returns a full name: "first last"
	 *
	 * @return string
	 */
	public function getName()
	{
		if($this->attributes['firstname'] && $this->attributes['lastname'])
		return trim(trim($this->attributes['firstname']) . ' ' . trim($this->attributes['lastname']));
	}

    /**
     * Generates a secure hash to use for password reset purposes,
     * saves it to the instance.
     *
     * @throws Exception
     *
     * @return $this
     */
    public function generateResetHash()
    {
        $this->attributes['reset_hash']		= random_int(100000, 999999);
        $this->attributes['reset_expires']	= date('Y-m-d H:i:s', time() + service("settings")->get('Auth.resetTime'));

        return $this;
    }

    /**
     * Generates a secure random hash to use for account activation.
     *
     * @throws Exception
     *
     * @return $this
     */
    public function generateActivateHash()
    {
        $this->attributes['activate_hash'] = random_int(100000, 999999);

        return $this;
    }

	/**
	 * Get details about this user
	 * 
	 * @param (bool)	$subtractPersonal 	By Default remove personal data from output, but can also return sensitive data.
	 */
	public function publicInfo(bool $subtractPersonal = true, $deleteCache = false){
		 
		$avatar = $this->id ? $this->getMeta('avatar') : null;


		if($avatar)
			$avatar = base_url($avatar);
		else
			$avatar = base_url('public/storage/adv.png');
		
		$created = Jalalian::forge(($this->created_at), new \DateTimeZone('Asia/Tehran'));

		$userId = $this->id;

		// delete cache when user is modified
        if($deleteCache) cache()->delete("{$userId}_public_info");

        if (null === $found = cache("{$userId}_public_info")) {

			$roles = $this->id == 0 ? [] : (array)$this->getRoles();

			$found = (Object)[
				'id'			=> $this->id,
				'fullname'		=> $this->getName(),
				'avatar' 		=> $avatar,
				'firstname' 	=> $this->firstname,
				'lastname' 		=> $this->lastname,
				'username' 		=> $this->username,
				'phone' 		=> $this->phone,
				'email' 		=> $this->email,
				'code_melli' 	=> $this->code_melli,
				'active' 		=> $this->active,
				'scope' 		=> $roles ? $roles : [],
				'permissions'	=> $this->id == 0 ? [] : $this->getPermissionList(),
				'level'			=> $this->id == 0 ? [] : $this->getAccessLevel(),
				'created_at'	=> $this->created_at ? [
					'date'		=> \CodeIgniter\I18n\Time::createFromInstance($this->created_at, 'en_US')->toDateTimeString(),
					'jalali'	=> $created->format("Y-m-d H:i:s"),
					'ago'		=> $created->ago(),  
				] : $this->created_at,
				'meta'			=> $this->id == 0 ? [] : [
					'phone_activated'	=> $this->getMeta("phone_activated") == 'yes' ? true : false,
					'bank_info'			=> @json_decode($this->getMeta("bank_info")),
				]
			];
	
			if($subtractPersonal)
			{
				$found->email = null;
				$found->phone = null;
				$found->code_melli = null;
				$found->scope = null;
				$found->level = null;
				$found->meta = null; 
			}

            cache()->save("{$userId}_public_info", $found, 300);
        }
		return (array)$found;
	} 
	
    /**
     * Gets all permissions for a user in a way that can be
     * easily used to check against:
     *
     * @return array<int, string> An array in format permissionId => permissionName
     */
    public function getPermissionList(): array
    {
        if (empty($this->id)) {
            throw new RuntimeException('Users must be created before getting roles.');
        }
		
		$list = $this->getPermissions();

		$found = [];
		
		$perms = new PermissionModel;

		foreach ($list as $key => $perm)
		{
			$found[$key] = $perms->select('name, description')->find($key);	
		}

        return $found;
    }

	/**
	 * Try to find Meta Data related to user access level
	 * 
	 * @return (int)		Access Level from 0 (= basic) to pre-defined number of levels
	 */
	public function getAccessLevel() {

		// Available levels
		$levels = null;
		
		// current user level
		$status = $this->getMeta("access_level");

		$level = 0;

		if($this->active == 1 && $this->getMeta("phone_activated") == 'yes') {
			$level = 1;
		}

		if($level == 1 && ( $this->getMeta("has_valid_cart_melli") ) )
		{
			$level = 2;
		}

		if($level == 2 && ( $this->getMeta("has_valid_passport") )) {
			$level = 3;
		}
		return $level;
	}
	
	/**
	 * Add a referral user to current user
	 */
	public function referredBy($refferByUser) {
		
		if(!$this->getMeta("reffered_by")) {
			return $this->addMeta("reffered_by", $refferByUser->id);
		}
		
		return false;
	}
	
	/**
	 * Get User Wallet Credit
	 */
	public function getWalletCredit() {
		return model("WalletModel")->getWalletCredit($this->id);
	}

	// ----------------------------------------------------
	
	/**
	 * Set User Avatar
	 * 
	 * @param string	$avatar		File to Upload
	 * 
	 * @return boolean
	 */
	public function setAvatar($avatar)
	{
		// Error? 
		if(!$this->id) throw new \Exception("Cannot set avatar without User ID", 1);

		$newName = $avatar->getRandomName();

		/**
		 * ! Path to /*public* /storage must be variable through settings
		 */
		$moved = $avatar->move(FCPATH . '/storage/avatars/', $newName);
		if($moved) {
			if($exists = $this->getMeta("avatar")) {
				$actualPath = @explode("/public", $exists)[1];

				if(file_exists(FCPATH. $actualPath)) {
					unlink(FCPATH. $actualPath);
				}
			}
	
			$path = FCPATH . 'storage/avatars/'. $newName;
			
			// !! Also `public` must be variable 
			$p = '/public/storage/avatars'. explode("storage/avatars", $path)[1];

			$this->addMeta("avatar", $p);
		}
		else
			throw new \Exception("Could not upload the file.", 1);
			
	} 

	// ----------------------------------------------------
	
	/**
	 * Record a login Session
	 * 
	 * @param string	$IP		Login IP Address
	 * @param string  	$JWT 	JSON Web Token
	 * @param array 	$agent 	User Agent Data
	 * 
	 * @return boolean
	 */
	public function recordLoginSession(string $IP,
		string $JWT,
		array $agent)
	{
		// Get the sessions list
		$sessions = $this->getMeta("LoginSession", TRUE) ?: (Object)[ 'tokens'=> [] ];
		// if(is_array($sessions)) $sessions = (array)$sessions;
		$manager = new \AliKhaleghi\BaseSys\Authentication\JWTManager($sessions->tokens);
		
		$manager->add([
			'ip' 	=> $IP,
			'token' => $JWT,
			'agent' => $agent,
			'created_at' => date("Y-m-d H:i:s"),
		]);

		$sessions->tokens = $manager->getSessions();
		// Save Changes
		return $this->addMeta("LoginSession", json_encode($sessions));
	} 

	// ----------------------------------------------------
	
	/**
	 * Recover User Password
	 * 
	 * @param string	$type 			sms,email
	 * @param string	$code 			Validation Token Code
	 * 
	 * @return (mixed)
	 */
	public function requestRecovery(string $type, string $code) {

		// delete previous attempts
		$this->deleteMeta("recovery");

		$recovery = [
			'type'		=> $type,
			'code'		=> $code,
			'validated'	=> 'no',
			'time'		=> time()
		];

		if($this->addMeta('recovery', json_encode($recovery))) {

			return true;
		}

		return false;
	}

	// ----------------------------------------------------
	
	/**
	 * Recover User Password
	 * 
	 * @param string	$code 			Validation Token Code
	 * 
	 * @return (mixed)
	 */
	public function validateRecoverToken($type, $code) {

		// get original attempt
		$recover = json_decode($this->getMeta("recovery"));

		if($recover) {
			if($recover->type == $type && $recover->code == $code && $recover->validated == 'no') {
				$recover->validated = 'yes';

				$this->addMeta('recovery', json_encode($recover));

				return true;
			}
		}

		return false;
	}

	// ----------------------------------------------------
	
	/**
	 * Recover User Password
	 * 
	 * @param string	$code 			Validation Token Code
	 * 
	 * @return (mixed)
	 */
	public function recoverPassword($type, $code, $newPassword) {

		// get original attempt
		$recover = json_decode($this->getMeta("recovery"));

		if($recover) {

			if($recover->type == $type && $recover->code == $code && $recover->validated == 'yes') {
				//
				$this->deleteMeta('recovery');

				$this->setPassword($newPassword);
				
				model("UserModel")->save($this);

				cache()->delete($this->id."_public_info");

				return true;
			}
		}

		return false;
	}

	// ----------------------------------------------------
	
	/**
	 * Set User Banking Information
	 * 
	 * @return (mixed)
	 */
	public function setBankInfo(string $shabaCode, string $cartNumber)
	{
		$data = [
			'shaba'		=> $shabaCode,
			'number'	=> $cartNumber,
		];

		// update bank data
		return $this->addMeta('bank_info', json_encode($data));

	}

	// ----------------------------------------------------
	
	/**
	 * Get User Banking Information
	 * 
	 * @return (mixed)
	 */
	public function getBankInfo() {

		// get original attempt
		$bank = json_decode($this->getMeta("bank_info"));

		if($bank) {
			
			return $bank;
		}

		return false;
	}

	// ----------------------------------------------------
	
	/**
	 * Create a user meta
	 * 
	 * @param string	$metaKey
	 * @param string  	$metaValue
	 * 
	 * @return (mixed)
	 */
	public function addMeta(string $metaKey, string $metaValue) {

		// Error? 
		if(!$this->id) throw new \Exception("Cannot set meta data without User ID", 1);
		
		$model = model("UserMetaModel");

		// Array to be saved
		$data = [
			'user_id'		=> $this->id,
			'meta_key'		=> $metaKey,
			'meta_value'	=> $metaValue,
		];
		
		// Find existing meta key
		$exists = $model->where('user_id', $this->id)->where('meta_key', $metaKey)->first();

		// add existed meta id to data so we can save data instead of insert
		if($exists) $data['id'] = $exists->id;
		
		// Save the new meta data
		$model->save($data);

		// get the saved data to be sure
		return $this->getMeta($metaKey);
	}

	// ----------------------------------------------------
	
	/**
	 * Get a user meta
	 * 
	 * @param string	$metaKey
	 * @param boolean  	$JSON
	 * 
	 * @return (mixed)
	 */
	public function getMeta(string $metaKey, bool $JSON = false) {

		// Error? 
		if(!$this->id) throw new \Exception("Cannot get meta data without User ID", 1);

		$model = model("UserMetaModel");
		$result = $model
			->where('user_id', $this->id)
			->where('meta_key', $metaKey)->first();
		
		return $result ? ($JSON ? json_decode($result->meta_value) : $result->meta_value) : false;
	}

	// ----------------------------------------------------
	
	/**
	 * Delete Meta
	 * 
	 * @param string	$metaKey
	 * @param boolean  	$JSON
	 * 
	 * @return (mixed)
	 */
	public function deleteMeta(string $metaKey) {

		// Error? 
		if(!$this->id) throw new \Exception("Cannot get meta data without User ID", 1);

		$model = model("UserMetaModel");
		$result = $model
			->where('user_id', $this->id)
			->where('meta_key', $metaKey)->delete();
		
		return $result;
	}

	// ----------------------------------------------------
	
	/**
	 * Delete User Entity
	 * 
	 * @param boolean  	$soft 			Soft Delete
	 * 
	 * @return (mixed)
	 */
	public function delete(bool $soft = false) {

		// Error? 
		if(!$this->id) throw new \Exception("Cannot get meta data without User ID", 1);

		

		$projects = model("ProjectsModel")->$projects->where("user_id", $this->id)->find();

		if(!empty($projects)) {
			foreach ($projects as $key => $project) {

				// Deletes Several Entities, including \Project, @ProjectAssignees, \ChatRoom (+conversations)
				$project->delete();
			}
		}

		$user = $model->withDeleted()->find($this->id);

		$result = $model
			->where('user_id', $this->id)
			->where('meta_key', $metaKey)->delete();
		
		return $result;
	}
}