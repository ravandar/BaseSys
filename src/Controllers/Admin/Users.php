<?php
namespace AliKhaleghi\BaseSys\Controllers\Admin;
use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

use AliKhaleghi\BaseSys\Models\UserModel;
use Config\Services;
use \Myth\Auth\Authorization\GroupModel;
use \Myth\Auth\Authorization\PermissionModel;
use AliKhaleghi\BaseSys\Entities\User;
use \Myth\Auth\Authorization\FlatAuthorization;

/**
 * Admin User Management Controller
 * 
 * @category CodeIgniter4
 * @package  AliKhaleghi\BaseSys
 * 
 * @author   Ali Khaleghi <awli.khaleghi@gmail.com>
 * @license  <Private>
 */
class Users extends Controller
{
	use ResponseTrait;

	public function __construct()
	{

	} 

	// ---------------------------------------------------------------------------

	/**
	 * Get List of Users
	 * 
	 * @method  GET
	 * 
	 * @param   (str)     $fullname            (Optional)
	 * @param   (str)     $username            (Optional)
	 * @param   (str)     $status              (Optional)
	 * @param   (str)     $sortBy              (Optional)
	 * @param   (str)     $sortBy              (Optional)
	 * @param   (str)     $phone               (Optional)
	 * @param   (str)     $email               (Optional)
	 * @param   (str)     $code_melli          (Optional)
	 * @param   (str)     $groups              (Optional)
	 * 
	 * @return Response
	 */
	public function getUsers()
	{
		$users = new UserModel;

		// Filter Status
		if($status = $this->request->getGet("status")) {
			switch ($status) {
				case 'active':
				case 'strike':
				case 'banned':
					$users->where('status', $status); 
					break;
				case 'deleted':
					$users->withDeleted();
					break;
				default:
					break;
			}
		}
		
		// Filter Sorting
		$sortBy = $this->request->getGet("sortBy");
		switch ($sortBy) {
			case 'id':
			case 'updated_at':
			case 'deleted_at':
			case 'created_at':
			case 'phone':
			case 'code_melli':
			case 'email':
				$users->orderBy("`users`.`$sortBy`", $this->request->getGet("sortDesc") === 'true' ? 'desc' : 'asc');
				break;
			default: 
				$users->orderBy('`users`.`id`', 'desc');
				break;
			
		}
		// Filter Full name
		if($fullname = $this->request->getGet("fullname"))
		{
			$users->where("CONCAT( firstname,  ' ', lastname ) LIKE  '%".$fullname."%'");
		}

		// Filter Email
		if($username = $this->request->getGet("username")) $users->like("username", $username);

		// Filter Email
		if($phone = $this->request->getGet("phone")) $users->like("phone", $phone);

		// Filter Email
		if($email = $this->request->getGet("email")) $users->like("email", $email);

		// Filter Code Melli
		if($code_melli = $this->request->getGet("code_melli")) $users->like("code_melli", "%$code_melli%");

		// Filter Groups
		if($groups = $this->request->getGet("groups"))
		{
			$groups = explode(",", $groups);
			if(!empty($groups) && is_array($groups))
				$users->join('auth_groups_users', '`auth_groups_users`.`user_id` = `users`.`id`', 'left')->whereIn('`auth_groups_users`.`group_id`', $groups);

			$users->groupBy("users.id");
		}
		
		// Limit Results
		if((int)$this->request->getGet("limit") || (int)$this->request->getGet("itemsPerPage")) {
		
			if((int)$this->request->getGet("limit"))        $limit = (int)$this->request->getGet("limit");
			if((int)$this->request->getGet("itemsPerPage")) $limit = (int)$this->request->getGet("itemsPerPage");
		}
		else
			$limit   = 10;
			

		if($limit == '-1') $limit = 999999;
		$users->where("id != 0", NULL, NULL);
        $countAll = $users->countAllResults(false); 

		$users->where("id !=0");

		$list    = $users->paginate($limit);

		foreach ($list as $key => $user)
		{
			$list[$key] = $user->publicInfo(false, true);
		}

		return $this->respond([
			'status' => 'Ok',
			'data'   => [
				'total'         => $users->pager->getPageCount(),
				'total_rows'    => $countAll,
				'items'         => $list,
				'pager'         => $users->pager
			]
		], 200);
	}

	// ---------------------------------------------------------------------------

	/**
	 * Find A User by ID
	 * 
	 * @method  GET
	 * @param   (int)     $id               (Optional) if set, will update instead of insert
	 * 
	 * @return Response
	 */
	public function find()
	{
		$users = new UserModel;
		$userID = (int)$this->request->getGet("id");
		
		if($userID){

			if($user = $users->find($userID))
			{
				return $this->respond([
					'status' => 'Ok',
					'data'   => [ 
						'user' => $user->publicInfo(false)
					]
				], 200);
			}
			else
			{

				return $this->fail([
					'status' => 'not found',
					'data'   => [ ]
				], 404);
			}
		}

		$response = [
			'OK'     => false,
			'message' => 'Invalid Input'
		];
		return $this->fail($response , 409);
	}

	// ---------------------------------------------------------------------------

	/**
	 * Create A User
	 * 
	 * @method  PUT
	 *  
	 *  ? This method has too many params to describe here.
	 *  ? Please follow the documentation OR hear me out, just read the code.
	 * ! Please note that there are several different libraries are being used at the same time, have the same Class names but under their own different name spaces, please know what you are doing before modifying this, remember production must be safe.
	 * 
	 * @return Response
	 */
	public function createUser()
	{
		$rules = [
			'firstname' => [
				'label' => 'نام',
				'rules' => 'required|min_length[2]|max_length[20]'
			],
			'lastname' => [
				'label' => 'نام خانوادگی',
				'rules' => 'required|min_length[2]|max_length[30]'
			],
			'username' => [
				'label' => 'نام کاربری',
				'rules' => 'required|min_length[3]|max_length[20]|is_unique[users.username]'
			],
			'phone_number' => [
				'label' => 'شماره تلفن',
				'rules' => 'exact_length[10]|is_unique[users.phone]'
			],
			'code_melli' => [
				'label' => 'کد ملی',
				'rules' => 'required|min_length[3]|max_length[20]|is_unique[users.code_melli]'
			],
			'email' => [
				'label' => 'آدرس ایمیل',
				'rules' => 'required|min_length[4]|max_length[255]|valid_email|is_unique[users.email]'
			],
			'password' => [
				'label' => 'گذرواژه',
				'rules' => 'required|min_length[8]|max_length[255]'
			]
		];
		
		if(!empty($this->request->getFile("avatar")))
		{

			$rules['avatar'] = [
				'label' => 'عکس پروفایل',
				'rules' => 'uploaded[avatar]'
					. '|is_image[avatar]'
					. '|mime_in[avatar,image/jpg,image/jpeg,image/png,image/webp]'
					. '|max_size[avatar,2000]'
					. '|max_dims[avatar,2024,2068]',
			];
		}
		
		if($this->validate($rules)) {
			

			// Users DB Handler
			$Users = new UserModel();
			$data = [
				'firstname'         => $this->request->getVar('firstname'),
				'lastname'          => $this->request->getVar('lastname'),
				'username'          => $this->request->getVar('username'),
				'phone'             => $this->request->getVar('phone_number'),
				'code_melli'        => $this->request->getVar('code_melli'),
				'email'             => $this->request->getVar('email'),
				'active'            => 0,
				'force_pass_reset'  => 0,
			];

			// Create a new User entity
			$User = new User($data);

			$User->setPassword($this->request->getVar('password'));

			// Save the user inside database
			$Users->insert($User);
			$user_id = $Users->getInsertID();
			
			$User = $Users->find($user_id);

			if($avatar = $this->request->getFile("avatar"))
			{
				if ($avatar->isValid() && ! $avatar->hasMoved()) {
					$User->setAvatar($avatar);
				}
			}

			return $this->respond([
				'status' => 'Ok',
				'message' => 'ثبت نام شما با موفقیت انجام شد',
				'data'   => [
					'user' => $User
				]
			], 200);

		}
		$response = [
			'OK'     => false,
			'errors' => $this->validator->getErrors(),
			'message' => 'Invalid Inputs'
		];
		return $this->fail($response , 409);
	}

	// ---------------------------------------------------------------------------

	/**
	 * Get User Data
	 * 
	 * @method  GET
	 * @param   (int)     $id       User ID
	 * 
	 * @return Response
	 */
	public function getUser() 
	{
		$id = is_numeric($this->request->getGet("id")) ? (int) $this->request->getGet("id") : false;

		if(!is_bool($id) && !$id) return $this->fail('Not Found', 404);

		$model = new UserModel;

		$user  = $model->find($id);

		if(!$user) return $this->fail('Not Found', 404);

		$User = &$user->publicInfo(false,true);
		
        $groupsModel = new GroupModel();
		if(!empty(@$User['scope']))
        {
			foreach ($User['scope'] as $id => $groupName)
			{
				$User['scope'][$id] = $groupsModel->find($id);
                $User['scope'][$id]->permissions  = $groupsModel->getPermissionsForGroup($id);
			}
		}

		/**
		 * a signal to be recieved by other libraries to add their own editable items
		 * 
		 * @param (User) 	User to Modify
		 * 
		 * @return array
		 */
		$parsedUser = (object) $User;
        \CodeIgniter\Events\Events::trigger('base_user_edit_items', $parsedUser);

		return $this->respond([
			'user' => $parsedUser
		], 200);
	}

	// ---------------------------------------------------------------------------

	/**
	 * Modify a User
	 * 
	 * @method  GET
	 * @param   (int)     $id       User ID to modify
	 * 
	 * @return Response
	 */
	public function modifyUser()
	{
		$userID = $this->request->getVar("id") === NULL ? FALSE : (int)$this->request->getVar("id");

		$user   = $userID ? (new UserModel)->find($userID) : FALSE; 

		if($userID && !$user) {
			
			$response = [
				'OK'     => false,
				'message' => 'کاربر موردنظر یافت نشد'
			];
			return $this->fail($response , 409);
		}

		// We had no user, create one
		if(!$userID) $user = new User;
		
		$rules = [
			'firstname' => [
				'label' => 'نام',
				'rules' => 'required|min_length[2]|max_length[20]'
			],
			'lastname' => [
				'label' => 'نام خانوادگی',
				'rules' => 'required|min_length[2]|max_length[30]'
			],
		];
 
		if($user->username !== $this->request->getVar("username")) {
			$rules['username'] = [
				'label' => 'نام کاربری',
				'rules' => 'required|min_length[3]|max_length[20]|is_unique[users.username]'
			];
		}
		if($user->phone !== $this->request->getVar("phone")) {
			$rules['phone'] = [
				'label' => 'شماره تلفن',
				'rules' => 'exact_length[10]|is_unique[users.phone]'
			];
		}
		if($user->email !== $this->request->getVar("email")) {
			$rules['email'] = [
				'label' => 'آدرس ایمیل',
				'rules' => 'required|min_length[4]|max_length[255]|valid_email|is_unique[users.email]'
			];
		}
		
		if(!empty($this->request->getVar("password")) || !$userID) {
			$rules['password'] = [
				'label' => 'گذرواژه',
				'rules' => 'required|strong_password|min_length[8]|max_length[255]'
			];
			$rules['pass_confirm'] = [
				'label' => 'تکرار گذرواژه',
				'rules' => 'required|matches[password]'
			];
		} 
		if(!empty($this->request->getFile("avatar")))
		{

			$rules['avatar'] = [
				'label' => 'عکس پروفایل',
				'rules' => 'uploaded[avatar]'
					. '|is_image[avatar]'
					. '|mime_in[avatar,image/jpg,image/jpeg,image/png,image/webp]'
					. '|max_size[avatar,100]'
					. '|max_dims[avatar,2024,2068]',
			];
		}
		
		if($this->validate($rules)) {

			// if there's any password, it has already been validated, therefor we just set it.
			if($this->request->getVar('password')) $user->setPassword($this->request->getVar('password'));

			// Users DB Handler
			$Users = new UserModel();

			//
			$groupModel = model(GroupModel::class); 

			if($avatar = $this->request->getFile("avatar"))
			{
				if ($avatar->isValid() && ! $avatar->hasMoved()) {
					$user->setAvatar($avatar);
				}
			}

			$user->firstname    = $this->request->getVar("firstname");
			$user->lastname     = $this->request->getVar("lastname");
			$user->username     = $this->request->getVar("username");
			$user->phone        = $this->request->getVar("phone");
			$user->code_melli   = $this->request->getVar("code_melli");
			$user->email        = $this->request->getVar("email");
			
			$changedActive		= $this->request->getVar("active") == 'true' ? true : false; 

			if($changedActive != $user->active) {
				$user->active = $changedActive;
			} 

			$groups = $this->request->getVar("groups")  ;
			// updating data?
			if($user->hasChanged(null) || $groups) {
				
				try {
					if($userID) {
						$saved = $Users->save($user);
					}
					else {

						// insert new user
						$userID = $Users->insert($user);
						
						// get user
						$user   = $Users->find($userID);
						
						
						$saved  = ($user && $userID);
					}
					
					if(!$saved) return $this->fail($Users->errors() , 409);
						 

				}
				// Do nothing if nothing changed on user row
				catch (\CodeIgniter\Database\Exceptions\DataException $th) { }
				catch (\Throwable $th) {
					var_dump($th->getCode());die();
					//throw $th;
					return $this->fail($th->getMessage() , 409);
				} 
			}

			if(!$user) {
				return $this->fail($Users->errors() , 409);
			}

			$this->request->getVar("activate_sms") == 'true' ?
			// Set Phone as Activate
			$user->addMeta("phone_activated", 'yes')
			:
			// Delete Activated State
			$user->deleteMeta("phone_activated"); 


			// Get User Groups
			$userGroups         = $groupModel->getGroupsForUser($user->id);

			$groups = $this->request->getVar("groups") ? json_decode($this->request->getVar("groups")) : false;
			if(is_array($groups)) {
				// Delete All User Groups, we will re-add new groups 
				$groupModel->removeUserFromAllGroups($user->id);

				foreach ($groups as $key => $group) {
					if(empty($group)) continue; // Skip Empty Group ID

					// delete any previous group for user
					$groupModel->removeUserFromGroup($user->id, $group->id);

					// Add User to Group
					$groupModel->addUserToGroup($user->id, $group->id);
				}
			}

			return $this->respond([
				'status' => 'Ok',
				'message' => 'ویرایش با موفقیت انجام شد',
				'data'   => [
					'user' => $user->publicInfo()
				]
			], 200);

		} 

		return $this->fail($this->validator->getErrors() , 409);
	}
	// ---------------------------------------------------------------------------

	/**
	 * !!Add or Modify A User Group
	 * 
	 * @method  PUT
	 * @param   (int)     $id               (Optional) if set, will update instead of insert
	 * @param   (str)     $name             name
	 * @param   (str)     $description      description
	 * 
	 * @return Response
	 */
	public function saveUserGroup()
	{
		$model = (new GroupModel);
		
		$rules = [
			'name'	=> [
				'label' => 'نام دسترسی',
				'rules' => [
					'required'
				]
			],
			'description'	=> [
				'label' => 'توضیح دسترسی',
				'rules' => [
					'required'
				]
			],
		];
		if (! $this->validate($rules))
		{
			$response = [
				'OK'     => false,
				'errors' => $this->validator->getErrors(),
				'message' => 'Invalid Inputs'
			];
			return $this->fail($response , 409);
		}
		if((int)$this->request->getVar("id")){
			$updating = true;
			$model->save([
				'id'    => $this->request->getVar("id"),
				'name'  => $this->request->getVar("name"),
				'description'  => $this->request->getVar("description"),
			]);
		}
		else
		{
			$model->insert([
				'name'  => $this->request->getVar("name"),
				'description'  => $this->request->getVar("description"),
			]);
		}
		if($errors = $model->errors()) {
			return $this->fail($errors, 409);
		}

		return $this->respond([
			'status' => 'Ok',
			'message' => isset($updating) ? lang('Groups.groupUpdated') : lang('Groups.groupCreated'), //'گروه جدید با موفقت ذخیره شد',
			'data'   => [
				'total' => (new GroupModel)->countAll(),
				'groups' => (new GroupModel)->find(),
			]
		], 200);
	}
	
	// ---------------------------------------------------------------------------

	/**
	 * Delete User Group
	 * 
	 * @method  DELETE
	 * @param   (int)     $id             Group to Delete
	 * 
	 * @return Response
	 */
	public function deleteUserGroup()
	{
		$model = (new GroupModel);
		
		if((int)$this->request->getVar("id")){

			$model->where('id',$this->request->getVar("id"))->delete();
			return $this->respond([
				'status' => 'Ok',
				'message' => 'گروه با موفقیت حذف شد',
			], 200);
		}
		return $this->fail(lang('Groups.couldNotDelete') , 409);
	}
	
	// ---------------------------------------------------------------------------

	/**
	 * Get User Groups
	 * 
	 * @method  GET
	 * 
	 * @return Response
	 */
	public function getUserGroups()
	{
		$model = new GroupModel();

		$groups = $model->find();
		if(!empty($groups)) {
			foreach ($groups as $key => $group)
			{
				$groups[$key]->permissions  = $model->getPermissionsForGroup($group->id);
				$groups[$key]->users  = $model->from('auth_groups_users')->select('count(user_id) as count')->where('group_id', $group->id)->first()->count;
			}
		}
		
		return $this->respond([
			'status' => 'Ok',
			'data'   => [
				'total' =>  $model->countAll(),
				'groups' => $groups,
			]
		], 200);
	}

	// ---------------------------------------------------------------------------

	/**
	 * Get All Permissions
	 * 
	 * @method  GET
	 * 
	 * @return Response
	 */
	public function getUserPermissions()
	{

		return $this->respond([
			'status' => 'Ok',
			'data'   => [
				'total' => (new PermissionModel)->countAll(),
				'permissions' => (new PermissionModel)->find(),
			]
		], 200);
	}
	
	// ---------------------------------------------------------------------------

	/**
	 * Add Permission to Authorization
	 * 
	 * @method  PUT
	 * @param   (str)     $name             name
	 * @param   (str)     $description      description
	 * 
	 * @return Response
	 */
	public function addPermission()
	{
		$authorization = (new FlatAuthorization);
		
		$rules = [
			'name'	=> [
				'label' => 'نام دسترسی',
				'rules' => [
					'required'
				]
			],
			'description'	=> [
				'label' => 'توضیح دسترسی',
				'rules' => [
					'required'
				]
			],
		];
		if (! $this->validate($rules))
		{ 
			return $this->fail($this->validator->getErrors() , 409);
		}
		
		// Try to Create new Permission
		$newPerm = $authorization->createPermission($this->request->getVar("name"), $this->request->getVar("description"));

		if(!$newPerm) {
			return $this->fail('خطا در ایجاد دسترسی' , 409);
		}

		// Return success
		return $this->respond([
			'status' => 'Ok',
			'message' => 'دسترسی جدید ایجاد شد',
			'data'   => [
				'total' => (new PermissionModel)->countAll(),
				'permissions' => (new PermissionModel)->find(),
			]
		], 200);
	}
	
	// ---------------------------------------------------------------------------

	/**
	 * Get Config
	 * 
	 * @method  PUT
	 * @param   (str)     $key      Config Class.Key to delete
	 * @param   (str)     $value    New Value
	 * 
	 * @return Response
	 */
	public function getGroups() {
		$context	= $this->request->getVar("context");
		$key		= $this->request->getGet("key");
		try {
			return $this->respond([
				'status'    => 'Ok',
				'result'    => [
					'value'  => service('settings')->get($key, $context),
				]
			], 200);
		} catch (\Throwable $th) {
			return $this->fail($th->getMessage());
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * Set Config 
	 * 
	 * @method  PUT
	 * @param   (str)     $key      Config Class.Key to delete
	 * @param   (str)     $value    New Value
	 * 
	 * @return Response
	 */
	public function setConfig() {
		$context	= $this->request->getVar("context");
		$key		= $this->request->getVar("key");
		$value		= $this->request->getVar("value");

		if($key && $value) {
			
			try {
				service('settings')->set($key, ($value === 'false' ? false : ( $value === 'true' ? true : $value)), $context);
				return $this->respond([
					'status'    => 'Ok',
					'result'    => [
						'value'  => service('settings')->get($key, $context),
					]
				], 200);
			} catch (\Throwable $th) {
				return $this->fail($th->getMessage());
			}
		}
	}

	// ---------------------------------------------------------------------------

	/**
	 * Delete Config 
	 * 
	 * @method  PUT
	 * @param   (str)     $key      Config Class.Key to delete
	 * 
	 * @return Response
	 */
	public function deleteConfig() {
		$key  = $this->request->getVar("key");

		try {
			service('settings')->forget($key);
			return $this->respond([
				'status'    => 'Ok'
			], 200);
		} catch (\Throwable $th) {
			return $this->fail($th->getMessage());
		}
	}
}
