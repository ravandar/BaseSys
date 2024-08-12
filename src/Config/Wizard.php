<?php

namespace AliKhaleghi\BaseSys\Config;

use CodeIgniter\Config\BaseConfig;
use AliKhaleghi\Wizard\Install\Setup;

$wizard = new Setup;

// on each request ( this must be cached if validation is done ), check package validation
$wizard->validatePackage();

class Wizard extends BaseConfig
{

    /**
     * --------------------------------------------------------------------
     * Package Serial
     * --------------------------------------------------------------------
     *
     * This is the code to validate your purchase of this package.
     * To find your code please navigate to https://[?khaleghi.space]/backend/packages/basesys
     * 
     * @var string
     */
    public $serial = 'ABCD-EFGH-IJKL-MNOP-ZXRR'; 
}
