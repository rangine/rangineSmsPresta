<?php
/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*
*  @author    Hadi Mollaei <mr.hadimollaei@gmail.com>
*  @copyright 2007-2017 Rangine.ir
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
class RangineSmsPresta extends Module
{
    private $prefix;
    public function __construct()
    {
        $this->preload();
        $this->name          = 'ranginesmspresta';
        $this->tab           = 'emailing';
        $this->version       = '1.1.2';
        $this->author        = 'Hadi Mollaei';
        $this->support        = 'http://rangine.ir/';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '2f0ead43a556328cd724cdfaab24c08f';

        parent::__construct();

        $this->displayName      = $this->l('Rangine Sms Gateway');
        $this->description      = $this->l('Rangine sms web service for PrestaShop');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->prefix           = 'RANGINE_SMS_';
        $this->enable            = Configuration::get($this->prefix .'SMSENABLE');
        $this->PSversion = Configuration::get('PS_INSTALL_VERSION');
        $this->shop = Configuration::get('PS_SHOP_NAME');
        $this->domain = Configuration::get('PS_SHOP_DOMAIN');
        // Some long messages that make the code messy. TODO: May I can bring all messages here?!
        $this->text = array(
            'NEWACADTEXT' => '{firstname} {lastname} registred on site',
            'NEWACCUTEXT' => 'Dear {firstname} {lastname},~Welcome to our site.',
            'NEWORADTEXT' => 'Customer: {firstname} {lastname}~Order:'.
            ' {order_name}~Payment: {payment}~Total: ({total_paid} {currency})',
            'NEWORCUTEXT' => '{shop_name}~Customer: {firstname} {lastname}~Order:'.
            '{order_name}~Payment: {payment}~Total: ({total_paid} {currency})',
            'UPORCUTEXT' => '{shop_name}~Dear {firstname} {lastname},'.
            '~New status for order: {order_name}: {order_state}',
            'UPORTRTEXT' => 'Dear {firstname} {lastname},~Your order has sent by {carrier}'.
            '~Order: {order_name}~Tracking No: {tracking}~{shop_name}',
            'NewOrderTextDesc' => 'Variables: {firstname} {lastname} {order_name} {payment} {total_paid} {currency}',
            'NewOrderCustomerTextDesc' => 'Variables: {firstname} {lastname} '.
            '{order_name} {payment} {total_paid} {currency}',
            'UpdateOrderTrackingHint' => 'Select which type you want to send to customer'.
            'on update order tracking number.',
        );
        foreach ($this->text as $key => $text) {
            $this->text[$key] = $this->l($text);
        }
    }

    public function install()
    {
        if (!extension_loaded('curl')) {
            return false;
        }
        if (!$this->installConfig()) {
            return false;
        }
        if (!$this->installDatabase()) {
            return false;
        }
        if (!parent::install() or
            !$this->registerHook('actionValidateOrder') or
            !$this->registerHook('actionOrderStatusPostUpdate') or
            !$this->registerHook('actionAdminOrdersTrackingNumberUpdate') or
            !$this->registerHook('actionAdminControllerSetMedia') or
            !$this->registerHook('actionUpdateQuantity') or
            !$this->registerHook('actionProductOutOfStock') or
            !$this->registerHook('actionCustomerAccountAdd') or
            !$this->installModuleTab($this->name, array(1 => $this->displayName), 16)) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        if (!$this->uninstallConfig()) {
            return false;
        }
        if (!$this->unistallDatabase()) {
            return false;
        }
        if (!parent::uninstall()) {
            return false;
        }
        if (!$this->uninstallModuleTab($this->name)) {
            return false;
        }

        return true;
    }

    protected function installConfig()
    {
        if (!Configuration::updateValue($this->prefix . 'USERNAME', 'demo') or
            !Configuration::updateValue($this->prefix . 'PASSWORD', 'demo') or
            !Configuration::updateValue($this->prefix . 'ADMINPHONE', '') or
            !Configuration::updateValue($this->prefix . 'SMSNUMBER', '100009') or
            !Configuration::updateValue($this->prefix . 'SMSSENUMBER', '5000125475') or
            !Configuration::updateValue($this->prefix . 'SMSENABLE', '1') or
            !Configuration::updateValue($this->prefix . 'NEWORDERA', '1') or
            !Configuration::updateValue($this->prefix . 'NEWORDERC', '1') or
            !Configuration::updateValue($this->prefix . 'UPDATEORDERC', '1') or
            !Configuration::updateValue($this->prefix . 'NEWCUSTOMERA', '1') or
            !Configuration::updateValue($this->prefix . 'NEWCUSTOMERC', '0') or
            !Configuration::updateValue($this->prefix . 'NEWORDERFC', '0') or
            !Configuration::updateValue($this->prefix . 'NEWORDERFA', '0') or
            !Configuration::updateValue($this->prefix . 'NEWACADTETYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'NEWACADTEXT', $this->text['NEWACADTEXT']) or
            !Configuration::updateValue($this->prefix . 'NEWACCUTETYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'NEWACCUTEXT', $this->text['NEWACCUTEXT']) or
            !Configuration::updateValue($this->prefix . 'NEWORADTETYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'NEWORADTEXT', $this->text['NEWORADTEXT']) or
            !Configuration::updateValue($this->prefix . 'NEWORCUTETYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'NEWORCUTEXT', $this->text['NEWORCUTEXT']) or
            !Configuration::updateValue($this->prefix . 'UPORCUTETYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'UPORCUTEXT', $this->text['UPORCUTEXT']) or
            !Configuration::updateValue($this->prefix . 'UPORTRTETYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'UPORTRTEXT', $this->text['UPORTRTEXT'])) {
                return false;
        }

        return true;
    }

    protected function uninstallConfig()
    {
        if (!Configuration::deleteByName($this->prefix . 'USERNAME') or
            !Configuration::deleteByName($this->prefix . 'PASSWORD') or
            !Configuration::deleteByName($this->prefix . 'ADMINPHONE') or
            !Configuration::deleteByName($this->prefix . 'SMSNUMBER') or
            !Configuration::deleteByName($this->prefix . 'SMSSENUMBER') or
            !Configuration::deleteByName($this->prefix . 'SMSENABLE') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERA') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERC') or
            !Configuration::deleteByName($this->prefix . 'UPDATEORDERC') or
            !Configuration::deleteByName($this->prefix . 'NEWCUSTOMERA') or
            !Configuration::deleteByName($this->prefix . 'NEWCUSTOMERC') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERFC') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERFA') or
            !Configuration::deleteByName($this->prefix . 'NEWACADTETYPE') or
            !Configuration::deleteByName($this->prefix . 'NEWACADTEXT') or
            !Configuration::deleteByName($this->prefix . 'NEWACCUTETYPE') or
            !Configuration::deleteByName($this->prefix . 'NEWACCUTEXT') or
            !Configuration::deleteByName($this->prefix . 'NEWORADTETYPE') or
            !Configuration::deleteByName($this->prefix . 'NEWORADTEXT') or
            !Configuration::deleteByName($this->prefix . 'NEWORCUTETYPE') or
            !Configuration::deleteByName($this->prefix . 'NEWORCUTEXT') or
            !Configuration::deleteByName($this->prefix . 'UPORCUTETYPE') or
            !Configuration::deleteByName($this->prefix . 'UPORCUTEXT') or
            !Configuration::deleteByName($this->prefix . 'UPORTRTETYPE') or
            !Configuration::deleteByName($this->prefix . 'UPORTRTEXT')) {
                return false;
        }

        return true;
    }

    protected function installDatabase()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->name . '`(
                `id_' . $this->name . '` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `customer` INT(5) DEFAULT NULL ,
                `phone` VARCHAR(100) DEFAULT NULL ,
                `position` VARCHAR(50) DEFAULT NULL ,
                `status` VARCHAR(15) NOT NULL ,
                `bulk` varchar(50) DEFAULT NULL,
                `delivery` varchar(50) DEFAULT NULL,
                `description` TEXT DEFAULT NULL
                 )ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        return Db::getInstance()->execute($sql);
    }

    protected function unistallDatabase()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $this->name . '`';
        return Db::getInstance()->execute($sql);
    }

    /**
     * Create plugin tab. Called from install function.
     *
     * @param string $tabClass
     * @param string $tabName
     * @param array $idTabParent
     * @return bool
     */
    private function installModuleTab($tabClass, $tabName, $idTabParent)
    {
        $partab             = new Tab();
        $partab->name       = $tabName;
        $partab->class_name = $tabClass;
        $partab->module     = $this->name;
        $partab->id_parent  = $idTabParent;
        if ($partab->save()) {
            return true;
        }
        return false;
    }

    /**
     * Delete plugin tab. Called from uninstall function.
     *
     * @param string $tabClass
     * @return bool
     */
    private function uninstallModuleTab($tabClass)
    {
        $sql = 'SELECT id_tab FROM ' . _DB_PREFIX_ . 'tab WHERE class_name = "' . pSQL($tabClass) . '"';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            foreach ($results as $row) {
                $idTab = $row['id_tab'];
                if ($idTab != 0) {
                    $tab = new Tab((int)$idTab);
                    $tab->delete();
                }
            }
        }
        return true;
    }

    /**
     * Initialization function called from Constructor.
     */
    public function preload()
    {
        if (Tools::getValue('controller') != ''
            && (Tools::getValue('controller') == 'ranginesmspresta'
            || Tools::getValue('controller') == 'ranginesmspresta')) {
                $token   = Tools::getAdminTokenLite('AdminModules');
                $request_scheme = $_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] : 'http';
                $hostlink = $request_scheme . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
                $ctrlconfi = "?controller=AdminModules&configure=" . Tools::getValue('controller');
                $urlLink = $hostlink . $ctrlconfi ."&token=" . $token;
                Tools::redirect($urlLink);
        }
    }

    public function getContent()
    {

        if (!extension_loaded('curl')) {
            return $this->displayError($this->l('cURL is not enabled. You should enable it before using thes module.'));
        }
        $output = null;

        $errors = array();
        //update gateway Settings
        if (Tools::isSubmit('gatewaySettings')) {
            $variables = array('USERNAME','PASSWORD','ADMINPHONE','SMSNUMBER','SMSSENUMBER','SMSENABLE');
            foreach ($_POST as $key => $value) {
                if (!in_array($key, $variables)) {
                    continue;
                }
                if (!Configuration::updateValue($this->prefix . $key, $value)) {
                    $errors[] = Tools::displayError($this->l('Settings is not updated'));
                    break;
                }
            }
            if (isset($errors) && count($errors)) {
                $output .= $this->displayError(implode('<br />', $errors));
            } else {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
            //update alert Settings
        } elseif (Tools::isSubmit('alertSettings')) {
            $variables = array('NEWORDERA','NEWORDERC','UPDATEORDERC',
            'NEWCUSTOMERA','NEWCUSTOMERC','NEWORDERFC' ,'NEWORDERFA');

            foreach ($_POST as $key => $value) {
                if (!in_array($key, $variables)) {
                    continue;
                }
                if (!Configuration::updateValue($this->prefix . $key, $value)) {
                    $errors[] = Tools::displayError($this->l('Settings is not updated'));
                    break;
                }
            }
            if (isset($errors) && count($errors)) {
                $output .= $this->displayError(implode('<br />', $errors));
            } else {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
            //save text
        } elseif (Tools::isSubmit('saveTexts')) {
            $variables = array('NEWACADTEXT',
            'NEWACADTETYPE',
            'NEWACCUTEXT',
            'NEWACCUTETYPE',
            'NEWORADTETYPE',
            'NEWORADTEXT',
            'NEWORCUTETYPE',
            'NEWORCUTEXT',
            'UPORCUTETYPE',
            'UPORCUTEXT',
            'UPORTRTETYPE',
            'UPORTRTEXT',
            );
            foreach ($_POST as $key => $value) {
                if (!in_array($key, $variables)) {
                    continue;
                }
                if (!Configuration::updateValue($this->prefix . $key, $value)) {
                    $errors[] = Tools::displayError($this->l('Settings is not updated'));
                    break;
                }
            }
            if (isset($errors) && count($errors)) {
                $output .= $this->displayError(implode('<br />', $errors));
            } else {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
            //send sms
        } elseif (Tools::isSubmit('SendSMS')) {
            $phones = Tools::getValue('RECIVER');
            if (Tools::strlen($phones)<11) {
                $errors[] = Tools::displayError($this->l('insert valid phone number'));
            } else {
                $text = Tools::getValue('SENDONETEXT');
                $result=$this->sendOne($text, $phones, '-', $this->l('Send SMS Manual'));
                if ($result !='sent') {
                    $errors[]=Tools::displayError($result);
                }
            }
            if (isset($errors) && count($errors)) {
                $output .= $this->displayError(implode('<br />', $errors));
            } else {
                $output .= $this->displayConfirmation($this->l('sent'));
            }
            // send to all customers
        } elseif (Tools::isSubmit('SendCustomers')) {
            $text = Tools::getValue('SENDALLTEXT');
            if (Tools::isEmpty($text)) {
                $errors[]=Tools::displayError($this->l('text is empty'));
            }
            $phones=$this->getPhoneMobiles();

            $result=$this->sendOne($text, $phones, $this->l('multiple'), $this->l('Send Customers'));
            if ($result !='sent') {
                $errors[]=Tools::displayError($result);
            }
            if (isset($errors) && count($errors)) {
                $output .= $this->displayError(implode('<br />', $errors));
            } else {
                $output .= $this->displayConfirmation($this->l('The sms sent to customers'));
            }
        }
        $this->auth = $this->panelAuth();
        $this->smarty->assign(array(
            'auth' => $this->auth,
            'alerttype' => 'info',
        ));
        $nessesary_news = $this->panelNews();
        return $output . $this->display(__FILE__, 'views/templates/admin/headerinfo.tpl') .
        $nessesary_news.$this->displayForm();
    }

    protected function displayForm()
    {
        $auth = $this->auth;
        $phone_mobiles=$this->getPhoneMobiles(true);
        $gatewayDescription = $gatewayWarning = $gatewaySuccess = $gatewayError = null;
        if ($auth['ok'] == 'user') {
            $sendnumber = str_replace('+98', '', Configuration::get($this->prefix .'SMSNUMBER'));
            $sendKhadamatiNumber = str_replace('+98', '', Configuration::get($this->prefix .'SMSSENUMBER'));
            if (in_array($sendnumber, $auth['lines'])) {
                $gatewaySuccess = $this->l('The gateway configured correctly!').' '
                    .$this->l('Your remain credit is: ') .' '
                    .$auth['credit'].' '
                    .$this->l('rials').', '
                    .$this->l('And your account expire time is after').' '
                    .$auth['expireTime'].' '
                    .$this->l('days');
            } else {
                $gatewayWarning = 'The user and pass are correct but this line is not belong to you: ';
                $gatewayWarning = $this->l($gatewayWarning).$sendnumber;
            }
            if ($sendKhadamatiNumber != '' and
                $sendKhadamatiNumber != null and
                !in_array($sendKhadamatiNumber, $auth['lines'])) {
                $gatewayWarning .= $this->l(' Service line is not belong to you: ').$sendKhadamatiNumber;
            }
            if (Configuration::get($this->prefix .'NEWACADTETYPE') == 'sample' or
                Configuration::get($this->prefix .'NEWACCUTETYPE') == 'sample' or
                Configuration::get($this->prefix .'NEWORADTETYPE') == 'sample' or
                Configuration::get($this->prefix .'NEWORCUTETYPE') == 'sample' or
                Configuration::get($this->prefix .'UPORCUTETYPE') == 'sample') {
                if ($sendKhadamatiNumber == '' or !in_array($sendKhadamatiNumber, $auth['lines'])) {
                    $KhadamatiNumber = 'You select sample for one of your sms messages but you don\'t specify Service';
                    $KhadamatiNumber .= 'line or it is not belong to you: ';
                    $gatewayError = $this->l($KhadamatiNumber).$sendKhadamatiNumber;
                }
            }
        } elseif ($auth['ok'] == 'demo') {
            $gatewayDescription = 'You are testing our demo services. The maximum amount of utilization of these ';
            $gatewayDescription .= 'services depends on our support section. If you have questions about the system ';
            $gatewayDescription .= 'and purchase a panel, you can contact 09191483567 with a call,';
            $gatewayDescription .= ' text message or telegram.';
            $gatewayDescription = $this->l($gatewayDescription);
        } else {
            $gatewayError = $auth['error'];
        }
        if (version_compare(_PS_VERSION_, '1.6.1', '>=') === false) {
            $gatewayDescription= $gatewayDescription.$gatewayWarning.$gatewaySuccess.$gatewayError;
        }
        $fields_form_gateway = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Gateway Settings'),
                    'icon' => 'icon-cogs'
                ),
                'description' => $gatewayDescription,
                'warning' => $gatewayWarning,
                'success' => $gatewaySuccess,
                'error' => $gatewayError,
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Username:'),
                        'name' => 'USERNAME',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('ex: username.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Password:'),
                        'name' => 'PASSWORD',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Rangine SMS login Password.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Admin phone:'),
                        'name' => 'ADMINPHONE',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('ex: 09123456789'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sms number:'),
                        'name' => 'SMSNUMBER',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('ex: +985000XXXXXXX'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Service Sms number:'),
                        'name' => 'SMSSENUMBER',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('ex: +985000XXXXXXX.'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('ٍEnable SMS system:'),
                        'name' => 'SMSENABLE',
                        'class' => 'fixed-width-md',
                        'desc' => $this->l('Enable if you want you site to send SMS.'),
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
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Update Settings'),
                    'name' => 'gatewaySettings',
                )
            ),
        );
        $fields_form_alerts = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Alerts Settings'),
                    'icon' => 'icon-bell'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Alerts on new order to Admin:'),
                        'name' => 'NEWORDERA',
                        'class' => 'fixed-width-md',
                        'desc' => $this->l('Send to Admin'),
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
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Alerts on new order to Customer:'),
                        'name' => 'NEWORDERC',
                        'class' => 'fixed-width-md',
                        'desc' => $this->l('Send to Customer'),
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
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Alerts on new free order to admin:'),
                        'name' => 'NEWORDERFA',
                        'class' => 'fixed-width-md',
                        'desc' => $this->l('Send to Admin'),
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
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Alerts on new free order to customer:'),
                        'name' => 'NEWORDERFC',
                        'class' => 'fixed-width-md',
                        'desc' => $this->l('Send to Customer'),
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
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Alert on update order status to customer:'),
                        'name' => 'UPDATEORDERC',
                        'class' => 'fixed-width-md',
                        'desc' => $this->l('Send to Customer'),
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
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Alert on create new account to admin:'),
                        'name' => 'NEWCUSTOMERA',
                        'class' => 'fixed-width-md',
                        'desc' => $this->l('Send to Admin'),
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
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Alert on create new account to customer:'),
                        'name' => 'NEWCUSTOMERC',
                        'class' => 'fixed-width-md',
                        'desc' => $this->l('Send to Customer'),
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
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Update Settings'),
                    'name' => 'alertSettings',
                )
            ),
        );
        $fields_form_send_sms = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Send SMS'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Sent to:'),
                        'name' => 'RECIVER',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('To send multiple sms, saperate numbers with ; ex:09123456789;09101234567.'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Text message:'),
                        'name' => 'SENDONETEXT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Type SMS text.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Send SMS'),
                    'name' => 'SendSMS',
                    'icon' => 'process-icon-envelope',
                )
            ),
        );
        $fields_form_send_all_customers = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Send sms to customers'),
                    'icon' => 'icon-cogs'
                ),
                'description' => $phone_mobiles.' '.$this->l('unique phone mobile(s) from customers is exist'),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Text message:'),
                        'name' => 'SENDALLTEXT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Type SMS text.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Send to All Customers'),
                    'name' => 'SendCustomers',
                    'icon' => 'process-icon-envelope',
                )
            ),
        );
        $fields_form_sms_texts = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('SMS Texts'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Text for Admin on New Account:'),
                        'name' => 'NEWACADTETYPE',
                        'hint' => $this->l('Select which type you want to send to admin on new account event.'),
                        'values' => array(
                            array(
                                'id' => 'default',
                                'value' => 'default',
                                'label' => $this->l('default')
                            ),
                             array(
                                'id' => 'sample',
                                'value' => 'sample',
                                'label' => $this->l('By Sample')
                            ),
                            array(
                                'id' => 'custom',
                                'value' => 'custom',
                                'label' => $this->l('Custom')
                            ),
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Custom Text:'),
                        'name' => 'NEWACADTEXT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Type your custom text. you can use this variables: {firstname} {lastname}'),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Text for Customer on New Account:'),
                        'name' => 'NEWACCUTETYPE',
                        'hint' => $this->l('Select which type you want to send to customer on new account event.'),
                        'values' => array(
                            array(
                                'id' => 'default',
                                'value' => 'default',
                                'label' => $this->l('default')
                            ),
                             array(
                                'id' => 'sample',
                                'value' => 'sample',
                                'label' => $this->l('By Sample')
                            ),
                            array(
                                'id' => 'custom',
                                'value' => 'custom',
                                'label' => $this->l('Custom')
                            ),
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Custom Text:'),
                        'name' => 'NEWACCUTEXT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Type your custom text. you can use this variables: {firstname} {lastname}'),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Text for Admin on New Order:'),
                        'name' => 'NEWORADTETYPE',
                        'hint' => $this->l('Select which type you want to send to admin on new order event.'),
                        'values' => array(
                            array(
                                'id' => 'default',
                                'value' => 'default',
                                'label' => $this->l('default')
                            ),
                            array(
                                'id' => 'sample',
                                'value' => 'sample',
                                'label' => $this->l('By Sample')
                            ),
                            array(
                                'id' => 'custom',
                                'value' => 'custom',
                                'label' => $this->l('Custom')
                            ),
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Custom Text:'),
                        'name' => 'NEWORADTEXT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->text['NewOrderTextDesc'],
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Text for Customer on New Order:'),
                        'name' => 'NEWORCUTETYPE',
                        'hint' => $this->l('Select which type you want to send to customer on new order event.'),
                        'values' => array(
                            array(
                                'id' => 'default',
                                'value' => 'default',
                                'label' => $this->l('default')
                            ),
                            array(
                                'id' => 'sample',
                                'value' => 'sample',
                                'label' => $this->l('By Sample')
                            ),
                            array(
                                'id' => 'custom',
                                'value' => 'custom',
                                'label' => $this->l('Custom')
                            ),
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Custom Text:'),
                        'name' => 'NEWORCUTEXT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->text['NewOrderCustomerTextDesc'],
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Text for Customer on Update Order:'),
                        'name' => 'UPORCUTETYPE',
                        'hint' => $this->l('Select which type you want to send to customer on update order event.'),
                        'values' => array(
                            array(
                                'id' => 'default',
                                'value' => 'default',
                                'label' => $this->l('default')
                            ),
                            array(
                                'id' => 'sample',
                                'value' => 'sample',
                                'label' => $this->l('By Sample')
                            ),
                            array(
                                'id' => 'custom',
                                'value' => 'custom',
                                'label' => $this->l('Custom')
                            ),
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Custom Text:'),
                        'name' => 'UPORCUTEXT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Variables: {shop_name} {firstname} {lastname} {order_name} {order_state}'),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Text for Customer on Update Order Tracking:'),
                        'name' => 'UPORTRTETYPE',
                        'hint' => $this->l($this->text['UpdateOrderTrackingHint']),
                        'values' => array(
                            array(
                                'id' => 'default',
                                'value' => 'default',
                                'label' => $this->l('default')
                            ),
                            array(
                                'id' => 'sample',
                                'value' => 'sample',
                                'label' => $this->l('By Sample')
                            ),
                            array(
                                'id' => 'custom',
                                'value' => 'custom',
                                'label' => $this->l('Custom')
                            ),
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Custom Text:'),
                        'name' => 'UPORTRTEXT',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Variables: {shop_name} {firstname} {lastname} {order_name} {carrier}'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name' => 'saveTexts',
                )
            ),
        );
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $allow_employee_form_lang ? $allow_employee_form_lang : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).
        '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        $result = null;
        $result .= $helper->generateForm(array($fields_form_gateway));
        if ($auth['ok'] == 'user' or $auth['ok'] == 'demo') {
            $result .= $helper->generateForm(array($fields_form_alerts));
            $result .= $helper->generateForm(array($fields_form_send_sms));
            $result .= $helper->generateForm(array($fields_form_send_all_customers));
            $result .= $helper->generateForm(array($fields_form_sms_texts));
        }
        return $result;
    }
    public function getConfigFieldsValues()
    {
        return array(
            'USERNAME' => Tools::getValue('USERNAME', Configuration::get($this->prefix .'USERNAME')),
            'PASSWORD' => Tools::getValue('PASSWORD', Configuration::get($this->prefix .'PASSWORD')),
            'ADMINPHONE' => Tools::getValue('ADMINPHONE', Configuration::get($this->prefix .'ADMINPHONE')),
            'SMSNUMBER' => Tools::getValue('SMSNUMBER', Configuration::get($this->prefix .'SMSNUMBER')),
            'SMSSENUMBER' => Tools::getValue('SMSSENUMBER', Configuration::get($this->prefix .'SMSSENUMBER')),
            'SMSENABLE' => Tools::getValue('SMSENABLE', Configuration::get($this->prefix .'SMSENABLE')),
            'NEWORDERA' => Tools::getValue('NEWORDERA', Configuration::get($this->prefix .'NEWORDERA')),
            'NEWORDERC' => Tools::getValue('NEWORDERC', Configuration::get($this->prefix .'NEWORDERC')),
            'NEWORDERFA' => Tools::getValue('NEWORDERFA', Configuration::get($this->prefix .'NEWORDERFA')),
            'NEWORDERFC' => Tools::getValue('NEWORDERFC', Configuration::get($this->prefix .'NEWORDERFC')),
            'UPDATEORDERC' => Tools::getValue('UPDATEORDERC', Configuration::get($this->prefix .'UPDATEORDERC')),
            'NEWCUSTOMERA' => Tools::getValue('NEWCUSTOMERA', Configuration::get($this->prefix .'NEWCUSTOMERA')),
            'NEWCUSTOMERC' => Tools::getValue('NEWCUSTOMERC', Configuration::get($this->prefix .'NEWCUSTOMERC')),
            'RECIVER' => Tools::getValue('RECIVER', Configuration::get($this->prefix .'RECIVER')),
            'SENDONETEXT' => Tools::getValue('SENDONETEXT', Configuration::get($this->prefix .'SENDONETEXT')),
            'SENDALLTEXT' => Tools::getValue('SENDALLTEXT', Configuration::get($this->prefix .'SENDALLTEXT')),
            'NEWACADTETYPE' => Tools::getValue('NEWACADTETYPE', Configuration::get($this->prefix .'NEWACADTETYPE')),
            'NEWACADTEXT' => Tools::getValue('NEWACADTEXT', Configuration::get($this->prefix .'NEWACADTEXT')),
            'NEWACCUTEXT' => Tools::getValue('NEWACCUTEXT', Configuration::get($this->prefix .'NEWACCUTEXT')),
            'NEWACCUTETYPE' => Tools::getValue('NEWACCUTETYPE', Configuration::get($this->prefix .'NEWACCUTETYPE')),
            'NEWORADTETYPE' => Tools::getValue('NEWORADTETYPE', Configuration::get($this->prefix .'NEWORADTETYPE')),
            'NEWORADTEXT' => Tools::getValue('NEWORADTEXT', Configuration::get($this->prefix .'NEWORADTEXT')),
            'NEWORCUTETYPE' => Tools::getValue('NEWORCUTETYPE', Configuration::get($this->prefix .'NEWORCUTETYPE')),
            'NEWORCUTEXT' => Tools::getValue('NEWORCUTEXT', Configuration::get($this->prefix .'NEWORCUTEXT')),
            'UPORCUTETYPE' => Tools::getValue('UPORCUTETYPE', Configuration::get($this->prefix .'UPORCUTETYPE')),
            'UPORCUTEXT' => Tools::getValue('UPORCUTEXT', Configuration::get($this->prefix .'UPORCUTEXT')),
            'UPORTRTETYPE' => Tools::getValue('UPORTRTETYPE', Configuration::get($this->prefix .'UPORTRTETYPE')),
            'UPORTRTEXT' => Tools::getValue('UPORTRTEXT', Configuration::get($this->prefix .'UPORTRTEXT')),
        );
    }
    public function hookActionCustomerAccountAdd($params)
    {
        if (!$this->enable) {
            return true;
        }
        if (Configuration::get($this->prefix . 'USERNAME') == '' or
            Configuration::get($this->prefix . 'PASSWORD') == '' or
            Configuration::get($this->prefix . 'NEWCUSTOMERA') == 0 and
            Configuration::get($this->prefix . 'NEWCUSTOMERC') == 0) {
            return true;
        }
        $NewCustomerAdmin = Configuration::get($this->prefix . 'NEWCUSTOMERA');
        $AdminPhone = Configuration::get($this->prefix . 'ADMINPHONE');
        if ($NewCustomerAdmin != 0 and $AdminPhone == 0) {
            return true;
        }

        if (is_object($params['newCustomer'])) {
            $newCustomer = get_object_vars($params['newCustomer']);
        }
        $firstName = $newCustomer['firstname'];
        $lastName  = $newCustomer['lastname'];


        $vars = array(
            '{firstname}' => $firstName,
            '{lastname}' => $lastName
        );
        if (Configuration::get($this->prefix . 'NEWCUSTOMERA') != 0) {
            if (!$text = $this->generateSMStext($vars, 'newacount', 'admin')) {
                return true;
            }
            $this->sendOne($text, $AdminPhone, '-', $this->l('New Customer'));
        }
        if (Configuration::get($this->prefix.'NEWCUSTOMERC')!=0) {
            $id_address      = Address::getFirstCustomerAddressId($newCustomer['id']);
            $customerAddress = new AddressCore($id_address);
            $phone           = $customerAddress->phone_mobile;

            if (!$phone or Tools::strlen($phone) < 10) {
                return true;
            }
            if (!$text = $this->generateSMStext($vars, 'newacount', 'customer')) {
                return;
            }
            $this->sendOne($text, $phone, $newCustomer['id'], $this->l('New Customer'));
        }
        return true;
    }

    public function hookActionValidateOrder($params)
    {
        if (!$this->enable) {
            return true;
        }
        if (Configuration::get($this->prefix . 'USERNAME') == '0' or
            Configuration::get($this->prefix . 'PASSWORD') == '0' or
            (Configuration::get($this->prefix . 'NEWORDERA') == 0 and
            Configuration::get($this->prefix . 'NEWORDERC') == 0)) {
            return true;
        }
        if (Configuration::get($this->prefix . 'NEWCUSTOMERA') != 0 and
            Configuration::get($this->prefix . 'ADMINPHONE') == 0) {
            return true;
        }

        $order    = $params['order'];
        $customer = $params['customer'];
        $currency = $params['currency'];
        //$address  = new Address(intval($order->id_address_invoice));

        $vars = array(
            '{firstname}' => ($customer->firstname),
            '{lastname}' => ($customer->lastname),
            '{order_name}' => sprintf("%06d", $order->id),
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{payment}' => $order->payment,
            '{total_paid}' => $order->total_paid,
            '{currency}' => $currency->name
        );

        if (Configuration::get($this->prefix . 'NEWORDERA') != 0) {
            if ($order->total_paid == 0 and Configuration::get($this->prefix . 'NEWORDERFA') == 0) {
                return true;
            }

            if (!$text = $this->generateSMStext($vars, 'neworder', 'admin')) {
                return true;
            }
            $AdminPhone = Configuration::get($this->prefix . 'ADMINPHONE');
            $this->sendOne($text, $AdminPhone, '-', $this->l('New Order'));
        }

        if (Configuration::get($this->prefix . 'NEWORDERC') != 0) {
            if ($order->total_paid == 0 and Configuration::get($this->prefix . 'NEWORDERFC') == 0) {
                return true;
            }

            $id_address      = Address::getFirstCustomerAddressId($customer->id);
            $customerAddress = new AddressCore($id_address);
            $phone           = $customerAddress->phone_mobile;

            if (!$phone or Tools::strlen($phone) < 10) {
                return true;
            }
            if (!$text = $this->generateSMStext($vars, 'neworder', 'customer')) {
                return true;
            }
            $this->sendOne($text, $phone, $customer->id, $this->l('New Order'));
        }
        return true;
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $customer_alert = Tools::getValue('customer_alert');
        if (!$this->enable or $customer_alert != 'on') {
            return true;
        }
        if (!$this->enable) {
            return true;
        }
        if (Configuration::get($this->prefix . 'USERNAME') == '0' or
            Configuration::get($this->prefix . 'PASSWORD') == '0' or
            (Configuration::get($this->prefix . 'NEWCUSTOMERA') == 0 and
            Configuration::get($this->prefix . 'NEWCUSTOMERC') == 0)) {
            return true;
        }
        if (Configuration::get($this->prefix . 'UPDATEORDERC') == 0) {
            return true;
        }
        $order      = new Order((int) ($params['id_order']));
        $orderstate = $params['newOrderStatus'];

        $customer = new Customer((int) $order->id_customer);
        $id_address = Address::getFirstCustomerAddressId($customer->id);
        $address  = new Address((int)($id_address));
        if ($address->phone_mobile == null) {
            return true;
        }

        if (Tools::strtolower($orderstate->name) == Tools::strtolower('Awaiting cheque payment') or
            Tools::strtolower($orderstate->name) == Tools::strtolower('Awaiting bank wire payment')) {
            return true;
        }
        $vars = array(
            '{firstname}' => ($customer->firstname),
            '{lastname}' => ($customer->lastname),
            '{order_name}' => sprintf("%06d", $order->id),
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{order_state}' => $orderstate->name
        );

        if (!$text = $this->generateSMStext($vars, 'updateorder', 'customer')) {
            return;
        }

        $smsResult = $this->sendOne($text, $address->phone_mobile, $customer->id, $this->l('Update Order Status'));
        if ($smsResult == 'sent') {
            $this->adminDisplayInformation($this->l('The sms has sent.'));
        } else {
            $this->adminDisplayWarning($this->l('The sms can\'t be sent. Error: ') . $smsResult);
        }
        return true;
    }
    public function hookActionProductOutOfStock($params)
    {
        //TODO: send sms to admin on out of stock product
        return true;
    }
    public function hookactionUpdateQuantity($params)
    {
        //TODO: send sms to admin on return product to stock
        return true;
    }
    public function hookactionAdminOrdersTrackingNumberUpdate($params)
    {
        $customer_alert = Tools::getValue('customer_alert');
        if (!$this->enable or $customer_alert != 'on') {
            return true;
        }
        $order = $params['order'];
        $customer = $params['customer'];
        $carrier = $params['carrier'];
        $carrier = $params['carrier'];
        $id_address = Address::getFirstCustomerAddressId($customer->id);
        $address = new Address((int)($id_address));
        if ($address->phone_mobile == null) {
            return true;
        }
        $vars = array(
            '{firstname}' => ($customer->firstname),
            '{lastname}' => ($customer->lastname),
            '{order_name}' => sprintf("%06d", $order->id),
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{carrier}' => $carrier->name,
            '{tracking}' => $order->shipping_number,
        );

        if (!$text = $this->generateSMStext($vars, 'updateOrderTracking', 'customer')) {
            return;
        }

        $smsResult = $this->sendOne($text, $address->phone_mobile, $customer->id, $this->l('Update Tracking Number'));
        if ($smsResult == 'sent') {
            $this->adminDisplayInformation($this->l('The sms has sent.'));
        } else {
            $this->adminDisplayWarning($this->l('The sms can\'t be sent. Error: ') . $smsResult);
        }
        return true;
    }
    /** generate text message
     * @param array vars varible to set in tamplate
     * @param string event
     * @param string reciver
     * @return text message
     */
    private function generateSMStext($vars, $event, $reciver)
    {
        $serviceLineStatus = true;
        $sendServiceNumber = str_replace('+98', '', Configuration::get($this->prefix .'SMSSENUMBER'));
        if ($sendServiceNumber == '') {
            $serviceLineStatus = false;
        }
        if ($reciver == 'admin') {
            switch ($event) {
                case 'newacount':
                    $type = Configuration::get($this->prefix .'NEWACADTETYPE');
                    if ($type == 'custom') {
                        $massage = Configuration::get($this->prefix .'NEWACADTEXT');
                    } elseif ($type == 'sample' and $serviceLineStatus) {
                        $massage = "patterncode:142;firstname:{firstname};lastname:{lastname};site:".$this->shop;
                    } else {
                        $massage = $this->l('{firstname} {lastname} registred on site ').$this->shop;
                    }
                    break;
                case 'neworder':
                    $type = Configuration::get($this->prefix .'NEWORADTETYPE');
                    if ($type == 'custom') {
                        $massage = Configuration::get($this->prefix .'NEWORADTEXT');
                    } elseif ($type == 'sample' and $serviceLineStatus) {
                        $massage = "patterncode:144;customer:{firstname} {lastname};";
                        $massage .= "order-code:{order_name};amount:{total_paid} {currency}";
                    } else {
                        $massage = 'Customer: {firstname} {lastname}~Order:';
                        $massage .= ' {order_name}~Payment: {payment}~Total: ({total_paid} {currency})';
                        $massage = $this->l($massage);
                    }
                    break;
            }
        } else {
            switch ($event) {
                case 'newacount':
                    $type = Configuration::get($this->prefix .'NEWACCUTETYPE');
                    if ($type == 'custom') {
                        $massage = Configuration::get($this->prefix .'NEWACCUTEXT');
                    } elseif ($type == 'sample' and $serviceLineStatus) {
                        $massage = "patterncode:143;firstname:{firstname};lastname:{lastname};system-name:".$this->shop;
                    } else {
                        $massage = $this->l('Dear {firstname} {lastname},~Welcome to our site.');
                    }
                    break;
                case 'neworder':
                    $type = Configuration::get($this->prefix .'NEWORCUTETYPE');
                    if ($type == 'custom') {
                        $massage = Configuration::get($this->prefix .'NEWORCUTEXT');
                    } elseif ($type == 'sample' and $serviceLineStatus) {
                        $massage = "patterncode:145;order-code:{order_name};amount:{total_paid} {currency}";
                    } else {
                        $massage = '{shop_name}~Customer: {firstname} {lastname}~Order:';
                        $massage .= '{order_name}~Payment: {payment}~Total: ({total_paid} {currency})';
                        $massage = $this->l($massage);
                    }
                    break;
                case 'updateorder':
                    $type = Configuration::get($this->prefix .'UPORTRTETYPE');
                    if ($type == 'custom') {
                        $massage = Configuration::get($this->prefix .'UPORTRTEXT');
                    } elseif ($type == 'sample' and $serviceLineStatus) {
                        $massage = "patterncode:152;name:{firstname} {lastname};order:{order_name};";
                        $massage .= "status:{order_state};company:{shop_name}";
                    } else {
                        $massage = '{shop_name}~Dear {firstname} {lastname},';
                        $massage .= '~New status for order: {order_name}: {order_state}';
                        $massage = $this->l($massage);
                    }
                    break;
                case 'updateOrderTracking':
                    $type = Configuration::get($this->prefix .'UPORTRTETYPE');
                    if ($type == 'custom') {
                        $massage = Configuration::get($this->prefix .'UPORTRTEXT');
                    } elseif ($type == 'sample' and $serviceLineStatus) {
                        $massage = "patterncode:165;name:{firstname} {lastname};order:{order_name};";
                        $massage .= "service:{carrier};company:{shop_name};";
                        $massage .= "tracking-code:{tracking}";
                    } else {
                        $massage = 'Dear {firstname} {lastname},';
                        $massage .= '~Your order has sent by {carrier}';
                        $massage .= '~Order: {order_name}';
                        $massage .= '~Tracking No: {tracking}';
                        $massage .= '~{shop_name}';
                        $massage = $this->l($massage);
                    }
                    break;
            }
        }

        $template = str_replace(array_keys($vars), array_values($vars), $massage);
        return $template;
    }

    /** save logs to database
     * @param string status
     * @param string logs form webservice
     * @return boolean
     */
    public function saveLogs($status, $description, $bulk = null, $phone = null, $customer = null, $position = null)
    {
        $fields = array(
            'customer' => pSQL($customer),
            'phone' => pSQL($phone),
            'position' => pSQL($position),
            'status' => pSQL($status),
            'bulk' => pSQL($bulk),
            'description' => pSQL($description)
        );
        return Db::getInstance()->insert($this->name, $fields);
    }

     /** get phone mobile from customers
     * @return strings of phonemobiles
     */
    protected function getPhoneMobiles($getTotal = false)
    {
        //get mobiles
        $mobilesArray = array();
        $mobiles = null;
        if (!$getTotal) {
            $sql = new DbQueryCore();
            $sql->select('DISTINCT phone_mobile');
            $sql->from('address');
            $query = $sql->build();

            $results=Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
            if ($results and count($results)>0) {
                foreach ($results as $result) {
                    if (!Tools::isEmpty($result['phone_mobile'])) {
                        $mobilesArray[]=$result['phone_mobile'];
                    }
                }
                $mobiles = implode(';', $mobilesArray);
            } else {
                return false;
            }
            return $mobiles;
        } else {
            $sql= new DbQueryCore();
            $sql->select('count(DISTINCT phone_mobile)');
            $sql->from('address');
            $sql->where('phone_mobile !=""');
            $query= $sql->build();
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }
    }
    private function panelExpireTime()
    {
        $param = array('op'=>'usertime');
        $connectpanel = $this -> connectWebservice($param);
        if ($connectpanel) {
            $time = strtotime($connectpanel);
            $now = new DateTime;
            $expire = new DateTime('@'.$time);
            $diff = $now->diff($expire);
            $remaindays = $diff->days;
        } else {
            $remaindays = '???';
        }
        return $remaindays;
    }
    private function panelAuth()
    {
        if (Configuration::get($this->prefix . 'USERNAME') == 'demo' &&
            Configuration::get($this->prefix . 'PASSWORD') == 'demo') {
            return array(
                'ok' => 'demo',
                'credit' => 0,
                'expireTime' => 0,
                'lines' => 0,
            );
        }
        $param = array('op'=>'credit');
        $connectpanel = $this -> connectWebservice($param);
        if (isset($connectpanel['status']) and $connectpanel['status'] == 'failed') {
            return array('ok' => false,'error' => $connectpanel['res_data']);
        }
        if ($connectpanel[0] == 0) {
            return array(
                'ok' => 'user',
                'credit' => round($connectpanel[1]),
                'expireTime' => $this -> panelExpireTime(),
                'lines' => $this -> panelLines(),
            );
        } else {
            return array(
                'ok' => 'failed',
                'error' => $connectpanel[1],
            );
        }
    }
    private function panelLines()
    {
        $param = array('op'=>'lines');
        $connectpanel = $this -> connectWebservice($param);
        return $connectpanel;
    }
    private function panelCredit()
    {
        $param = array('op'=>'credit');
        $connectpanel = $this -> connectWebservice($param);
        return round($connectpanel[1]);
    }
    private function panelNews()
    {
        $url = $this->support.$this->name.'/?getNews';
        $param = array(
            'Mversion' => $this->version,
            'PSversion' => $this->PSversion,
            'shop' => $this->shop,
            'domain' => $this->domain,
            );
        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_POSTFIELDS, $param);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handler);

        return $response;
    }
    private function panelGetDelivery($uinqid)
    {
        $param = array('op'=>'delivery','uinqid'=>$uinqid);
        $connectpanel = $this -> connectWebservice($param);
        return $connectpanel;
    }
    private function demo()
    {
        $user = Configuration::get($this->prefix . 'USERNAME');
        $pass = Configuration::get($this->prefix . 'PASSWORD');
        if ($user == 'demo' and $pass == 'demo') {
            return true;
        } else {
            return false;
        }
    }
    protected function sendOne($text, $phonenumber, $customer = null, $position = null)
    {
        //If text is sample
        if (Tools::substr($text, 0, 11) === "patterncode") {
            $splited = explode(';', $text);
            $pattern_code = explode(':', $splited[0])[1];
            unset($splited[0]);
            $resArray = array();
            foreach ($splited as $parm) {
                $splited_parm = explode(':', $parm);
                $resArray[$splited_parm[0]] = $splited_parm[1];
            }
            if ($this->demo()) {
                $param = array(
                        'uname' => 'demo',
                        'pass' => 'demo',
                        'message' => $text,
                        'to' => $phonenumber,
                        'op' => 'sendPattern',
                    );
                $res = $this -> connectWebservice($param);
                if ($res['status'] == 'sent') {
                    $status = $res['status'];
                    $description = 'pattern:' . $text;
                    $bulk = $res['result'];
                    $this->saveLogs($status, $description, $bulk, $phonenumber, $customer, $position);
                    return 'sent';
                } else {
                    $status = 'failed';
                    $description = 'Text:' . $text . ' - error: ' . $res['result'];
                    $bulk = '-';
                    $this->saveLogs($status, $description, $bulk, $phonenumber, $customer, $position);
                    return $res['result'];
                }
            }
            $client = new SoapClient("http://37.130.202.188/class/sms/wsdlservice/server.php?wsdl");
            $user = Configuration::get($this->prefix . 'USERNAME');
            $pass = Configuration::get($this->prefix . 'PASSWORD');
            $fromNum = Configuration::get($this->prefix . 'SMSSENUMBER');
            $toNum = explode(';', $phonenumber);
            $input_data = $resArray;
            $response = $client -> sendPatternSms($fromNum, $toNum, $user, $pass, $pattern_code, $input_data);
            $response2 = json_decode($response);
            if (is_array($response2)) {
                $res_code = $response2[0];
                $res_data = $response2[1];
                $errorCodes = array(
                    '962' => $this->l('The username or password is incorrect!'),
                    '971' => $this->l('Patterns is not available!'),
                    '970' => $this->l('Parameters are incorrect!'),
                    '972' => $this->l('The reciver number is not correct!'),
                );
                $status = 'failed';
                if (isset($errorCodes[$res_code])) {
                    $result = $errorCodes[$res_code];
                } else {
                    $result = $res_data.' - Error Code:'.$res_code;
                }
                $this->saveLogs($status, $text, '-', $toNum, $customer, $position);
            } else {
                $status = $result = 'sent';
                $this->saveLogs($status, $text, $response, $toNum, $customer, $position);
            }
            return $result;
        }
        $rcpt_nm = explode(';', $phonenumber);
        $param = array();
        $param = array
                    (
                        'message'=> $text,
                        'to'=>json_encode($rcpt_nm),
                        'op'=> 'send'
                    );
        $connectpanel = $this -> connectWebservice($param);
        if (count($rcpt_nm) > 5) {
            $phonenumber = 'multiple';
        }
        $status = $connectpanel['status'];
        $description = 'Text:' . $text;
        $bulk = $connectpanel['res_data'];
        $this->saveLogs($status, $description, $bulk, $phonenumber, $customer, $position);
        return $connectpanel['result'];
    }
    public function sendSMS($param = array())
    {
        $rcpt_nm = explode(';', $param['reciver']);
        $param['to'] = json_encode($rcpt_nm);
        $param['op'] = 'send';
        $result = $this -> connectWebservice($param);
        if ($result['status'] == 'sent') {
            return true;
        } else {
            return false;
        }
    }
    protected function connectWebservice($param = array())
    {
        $url = "37.130.202.188/services.jspd";
        $param['uname'] = Configuration::get($this->prefix . 'USERNAME');
        $param['pass'] = Configuration::get($this->prefix . 'PASSWORD');
        $param['from'] = Configuration::get($this->prefix . 'SMSNUMBER');
        if (isset($param['message'])) {
            $param['message'] = str_replace('~', "\n", $param['message']);
        }
        if ($this->demo()) {
            $url = $this->support.$this->name.'/?demo';
        } elseif (!$param['uname'] or !$param['pass'] or !$param['from']) {
            return array(
                'status' => 'failed',
                'result' => $this->l('Gateway configuration is not set proprely.'),
                'res_data' => $this->l('Gateway configuration is not set proprely.')
                );
        }
        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_POSTFIELDS, $param);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handler);
        $response2 = json_decode($response);
        $res_code = $response2[0];
        $res_data = $response2[1];
        switch ($param['op']) {
            case 'sendPattern':
                $result = array('status' => $res_code,'result' => $res_data);
                break;
            case 'usertime':
                $result = $res_data;
                break;
            case 'credit':
                $result = $response2;
                break;
            case 'delivery':
                if ($response == 'null') {
                    return null;
                }
                $responseArray = array(
                    'notsync' =>     $this->l('Not Sync'),
                    'send' =>         $this->l('send'),
                    'pending' =>     $this->l('pending'),
                    'failed' =>     $this->l('failed'),
                    'discarded' =>     $this->l('discarded'),
                    'delivered' =>     $this->l('delivered'),
                    );
                if ($res_code == 0) {
                    $result = json_decode($res_data);
                    $res2 = explode(':', $result[0]);
                    $res_data = $responseArray[$res2[1]];
                }
                $result = $res_data;
                break;
            case 'lines':
                $linesArray = json_decode($res_data);
                $lines = array();
                foreach ($linesArray as $line) {
                    $lines[] = str_replace('+98', '', json_decode($line)->number);
                }
                $result = $lines;
                break;
            case 'send':
                $errorCodes = array(
                    '1' =>    $this->l('The message is empty!'),
                    '2' =>    $this->l('The user is limited!'),
                    '3' =>    $this->l('The Number is not belong to you!'),
                    '4' =>    $this->l('You have no reciver!'),
                    '5' =>    $this->l('You do not have enough credit!'),
                    '6' =>    $this->l('The message length is not proper!'),
                    '7' =>    $this->l('The line is not for send all'),
                    '98' => $this->l('The maximum reciver is not alowed!'),
                    '99' => $this->l('The operator is Off!'),
                    '962' =>$this->l('The username or password is incorrect!'),
                    '963' =>$this->l('The user is limited!'),
                    );
                if ($res_code == '0') {
                    $status = $result = 'sent';
                } else {
                    $status = 'failed';
                    if (isset($errorCodes[$res_code])) {
                        $result = $errorCodes[$res_code];
                    } else {
                        $result = $res_data.' - Error Code2:'.$res_code;
                    }
                }
                $result = array('status' => $status,'result' => $result,'res_data' => $res_data);
                break;
        }
        return $result;
    }
    
    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookActionAdminControllerSetMedia($params)
    {
        if (Tools::getValue('controller') == 'AdminOrders') {
            $this->context->controller->addJS($this->_path.'views/js/adminorders.js');
        }
        if (Tools::getValue('controller') == 'AdminCustomers' and Tools::getValue('id_customer')) {
            $this->context->controller->addJS($this->_path.'views/js/admincustomers.js');
        } elseif (Tools::getValue('controller') == 'AdminCustomers') {
            $this->context->controller->addJS($this->_path.'views/js/editcustomer.js');
        }
    }
}
