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

class FreeSMSConfirmationModuleFrontController extends ModuleFrontController
{

    public $auth = false;
    public $confirmation = '';
    private $table_name = 'freesms';

    // just mention
    public function setMedia()
    {
        parent::setMedia();
    }

    protected function setColumns()
    {
        $this->display_column_left = true;
        $this->display_column_right = true;
        $this->display_footer = true;
        $this->display_header = true;
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = array(
            'title' => $this->getTranslator()->trans('Order Confirmation', array(), 'Breadcrumb'),
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

    public function initContent()
    {
        parent::initContent();
        $this->setColumns();

        $order_exists = false;
        $order_data = array();
        $confirmation = false;
        $product_data = array();

        if (Configuration::get('WL_FREE_SMS_ENABLE_ORDER_CONF') == 1) {
            if (Tools::getIsset('id') && Tools::getIsset('ref') &&
                Tools::strlen(Tools::getValue('id'))>0 &&
                Tools::strlen(Tools::getValue('ref'))>0
            ) {
                $order_data = new Order((int)Tools::getValue('id'));
                //dump($order_data);
                if ($order_data->id && $order_data->reference && $order_data->reference == Tools::getValue('ref')) {
                    $order_exists = true;
                    $product_data = $order_data->getProductsDetail();
                }
            }

            if (Tools::getIsset('confirmOrder')) {
                $confirmation = 'confirmOrder';
            } elseif (Tools::getIsset('cancelOrder')) {
                $confirmation = 'cancelOrder';
            }

            $this->context->smarty->assign(array(
                'order_data_paid' => $order_data->total_paid,
                'order_data_payment' => $order_data->payment,
            ));
        }

        $this->context->smarty->assign(array(
            'confirmation' => $confirmation,
            //'getsms' => $this->getCustomerSMS($this->context->customer->id),
            'orderid' => (int)Tools::getValue('id'),
            'order_reference' => Tools::getValue('ref'),
            'order_exists' => $order_exists,
            'product_data' => $product_data,
        ));
        
            

        if ($this->psversion() == 6) {
            $this->setTemplate('confirmation.tpl');
        } elseif ($this->psversion() == 7) {
            $this->setTemplate('module:freesms/views/templates/front/confirmation-17.tpl');
        }
    }

    public function postProcess()
    {
        if (Tools::getIsset('confirmOrder')) {
            $order = new Order((int)Tools::getValue('id'));
            $order->setCurrentState((int)Configuration::get('WL_FREE_SMS_ORDER_CONFIRMED'));
        } elseif (Tools::getIsset('cancelOrder')) {
            $order = new Order((int)Tools::getValue('id'));
            $order->setCurrentState((int)Configuration::get('WL_FREE_SMS_ORDER_CANCELED'));
        }

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
