<?php
/**
 * Ahle Sokhan API Controller
 * 
 * PHP version 8
 *
 * @category AhleSokhan
 * @package  Client\Users
 * 
 * @author   Ali Khaleghi <awli.khaleghi@gmail.com>
 * @license  <Private>
 */
 
namespace AliKhaleghi\BaseSys\Controllers\Client;
 
use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use \Myth\Auth\Authorization\GroupModel;
use AliKhaleghi\BaseSys\Models\UserModel;
use AliKhaleghi\BaseSys\Entities\User;

class Files extends Controller
{
    use ResponseTrait;
    public function __construct(){
        
    }
    /**
     * Get File(s)
     */
    public function getFile() {
        if(!$this->user) {

            $response = [
                'OK'     => false,
                'errors' => 'دسترسی محدود است',
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response , 409);
        }

        $output = [];
        $files = model("FilesModel");

        if($section = $this->request->getVar("section")) {
            $files->where('section', $section);

            if($section_id = $this->request->getVar("section_id")) {
                $files->where('section_id', $section_id);
            }
        }

        $res = $files->find();

        if(!empty($res))
        {
            foreach ($res as $key => $value)
            {
                $value->path = base_url(PUBLIC_URL.$value->path);
                $output[] = $value;
            }
        }

        return $this->respond([
            'status'    => 'Ok',
            'files'     => $output
        ], 200);
    }

    /**
     * Upload File
     */
    public function uploadFile() {
        $this->user = user();
        if(!$this->user) {

            $response = [
                'OK'     => false,
                'errors' => 'دسترسی محدود است',
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response , 409);
        }

        $rules = [
            'n_file'  => [
                'label' => 'فایل مورد نظر',
                'rules' => 'uploaded[n_file]'
                    . '|is_image[n_file]'
                    . '|mime_in[n_file, image/jpg,image/jpeg,image/gif,image/png]' // is this really needed ?
                    . '|max_size[n_file,5500]'
                    // . '|max_dims[n_file,1024,768]',
            ],
            'section'  => [
                'label' => 'بخش',
                'rules' => 'required',
            ],
        ];
        $section = str_replace(" ", '_', $this->request->getVar("section"));
        switch ($section) {
            case 'publisher_book_covers':
                // allowed types ^
                break;
            default:
                $response = [
                    $section,
                    'OK'     => false,
                    'errors' => 'دسترسی محدود است',
                    'message' => 'Invalid Inputs'
                ];
                return $this->fail($response , 409);
                break;
        }
        if($this->validate($rules)) {
            
            $file = $this->request->getFile('n_file');
            $ext = $file->guessExtension();
            $newName = $file->getRandomName();

            if($file->move(FCPATH . 'storage/s/'.$section.'/', $newName)) {
                    
                $path = FCPATH . 'storage/s/'.$section.'/'. $newName;
            
                $p = '/storage/s/'.$section.''. explode("/storage/s/$section", $path)[1];

                $newFile = [
                    'uploaded_by'   => $this->user->id,
                    'name'          => $file->getClientName(),
                    'caption'       => $this->request->getVar("caption") ?: '',
                    'path'          => $p, 
                    'type'          => $ext, 
                    'section'       => $section,
                    'section_id'    => $this->request->getVar("section_id") ?: '',
                    'details'       => $this->request->getVar("details") ?: '',
                ];

                model("FilesModel")->save($newFile);
                return $this->respond([
                    'status' => 'Ok',
                    'message' => 'فایل مورد نظر با موفقیت آپلود شد',
                    'data'   => [
                        'file'  => null,
                    ]
                ], 200);
            }
        }
        $response = [
            'OK'     => false,
            'errors' => $this->validator->getErrors(),
            'message' => 'Invalid Inputs'
        ];
        return $this->fail($response , 409);
    }
}