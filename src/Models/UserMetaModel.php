<?php namespace AliKhaleghi\BaseSys\Models;


use CodeIgniter\Model;

class UserMetaModel extends Model
{
    protected $table = 'user_meta';
    protected $primaryKey = 'id';
    
    protected $returnType = 'object';

    protected $allowedFields = [
        'user_id',
        'meta_key',
        'meta_value', 
    ];
    
    protected $validationRules = [

    ]; 
}