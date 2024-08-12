<?php namespace AliKhaleghi\BaseSys\Models;


use CodeIgniter\Model;

class UserModel extends \Myth\Auth\Models\UserModel
{
    protected $returnType = \AliKhaleghi\BaseSys\Entities\User::class;
    
    protected $allowedFields  = [
        'firstname',
        'lastname',
        'username',
        'active',
        'email',
        'phone',
        'code_melli',

        'password_hash', 'reset_hash', 'reset_at', 'reset_expires', 'activate_hash',
        'status', 'status_message', 'force_pass_reset', 'permissions', 'deleted_at',
    ];
}