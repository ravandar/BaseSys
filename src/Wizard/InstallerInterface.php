<?php namespace AliKhaleghi\BaseSys\Wizard;

/**
 * Serial Validation
 * 
 * PHP version 8
 *
 * @category CodeIgniter4
 * @package  AliKhaleghi\BaseSys
 * 
 * @author   Ali Khaleghi <awli.khaleghi@gmail.com>
 * @license  <Private>
 */
interface InstallerInterface {

    public $packageName;
    public $version;

    public function install();

}