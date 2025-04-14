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

class FreeSMSSubscribeSMSModuleFrontController extends ModuleFrontController
{

    public $auth = true;
    public $confirmation = '';
    private $table_name = 'freesms';

    // just mention
    public function setMedia()
    {
        parent::setMedia();
    }

    protected function disableColumns()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        $this->display_footer = true;
        $this->display_header = true;
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
     
        $breadcrumb['links'][] = array(
            'title' => $this->getTranslator()->trans('My account', array(), 'Breadcrumb'),
            'url' => $this->context->link->getPageLink('my-account', true)
        );
     
        $breadcrumb['links'][] = array(
            'title' => $this->getTranslator()->trans('SMS and Whatsapp Notifications', array(), 'Breadcrumb'),
            'url' => $this->context->link->getModuleLink('freesms', 'subscribesms')
        );
     
        return $breadcrumb;
    }

    public function init()
    {
        parent::init();
    }

    public function psversion()
    {
        $version = _PS_VERSION_;
        $ver = explode(".", $version);
        return $ver[1];
    }

    public function getCustomerSMS($id_customer)
    {
        $sql = 'SELECT subscribed FROM '._DB_PREFIX_.$this->table_name.
            '_subscribers WHERE id_customer = '.(int)$id_customer;
        $subscribed = Db::getInstance()->getValue($sql);
        //d($subscribed);
        return (int)$subscribed;
    }

    public function subscriptionLineExists($id_customer)
    {

        $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.$this->table_name.
            '_subscribers WHERE id_customer = '.(int)$id_customer;
        $exists = Db::getInstance()->getValue($sql);

        if ($exists>0) {
            return true;
        } else {
            return false;
        }
    }

    public function initContent()
    {
        parent::initContent();
        $this->disableColumns();

        $this->context->smarty->assign(array(
            'confirmation' => $this->confirmation,
            'getsms' => $this->getCustomerSMS($this->context->customer->id),
            'customerid' => $this->context->customer->id,
        ));

        if ($this->psversion() == 6) {
            $this->setTemplate('subscribe.tpl');
        } elseif ($this->psversion() == 7) {
            $this->setTemplate('module:freesms/views/templates/front/subscribe-17.tpl');
        }
    }

    protected function addSubscription($sub)
    {
        $sub['id_customer'] = (int)Context::getContext()->customer->id;
        if (!Db::getInstance()->insert(
            pSQL($this->table_name.'_subscribers'),
            $sub
        )
        ) {
            $this->_errors[] = Tools::displayError('Error while adding the request');
        } else {
            $this->confirmations[] = 'Subscription successfully added';
        }
    }

    public function updateSubscription($sub)
    {
        //d($sub);
        if (!Db::getInstance()->update(
            pSQL($this->table_name.'_subscribers'),
            $sub,
            'id_customer = '. (int)Context::getContext()->customer->id
        )
        ) {
            $this->_errors[] = Tools::displayError('Error while updating request!');
        } else {
            $this->confirmations[] = 'Subscription successfully updated';
        }
    }

    public function postProcess()
    {
        $data = array();
        if (Tools::getIsset('savesms')) {
            if ($this->subscriptionLineExists($this->context->customer->id)) {
                $data['subscribed'] = (int)Tools::getValue('get-sms-notifications');
                $this->updateSubscription($data);
            } else {
                $data['subscribed'] = (int)Tools::getValue('get-sms-notifications');
                $this->addSubscription($data);
            }
        }
    }
}
