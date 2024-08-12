<?php
namespace AliKhaleghi\BaseSys\Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{ 
    /**
     * --------------------------------------------------------------------------
     * API Root Directory Name
     * --------------------------------------------------------------------------
     * Name of the directory in which your original installation.
     * please note that you only need to enter the directory name.
     *
     * E.g. "api"
     *
     * @var string
     */
    public string $rootDirName = ''; 
}
