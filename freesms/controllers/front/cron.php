<?php
/**
* 2007-2021 MsgBucket
*
*  @author    msgbucket <sales@msgbucket.com>
*  @copyright 2012-2021 msgbucket
*  @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
*  International Registered Trademark & Property of msgbucket.com
*
*  You are allowed to modify this copy for your own use only. You must not redistribute it. License
*  is permitted for one Prestashop instance only but you can install it on your test instances.
*/

class FreesmsCronModuleFrontController extends ModuleFrontController
{
    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /** @var bool */
    public $ajax;

    public function __construct()
    {
        parent::__construct();
        
        if (Tools::getValue('secret') && Tools::getValue('secret') == Configuration::get('WL_FS_SECRET_CRON')) {
            if (Tools::getValue('action') == 'sendNext') {
                $do = new FreeSMS();
                $do->sendNext();
            } elseif (Tools::getValue('action') == 'sendCartReminder') {
                if (Tools::getValue('debug') == '1') {
                    $debug = true;
                } else {
                    $debug = false;
                }
                
                $do = new FreeSMS();
                $do->sendCartReminders($debug);
            } elseif (Tools::getValue('action') == 'sendBirthdayGift') {
                if (Tools::getValue('debug') == '1') {
                    $debug = true;
                } else {
                    $debug = false;
                }
                
                $do = new FreeSMS();
                $do->sendBirthdayGift($debug);
            }
        } else {
            echo "The token does not exist or it's wrong!";
            die();
        }
    }

    public function display()
    {
        $this->ajax = 1;
        $this->ajaxDie();
    }
}
