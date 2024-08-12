<?php namespace AliKhaleghi\BaseSys\Models;

use CodeIgniter\Model;

class FilesModel extends Model
{
    protected $table = 'files';
    protected $primaryKey = 'id';
    
    protected $returnType = 'object';

    protected $allowedFields = [
        'uploaded_by',
        'name',
        'caption',
        'path', 
        'type', 
        'section', 
        'section_id', 
        'details', 
    ]; 
}