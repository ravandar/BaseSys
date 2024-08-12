<?php
namespace AliKhaleghi\BaseSys\Controllers\Admin;
use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

use App\Models\UserModel;
use Config\Services;
use \Myth\Auth\Authorization\GroupModel;
use \Myth\Auth\Authorization\PermissionModel;
use App\Entities\User;
use \Myth\Auth\Authorization\FlatAuthorization;

/**
 * Admin Group Management Controller
 * 
 * @category CodeIgniter4
 * @package  AliKhaleghi\BaseSys
 * 
 * @author   Ali Khaleghi <awli.khaleghi@gmail.com>
 * @license  <Private>
 */
class Groups extends Controller
{
	use ResponseTrait;

	public function __construct()
	{

	} 
    
	// ---------------------------------------------------------------------------

	/**
	 * Add or Modify A User Group
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
        $authorization = service("authorization");
        
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
        
        if($id = (int)$this->request->getVar("id")) {
            
            // Update  Permission
            $Updated = $authorization->updatePermission($id, $this->request->getVar("name"), $this->request->getVar("description"));
            if(!$Updated) return $this->fail($authorization->error() , 409);
        }
        else {
            // Try to Create new Permission
            $newPerm = $authorization->createPermission($this->request->getVar("name"), $this->request->getVar("description"));

            if(!$newPerm) return $this->fail($authorization->error() , 409);
        }

        // Return success
        return $this->respond([
            'status' => 'Ok',
            'message' => $id ? 'دسترسی با موفقیت بروزرسانی شد.' : 'دسترسی با موفقیت ایجاد شد.',
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
