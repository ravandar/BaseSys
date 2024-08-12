<?php namespace AliKhaleghi\BaseSys\Database\Migrations;

use AliKhaleghi\BaseSys\Models\UserModel;
use CodeIgniter\Database\Migration;

use \Myth\Auth\Authorization\GroupModel;
use \Myth\Auth\Authorization\PermissionModel;
use \Myth\Auth\Authorization\FlatAuthorization;
use Myth\Auth\Entities\User;

class Migration_alter_table_users extends Migration
{
	public function up()
	{		
		// add new identity info
		$fields = [
			'code_melli'          => [
                'type' => 'VARCHAR',
                'constraint' => 63,
                'after' => 'username'
            ],
			'firstname'      => [
                'type' => 'VARCHAR',
                'constraint' => 63,
                'after' => 'username'
            ],
			'lastname'       => [
                'type' => 'VARCHAR',
                'constraint' => 63,
                'after' => 'firstname'
            ],
			'phone'          => [
                'type' => 'VARCHAR',
                'constraint' => 63,
                'after' => 'lastname'
            ],
		];
		$this->forge->addColumn('users', $fields);
		$userModel = new UserModel;

		/**
		 * Insert System Admin
		 */
		$userModel->skipValidation()->insert([
			'email'	=> 'sys@server.com',
			'username'	=> 'sysadmin',
		]);
			
        $model = (new GroupModel);
		
		$model->skipValidation()->insert([
			'name'  => 'admin',
			'description'  => 'Admin',
		]);
		$model->skipValidation()->insert([
			'name'  => 'user',
			'description'  => 'Member',
		]);


        $users = model(\AliKhaleghi\BaseSys\Models\UserModel::class);

        $user              = new User([
			'username'	=> 'admin',
			'password'	=> 'saeidsaeid',
			'email'		=> 'awli.khaleghi@gmail.com',
		]);


		$user->activate(); // Activate Account
		$users->withGroup('admin');
		$users->save($user);

	}

	public function down()
	{
		// drop new columns
		$this->forge->dropColumn('users', 'code_melli');
		$this->forge->dropColumn('users', 'firstname');
		$this->forge->dropColumn('users', 'lastname');
		$this->forge->dropColumn('users', 'phone');
	}
}