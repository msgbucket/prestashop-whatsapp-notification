<?php
/**
* 2007-2021 MsgBucket
*
*  @author    msgbucket <sales@msgbucket.com>
*  @copyright 2012-2021 MsgBucket
*  @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
*  International Registered Trademark & Property of msgbucket.com
*
*  You are allowed to modify this copy for your own use only. You must not redistribute it. License
*  is permitted for one Prestashop instance only but you can install it on your test instances.
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class FreeSMS extends Module
{
    private $html = '';

    public function __construct()
    {
        $this->name = 'freesms';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'MsgBucket';
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => _PS_VERSION_);

        $this->need_instance = 0;
        $this->bootstrap = true;

        $this->displayName = $this->l('FREE SMS & Whatsapp Notifications and Marketing Campaigns');
        $this->description = $this->l('Send Free notifications on different events on the shop,
            create SMS & Whatsapp Marketing Campaigns');
        $this->module_key = '';
        $this->table_name = $this->name;
        parent::__construct();

        $this->apis = array(
          		  
		  // adding msgbucket Whatsapp start
		  
		  array(
            'id_option' => 1,
            'name' => 'MsgBucket Whatsapp Indian User'
          ),
          
          array(
            'id_option' => 2,
            'name' => 'MsgBucket Whatsapp International User'
          ),
		  
		  // adding msgbucket Whatsapp End
          
		  
		  // adding msgbucket SMS start
		  array(
            'id_option' => 3,
            'name' => 'MsgBucket Sms'
          ),
		  
		  // adding msgbucket SMS end
		  
        );
    }

    public function install()
    {
        Configuration::updateValue('WL_FREE_SMS_CONSENT', 0);
        Configuration::updateValue('WL_FREE_SMS_DEBUG', 0);
        Configuration::updateValue('SMS_NOTIFICATION_ACTIVEAPI', 0);
        Configuration::updateValue('SMS_NOTIFICATION_ENABLE_DELAY', 0);
        Configuration::updateValue('WL_FREE_SMS_INTERVAL', 3);
        Configuration::updateValue('WL_FREE_SMS_LOWER', 3);
        Configuration::updateValue('WL_FREE_SMS_HIGHER', 300);
        Configuration::updateValue('SMS_NOTIFICATION_DEVICEID', 0);
        Configuration::updateValue('SMS_NOTIFICATION_SIM', 0);

        $this->addOrderStatus(
            'WL_FREE_SMS_ORDER_CONFIRMED',
            $this->l('Order confirmed by customer'),
            '#addb31',
            'confirmed',
            'preparation'
        );

        $this->addOrderStatus(
            'WL_FREE_SMS_ORDER_CANCELED',
            $this->l('Order canceled by customer'),
            '#e76e54',
            'canceled',
            'order_canceled'
        );

        $msg_admin_neworder = $this->l('You have a new order from {customer_firstname}, {customer_lastname}. 
            Total: {order_total}');
        $msg_admin_customer = $this->l('You have a new customer: {customer_firstname}, {customer_lastname}');
        $msg_admin_message = $this->l('You have a new message from {message_from}. Message: {message_content}');
        $msg_customer_neworder = $this->l('Thanks for shopping with us {customer_firstname}!
            Your order is being processed for shipping.You will receive a message with the shipping 
            link once we send it.').' '.Tools::getHttpHost(true).__PS_BASE_URI__;
        $msg_customer_abandoned = $this->l('Hi {customer_firstname}! Return to your cart and complete your purchase 
            with 10% off your first order with the code: FIRST.').' '.Tools::getHttpHost(true).__PS_BASE_URI__;
        $msg_customer_birthday = $this->l('Happy birthday {customer_firstname}! We are giving a 20% birthday 
            discount with the code: BDAY @').' '.Tools::getHttpHost(true).__PS_BASE_URI__;

        if (Language::countActiveLanguages()>1) {
            $admin_new_order = array();
            $admin_new_customer = array();
            $admin_new_message = array();
            $customer_new_order = array();
            $customer_abandoned_cart = array();
            $customer_birthday = array();

            foreach ($this->context->controller->getLanguages() as $lang) {
                $admin_new_order[$lang['id_lang']] = $msg_admin_neworder;
                $admin_new_customer[$lang['id_lang']] = $msg_admin_customer;
                $admin_new_message[$lang['id_lang']] = $msg_admin_message;
                $customer_new_order[$lang['id_lang']] = $msg_customer_neworder;
                $customer_abandoned_cart[$lang['id_lang']] = $msg_customer_abandoned;
                $customer_birthday[$lang['id_lang']] = $msg_customer_birthday;
            }

            Configuration::updateValue('WL_TP_ADMIN_NEW_ORDER', $admin_new_order, true);
            Configuration::updateValue('WL_TP_ADMIN_NEW_CUSTOMER', $admin_new_customer, true);
            Configuration::updateValue('WL_TP_ADMIN_NEW_MESSAGE', $admin_new_message, true);
            Configuration::updateValue('WL_TP_CUSTOMER_NEW_ORDER', $customer_new_order, true);
            Configuration::updateValue('WL_TP_ABANDONED_CART', $customer_abandoned_cart, true);
            Configuration::updateValue('WL_TP_BIRTHDAY', $customer_birthday, true);
        } else {
            Configuration::updateValue('WL_TP_ADMIN_NEW_ORDER', $msg_admin_neworder);
            Configuration::updateValue('WL_TP_ADMIN_NEW_CUSTOMER', $msg_admin_customer);
            Configuration::updateValue('WL_TP_ADMIN_NEW_MESSAGE', $msg_admin_message);
            Configuration::updateValue('WL_TP_CUSTOMER_NEW_ORDER', $msg_customer_neworder);
            Configuration::updateValue('WL_TP_ABANDONED_CART', $msg_customer_abandoned);
            Configuration::updateValue('WL_TP_BIRTHDAY', $msg_customer_birthday);
        }

        if (!parent::install() or
            !$this->registerHook('displayOrderConfirmation') or
            !$this->registerHook('displayFooter') or
            !$this->registerHook('actionOrderStatusUpdate') or
            !$this->registerHook('actionUpdateQuantity') or
            !$this->registerHook('actionCustomerAccountAdd') or
            !$this->registerHook('displayAdminOrderContentOrder') or
            !$this->registerHook('displayAdminOrderTabOrder') or
            !$this->registerHook('displayAdminOrderSide') or
            !$this->registerHook('displayCustomerAccount') or
            !$this->registerHook('displayBackOfficeHeader') or
            !$this->installMyTables() or
            !Configuration::updateValue('WL_FS_SECRET_CRON', $this->randomString())
            ) {
            return false;
        }
            
        return true;
    }

    private function removeTable()
    {
        if (!Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.$this->name.'_log`') ||
            !Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.$this->name.'_stack`') ||
            !Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.$this->name.'_subscribers`')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        $this->deleteOrderState((int)Configuration::get('WL_FREE_SMS_ORDER_CONFIRMED'));
        $this->deleteOrderState((int)Configuration::get('WL_FREE_SMS_ORDER_CANCELED'));

        if (!parent::uninstall() or
            !$this->removeTable() or
            !Configuration::deleteByName('SMS_NOTIFICATION_THESWITCH') or
            !Configuration::deleteByName('SMS_NOTIFICATION_ENABLE_DELAY') or
            !Configuration::deleteByName('WL_FREE_SMS_ORDER_CONFIRMED') or
            !Configuration::deleteByName('WL_FREE_SMS_ORDER_CANCELED') or
            !Configuration::deleteByName('WL_FREE_SMS_INTERVAL') or
            !Configuration::deleteByName('WL_FREE_SMS_LOWER') or
            !Configuration::deleteByName('WL_FREE_SMS_HIGHER') or
            !Configuration::deleteByName('SMS_NOTIFICATION_ACTIVEAPI') or
            !Configuration::deleteByName('SMS_NOTIFICATION_ADMIN_ORDERS') or
            !Configuration::deleteByName('SMS_NOTIFICATION_ADMIN_CUSTOMERS') or
            !Configuration::deleteByName('SMS_NOTIFICATION_ORDERNOTIF') or
            !Configuration::deleteByName('SMS_NOTIFICATION_ACCOUNTNOTIF') or
            !Configuration::deleteByName('SMS_NOTIFICATION_APISECRET') or
            !Configuration::deleteByName('SMS_NOTIFICATION_DEVICEID')
            ) {
            return false;
        }
        return true;
    }

    /**
     * Create order status
     */
    public function addOrderStatus($configKey, $statusName, $statusColor, $statusIconName, $template)
    {
        if (!Configuration::get($configKey)) {
            $orderState = new OrderState();
            $orderState->name = array();
            $orderState->module_name = $this->name;
            $orderState->send_email = false;
            $orderState->color = $statusColor;
            $orderState->hidden = false;
            $orderState->delivery = false;
            $orderState->logable = true;
            $orderState->invoice = false;
            $orderState->paid = false;
            foreach (Language::getLanguages() as $language) {
                $orderState->template[$language['id_lang']] = $template;
                $orderState->name[$language['id_lang']] = $statusName;
            }

            if ($orderState->add()) {
                $revoluticon = dirname(__FILE__).'/views/img/'.$statusIconName.'.gif';
                $newStateIcon = dirname(__FILE__).'/../../img/os/'.(int) $orderState->id.'.gif';
                copy($revoluticon, $newStateIcon);
            }

            Configuration::updateValue($configKey, (int) $orderState->id);
        }
    }

    /**
     * Delete order status
     */
    public function deleteOrderState($id_order_state)
    {
        $orderState = new OrderState($id_order_state);
        $orderState->delete();
    }

    public function randomString($length = 7)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = Tools::strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function sendNext()
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.$this->table_name.'_stack ORDER BY id ASC LIMIT 1';

        if ($row = Db::getInstance()->executeS($sql)) {
            $msg = $row[0]['message'];
            $number = $row[0]['phone'];
            
            $this->sendSMS($number, $msg);

            $id_message = $row[0]['id'];
            Db::getInstance()->delete($this->table_name.'_stack', 'id = ' . (int)$id_message);

            //add sms log
            Db::getInstance()->insert(
                $this->table_name.'_log',
                array(
                    'phone' => pSQL($number),
                    'message' => pSQL($msg)
                )
            );
        }
    }

    private function installMyTables()
    {
        $log = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.$this->table_name .'_log` (
                `id` INT(12) NOT NULL AUTO_INCREMENT,
                `phone` VARCHAR(255) NOT NULL,
                `message` VARCHAR(255) NOT NULL,
                `date_sent` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY ( `id` )
                ) ENGINE = ' ._MYSQL_ENGINE_;

        $stack = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.$this->table_name .'_stack` (
                `id` INT(12) NOT NULL AUTO_INCREMENT,
                `phone` VARCHAR(255) NOT NULL,
                `message` VARCHAR(255) NOT NULL,
                `date_sent` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY ( `id` )
                ) ENGINE = ' ._MYSQL_ENGINE_;

        $subscribers = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.$this->table_name .'_subscribers` (
                `id_customer` INT(12) NOT NULL AUTO_INCREMENT,
                `subscribed` INT(12) NOT NULL,
                PRIMARY KEY ( `id_customer` )
                ) ENGINE = ' ._MYSQL_ENGINE_;
        if (!Db::getInstance()->Execute($log) ||
            !Db::getInstance()->Execute($stack) ||
            !Db::getInstance()->Execute($subscribers)
        ) {
            return false;
        }
        return true;
    }

    public function sendCartReminders($debug = false)
    {
        $lower = (int)Configuration::get('WL_FREE_SMS_LOWER');
        $higher = 60 * (int)Configuration::get('WL_FREE_SMS_HIGHER');
        // get abandoned cart :
        $sql = "SELECT * FROM (
        SELECT
            c.`lastname`,
            c.`firstname`,
            a.id_cart total,ca.name carrier,
            c.id_customer,
            ad.phone_mobile,
            a.id_cart,
            a.date_upd,
            a.date_add,
                IF (IFNULL(o.id_order, 'Non ordered') = 'Non ordered', 
                IF(TIME_TO_SEC(TIMEDIFF('".date('Y-m-d H:i:s')."', a.`date_add`)) > ".$lower.",
                'Abandoned cart', 'Non ordered'), o.id_order) id_order,
                IF(o.id_order, 1, 0) badge_success,
                IF(o.id_order, 0, 1) badge_danger
        FROM `"._DB_PREFIX_."cart` a  
                JOIN `"._DB_PREFIX_."customer` c ON (c.id_customer = a.id_customer)
                LEFT JOIN `"._DB_PREFIX_."currency` cu ON (cu.id_currency = a.id_currency)
                LEFT JOIN `"._DB_PREFIX_."carrier` ca ON (ca.id_carrier = a.id_carrier)
                LEFT JOIN `"._DB_PREFIX_."address` ad ON (a.id_address_invoice = ad.id_address)
                LEFT JOIN `"._DB_PREFIX_."orders` o ON (o.id_cart = a.id_cart)
                WHERE a.date_add > (NOW() - INTERVAL ".$higher." MINUTE) ORDER BY a.id_cart DESC 
        ) AS toto WHERE id_order='Abandoned cart'
        ORDER BY toto.date_add DESC";

        $cart_list = Db::getInstance()->ExecuteS($sql);

        if ($debug) {
            $this->doDebug("Found ".count($cart_list) . " abandoned carts:");
            $this->doDebug($cart_list);
            echo "<br><br>";
            $this->doDebug("The following carts have a valid mobile number:");
        }

        if ($cart_list && is_array($cart_list) && count($cart_list)>0) {
            if (Language::countActiveLanguages()>1) {
                $message_template = Configuration::getInt('WL_TP_ABANDONED_CART')[$this->context->language->id];
            } else {
                $message_template = Configuration::get('WL_TP_ABANDONED_CART');
            }

            foreach ($cart_list as $recipient) {
                if ($recipient['phone_mobile'] != null && Tools::strlen($recipient['phone_mobile'])>4) {
                    $message = str_replace("{customer_firstname}", $recipient['firstname'], $message_template);
                    $message = str_replace("{customer_lastname}", $recipient['lastname'], $message);

                    if ($debug) {
                        $this->doDebug($message);
                    } else {
                        $this->sendSMS(
                            $recipient['phone_mobile'],
                            $message
                        );
                    }
                }
            }
        }
    }

    public function sendBirthdayGift($debug = false)
    {
        // get celebrants of the day
        $sql = "
        SELECT
            cstmr.`lastname`,
            cstmr.`firstname`,
            cstmr.`id_customer`,
            ad.`phone_mobile`
        FROM `"._DB_PREFIX_."customer` cstmr
                LEFT JOIN `"._DB_PREFIX_."address` ad ON cstmr.id_customer = ad.id_customer
        WHERE DAY(cstmr.birthday) = DAY(CURDATE()) and MONTH(cstmr.birthday) = MONTH(CURDATE())
        ORDER BY cstmr.id_customer ASC";

        $celebrants_list = Db::getInstance()->ExecuteS($sql);

        if ($debug) {
            $this->doDebug("Found ".count($celebrants_list) . " celebrants of the day:");
            $this->doDebug($celebrants_list);
            $this->doDebug("The following celebrants have a valid mobile number:");
        }

        if ($celebrants_list && is_array($celebrants_list) && count($celebrants_list)>0) {
            if (Language::countActiveLanguages()>1) {
                $message_template = Configuration::getInt('WL_TP_BIRTHDAY')[$this->context->language->id];
            } else {
                $message_template = Configuration::get('WL_TP_BIRTHDAY');
            }

            foreach ($celebrants_list as $recipient) {
                if ($recipient['phone_mobile'] != null && Tools::strlen($recipient['phone_mobile'])>4) {
                    $message = str_replace("{customer_firstname}", $recipient['firstname'], $message_template);
                    $message = str_replace("{customer_lastname}", $recipient['lastname'], $message);

                    if ($debug) {
                        $this->doDebug($message);
                    } else {
                        $this->sendSMS(
                            $recipient['phone_mobile'],
                            $message
                        );
                    }
                }
            }
        }
    }

    private function abandonedCarts($count = false, $page = 1, $fields_list = 50)
    {
        if ($page == 1) {
            $offset = 0;
        } else {
            $offset = ($page-1)*$fields_list;
        }
        // get abandoned cart :
        $sql = "SELECT * FROM (
        SELECT
            CONCAT(LEFT(c.`firstname`, 1), '. ', c.`lastname`) `customer`,
            a.id_cart total,ca.name carrier,
            c.id_customer,
            a.id_cart,
            a.date_upd,
            a.date_add,
                IF (IFNULL(o.id_order, 'Non ordered') = 'Non ordered', 
                IF(TIME_TO_SEC(TIMEDIFF('".date('Y-m-d H:i:s')."', a.`date_add`)) > 86000,
                'Abandoned cart', 'Non ordered'), o.id_order) id_order, IF(o.id_order, 1, 0) badge_success,
            IF(o.id_order, 0, 1) badge_danger, IF(co.id_guest, 1, 0) id_guest
        FROM `"._DB_PREFIX_."cart` a  
                JOIN `"._DB_PREFIX_."customer` c ON (c.id_customer = a.id_customer)
                LEFT JOIN `"._DB_PREFIX_."currency` cu ON (cu.id_currency = a.id_currency)
                LEFT JOIN `"._DB_PREFIX_."carrier` ca ON (ca.id_carrier = a.id_carrier)
                LEFT JOIN `"._DB_PREFIX_."orders` o ON (o.id_cart = a.id_cart)
                LEFT JOIN `"._DB_PREFIX_."connections` co ON (a.id_guest = co.id_guest AND
                    TIME_TO_SEC(TIMEDIFF('".date('Y-m-d H:i:s')."', co.`date_add`)) < 1800)
                WHERE a.date_add > (NOW() - INTERVAL 60 DAY) ORDER BY a.id_cart DESC 
        ) AS toto WHERE id_order='Abandoned cart'
        LIMIT ".$offset.", ".$fields_list;

        if ($count) {
            return count(Db::getInstance()->ExecuteS($sql));
        } else {
            return Db::getInstance()->ExecuteS($sql);
        }
    }

    private function displayCustomTop()
    {
        $shop = Tools::getHttpHost(true).__PS_BASE_URI__;
        $ref = implode('', array('a','d','d','o','n','s'));
        $module_version = $this->version;

        $this->context->smarty->assign(array(
            'path'=> $this->_path,
            'module_page'=> $this->context->link->getAdminLink(
                'AdminModules',
                false
            ).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.
            $this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'shop'=> $shop,
            'ref'=> $ref,
            'moduleversion'=> $module_version,
            'modulename'=> $this->name,
            'moduletitle'=> $this->displayName
        ));
        $this->html .= $this->display(__FILE__, 'top.tpl');
    }

    private function displayCustomBottom()
    {
        $this->context->smarty->assign(array(
            'delay_enabled'=> Configuration::get('SMS_NOTIFICATION_ENABLE_DELAY')
        ));
        $this->html .= $this->display(__FILE__, 'bottom.tpl');
    }

    private function displayBackButton()
    {
        $this->context->smarty->assign(array(
            'goback'=> $this->context->link->getAdminLink(
                'AdminModules',
                false
            ).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.
            $this->name."&token=".Tools::getAdminTokenLite('AdminModules')
        ));
        $this->html .= $this->display(__FILE__, 'back-button.tpl');
    }

    public function getContent()
    {
        $this->postProcess();
        $this->displayCustomTop();
        $this->displayCustomBottom();
        
        if (Tools::getIsset('sentHistory')) {
            $this->displayBackButton();
            $this->displayHistory();
        } else {
            $this->displayForm();
            $this->html .= $this->generateAbandonedCartList();
        }
        return $this->html;
    }

    private function postProcess()
    {

        $fields = array();
        $fields['apisecret'] = Tools::getValue('apisecret');
        $fields['deviceid'] = Tools::getValue('deviceid');
        $fields['adminmobile'] = Tools::getValue('adminmobile');
        $fields['sim'] = Tools::getValue('sim');

        $errors = 0;

        foreach ($fields as $key => $value) {
            if (!Validate::isGenericName($value)) {
                $this->_errors[] = $this->l('Invalid Field'). ': ' . $key;
            }
        }

        if (Tools::isSubmit('submitUpdate')) {
            if (Tools::strlen(Tools::getValue('apisecret'))<1) {
                $this->_errors[] = $this->l('Empty Field'). ': ' . $this->l('API Token');
                $errors++;
            }

            if (Tools::strlen(Tools::getValue('adminmobile'))<1) {
                $this->_errors[] = $this->l('Empty Field'). ': ' . $this->l('Admin\'s mobile phone');
                $errors++;
            }

            if ($errors<1) {
                // $country_data = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));
                $admin_mobile = Tools::getValue('adminmobile');
                Configuration::updateValue(
                    'SMS_NOTIFICATION_THESWITCH',
                    Tools::getValue('theswitch')
                );
                Configuration::updateValue(
                    'WL_FREE_SMS_CONSENT',
                    Tools::getValue('WL_FREE_SMS_CONSENT')
                );
                Configuration::updateValue(
                    'WL_FREE_SMS_DEBUG',
                    Tools::getValue('WL_FREE_SMS_DEBUG')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_ACTIVEAPI',
                    Tools::getValue('activeapi')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_APISECRET',
                    Tools::getValue('apisecret')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_DEVICEID',
                    Tools::getValue('deviceid')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_SIM',
                    Tools::getValue('sim')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_ADMINMOBILE',
                    $admin_mobile
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_ADMIN_ORDERS',
                    Tools::getValue('notif_admin_new_orders')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_ADMIN_CUSTOMERS',
                    Tools::getValue('notif_admin_new_customers')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_CUSTOMERS_SIGNUP',
                    Tools::getValue('notif_customer_customer_signup')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_SUPPLIER_SALE',
                    Tools::getValue('notif_supplier_product_sale')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_OUT_OF_STOCK',
                    Tools::getValue('notif_admin_out_of_stock')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_ADMIN_ORDER_UPDATE',
                    Tools::getValue('notif_admin_order_update')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_CUSTOMERS_ORDERS',
                    Tools::getValue('notif_customer_new_orders')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_CUSTOMERS_ORDER_UPDATE',
                    Tools::getValue('notif_customer_order_update')
                );
                Configuration::updateValue(
                    'SMS_NOTIFICATION_NEW_MESSAGE',
                    Tools::getValue('notif_admin_new_message')
                );
            }

            if ($this->_errors) {
                $this->html .= $this->displayError(implode($this->_errors, '<br />'));
            } else {
                $this->html .= $this->displayConfirmation($this->l('Settings Updated'));
            }
        }

        if (Tools::isSubmit('submitCronUpdate')) {
            if (Validate::isInt(Tools::getValue('WL_FREE_SMS_LOWER'))) {
                $this->_errors[] = $this->l('Invalid Field'). ': ' . $this->l('Abandoned carts lower limit timeframe');
                $errors++;
            }

            if (Validate::isInt(Tools::getValue('WL_FREE_SMS_HIGHER'))) {
                $this->_errors[] = $this->l('Invalid Field'). ': ' . $this->l('Abandoned carts high limit timeframe');
                $errors++;
            }

            if ($errors<1) {
                Configuration::updateValue(
                    'SMS_NOTIFICATION_ENABLE_DELAY',
                    Tools::getValue('enable_delay')
                );
                Configuration::updateValue(
                    'WL_FREE_SMS_INTERVAL',
                    Tools::getValue('WL_FREE_SMS_INTERVAL')
                );
                Configuration::updateValue(
                    'WL_FREE_SMS_LOWER',
                    Tools::getValue('WL_FREE_SMS_LOWER')
                );
                Configuration::updateValue(
                    'WL_FREE_SMS_HIGHER',
                    Tools::getValue('WL_FREE_SMS_HIGHER')
                );
            }

            if ($this->_errors) {
                $this->html .= $this->displayError(implode($this->_errors, '<br />'));
            } else {
                $this->html .= $this->displayConfirmation($this->l('Settings Updated'));
            }
        }

        if (Tools::isSubmit('saveSMSTemplates')) {
            $order_states = OrderState::getOrderStates($this->context->language->id);
            foreach ($order_states as $order_state) {
                $input_array = array();

                foreach ($this->context->controller->getLanguages() as $lang) {
                    $input_array[$lang['id_lang']] =
                        Tools::getValue('WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state'].'_'.$lang['id_lang']);
                }

                if (Language::countActiveLanguages()>1) {
                    Configuration::updateValue(
                        'WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state'],
                        $input_array,
                        true
                    );
                } else {
                    Configuration::updateValue(
                        'WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state'],
                        Tools::getValue('WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state'])
                    );
                }
            }

            $admin_new_order = array();
            $admin_new_customer = array();
            $admin_new_message = array();
            $admin_product_oos = array();
            $customer_new_order = array();
            $customer_abandoned_cart = array();
            $customer_birthday = array();

            foreach ($this->context->controller->getLanguages() as $lang) {
                $admin_new_order[$lang['id_lang']] = Tools::getValue('WL_TP_ADMIN_NEW_ORDER_'.$lang['id_lang']);
                $admin_new_customer[$lang['id_lang']] = Tools::getValue('WL_TP_ADMIN_NEW_CUSTOMER_'.$lang['id_lang']);
                $admin_new_message[$lang['id_lang']] = Tools::getValue('WL_TP_ADMIN_NEW_MESSAGE_'.$lang['id_lang']);
                $admin_product_oos[$lang['id_lang']] = Tools::getValue('WL_TP_ADMIN_PRODUCT_OOS_'.$lang['id_lang']);
                $customer_new_order[$lang['id_lang']] = Tools::getValue('WL_TP_CUSTOMER_NEW_ORDER_'.$lang['id_lang']);
                $customer_abandoned_cart[$lang['id_lang']] = Tools::getValue('WL_TP_ABANDONED_CART_'.$lang['id_lang']);
                $customer_birthday[$lang['id_lang']] = Tools::getValue('WL_TP_BIRTHDAY_'.$lang['id_lang']);
            }

            if (Language::countActiveLanguages()>1) {
                Configuration::updateValue('WL_TP_ADMIN_NEW_ORDER', $admin_new_order, true);
                Configuration::updateValue('WL_TP_ADMIN_NEW_CUSTOMER', $admin_new_customer, true);
                Configuration::updateValue('WL_TP_ADMIN_NEW_MESSAGE', $admin_new_message, true);
                Configuration::updateValue('WL_TP_ADMIN_PRODUCT_OOS', $admin_product_oos, true);
                Configuration::updateValue('WL_TP_CUSTOMER_NEW_ORDER', $customer_new_order, true);
                Configuration::updateValue('WL_TP_ABANDONED_CART', $customer_abandoned_cart, true);
                Configuration::updateValue('WL_TP_BIRTHDAY', $customer_birthday, true);
            } else {
                Configuration::updateValue('WL_TP_ADMIN_NEW_ORDER', Tools::getValue('WL_TP_ADMIN_NEW_ORDER'));
                Configuration::updateValue('WL_TP_ADMIN_NEW_CUSTOMER', Tools::getValue('WL_TP_ADMIN_NEW_CUSTOMER'));
                Configuration::updateValue('WL_TP_ADMIN_NEW_MESSAGE', Tools::getValue('WL_TP_ADMIN_NEW_MESSAGE'));
                Configuration::updateValue('WL_TP_ADMIN_PRODUCT_OOS', Tools::getValue('WL_TP_ADMIN_PRODUCT_OOS'));
                Configuration::updateValue('WL_TP_CUSTOMER_NEW_ORDER', Tools::getValue('WL_TP_CUSTOMER_NEW_ORDER'));
                Configuration::updateValue('WL_TP_ABANDONED_CART', Tools::getValue('WL_TP_ABANDONED_CART'));
                Configuration::updateValue('WL_TP_BIRTHDAY', Tools::getValue('WL_TP_BIRTHDAY'));
            }
            

            $this->confirmations[] = $this->l('Settings Updated');
        }

        if (Tools::isSubmit('sendSMS')) {
            if (Tools::strlen(Tools::getValue('recipient'))<1) {
                $this->html .= $this->displayError('Empty recipient!');
            } elseif (Tools::strlen(Tools::getValue('message'))<1) {
                $this->html .= $this->displayError('Empty message!');
            } elseif (!Validate::isPhoneNumber(Tools::getValue('recipient'))) {
                $this->html .= $this->displayError('Please enter a valid phone number!');
            } else {
                $mobile = $this->formatMobileNumber(Tools::getValue('recipient'), false);

                $this->sendSMS(
                    $mobile,
                    Tools::getValue('message')
                );

                $this->html .= $this->displayConfirmation($this->l('Message sent'));
            }
        }

        if (Tools::isSubmit('submitSMSConf')) {
            Configuration::updateValue(
                'WL_FREE_SMS_ENABLE_ORDER_CONF',
                Tools::getValue('WL_FREE_SMS_ENABLE_ORDER_CONF')
            );
            $this->confirmations[] = $this->l('Settings Updated');
        }

        if (Tools::getIsset('clearHistory')) {
            Db::getInstance()->delete($this->table_name.'_log', '');
            $this->html .= $this->displayConfirmation($this->l('SMS & Whatsapp History cleared'));
        }

        if (Tools::getIsset('initiate') && Tools::getValue('initiate')=="phone_mobile") {
            $sql = "UPDATE ".
                _DB_PREFIX_."address SET `phone_mobile`=`phone` WHERE phone_mobile IS NULL OR phone_mobile = ''";

            Db::getInstance()->Execute($sql);

            $this->html .= $this->displayConfirmation(
                $this->l('Customer addresses have been successfully updated! All addresses containing null
                    value on phone_mobile have been populated with the phone number value.')
            );
        }

        if (Tools::isSubmit('sendMarketingSMS')) {
            if (Tools::strlen(Tools::getValue('bulkto'))<1) {
                $this->html .= $this->displayError('Empty recipient list!');
            } elseif (Tools::strlen(Tools::getValue('bulkmessage'))<1) {
                $this->html .= $this->displayError('Empty message!');
            } else {
                $recipients = explode(',', Tools::getValue('bulkto'));
                $message = Tools::getValue('bulkmessage');

                foreach ($recipients as $recipient) {
                    $mobile = $this->formatMobileNumber($recipient, false);

                    $this->sendSMS(
                        $mobile,
                        $message
                    );
                }

                $this->html .= $this->displayConfirmation($this->l('Message sent'));
            }
        }

        if (Tools::getIsset('submitBulksendabandoned')) {
            $customers = Tools::getValue('abandonedBox');
            $mobile_numbers = $this->getCustomerMobileNumbers($customers);

            if (Language::countActiveLanguages()>1) {
                $message_template = Configuration::getInt('WL_TP_ABANDONED_CART')[$this->context->language->id];
            } else {
                $message_template = Configuration::get('WL_TP_ABANDONED_CART');
            }

            foreach ($mobile_numbers as $recipient) {
                $message = str_replace("{customer_firstname}", $recipient['firstname'], $message_template);
                $message = str_replace("{customer_lastname}", $recipient['lastname'], $message);

                $this->sendSMS(
                    $recipient['phone_mobile'],
                    $message
                );
            }

            $this->html .= $this->displayConfirmation($this->l('Abandoned cart reminder successfully sent.'));
        }

        if (Tools::getIsset('updateabandoned')) {
            $customers = Tools::getValue('id_customer');
            $mobile_numbers = $this->getCustomerMobileNumbers(array($customers));

            if (Language::countActiveLanguages()>1) {
                $message_template = Configuration::getInt('WL_TP_ABANDONED_CART')[$this->context->language->id];
            } else {
                $message_template = Configuration::get('WL_TP_ABANDONED_CART');
            }

            foreach ($mobile_numbers as $recipient) {
                $message = str_replace("{customer_firstname}", $recipient['firstname'], $message_template);
                $message = str_replace("{customer_lastname}", $recipient['lastname'], $message);

                $this->sendSMS(
                    $recipient['phone_mobile'],
                    $message
                );
            }

            $this->html .= $this->displayConfirmation($this->l('Abandoned cart reminder successfully sent.'));
        }
    }

    public function hookActionUpdateQuantity($params)
    {
        if (Configuration::get('SMS_NOTIFICATION_OUT_OF_STOCK') == 1) {
            $id_product = (int) $params['id_product'];
            $id_product_attribute = (int) $params['id_product_attribute'];

            $quantity = (int) $params['quantity'];
            $context = Context::getContext();
            $id_shop = (int) $context->shop->id;
            $id_lang = (int) $context->language->id;
            $product = new Product($id_product, false, $id_lang, $id_shop, $context);
            $product_has_attributes = $product->hasAttributes();

            $check_oos = ($product_has_attributes && $id_product_attribute) ||
                (!$product_has_attributes && !$id_product_attribute);

            if ($check_oos &&
                $product->active == 1 &&
                (int) $quantity <= 0) {
                $product_name = Product::getProductName($id_product, $id_product_attribute, $id_lang);

                $message = Configuration::get('WL_TP_ADMIN_PRODUCT_OOS');
                $message = str_replace("{product_name}", $product_name, $message);

                foreach ($this->getAdminMobileNumbers() as $admin_mobile_number) {
                    $this->sendSMS(
                        $admin_mobile_number,
                        $message
                    );
                }
            }
        }
    }

    public function formatMobileNumber($number, $country_code)
    {
        if (Tools::substr(Tools::getValue('recipient'), 0, 1) == "+") {
            return $number;
        }

        if ($country_code == false) {
            if (isset($this->context) &&
                isset($this->context->country) &&
                isset($this->context->country->call_prefix)
            ) {
                $country_code = $this->context->country->call_prefix;
            } else {
                $country_data = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));
                $country_code = $country_data->call_prefix;
            }
        }
        $country_code = str_replace('+', '', $country_code);
        $num = preg_replace('/^(?:\+?'.$country_code.'|0)?/', '+'.$country_code, $number);

        return $num;
    }

    public function checkLastMessages($mobile)
    {
        $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.$this->table_name.'_log
            WHERE phone = "'.$mobile.'"  AND date_sent  >= (NOW() - 300)';
        $result = Db::getInstance()->getValue($sql);

        return $result;
    }

    public function checkSMSDuplicate($mobile, $message)
    {
        $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.$this->table_name.'_log
            WHERE phone = "'.$mobile.'" AND message = "'.pSQL($message).'"';
        $result = Db::getInstance()->getValue($sql);

        return $result;
    }

    public function sendSMS($to, $message)
    {
        //check for duplicated sms messages
        $duplicates = $this->checkSMSDuplicate($to, $message);
        if ((int)$duplicates>0) {
            return true;
        }

        if (Configuration::get('SMS_NOTIFICATION_ENABLE_DELAY') == 0) {
            if ((int)Configuration::get('SMS_NOTIFICATION_THESWITCH') === 1 && Tools::strlen($message)>3) {
                
				
				// adding msgbucket Whatsapp Indian Users start
				
				if (Configuration::get('SMS_NOTIFICATION_ACTIVEAPI') == 1) {
                    $token = Configuration::get('SMS_NOTIFICATION_APISECRET');
					$nodeurl = 'https://server.msgbucket.com/send';
                    $data = [
                        'token' => Configuration::get('SMS_NOTIFICATION_APISECRET'),
                        'receiver' => $to,
                        'msgtext'   => $message
                    ];

                    $ch = curl_init();
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
					curl_setopt($ch, CURLOPT_URL, $nodeurl);
					curl_setopt($ch, CURLOPT_TIMEOUT, 30);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					$response = curl_exec($ch);
					curl_close($ch);
                }
				
				// adding msgbucket Whatsapp end
				
                
                // adding msgbucket Whatsapp International Users start
				
				elseif (Configuration::get('SMS_NOTIFICATION_ACTIVEAPI') == 2) {
                    $token = Configuration::get('SMS_NOTIFICATION_APISECRET');
					$nodeurl = 'https://server-us.msgbucket.com/send';
                    $data = [
                        'token' => Configuration::get('SMS_NOTIFICATION_APISECRET'),
                        'receiver' => $to,
                        'msgtext'   => $message
                    ];

                    $ch = curl_init();
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
					curl_setopt($ch, CURLOPT_URL, $nodeurl);
					curl_setopt($ch, CURLOPT_TIMEOUT, 30);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					$response = curl_exec($ch);
					curl_close($ch);
                }
				
				// adding msgbucket Whatsapp end
                
				
				// adding msgbucket Sms start
				
				
				elseif (Configuration::get('SMS_NOTIFICATION_ACTIVEAPI') == 3) {
					$msg = $message;
					$number = $to;
					$sim = (int)Configuration::get('SMS_NOTIFICATION_SIM');
					$key = Configuration::get('SMS_NOTIFICATION_APISECRET');
					$url = 'https://sms.msgbucket.com/services/send.php';
					$postData = array(
						'number' => $number,
						'message' => $message,
						'key' => $key,
						'devices' => (int)Configuration::get('SMS_NOTIFICATION_DEVICEID'),
						'simSlot' => $sim,
						'type' => "sms",
						'prioritize' => 1
					);
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
				$response = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if (curl_errno($ch)) {
				throw new Exception(curl_error($ch));
				}
				curl_close($ch);
				
				}
				
				
				// adding msgbucket Sms end
				
				
            } else {
                return true;
            }
            Db::getInstance()->insert(
                $this->table_name.'_log',
                array(
                    'phone' => pSQL($to),
                    'message' => pSQL($message)
                )
            );
        } else {
            Db::getInstance()->insert(
                $this->table_name.'_stack',
                array(
                    'phone' => pSQL($to),
                    'message' => pSQL($message),
                    'date_sent' => date("d-m-Y h:i:s", time())
                )
            );
        }
    }

    public function displayForm()
    {
        $this->html .= $this->generateForm();
    }

    public function displayHistory()
    {
        $this->html .= $this->generateLogList();
    }

    private function generateForm()
    {
        $inputs = array();
        $inputs2 = array();
        $inputs3 = array();
        $inputs_cron = array();
        $inputs_conf = array();
        $templates = array();

        $periods = array(
            array('id_option' => '1', 'name' => $this->l('Every 1 Minute')),
            array('id_option' => '2', 'name' => $this->l('Every 2 Minutes')),
            array('id_option' => '3', 'name' => $this->l('Every 5 Minutes')),
            array('id_option' => '4', 'name' => $this->l('Every 10 Minutes')),
            array('id_option' => '5', 'name' => $this->l('Every 30 Minutes')),
            array('id_option' => '6', 'name' => $this->l('Every Hour')),
            array('id_option' => '7', 'name' => $this->l('Every 2 Hours')),
            array('id_option' => '8', 'name' => $this->l('Every 6 Hours'))
        );

        $order_states = OrderState::getOrderStates($this->context->language->id);

        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Enable'),
            'name' => 'theswitch',
            'desc' => $this->l('Choose to enable/disable SMS and Whatsapp Notifications on your website'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $consent_text = '';
        if (Configuration::get('WL_FREE_SMS_CONSENT') == 1) {
            $consent_text = '<br/><span style="color:red;">'.$this->l('Warning!'). ' ' .
                $this->l('If set to Yes, SMS and Whatsapp will be delivered only to customers that gave their consent.').'</span>';
        }

        $debug_text = '';
        if (Configuration::get('WL_FREE_SMS_DEBUG') == 1) {
            $debug_text = '<br/><span style="color:red;">'.$this->l('Warning!'). ' ' .
                $this->l('Debug mode active. Customers will be able to see the debug informations on the frontend.')
                .'</span>';
        }

        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Customer SMS & Whatsapp consent'),
            'name' => 'WL_FREE_SMS_CONSENT',
            'desc' => $this->l('Choose if customers should give their consent when receiving SMS & Whatsapp notifications.').
                "<br/>".$this->l('A dedicated page will be displayed on customer\'s account for the SMS & Whatsapp consent.').
                $consent_text,
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Debug Mode'),
            'name' => 'WL_FREE_SMS_DEBUG',
            'desc' => $this->l('Choose to enable/disable Debug Mode on your website').'<br><strong>'.
                $this->l('Important: If enabled, debug messages will be visible to your customers too').'</strong>'.
                $debug_text,
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Active API'),
            'name' => 'activeapi',
            'desc' => $this->l('The SMS & Whatsapp Gateway used to send SMS & Whatsapp notifications'),
            'hint' => $this->l('This version of the module allows you to send free SMS & Whatsapp using your mobile carrier
                , but it can be used with many other integrations'),
            'options' => array(
                'query' => $this->apis,
                'id' => 'id_option',
                'name' => 'name'
            )
        );

        $apitoken_string = '';
        if (Configuration::get('SMS_NOTIFICATION_ACTIVEAPI') == 0) {
        } else {
            $apitoken_string = $this->l('The API Token from your wa.msgbucket.com or us.msgbucket.com or sms.msgbucket.com account').
                '<br><a href="https://wa.msgbucket.com" target="_blank">'.
                $this->l('Click here').'</a> '.$this->l('to get the token from your wa.msgbucket.me account (Indian Users)').
                
                '<br><a href="https://us.msgbucket.com" target="_blank">'.
                $this->l('Click here').'</a> '.$this->l('to get the token from your us.msgbucket.com account (International Users).').
                '<br><a href="https://sms.msgbucket.com" target="_blank">'.
                $this->l('Click here').'</a> '.$this->l('to get the token from your sms.msgbucket.com account.');
        }

        $inputs[] = array(
            'type' => 'text',
            'required' => true,
            'class' => 'fixed-width-lg',
            'label' => $this->l('API Token'),
            'desc' => $apitoken_string,
            'name' => 'apisecret'
        );

        $deviceid_string = '';
        if (Configuration::get('SMS_NOTIFICATION_ACTIVEAPI') == 0) {
            
        } else {
            $deviceid_string = '<strong>'.$this->l('Leave empty to disable and while using whatsapp.').'<br>'.'</strong>'.
                $this->l('The Device ID from your sms.msgbucket.com account').
                '<br><a href="https://sms.msgbucket.com" target="_blank">'.
                $this->l('Click here').'</a> '.
                $this->l('to get the Device ID from your sms.msgbucket.com account.').'</a>';
        }


        $inputs[] = array(
            'type' => 'text',
            'class' => 'fixed-width-lg',
            'label' => $this->l('Device ID'),
            'desc' => $deviceid_string,
            'name' => 'deviceid'
        );

        $simid_string = '';
        if (Configuration::get('SMS_NOTIFICATION_ACTIVEAPI') == 0) {
            
        } else {
            $simid_string = '<strong>'.$this->l('Leave empty to disable while using Whatsapp.').'<br>'.'</strong>'.
            '<strong>'.$this->l('SIM ID is required only if you use sms.msgbucket.com!').'</strong>'.
                '<br>'.$this->l('The SIM ID from your sms.msgbucket.com account').
                $this->l('and Click on the Device Name assigned to your mobile phone.');
                
        }

        $inputs[] = array(
            'type' => 'text',
            'class' => 'fixed-width-lg',
            'label' => $this->l('SIM ID'),
            'desc' => $simid_string,
            'name' => 'sim'
        );


        $inputs[] = array(
            'type' => 'text',
            'required' => true,
            'class' => 'fixed-width-lg',
            'label' => $this->l("Admin's mobile phone"),
            'desc' => $this->l('The number used to receive Admin Notifications').'<br>'.
                $this->l('You can also enter multiple admin mobile numbers delimited by comma.').'<br>'.
                $this->l('Example:').' +40123456789<strong>,</strong>+401234567890',
            'name' => 'adminmobile'
        );

        $inputs[] = array(
            'type' => 'checkbox',
            'label' => $this->l('Customer notifications'),
            'name' => 'notif_customer',
            'desc' => $this->l('Notifications send only to customers'),
            'values' => array(
                'query' => array(
                    array(
                        'id' => 'new_orders',
                        'name' => $this->l('New order'),
                        'val' => '1',
                    ),
                    array(
                        'id' => 'order_update',
                        'name' => $this->l('Order update'),
                        'val' => '2',
                    ),
                    // array(
                    //     'id' => 'customer_signup',
                    //     'name' => $this->l('Signup welcome message'),
                    //     'val' => '3',
                    // ),
                ),
                'id' => 'id',
                'name' => 'name'
            )
        );

        $inputs[] = array(
            'type' => 'checkbox',
            'label' => $this->l('Admin notifications'),
            'name' => 'notif_admin',
            'desc' => $this->l('Notifications send only to admin'),
            'values' => array(
                'query' => array(
                    array(
                        'id' => 'new_orders',
                        'name' => $this->l('New orders'),
                        'val' => '1',
                    ),
                    array(
                        'id' => 'new_customers',
                        'name' => $this->l('New customers'),
                        'val' => '2',
                    ),
                    // array(
                    //     'id' => 'out_of_stock',
                    //     'name' => $this->l('Product out of stock'),
                    //     'val' => '3',
                    // ),
                    array(
                        'id' => 'new_message',
                        'name' => $this->l('New message received'),
                        'val' => '4',
                    ),
                ),
                'id' => 'id',
                'name' => 'name'
            )
        );

        $inputs[] = array(
            'type' => 'checkbox',
            'label' => $this->l('Supplier notifications'),
            'name' => 'notif_supplier',
            'desc' => $this->l('Notifications send only to suppliers'),
            'values' => array(
                'query' => array(
                    array(
                        'id' => 'product_sale',
                        'name' => $this->l('Product sale'),
                        'val' => '1',
                    )
                ),
                'id' => 'id',
                'name' => 'name'
            )
        );

        $inputs_cron[] = array(
            'type' => 'switch',
            'label' => $this->l('SMS & Whatsapp delay'),
            'name' => 'enable_delay',
            'class' => 'enable_delay',
            'id' => 'enable_delay',
            'desc' => $this->l('Send SMS & Whatsapp notifications with a delay using CRON JOB'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs_cron[] = array(
            'type' => 'select',
            'label' => $this->l('CRON JOB interval'),
            'desc' => $this->l('Set the cron run interval'),
            'name' => 'WL_FREE_SMS_INTERVAL',
            'class' => 'interval_select',
            'options' => array(
                'query' => $periods,
                'id' => 'id_option',
                'name' => 'name'
            )
        );

        $inputs_cron[] = array(
            'type' => 'html',
            'label' => $this->l('Important'),
            'name' => '',
            'desc' => $this->l('For the delayed SMS & Whatsapp delivery, the CRON JOB needs to be
                executed on your server with the following command: ').
                '<br /><span class="cron-command"><strong><em><span class="cron-target"></span> '.
                    Context::getContext()->link->getModuleLink(
                        $this->name,
                        'cron',
                        array('secret' => Configuration::get('WL_FS_SECRET_CRON'))
                    ).
                    '</em></strong></span><hr>'
        );


        $inputs_cron[] = array(
            'type' => 'text',
            'required' => true,
            'label' => $this->l('Abandoned carts lower limit timeframe'),
            'desc' => $this->l('Set the lower limit amount of minutes used to determine whenever
                a cart is selected into the abandoned list.').'<br>'.
                $this->l('For example if we write down 3 minutes, the CRON JOB will send
                    SMS & Whatsapp notifications to users that abandoned their cart 3 minutes ago or higher').'<br>'.
                $this->l('Default value: 3 minutes'),
            'name' => 'WL_FREE_SMS_LOWER',
            'class' => 'input fixed-width-md',
            'suffix' => $this->l('minutes'),
        );

        $inputs_cron[] = array(
            'type' => 'text',
            'required' => true,
            'label' => $this->l('Abandoned carts high limit timeframe'),
            'desc' => $this->l('Set the higher limit amount of minutes used to determine whenever
                a cart is selected into the abandoned list.').'<br>'.
                $this->l('For example if we write down 300 minutes, the CRON JOB will send
                    SMS & Whatsapp notifications to users that abandoned their cart 300 minutes ago or higher').'<br>'.
                $this->l('Default value: 300 minutes'),
            'name' => 'WL_FREE_SMS_HIGHER',
            'class' => 'input fixed-width-md',
            'suffix' => $this->l('minutes'),
        );


        $croninterval = ((int)Configuration::get('WL_FREE_SMS_HIGHER')>0?
            (int)Configuration::get('WL_FREE_SMS_HIGHER')/60:5);
        $croninterval_low = ((int)Configuration::get('WL_FREE_SMS_LOWER')>0?
            (int)Configuration::get('WL_FREE_SMS_LOWER'):3);

        $inputs_cron[] = array(
            'type' => 'html',
            'label' => $this->l('Abandoned carts notifications'),
            'name' => '',
            'desc' => $this->l('This feature allows you to send automated SMS & Whatsapp notifications to your customers that have
                abandoned their carts.').'<br />'.$this->l('Using it, your shop will have higher conversion rate.')
                .'<br /><br />'.$this->l('For sending abandoned cart SMS & Whatsapp notifications, the CRON JOB needs to be
                executed with the following link: ').'<br /><strong><em> '.
                Context::getContext()->link->getModuleLink(
                    $this->name,
                    'cron',
                    array(
                        'secret' => Configuration::get('WL_FS_SECRET_CRON'),
                        'action' => 'sendCartReminder'
                    )
                ).
                '</em></strong></span>'.'<br>'.
                $this->l('The cron should be executed every').' '.$croninterval.' '. $this->l('hours and').' '.
                $croninterval_low.' '.$this->l('minutes').' '.
                $this->l('and it will send notifications to customers that
                abandoned their carts in this timeframe: between').' '.$croninterval_low.' '.$this->l('minutes ago and')
                .' '.$croninterval.' '.$this->l('hours ago').
                '<br><br>'.
                $this->l('Add the following parameter in order to debug the abandoned cart sending queue: ').
                "<br><strong>&debug=1</strong><br> - ".$this->l('if using &debug=1 the SMS & Whatsapp messages will not be sent.')
        );

        $inputs_cron[] = array(
            'type' => 'html',
            'label' => $this->l('Birthday SMS & Whatsapp notifications'),
            'name' => '',
            'desc' => $this->l('This feature allows you to send automated SMS & Whatsapp notifications to your customers that
                celebrate their birthday.').'<br />'.$this->l('Using it, your shop will have higher conversion rate.')
                .'<br />'.
                $this->l('We recommend that you also add a voucher code into the Birthday SMS & Whatsapp Template down below.')
                .'<br /><br />'.$this->l('For sending automated birthday SMS & Whatsapp notifications, the CRON JOB needs to be
                executed with the following link: ').'<br /><strong><em> '.
                Context::getContext()->link->getModuleLink(
                    $this->name,
                    'cron',
                    array(
                        'secret' => Configuration::get('WL_FS_SECRET_CRON'),
                        'action' => 'sendBirthdayGift'
                    )
                ).
                '</em></strong></span>'.'<br>'.
                $this->l('The cron job should be executed every day').
                '<br><br>'.
                $this->l('Add the following parameter in order to list the celebrants of the day: ').
                "<br><strong>&debug=1</strong><br> - ".$this->l('if using &debug=1 the SMS & Whatsapp messages will not be sent.')
        );

        $inputs2[] = array(
            'type' => 'text',
            'class' => 'fixed-width-lg',
            'label' => $this->l('To'),
            'name' => 'recipient'
        );

        $inputs2[] = array(
            'type' => 'hidden',
            'class' => 'fixed-width-lg',
            'label' => $this->l('Sender'),
            'name' => 'sender'
        );

        $inputs2[] = array(
            'type' => 'textarea',
            'label' => $this->l('Message'),
            'maxlength' => '160',
            'name' => 'message',
            // 'autoload_rte' => true,
            'lang' => false
        );

        $inputs3[] = array(
            'type' => 'textarea',
            'label' => $this->l('To'),
            'name' => 'bulkto',
            'desc' =>
                $this->l('Enter the numbers separated by comma. Be sure that all the numbers have the country code!'),
            // 'autoload_rte' => true,
            'lang' => false
        );

        $inputs3[] = array(
            'type' => 'hidden',
            'class' => 'fixed-width-lg',
            'label' => $this->l('Sender'),
            'name' => 'bulksender'
        );

        $inputs3[] = array(
            'type' => 'textarea',
            'label' => $this->l('Message'),
            'name' => 'bulkmessage',
            'maxlength' => '160',
            // 'autoload_rte' => true,
            'lang' => false
        );

        $templates[] = array(
            'type' => 'textarea',
            'label' => $this->l('Admin new order'),
            'desc' => $this->l('You can also use shortcodes to build a message:') .
                ' {customer_firstname}, {customer_lastname}, {order_total}, {order_id},
                {product_data}, {order_number}, {courier_service}',
            'name' => 'WL_TP_ADMIN_NEW_ORDER',
            'maxlength' => '160',
            // 'autoload_rte' => true,
            'lang' => (Language::countActiveLanguages()>1?true:false)
        );

        $templates[] = array(
            'type' => 'textarea',
            'label' => $this->l('ADMIN new customer'),
            'desc' => $this->l('You can also use shortcodes to build a message:') .
                ' {customer_firstname}, {customer_lastname}, {customer_email}',
            'name' => 'WL_TP_ADMIN_NEW_CUSTOMER',
            'maxlength' => '160',
            // 'autoload_rte' => true,
            'lang' => (Language::countActiveLanguages()>1?true:false)
        );

        $templates[] = array(
            'type' => 'textarea',
            'label' => $this->l('ADMIN new message'),
            'desc' => $this->l('You can also use shortcodes to build a message:').'
                {message_from}, {message_content}',
            'name' => 'WL_TP_ADMIN_NEW_MESSAGE',
            'maxlength' => '160',
            // 'autoload_rte' => true,
            'lang' => (Language::countActiveLanguages()>1?true:false)
        );

        $templates[] = array(
            'type' => 'textarea',
            'label' => $this->l('ADMIN product out of stock'),
            'desc' => $this->l('You can also use shortcodes to build a message:').' {product_name}',
            'name' => 'WL_TP_ADMIN_PRODUCT_OOS',
            'maxlength' => '160',
            // 'autoload_rte' => true,
            'lang' => (Language::countActiveLanguages()>1?true:false)
        );

        $templates[] = array(
            'type' => 'textarea',
            'label' => $this->l('Customer new order'),
            'desc' => $this->l('You can also use shortcodes to build a message:') .
                ' {customer_firstname}, {customer_lastname}, {customer_email},
                {order_total}, {order_id}, {order_number}, {courier_service}, {confirmation_link}',
            'name' => 'WL_TP_CUSTOMER_NEW_ORDER',
            'maxlength' => '160',
            // 'autoload_rte' => true,
            'lang' => (Language::countActiveLanguages()>1?true:false)
        );

        $templates[] = array(
            'type' => 'textarea',
            'label' => $this->l('Abandoned cart'),
            'desc' => $this->l('You can also use shortcodes to build a message:') .
                ' {customer_firstname}, {customer_lastname}',
            'name' => 'WL_TP_ABANDONED_CART',
            'maxlength' => '160',
            // 'autoload_rte' => true,
            'lang' => (Language::countActiveLanguages()>1?true:false)
        );

        $templates[] = array(
            'type' => 'textarea',
            'label' => $this->l('Birthday celebrants'),
            'desc' => $this->l('You can also use shortcodes to build a message:') .
                ' {customer_firstname}, {customer_lastname}',
            'name' => 'WL_TP_BIRTHDAY',
            'maxlength' => '160',
            // 'autoload_rte' => true,
            'lang' => (Language::countActiveLanguages()>1?true:false)
        );

        foreach ($order_states as $order_state) {
            $templates[] = array(
                'type' => 'textarea',
                'label' => $this->l('Order status is:') . ' ' . $order_state['name'],
                'desc' => $this->l('You can also use shortcodes to build a message:') .
                    ' {customer_firstname}, {customer_lastname}, {order_status}, {order_id},
                    {tracking_code}, {product_data}, {order_number}, {courier_service}, {tracking_link},
                    {confirmation_link}',
                'name' => 'WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state'],
                'maxlength' => '160',
                // 'autoload_rte' => true,
                'lang' => (Language::countActiveLanguages()>1?true:false)
            );
        }

        $inputs_conf[] = array(
            'type' => 'switch',
            'label' => $this->l('Customer order confirmation'),
            'name' => 'WL_FREE_SMS_ENABLE_ORDER_CONF',
            'class' => 'enable_conf',
            'id' => 'enable_conf',
            'desc' => $this->l('Enable customer order confirmation by sending a confirmation link
                that gives an option to confirm/cancel the order.').'<br>'.
            $this->l('If enabled, you need to add the following shortcode into
                the Customer new order SMS & Whatsapp Template message: {confirmation_link}'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );
        
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                    ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitUpdate'
                ),
                'buttons' => array(
                    array(
                        'href' => AdminController::$currentIndex.'&configure='.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules').'&sentHistory',
                        'title' => $this->l('SMS & Whatsapp History'),
                        'name' => 'sentHistory'
                    ),
                    array(
                        'href' => AdminController::$currentIndex.'&configure='.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules').'&clearHistory',
                        'title' => $this->l('Clear SMS & Whatsapp History'),
                        'id' => 'clear-history',
                        'class' => 'needs-confirmation',
                        'name' => 'clearHistory'
                    )
                )
            )
        );

        $fields_form_cron = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('CRON JOB Settings - Send SMS & Whatsapp Notifications using CRON Jobs'),
                    'icon' => 'icon-clock-o'
                    ),
                'input' => $inputs_cron,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitCronUpdate'
                ),
            )
        );

        $fields_form_conf = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings for Customer order confirmation via SMS & Whatsapp'),
                    'icon' => 'icon-mobile'
                    ),
                'input' => $inputs_conf,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitSMSConf'
                ),
            )
        );

        $templates_inputs = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('SMS & Whatsapp templates'),
                    'icon' => 'icon-envelope'
                    ),
                'input' => $templates,
                'submit' => array(
                    'title' => $this->l('Send'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'saveSMSTemplates'
                    )
                )
        );

        $fields_form2 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Send SMS or Whatsapp - Single SMS or Whatsapp'),
                    'icon' => 'icon-envelope'
                    ),
                'input' => $inputs2,
                'submit' => array(
                    'title' => $this->l('Send'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'sendSMS'
                    )
                )
        );

        $buttons = array();
        foreach (Group::getGroups(Context::getContext()->language->id) as $group) {
            $buttons[] = array(
                'href' => AdminController::$currentIndex.'&configure='.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules').'&fetchCustomers&id_group='.$group['id_group'],
                'title' => $this->l('Fetch mobile numbers from customers from '.$group['name'].' group'),
                'class' => 'new-window',
                'name' => 'fetchCustomers'
            );
        }

        $fields_form3 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('SMS & Whatsapp Marketing - Bulk SMS or Whatsapp'),
                    'icon' => 'icon-envelope'
                    ),
                'input' => $inputs3,
                'submit' => array(
                    'title' => $this->l('Send'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'sendMarketingSMS'
                ),
                'buttons' => $buttons
            )
        );

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper = new HelperForm();
        $helper->default_form_language = $lang->id;
        // $helper->submit_action = 'submitUpdate';
        $helper->currentIndex = $this->context->link->getAdminLink(
            'AdminModules',
            false
        ).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        return $helper->generateForm(
            array($fields_form, $fields_form_cron, $fields_form_conf, $templates_inputs, $fields_form2, $fields_form3)
        );
    }

    private function generateLogList()
    {
        if (Tools::getIsset('page')) {
            $page = Tools::getValue('page');
        } else {
            $page = 1;
        }

        if (Tools::getIsset('selected_pagination')) {
            $selected_pagination = Tools::getValue('selected_pagination');
        } else {
            $selected_pagination = 50;
        }

        $content = $this->getSmsHistory($page, $selected_pagination);

        $fields_list = array(
            'id' => array(
                'title' => 'ID',
                'align' => 'center',
                'search' => false,
                'class' => 'fixed-width-xs'
            ),
            'phone' => array(
                'title' => $this->l('Mobile Phone number'),
                'search' => false,
            ),
            'message' => array(
                'title' => $this->l('Message'),
                'search' => false,
            ),
            'date_sent' => array(
                'title' => $this->l('Timestamp'),
                'search' => false,
            )
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        //$helper->actions = array('delete');
        $helper->module = $this;
        $helper->listTotal = $this->countSmsHistory();
        $helper->identifier = 'id';
        $helper->title = $this->l('SMS and Whatsapp History');
        $helper->table = $this->name.'_categories';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name .'&module_name=' . $this->name;

        return $helper->generateList($content, $fields_list);
    }

    private function generateAbandonedCartList()
    {
        if (Tools::getIsset('page')) {
            $page = Tools::getValue('page');
        } else {
            $page = 1;
        }

        if (Tools::getIsset('selected_pagination')) {
            $selected_pagination = Tools::getValue('selected_pagination');
        } else {
            $selected_pagination = 50;
        }

        $content = $this->abandonedCarts(false, $page, $selected_pagination);

        $total = $this->abandonedCarts(true, 1, 5000000);

        foreach ($content as $key => $value) {
            $content[$key]['abutton'] = $this->l('Click here to send reminder');
            if (is_array($value)) {
                //do nothing
            }
        }

        $fields_list = array(
            'id_customer' => array(
                'title' => $this->l('Customer ID'),
                'align' => 'center',
                'search' => false,
                'class' => 'fixed-width-xs'
            ),
            'id_cart' => array(
                'title' => $this->l('Cart ID'),
                'align' => 'center',
                'search' => false,
                'class' => 'fixed-width-xs'
            ),
            'customer' => array(
                'title' => $this->l('Customer'),
                'search' => false,
            ),
            'date_add' => array(
                'title' => $this->l('Created'),
                'search' => false,
            ),
            'abutton' => array(
                'title' => $this->l('Send reminder'),
                'search' => false,
            ),
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        //$helper->actions = array('view');
        $helper->module = $this;
        $helper->listTotal = $total;
        $helper->identifier = 'id_customer';
        $helper->title = $this->l('Abandoned cart notifications');
        $helper->table = 'abandoned';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name .'&sentHistory&module_name=' . $this->name;
        $helper->bulk_actions = array(
            'send' => array(
                'text' => $this->l('Send notification'),
                'confirm' => $this->l('Send notification to selected customers?'),
                'icon' => 'icon-envelope',
            ),
        );

        return $helper->generateList($content, $fields_list);
    }

    public function getSmsHistory($page = 1, $fields_list = 50)
    {
        if ($page == 1) {
            $offset = 0;
        } else {
            $offset = ($page-1)*$fields_list;
        }
        $sql = 'SELECT * FROM '._DB_PREFIX_.$this->table_name.'_log
            ORDER BY id DESC
            LIMIT '.$offset.', '.$fields_list;

        return Db::getInstance()->ExecuteS($sql);
    }

    public function countSmsHistory()
    {
        $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.$this->table_name.'_log';

        return Db::getInstance()->getValue($sql);
    }

    public function getCustomerSMS($id_customer)
    {
        if (Configuration::get('WL_FREE_SMS_CONSENT') == 0) {
            return 1;
        } else {
            $sql = 'SELECT subscribed FROM '._DB_PREFIX_.$this->table_name.
                '_subscribers WHERE id_customer = '.(int)$id_customer;
            $subscribed = Db::getInstance()->getValue($sql);

            return (int)$subscribed;
        }
    }

    public function getConfigFieldsValues()
    {
        $input_values = array(
            'theswitch' => Configuration::get('SMS_NOTIFICATION_THESWITCH'),
            'enable_delay' => Configuration::get('SMS_NOTIFICATION_ENABLE_DELAY'),
            'WL_FREE_SMS_ENABLE_ORDER_CONF' => Configuration::get('WL_FREE_SMS_ENABLE_ORDER_CONF'),
            'WL_FREE_SMS_INTERVAL' => Configuration::get('WL_FREE_SMS_INTERVAL'),
            'WL_FREE_SMS_LOWER' => Configuration::get('WL_FREE_SMS_LOWER'),
            'WL_FREE_SMS_HIGHER' => Configuration::get('WL_FREE_SMS_HIGHER'),
            'WL_FREE_SMS_CONSENT' => Configuration::get('WL_FREE_SMS_CONSENT'),
            'WL_FREE_SMS_DEBUG' => Configuration::get('WL_FREE_SMS_DEBUG'),
            'activeapi' => Configuration::get('SMS_NOTIFICATION_ACTIVEAPI'),
            'apisecret' => Configuration::get('SMS_NOTIFICATION_APISECRET'),
            'deviceid' => Configuration::get('SMS_NOTIFICATION_DEVICEID'),
            'sim' => Configuration::get('SMS_NOTIFICATION_SIM'),
            'adminmobile' => Configuration::get('SMS_NOTIFICATION_ADMINMOBILE'),
            'notif_admin_new_orders' => Configuration::get('SMS_NOTIFICATION_ADMIN_ORDERS'),
            'notif_admin_new_customers' => Configuration::get('SMS_NOTIFICATION_ADMIN_CUSTOMERS'),
            'notif_customer_customer_signup' => Configuration::get('SMS_NOTIFICATION_CUSTOMERS_SIGNUP'),
            'notif_admin_out_of_stock' => Configuration::get('SMS_NOTIFICATION_OUT_OF_STOCK'),
            'notif_admin_new_message' => Configuration::get('SMS_NOTIFICATION_NEW_MESSAGE'),
            'notif_admin_order_update' => Configuration::get('SMS_NOTIFICATION_ADMIN_ORDER_UPDATE'),
            'notif_customer_new_orders' => Configuration::get('SMS_NOTIFICATION_CUSTOMERS_ORDERS'),
            'notif_customer_order_update' => Configuration::get('SMS_NOTIFICATION_CUSTOMERS_ORDER_UPDATE'),
            'notif_supplier_product_sale' => Configuration::get('SMS_NOTIFICATION_SUPPLIER_SALE'),
            'recipient' => '',
            'sender' => '',
            'message' => '',
            'bulkto' => (Tools::getIsset('fetchCustomers') ? $this->getMobileNumbers(Tools::getValue('id_group')) : ''),
            'bulksender' => '',
            'bulkmessage' => ''
        );
        if (Language::countActiveLanguages()>1) {
                $input_values['WL_TP_CUSTOMER_NEW_ORDER'] = Configuration::getInt('WL_TP_CUSTOMER_NEW_ORDER');
                $input_values['WL_TP_ABANDONED_CART'] = Configuration::getInt('WL_TP_ABANDONED_CART');
                $input_values['WL_TP_BIRTHDAY'] = Configuration::getInt('WL_TP_BIRTHDAY');
                $input_values['WL_TP_ADMIN_NEW_ORDER'] = Configuration::getInt('WL_TP_ADMIN_NEW_ORDER');
                $input_values['WL_TP_ADMIN_NEW_CUSTOMER'] = Configuration::getInt('WL_TP_ADMIN_NEW_CUSTOMER');
                $input_values['WL_TP_ADMIN_NEW_MESSAGE'] = Configuration::getInt('WL_TP_ADMIN_NEW_MESSAGE');
                $input_values['WL_TP_ADMIN_PRODUCT_OOS'] = Configuration::getInt('WL_TP_ADMIN_PRODUCT_OOS');
        } else {
                $input_values['WL_TP_CUSTOMER_NEW_ORDER'] = Configuration::get('WL_TP_CUSTOMER_NEW_ORDER');
                $input_values['WL_TP_ABANDONED_CART'] = Configuration::get('WL_TP_ABANDONED_CART');
                $input_values['WL_TP_BIRTHDAY'] = Configuration::get('WL_TP_BIRTHDAY');
                $input_values['WL_TP_ADMIN_NEW_ORDER'] = Configuration::get('WL_TP_ADMIN_NEW_ORDER');
                $input_values['WL_TP_ADMIN_NEW_CUSTOMER'] = Configuration::get('WL_TP_ADMIN_NEW_CUSTOMER');
                $input_values['WL_TP_ADMIN_NEW_MESSAGE'] = Configuration::get('WL_TP_ADMIN_NEW_MESSAGE');
                $input_values['WL_TP_ADMIN_PRODUCT_OOS'] = Configuration::get('WL_TP_ADMIN_PRODUCT_OOS');
        }

        $order_states = OrderState::getOrderStates($this->context->language->id);
        foreach ($order_states as $order_state) {
            if (Language::countActiveLanguages()>1) {
                $input_values['WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state']] =
                Configuration::getInt('WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state']);
            } else {
                $input_values['WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state']] =
                Configuration::get('WL_TP_CUSTOMER_ORDER_'.$order_state['id_order_state']);
            }
        }
        return $input_values;
    }

    public function getMobileNumbers($id_group = false)
    {
        $where = '';
        if ($id_group) {
            $where = ' WHERE cst.id_default_group = '.(int)$id_group;
        }
        $sql = 'SELECT DISTINCT addr.phone_mobile, addr.id_country FROM '._DB_PREFIX_.'address addr
        LEFT JOIN '._DB_PREFIX_.'customer cst ON addr.id_customer=cst.id_customer'.
        $where;

        if ($results = Db::getInstance()->ExecuteS($sql)) {
            if (count($results)>0) {
                $list = array();
                foreach ($results as $row) {
                    $mobile = $row['phone_mobile'];
                    $mobile = str_replace(array('.', ',', ' ', '-', '(', ')'), '', $mobile);

                    if (Tools::strlen($mobile)>7) {
                        $data = new Country($row['id_country']);
                        $formated = $this->formatMobileNumber($mobile, $data->call_prefix);
                        array_push($list, $formated);
                    }
                }
                return implode(',', $list);
            } else {
                return "No numbers found!";
            }
        }
    }

    public function getCustomerMobileNumbers($customers)
    {
        $sql = 'SELECT DISTINCT phone_mobile, id_country, firstname, lastname FROM '._DB_PREFIX_.'address
        WHERE id_customer IN ('.implode(',', $customers).')';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            if (count($results)>0) {
                $list = array();
                foreach ($results as $row) {
                    $mobile = $row['phone_mobile'];
                    $mobile = str_replace(array('.', ',', ' ', '-', '(', ')'), '', $mobile);
                    $item = array();
                    $item['firstname'] = $row['firstname'];
                    $item['lastname'] = $row['lastname'];

                    if (Tools::strlen($mobile)>5) {
                        if ($mobile[0] != '+') {
                            $data = new Country($row['id_country']);
                            $item_mobile = '+'.$data->call_prefix.$mobile;
                        } else {
                            $item_mobile = $mobile;
                        }
                        $item['phone_mobile'] = $item_mobile;
                    }
                    $list[] = $item;
                }
                return $list;
            } else {
                return false;
            }
        }
    }

    public function getAdminMobileNumbers()
    {
        $admin_mobile_string = str_replace(' ', '', Configuration::get('SMS_NOTIFICATION_ADMINMOBILE'));
        return explode(',', $admin_mobile_string);
    }

    public function hookActionCustomerAccountAdd($params)
    {
        // $hooks = new ModelSmsHooks();
        // $hooks->customerAddHook($params["newCustomer"]->id_shop, $params["newCustomer"]->id);

        if (Configuration::get('SMS_NOTIFICATION_ADMIN_CUSTOMERS')=='2') {
            $id_customer = $params["newCustomer"]->id;
            $customer = new Customer((int)$id_customer);

            if ($this->getCustomerSMS($id_customer)) {
                if (Language::countActiveLanguages()>1) {
                    $message = Configuration::getInt('WL_TP_ADMIN_NEW_CUSTOMER')[$this->context->language->id];
                } else {
                    $message = Configuration::get('WL_TP_ADMIN_NEW_CUSTOMER');
                }

                $message = str_replace("{customer_firstname}", $customer->firstname, $message);
                $message = str_replace("{customer_lastname}", $customer->lastname, $message);

                foreach ($this->getAdminMobileNumbers() as $admin_mobile_number) {
                    $this->sendSMS(
                        $admin_mobile_number,
                        $message
                    );
                }

                return true;
            }
        }

        if (Configuration::get('SMS_NOTIFICATION_CUSTOMERS_SIGNUP')=='3') {
            $first_name = $params["newCustomer"]->firstname;
            $last_name = $params["newCustomer"]->lastname;

            

            if (Language::countActiveLanguages()>1) {
                $message = Configuration::getInt('WL_TP_ADMIN_NEW_CUSTOMER')[$this->context->language->id];
            } else {
                $message = Configuration::get('WL_TP_ADMIN_NEW_CUSTOMER');
            }

            $message = str_replace("{customer_firstname}", $first_name, $message);
            $message = str_replace("{customer_lastname}", $last_name, $message);

            foreach ($this->getAdminMobileNumbers() as $admin_mobile_number) {
                $this->sendSMS(
                    $admin_mobile_number,
                    $message
                );
            }
            return true;
        }
    }

    public function hookactionOrderStatusUpdate($params)
    {
        $data = new Order($params["id_order"]);
        $paid = number_format($data->getOrdersTotalPaid(), 2);
        $product_names = array();

        foreach ($data->getProducts() as $prod) {
            $product_names[] = $prod['product_name'];
        }

        $tracking_code = $data->getWsShippingNumber();

        if (Configuration::get('SMS_NOTIFICATION_CUSTOMERS_ORDER_UPDATE') == "2" &&
            $this->getCustomerSMS($data->id_customer)
        ) {
            $id_lang = $this->context->language->id;
            $status = Db::getInstance()->getValue(
                "SELECT name FROM `" . _DB_PREFIX_ .
                "order_state_lang`
                WHERE `id_lang` = '" . (int)$id_lang . "'
                AND `id_order_state` = '" . (int)$params["newOrderStatus"]->id . "' "
            );

            $id_address = $data->id_address_delivery;
            $address_data = new Address($id_address);
            $carrier_data = new Carrier($data->id_carrier);
            $country_data = new Country($address_data->id_country);

            $conf_link = Context::getContext()->link->getModuleLink(
                $this->name,
                'confirmation',
                array('id' => $data->id, 'ref' => $data->reference)
            );

            $mobile_phone = $address_data->phone_mobile;

            if (Tools::strlen($mobile_phone)<1) {
                $mobile_phone = $this->formatMobileNumber($address_data->phone, $country_data->call_prefix);
            } else {
                $mobile_phone = $this->formatMobileNumber($address_data->phone_mobile, $country_data->call_prefix);
            }

            $tracking_link = str_replace("@", $tracking_code, $carrier_data->url);

            if (Language::countActiveLanguages()>1) {
                $message =Configuration::getInt(
                    'WL_TP_CUSTOMER_ORDER_'.(int)$params["newOrderStatus"]->id
                )[$this->context->language->id];
            } else {
                $message = Configuration::get('WL_TP_CUSTOMER_ORDER_'.(int)$params["newOrderStatus"]->id);
            }

            $message = str_replace("{courier_service}", $carrier_data->name, $message);
            $message = str_replace("{product_data}", implode(", ", $product_names), $message);
            $message = str_replace("{customer_firstname}", $address_data->firstname, $message);
            $message = str_replace("{customer_lastname}", $address_data->lastname, $message);
            $message = str_replace("{order_status}", $status, $message);
            $message = str_replace("{order_number}", $data->reference, $message);
            $message = str_replace("{order_id}", $data->id, $message);
            $message = str_replace("{confirmation_link}", $conf_link, $message);
            $message = str_replace("{order_total}", $paid, $message);
            $message = str_replace("{tracking_code}", $tracking_code, $message);
            $message = str_replace("{tracking_link}", $tracking_link, $message);

            $this->sendSMS($mobile_phone, $message);
        }

        return true;
    }

    public function hookDisplayAdminOrderTabOrder($params)
    {
        if (!$this->active) {
            return;
        }
    
        return $this->display(__FILE__, 'views/templates/hook/admin-order-tab.tpl');
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {

        $address = new Address($params['order']->id_address_delivery, $this->context->language->id);
        $country_data = new Country($address->id_country);

        $customer_mobile = $address->phone_mobile;

        if (Tools::strlen($customer_mobile)<1) {
            $customer_mobile = $this->formatMobileNumber($address->phone, $country_data->call_prefix);
        } else {
            $customer_mobile = $this->formatMobileNumber($address->phone_mobile, $country_data->call_prefix);
        }

        $this->smarty->assign(array(
            'customer_mobile' => $customer_mobile,
            'theorderid' => $params['order']->id,
            'thecustomerid' => $params['order']->id_customer,
        ));
            
        return $this->display(__FILE__, 'views/templates/hook/admin-order-content.tpl');
    }

    public function hookDisplayAdminOrderSide($params)
    {
        $data = new Order(Tools::getValue('id_order'));
        $address = new Address($data->id_address_delivery, $this->context->language->id);
        $country_data = new Country($address->id_country);

        if (Tools::strlen($customer_mobile)<1) {
            $customer_mobile = $this->formatMobileNumber($address->phone, $country_data->call_prefix);
        } else {
            $customer_mobile = $this->formatMobileNumber($address->phone_mobile, $country_data->call_prefix);
        }

        $this->smarty->assign(array(
            'customer_mobile' => $customer_mobile,
            'theorderid' => $params['order']->id,
            'thecustomerid' => $params['order']->id_customer,
        ));
            
        return $this->display(__FILE__, 'views/templates/hook/admin-order-side.tpl');
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $data = new Order(Tools::getValue('id_order'));
        $customer = new Customer($data->id_customer);

        //d($message);
        $product_names = array();
        
        foreach ($data->getProducts() as $key => $prod) {
            $product = new Product($prod['product_id']);
            $link = new Link();
            $url = $link->getProductLink($product);
            $product_names[] = $url;
        }

        //sms to customer
        if (Configuration::get('SMS_NOTIFICATION_CUSTOMERS_ORDERS') == "1") {
            //$id_lang = $this->context->language->id;

            $cart_data = new Cart(Tools::getValue('id_cart'));
            $id_address = $cart_data->id_address_delivery;
            $address_data = new Address($id_address);
            $country_data = new Country($address_data->id_country);

            $mobile_phone = $address_data->phone_mobile;
            
            if (Tools::strlen($mobile_phone)<1) {
                $mobile_phone = $this->formatMobileNumber($address_data->phone, $country_data->call_prefix);
            } else {
                $mobile_phone = $this->formatMobileNumber($address_data->phone_mobile, $country_data->call_prefix);
            }

            if (Language::countActiveLanguages()>1) {
                $message = Configuration::getInt('WL_TP_CUSTOMER_NEW_ORDER')[$this->context->language->id];
            } else {
                $message = Configuration::get('WL_TP_CUSTOMER_NEW_ORDER');
            }

            $conf_link = Context::getContext()->link->getModuleLink(
                $this->name,
                'confirmation',
                array('id' => $data->id, 'ref' => $data->reference)
            );
            
            $carrier_data = new Carrier($data->id_carrier);
            $message = str_replace("{courier_service}", $carrier_data->name, $message);
            $message = str_replace("{product_data}", implode(", ", $product_names), $message);
            $message = str_replace("{order_id}", $data->id, $message);
            $message = str_replace("{order_number}", $data->reference, $message);
            $message = str_replace("{confirmation_link}", $conf_link, $message);
            $message = str_replace("{customer_firstname}", $customer->firstname, $message);
            $message = str_replace("{customer_lastname}", $customer->lastname, $message);
            $message = str_replace("{order_total}", round($data->total_paid, 2). ' '.
                $this->context->currency->sign, $message);
        
            $this->sendSMS($mobile_phone, $message);
        }

        //sms to admin
        if (Configuration::get('SMS_NOTIFICATION_ADMIN_ORDERS') == "1") {
            //$order_data = new Order(Tools::getValue('id_order'));
            if (Language::countActiveLanguages()>1) {
                $message = Configuration::getInt('WL_TP_ADMIN_NEW_ORDER')[$this->context->language->id];
            } else {
                $message = Configuration::get('WL_TP_ADMIN_NEW_ORDER');
            }

            $conf_link = Context::getContext()->link->getModuleLink(
                $this->name,
                'confirmation',
                array('id' => $data->id, 'ref' => $data->reference)
            );

            $carrier_data = new Carrier($data->id_carrier);
            $message = str_replace("{courier_service}", $carrier_data->name, $message);
            $message = str_replace("{product_data}", implode(", ", $product_names), $message);
            $message = str_replace("{order_id}", $data->id, $message);
            $message = str_replace("{order_number}", $data->reference, $message);
            $message = str_replace("{confirmation_link}", $conf_link, $message);
            $message = str_replace("{customer_firstname}", $customer->firstname, $message);
            $message = str_replace("{customer_lastname}", $customer->lastname, $message);
            $message = str_replace("{order_total}", round($data->total_paid, 2). ' '.
                $this->context->currency->sign, $message);

            foreach ($this->getAdminMobileNumbers() as $admin_mobile_number) {
                $this->sendSMS(
                    $admin_mobile_number,
                    $message
                );
            }
        }

        if (Configuration::get('SMS_NOTIFICATION_SUPPLIER_SALE') == "1") {
            $product_data = $data->getCartProducts();
            $notified_suppliers = array();
            foreach ($product_data as $key => $product_data_single) {
                if ((int)$product_data_single['id_supplier']>0) {
                    $notified_suppliers[$product_data_single['id_supplier']][] =
                        $product_data_single['product_quantity']."x".$product_data_single['product_name'];
                }
            }

            foreach ($notified_suppliers as $key => $notified_supplier_products) {
                $id_supplier_address = Address::getAddressIdBySupplierId($key);
                $supplier_address = new Address($id_supplier_address);
                $number = $notified_supplier_products;
                $number = $this->formatMobileNumber($supplier_address->phone_mobile, false);
                $message = $this->l('Hello! A new order has been placed:')." ".
                    implode(',', $notified_suppliers[$key]);
                
                if (Tools::strlen($number)>0) {
                    $this->sendSMS($number, $message);
                }
            }
        }
    }

    public function hookDisplayFooter($params)
    {
        if (Configuration::get('SMS_NOTIFICATION_NEW_MESSAGE') == "4") {
            if (Tools::getIsset('submitMessage')
                && Tools::strlen(Tools::getValue('message'))>2
                && Tools::strlen(Tools::getValue('from'))>2
            ) {
                if (Language::countActiveLanguages()>1) {
                    $message = Configuration::getInt('WL_TP_ADMIN_NEW_MESSAGE')[$this->context->language->id];
                } else {
                    $message = Configuration::get('WL_TP_ADMIN_NEW_MESSAGE');
                }

                $message = str_replace("{message_from}", Tools::getValue('from'), $message);
                $message = str_replace("{message_content}", Tools::getValue('message'), $message);

                foreach ($this->getAdminMobileNumbers() as $admin_mobile_number) {
                    $this->sendSMS(
                        $admin_mobile_number,
                        $message
                    );
                }
            }
        }
    }

    public function psversion()
    {
        $version = _PS_VERSION_;
        $ver = explode(".", $version);
        return $ver[1];
    }

    public function isDebugMode()
    {
        $debug_enabled = Configuration::get('SMS_NOTIFICATION_DEBUG');
        if ($debug_enabled == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function doDebug($var)
    {
        if ($this->psversion()=='7') {
            dump($var);
        } else {
            d($var);
        }
    }

    public function hookdisplayCustomerAccount($params)
    {
        if (Configuration::get('SMS_NOTIFICATION_THESWITCH') == 1 && Configuration::get('WL_FREE_SMS_CONSENT') == 1) {
            $this->context->smarty->assign(
                array(
                    'psversion' => $this->psversion()
                )
            );

            if ($this->psversion() == 6) {
                return $this->display(__FILE__, 'account.tpl');
            } elseif ($this->psversion() == 7) {
                return $this->display(__FILE__, 'account-17.tpl');
            }
        }
    }

    public function hookDisplayBackOfficeHeader($params)
    {

        if (Tools::getIsset('submitSmsMessage')) {
            $this->sendSMS(
                Tools::getValue('send-to'),
                Tools::getValue('sms-message')
            );
            //$this->_confirmations[] = $this->l('Message successfully sent.');
        }

        if ((Tools::getValue('controller') == 'AdminModules' && Tools::getValue('configure') == $this->name)) {
            if ($this->psversion() == 6) {
                Tools::addCSS($this->_path.'views/css/admin.css', 'all');
                Tools::addJs($this->_path.'views/js/admin.js', 'all');
                //Tools::addJs($this->_path.'views/js/jquery.maxlength.min.js', 'all');
            } elseif ($this->psversion() == 7) {
                $this->context->controller->addCSS(($this->_path) . 'views/css/admin.css', 'all');
                $this->context->controller->addJs(($this->_path) . 'views/js/admin.js', 'all');
                //$this->context->controller->addJs(($this->_path) . 'views/js/jquery.maxlength.min.js', 'all');
            }
        }
    }
}
