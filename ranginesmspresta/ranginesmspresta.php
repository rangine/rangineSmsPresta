<?php
/**
*  @author Hadi Mollaei <mr.hadimollaei@gmail.com>
*  @copyright All rights Reseved for Rangine.ir.
*  This is a SMS gateway for lovely PrestaShop to sms.rangine.ir sms panels.
*/

if (!defined('_PS_VERSION_'))
    exit;
class RangineSmsPresta extends Module
{
    private $prefix;
    public function __construct()
    {
        $this->name          = 'ranginesmspresta';
        $this->tab           = 'emailing';
        $this->version       = '1.1.1';
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
		$this->enable			= Configuration::get($this->prefix .'SMSNUMBER');
		$this->PSversion = Configuration::get('PS_INSTALL_VERSION');
		$this->shop = Configuration::get('PS_SHOP_NAME');
		$this->domain = Configuration::get('PS_SHOP_DOMAIN');
    }

    public function install()
    {
        if (!$this->installConfig())
            return false;

        if (!$this->installDatabase())
            return false;

        if (!parent::install() or !$this->registerHook('actionValidateOrder') or !$this->registerHook('actionOrderStatusPostUpdate') or !$this->registerHook('updateQuantity') or !$this->registerHook('actionCustomerAccountAdd'))
            return false;

        return true;
    }

    public function uninstall()
    {
        if (!$this->uninstallConfig())
            return false;

        if (!$this->unistallDatabase())
            return false;

        if (!parent::uninstall())
            return false;

        return true;
    }

    protected function installConfig()
    {
        if (!Configuration::updateValue($this->prefix . 'USERNAME', '') or
            !Configuration::updateValue($this->prefix . 'PASSWORD', '') or
            !Configuration::updateValue($this->prefix . 'ADMINPHONE', '') or
            !Configuration::updateValue($this->prefix . 'SMSNUMBER', '') or
            !Configuration::updateValue($this->prefix . 'SMSENABLE', '0') or
            !Configuration::updateValue($this->prefix . 'NEWORDERA', '1') or
            !Configuration::updateValue($this->prefix . 'NEWORDERC', '0') or
            !Configuration::updateValue($this->prefix . 'UPDATEORDERC', '1') or
            !Configuration::updateValue($this->prefix . 'NEWCUSTOMERA', '1') or
            !Configuration::updateValue($this->prefix . 'NEWCUSTOMERC', '0') or
            !Configuration::updateValue($this->prefix . 'NEWORDERFC', '0') or
            !Configuration::updateValue($this->prefix . 'NEWORDERFA', '0') or
            !Configuration::updateValue($this->prefix . 'NEWACCOUNTADMINCUSTOMTEXT', $this->l('{firstname} {lastname} registred on site')) or
            !Configuration::updateValue($this->prefix . 'NEWACCOUNTADMINTEXTTYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'NEWACCOUNTCUSTOMERCUSTOMTEXT', $this->l('Dear {firstname} {lastname},~Welcome to our site.')) or
            !Configuration::updateValue($this->prefix . 'NEWACCOUNTCUSTOMERTEXTTYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'NEWORDERADMINTEXTTYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'NEWORDERADMINCUSTOMTEXT', $this->l('Customer: {firstname} {lastname}~Order: {order_name}~Payment: {payment}~Total: ({total_paid} {currency})')) or
            !Configuration::updateValue($this->prefix . 'NEWORDERCUSTOMERTEXTTYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'NEWORDERCUSTOMERCUSTOMTEXT', $this->l('{shop_name}~Customer: {firstname} {lastname}~Order: {order_name}~Payment: {payment}~Total: ({total_paid} {currency})')) or
            !Configuration::updateValue($this->prefix . 'UPDATEORDERCUSTOMERTEXTTYPE', 'default') or
            !Configuration::updateValue($this->prefix . 'UPDATEORDERCUSTOMERCUSTOMTEXT', $this->l('{shop_name}~Dear {firstname} {lastname},~New status for order: {order_name}: {order_state}'))) return false;

        return true;
    }

    protected function uninstallConfig()
    {
        if (!Configuration::deleteByName($this->prefix . 'USERNAME') or
            !Configuration::deleteByName($this->prefix . 'PASSWORD') or
            !Configuration::deleteByName($this->prefix . 'ADMINPHONE') or
            !Configuration::deleteByName($this->prefix . 'SMSNUMBER') or
            !Configuration::deleteByName($this->prefix . 'SMSENABLE') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERA') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERC') or
            !Configuration::deleteByName($this->prefix . 'UPDATEORDERC') or
            !Configuration::deleteByName($this->prefix . 'NEWCUSTOMERA') or
            !Configuration::deleteByName($this->prefix . 'NEWCUSTOMERC') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERFC') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERFA') or
            !Configuration::deleteByName($this->prefix . 'NEWACCOUNTADMINCUSTOMTEXT') or
            !Configuration::deleteByName($this->prefix . 'NEWACCOUNTADMINTEXTTYPE') or
            !Configuration::deleteByName($this->prefix . 'NEWACCOUNTCUSTOMERCUSTOMTEXT') or
            !Configuration::deleteByName($this->prefix . 'NEWACCOUNTCUSTOMERTEXTTYPE') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERADMINTEXTTYPE') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERADMINCUSTOMTEXT') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERCUSTOMERTEXTTYPE') or
            !Configuration::deleteByName($this->prefix . 'NEWORDERCUSTOMERCUSTOMTEXT') or
            !Configuration::deleteByName($this->prefix . 'UPDATEORDERCUSTOMERTEXTTYPE') or
            !Configuration::deleteByName($this->prefix . 'UPDATEORDERCUSTOMERCUSTOMTEXT'))
            return false;

        return true;
    }

    protected function installDatabase()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->name . '`(
                `id_' . $this->name . '` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `status` VARCHAR(256) NOT NULL ,
                 `description` TEXT NOT NULL
                 )ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        return Db::getInstance()->execute($sql);
    }

    protected function unistallDatabase()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $this->name . '`';
        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {	

		if(!extension_loaded('curl')) return $this->displayError($this->l('cURL is not enabled. You should enable it before using thes module.'));
        $output = null;

        $errors = array();
        //update gateway Settings
        if (Tools::isSubmit('gatewaySettings')) {
			$variables = array('USERNAME','PASSWORD','ADMINPHONE','SMSNUMBER','SMSENABLE');
            foreach ($_POST as $key => $value) {
				if(!in_array($key,$variables)) continue;
                if (!Configuration::updateValue($this->prefix . $key, $value)) {
                    $errors[] = Tools::displayError($this->l('Settings is not updated'));
                    break;
                }
            }
            if (isset($errors) && count($errors)) $output .= $this->displayError(implode('<br />', $errors));
            else $output .= $this->displayConfirmation($this->l('Settings updated'));
			//update alert Settings
        }elseif (Tools::isSubmit('alertSettings')) {
			$variables = array('NEWORDERA','NEWORDERC','UPDATEORDERC','NEWCUSTOMERA','NEWCUSTOMERC','NEWORDERFC' ,'NEWORDERFA');

            foreach ($_POST as $key => $value) {
				if(!in_array($key,$variables)) continue;
                if (!Configuration::updateValue($this->prefix . $key, $value)) {
                    $errors[] = Tools::displayError($this->l('Settings is not updated'));
                    break;
                }
            }
            if (isset($errors) && count($errors)) $output .= $this->displayError(implode('<br />', $errors));
            else $output .= $this->displayConfirmation($this->l('Settings updated'));
			//save text
        } elseif (Tools::isSubmit('saveTexts')) {
			$variables = array('NEWACCOUNTADMINCUSTOMTEXT',
            'NEWACCOUNTADMINTEXTTYPE',
            'NEWACCOUNTCUSTOMERCUSTOMTEXT',
            'NEWACCOUNTCUSTOMERTEXTTYPE',
            'NEWORDERADMINTEXTTYPE',
            'NEWORDERADMINCUSTOMTEXT',
            'NEWORDERCUSTOMERTEXTTYPE',
            'NEWORDERCUSTOMERCUSTOMTEXT',
            'UPDATEORDERCUSTOMERTEXTTYPE',
            'UPDATEORDERCUSTOMERCUSTOMTEXT');
            foreach ($_POST as $key => $value) {
				if(!in_array($key,$variables)) continue;
                if (!Configuration::updateValue($this->prefix . $key, $value)) {
                    $errors[] = Tools::displayError($this->l('Settings is not updated'));
                    break;
                }
            }
            if (isset($errors) && count($errors)) $output .= $this->displayError(implode('<br />', $errors));
            else $output .= $this->displayConfirmation($this->l('Settings updated'));
			//send sms
        } elseif (Tools::isSubmit('SendSMS')) {
            $phones = Tools::getValue('RECIVER');
            if (Tools::strlen($phones)<11) {
                $errors[] = Tools::displayError($this->l('insert valid phone number'));
            } else {
                $text = Tools::getValue('SENDONETEXT');
                $result=$this->sendOne($text, $phones, 'private');
                if ($result !='sent') $errors[]=Tools::displayError($result);
            }
            if (isset($errors) && count($errors)) $output .= $this->displayError(implode('<br />', $errors));
            else $output .= $this->displayConfirmation($this->l('sent'));
			// send to all customers
        } elseif (Tools::isSubmit('SendCustomers')) {
            $text = Tools::getValue('SENDALLTEXT');
            if (Tools::isEmpty($text)) $errors[]=Tools::displayError($this->l('text is empty'));
            $phones=$this->getPhoneMobiles();

               $result=$this->sendOne($text, $phones, 'sendToCustomers');
                if ($result !='sent') $errors[]=Tools::displayError($result);
                if (isset($errors) && count($errors)) $output .= $this->displayError(implode('<br />', $errors));
                else $output .= $this->displayConfirmation($this->l('The sms sent to customers'));
        }
		$this->auth = $this->panelAuth();
		$this->smarty->assign(array(
			'auth' => $this->auth,
			'alerttype' => 'info',
		));
		$nessesary_news = $this->panelNews();
        return $output . $this->display(__FILE__, 'views/templates/admin/headerinfo.tpl') .$nessesary_news.$this->displayForm();
    }
	
    protected function displayForm()
    {
		$auth = $this->auth;
		$phone_mobiles=$this->getPhoneMobiles(true);
		$gatewayDescription = $gatewayWarning = $gatewaySuccess = $gatewayError = null;
		if($auth['ok'] == 'user'){ 
			$sendnumber = str_replace('+98','',Configuration::get($this->prefix .'SMSNUMBER'));
			if(in_array($sendnumber,$auth['lines'])){
				$gatewaySuccess = $this->l('The gateway configured correctly!').' '
					.$this->l('Your remain credit is: ') .' '
					.$auth['credit'].' '
					.$this->l('rials').', '
					.$this->l('And your account expire time is after').' '
					.$auth['expireTime'].' '
					.$this->l('days');
			}else{
				$gatewayWarning = $this->l('The user and pass are correct but this line is not belong to you: ').$sendnumber;
			}
		}elseif($auth['ok'] == 'demo'){
			$gatewayDescription = $this->l('You are testing our demo services. The maximum amount of utilization of these services depends on our support section. If you have questions about the system and purchase a panel, you can contact 09191483567 with a call, text message or telegram.');
		}else{
			$gatewayError = $auth['error'];
		}
		if (version_compare(_PS_VERSION_, '1.6.1', '>=') === false)  $gatewayDescription= $gatewayDescription.$gatewayWarning.$gatewaySuccess.$gatewayError;
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
						'desc' => $this->l('To send sms more than one number please saperate numbers with ; ex:09123456789;09101234567.'),
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
						'name' => 'NEWACCOUNTADMINTEXTTYPE',
						'hint' => $this->l('Select which type you want to send to admin on new account event.'),
						'values' => array(
							array(
								'id' => 'default',
								'value' => 'default',
								'label' => $this->l('default')
							),
							/* array(
								'id' => 'sample',
								'value' => 'sample',
								'label' => $this->l('By Sample')
							), */
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
						'name' => 'NEWACCOUNTADMINCUSTOMTEXT',
						'class' => 'fixed-width-lg',
						'desc' => $this->l('Type your custom text. you can use this variables: {firstname} {lastname}'),
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Text for Customer on New Account:'),
						'name' => 'NEWACCOUNTCUSTOMERTEXTTYPE',
						'hint' => $this->l('Select which type you want to send to customer on new account event.'),
						
						'values' => array(
							array(
								'id' => 'default',
								'value' => 'default',
								'label' => $this->l('default')
							),
							/* array(
								'id' => 'sample',
								'value' => 'sample',
								'label' => $this->l('By Sample')
							), */
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
						'name' => 'NEWACCOUNTCUSTOMERCUSTOMTEXT',
						'class' => 'fixed-width-lg',
						'desc' => $this->l('Type your custom text. you can use this variables: {firstname} {lastname}'),
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Text for Admin on New Order:'),
						'name' => 'NEWORDERADMINTEXTTYPE',
						'hint' => $this->l('Select which type you want to send to admin on new order event.'),
						'values' => array(
							array(
								'id' => 'default',
								'value' => 'default',
								'label' => $this->l('default')
							),
							/* array(
								'id' => 'sample',
								'value' => 'sample',
								'label' => $this->l('By Sample')
							), */
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
						'name' => 'NEWORDERADMINCUSTOMTEXT',
						'class' => 'fixed-width-lg',
						'desc' => $this->l('Type your custom text. you can use this variables: {firstname} {lastname} {order_name} {payment} {total_paid} {currency}'),
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Text for Customer on New Order:'),
						'name' => 'NEWORDERCUSTOMERTEXTTYPE',
						'hint' => $this->l('Select which type you want to send to customer on new order event.'),
						
						'values' => array(
							array(
								'id' => 'default',
								'value' => 'default',
								'label' => $this->l('default')
							),
							/* array(
								'id' => 'sample',
								'value' => 'sample',
								'label' => $this->l('By Sample')
							), */
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
						'name' => 'NEWORDERCUSTOMERCUSTOMTEXT',
						'class' => 'fixed-width-lg',
						'desc' => $this->l('Type your custom text. you can use this variables: {firstname} {lastname} {order_name} {payment} {total_paid} {currency}'),
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Text for Customer on Update Order:'),
						'name' => 'UPDATEORDERCUSTOMERTEXTTYPE',
						'hint' => $this->l('Select which type you want to send to customer on update order event.'),
						
						'values' => array(
							array(
								'id' => 'default',
								'value' => 'default',
								'label' => $this->l('default')
							),
							/* array(
								'id' => 'sample',
								'value' => 'sample',
								'label' => $this->l('By Sample')
							), */
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
						'name' => 'UPDATEORDERCUSTOMERCUSTOMTEXT',
						'class' => 'fixed-width-lg',
						'desc' => $this->l('Type your custom text. you can use this variables: {shop_name} {firstname} {lastname} {order_name} {order_state}'),
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
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'ranginesmspresta';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);
		$result = null;
		$result .= $helper->generateForm(array($fields_form_gateway));
		if($auth['ok'] == 'user' or $auth['ok'] == 'demo' ){ 
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
			'NEWACCOUNTADMINTEXTTYPE' => Tools::getValue('NEWACCOUNTADMINTEXTTYPE', Configuration::get($this->prefix .'NEWACCOUNTADMINTEXTTYPE')),
			'NEWACCOUNTADMINCUSTOMTEXT' => Tools::getValue('NEWACCOUNTADMINCUSTOMTEXT', Configuration::get($this->prefix .'NEWACCOUNTADMINCUSTOMTEXT')),
			'NEWACCOUNTCUSTOMERCUSTOMTEXT' => Tools::getValue('NEWACCOUNTCUSTOMERCUSTOMTEXT', Configuration::get($this->prefix .'NEWACCOUNTCUSTOMERCUSTOMTEXT')),
			'NEWACCOUNTCUSTOMERTEXTTYPE' => Tools::getValue('NEWACCOUNTCUSTOMERTEXTTYPE', Configuration::get($this->prefix .'NEWACCOUNTCUSTOMERTEXTTYPE')),
			'NEWORDERADMINTEXTTYPE' => Tools::getValue('NEWORDERADMINTEXTTYPE', Configuration::get($this->prefix .'NEWORDERADMINTEXTTYPE')),
			'NEWORDERADMINCUSTOMTEXT' => Tools::getValue('NEWORDERADMINCUSTOMTEXT', Configuration::get($this->prefix .'NEWORDERADMINCUSTOMTEXT')),
			'NEWORDERCUSTOMERTEXTTYPE' => Tools::getValue('NEWORDERCUSTOMERTEXTTYPE', Configuration::get($this->prefix .'NEWORDERCUSTOMERTEXTTYPE')),
			'NEWORDERCUSTOMERCUSTOMTEXT' => Tools::getValue('NEWORDERCUSTOMERCUSTOMTEXT', Configuration::get($this->prefix .'NEWORDERCUSTOMERCUSTOMTEXT')),
			'UPDATEORDERCUSTOMERTEXTTYPE' => Tools::getValue('UPDATEORDERCUSTOMERTEXTTYPE', Configuration::get($this->prefix .'UPDATEORDERCUSTOMERTEXTTYPE')),
			'UPDATEORDERCUSTOMERCUSTOMTEXT' => Tools::getValue('UPDATEORDERCUSTOMERCUSTOMTEXT', Configuration::get($this->prefix .'UPDATEORDERCUSTOMERCUSTOMTEXT')),
		);
	}
    public function hookActionCustomerAccountAdd($params)
    {
		if(!$this->enable) return true;
        if (Configuration::get($this->prefix . 'USERNAME') == '' or Configuration::get($this->prefix . 'PASSWORD') == '' or Configuration::get($this->prefix . 'NEWCUSTOMERA') == 0 and Configuration::get($this->prefix . 'NEWCUSTOMERC') == 0)
            return true;

        if (Configuration::get($this->prefix . 'NEWCUSTOMERA') != 0 and Configuration::get($this->prefix . 'ADMINPHONE') == 0)
            return true;


        if (is_object($params['newCustomer']))
            $newCustomer = get_object_vars($params['newCustomer']);

        $firstName = $newCustomer['firstname'];
        $lastName  = $newCustomer['lastname'];


        $vars = array(
            '{firstname}' => $firstName,
            '{lastname}' => $lastName
        );
        if (Configuration::get($this->prefix . 'NEWCUSTOMERA') != 0) {
            if (!$text = $this->generateSMStext($vars, 'newacount', 'admin'))
                die('Can not generate massage!');
            $this->sendOne($text, Configuration::get($this->prefix . 'ADMINPHONE'), 'newAccount');
        }
        if (Configuration::get($this->prefix.'NEWCUSTOMERC')!=0) {
            $id_address      = Address::getFirstCustomerAddressId($newCustomer['id']);
            $customerAddress = new AddressCore($id_address);
            $phone           = $customerAddress->phone_mobile;

            if (!$phone or Tools::strlen($phone) < 10)
                return true;
            if (!$text = $this->generateSMStext($vars, 'newacount', 'customer'))
                die('Can not generate massage!');

            $this->sendOne($text, $phone, 'neworder');
        }

        return true;
    }

    public function hookActionValidateOrder($params)
    {
		if(!$this->enable) return true;
        if (Configuration::get($this->prefix . 'USERNAME') == '0' or Configuration::get($this->prefix . 'PASSWORD') == '0' or (Configuration::get($this->prefix . 'NEWORDERA') == 0 and Configuration::get($this->prefix . 'NEWORDERC') == 0))
            return true;

        if (Configuration::get($this->prefix . 'NEWCUSTOMERA') != 0 and Configuration::get($this->prefix . 'ADMINPHONE') == 0)
            return true;



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
            if ($order->total_paid == 0 and Configuration::get($this->prefix . 'NEWORDERFA') == 0)
                return true;

            if (!$text = $this->generateSMStext($vars, 'neworder', 'admin'))
                die('Can not generate massage!');

            $this->sendOne($text, Configuration::get($this->prefix . 'ADMINPHONE'), 'neworder');
        }

        if (Configuration::get($this->prefix . 'NEWORDERC') != 0) {
            if ($order->total_paid == 0 and Configuration::get($this->prefix . 'NEWORDERFC') == 0) return true;

            $id_address      = Address::getFirstCustomerAddressId($customer->id);
            $customerAddress = new AddressCore($id_address);
            $phone           = $customerAddress->phone_mobile;

            if (!$phone or Tools::strlen($phone) < 10)
                return true;
            if (!$text = $this->generateSMStext($vars, 'neworder', 'customer'))
                die('Can not generate massage!');

            $this->sendOne($text, $phone, 'neworder');
        }
            return true;
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
		if(!$this->enable) return true;
        if (Configuration::get($this->prefix . 'USERNAME') == '0' or Configuration::get($this->prefix . 'PASSWORD') == '0' or (Configuration::get($this->prefix . 'NEWCUSTOMERA') == 0 and Configuration::get($this->prefix . 'NEWCUSTOMERC') == 0))
            return true;

        if (Configuration::get($this->prefix . 'UPDATEORDERC') == 0)
            return true;


        $order      = new Order((int) ($params['id_order']));
        $orderstate = $params['newOrderStatus'];

        $customer = new Customer((int) $order->id_customer);
        $id_address = Address::getFirstCustomerAddressId($customer->id);
        $address  = new Address((int)($id_address));
        if ($address->phone_mobile == null)
            return true;

         if (Tools::strtolower($orderstate->name) == Tools::strtolower('Awaiting cheque payment') or Tools::strtolower($orderstate->name) == Tools::strtolower('Awaiting bank wire payment'))
            return true;
        $vars = array(
            '{firstname}' => ($customer->firstname),
            '{lastname}' => ($customer->lastname),
            '{order_name}' => sprintf("%06d", $order->id),
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{order_state}' => $orderstate->name
        );

        if (!$text = $this->generateSMStext($vars, 'updateorder', 'customer'))
            die('Can not generate massage!');

        $this->sendOne($text, $address->phone_mobile, 'updateorder');

        return true;
    }
    /** generate text message
     * @param array vars varible to set in tamplate
     * @param string  exists template
     * @param string folder exists txtfile nam
     * @return text message
     */
    private function generateSMStext($vars, $event, $reciver)
    {
		$message = '';
		if($reciver == 'admin'){
			switch($event){
				case 'newacount':
					$type = Configuration::get('NEWACCOUNTADMINTEXTTYPE');
					if($type == 'custom'){
						$massage = Configuration::get('NEWACCOUNTADMINCUSTOMTEXT');
					}elseif($type == 'sample'){
						$massage = "patterncode:100;name:{firstname} {lastname}";
					}else{
						$massage = $this->l('{firstname} {lastname} registred on site');
					}
				break;
				case 'neworder':
					$type = Configuration::get('NEWORDERADMINTEXTTYPE');
					if($type == 'custom'){
						$massage = Configuration::get('NEWORDERADMINCUSTOMTEXT');
					}elseif($type == 'sample'){
						$massage = "patterncode:100;name:{firstname} {lastname};order:{order_name};payment:{payment};total:{total_paid} {currency}";
					}else{
						$massage = $this->l('Customer: {firstname} {lastname}~Order: {order_name}~Payment: {payment}~Total: ({total_paid} {currency})');
					}
				break;
			}
		}else{
			switch($event){
				case 'newacount':
					$type = Configuration::get('NEWACCOUNTCUSTOMERTEXTTYPE');
					if($type == 'custom'){
						$massage = Configuration::get('NEWACCOUNTCUSTOMERCUSTOMTEXT');
					}elseif($type == 'sample'){
						$massage = "patterncode:100;name:{firstname} {lastname}";
					}else{
						$massage = $this->l('Dear {firstname} {lastname},~Welcome to our site.');
					}
				break;
				case 'neworder':
					$type = Configuration::get('NEWORDERCUSTOMERTEXTTYPE');
					if($type == 'custom'){
						$massage = Configuration::get('NEWORDERCUSTOMERCUSTOMTEXT');
					}elseif($type == 'sample'){
						$massage = "patterncode:100;shopname:{shop_name};customer:{firstname} {lastname};order:{order_name};payment:{payment};total:{total_paid} {currency}";
					}else{
						$massage = $this->l('{shop_name}~Customer: {firstname} {lastname}~Order: {order_name}~Payment: {payment}~Total: ({total_paid} {currency})');
					}
				break;
				case 'updateorder':
					$type = Configuration::get('UPDATEORDERCUSTOMERTEXTTYPE');
					if($type == 'custom'){
						$massage = Configuration::get('UPDATEORDERCUSTOMERCUSTOMTEXT');
					}elseif($type == 'sample'){
						$massage = "patterncode:100;shopname:{shop_name};customer:{firstname} {lastname};order:{order_name};orderstatus:{order_state}";
					}else{
						$massage = $this->l('{shop_name}~Dear {firstname} {lastname},~New status for order: {order_name}: {order_state}');
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
    public function saveLogs($status, $description)
    {
        $fields = array(
            'status' => pSQL($status),
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
                    if (!Tools::isEmpty($result['phone_mobile']))
                    $mobilesArray[]=$result['phone_mobile'];
                }
				$mobiles = implode(';',$mobilesArray);
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
	private function panelExpireTime(){
		$param = array('op'=>'usertime');
		$connectpanel = $this -> connectWebservice($param);
		$time = strtotime($connectpanel);
		$now = new DateTime;
		$expire = new DateTime('@'.$time);
		$diff = $now->diff($expire);
		$remaindays = $diff->days;
		return $remaindays;
	}
	private function panelAuth(){
		if(Configuration::get($this->prefix . 'USERNAME') == 'demo' && Configuration::get($this->prefix . 'PASSWORD') == 'demo'){
			return array(
				'ok' => 'demo',
				'credit' => 0,
				'expireTime' => 0,
				'lines' => 0,
			);
		}
		$param = array('op'=>'credit');
		$connectpanel = $this -> connectWebservice($param);
		if(isset($connectpanel['status']) and $connectpanel['status'] == 'failed') return array('ok' => false,'error' => $connectpanel['res_data']);
		if($connectpanel[0] == 0 ) {
			return array(
				'ok' => 'user',
				'credit' => round($connectpanel[1]),
				'expireTime' => $this -> panelExpireTime(),
				'lines' => $this -> panelLines(),
			);
		} else{
			return array(
				'ok' => 'failed',
				'error' => $connectpanel[1],
			);
		}
	}
	private function panelLines(){
		$param = array('op'=>'lines');
		$connectpanel = $this -> connectWebservice($param);
		return $connectpanel;
	}
	private function panelCredit(){
		$param = array('op'=>'credit');
		$connectpanel = $this -> connectWebservice($param);
		return round($connectpanel[1]);
	}
	private function panelNews(){
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
	private function panelGetDelivery($uinqid){
		$param = array('op'=>'delivery','uinqid'=>$uinqid);
		$connectpanel = $this -> connectWebservice($param);	
		return $connectpanel;
	}
	private function demo(){
		$user = Configuration::get($this->prefix . 'USERNAME');
		$pass = Configuration::get($this->prefix . 'PASSWORD');	
		if($user == 'demo' and $pass = 'demo') return true; else return false;
	}
    protected function sendOne($text, $phonenumber, $status)
    {
		//If text is sample
		if(substr( $text, 0, 11 ) === "patterncode"){
			$splited = explode(';',$text);
			$pattern_code = explode(':',$splited[0])[1];
			unset($splited[0]);
			$resArray = array();
			foreach($splited as $parm){
				$splited_parm = explode(':',$parm);
				$resArray[$splited_parm[0]] = $splited_parm[1];
			}
			$client = new SoapClient("http://37.130.202.188/class/sms/wsdlservice/server.php?wsdl");
			$user = Configuration::get($this->prefix . 'USERNAME');
			$pass = Configuration::get($this->prefix . 'PASSWORD');
			$fromNum = "+985000125475";
			$toNum = explode(';', $phonenumber);
			$input_data = $resArray;
			$response = $client -> sendPatternSms($fromNum,$toNum,$user,$pass,$pattern_code,$input_data);
			$response2 = json_decode($response); 
			$res_code = $response2[0];
			$res_data = $response2[1];
			$errorCodes = array(
				'962' => $this->l('The username or password is incorrect!'),
				'971' => $this->l('Patterns is not available!'),
				'970' => $this->l('Parameters are incorrect!'),
            );
			if(is_array($response)){
				$status = 'failed';
				if (isset($errorCodes[$res_code])) $result = $errorCodes[$res_code];
				else $result = $res_data.' - Error Code1:'.$res_code;
          
			}else{
				$status = $result = 'sent';
			}
			$this->saveLogs($status, $res_data.':'.$text);
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
        $this->saveLogs($connectpanel['status'], $connectpanel['res_data'].':'.$text);
        return $connectpanel['result'];
    }
	public function sendSMS($param = array()){
		$rcpt_nm = explode(';', $param['reciver']);
		$param['to'] = json_encode($rcpt_nm);
		$param['op'] = 'send';
		$result = $this -> connectWebservice($param);
		if($result['status'] == 'sent') return true; else return false;
	}
	protected function connectWebservice($param = array()){
		$url = "37.130.202.188/services.jspd";
		if($this->demo()) $url = $this->support.$this->name.'/?demo';
 		$param['uname'] = Configuration::get($this->prefix . 'USERNAME');
        $param['pass'] = Configuration::get($this->prefix . 'PASSWORD');
        $param['from'] = Configuration::get($this->prefix . 'SMSNUMBER');
        if(isset($param['message'])) $param['message'] = str_replace('~',"\n",$param['message']);
		if(!$param['uname'] or !$param['pass'] or !$param['from']){
			
			return array('status' => 'failed','result' => $this->l('Gateway configuration is not set proprely.'),'res_data' => $this->l('Gateway configuration is not set proprely.'));
		}
        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_POSTFIELDS, $param);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handler);

        $response2 = json_decode($response);
        $res_code = $response2[0];
        $res_data = $response2[1];
		switch($param['op']){
			case 'usertime':
				return $res_data;
			break;
			case 'credit':
				return $response2;
			break;
			case 'delivery':
				if($response == 'null') return null;
				$responseArray = array(
					'notsync' => 	$this->l('Not Sync'),
					'send' => 		$this->l('send'), 
					'pending' => 	$this->l('pending'), 
					'failed' => 	$this->l('failed'), 
					'discarded' => 	$this->l('discarded'), 
					'delivered' => 	$this->l('delivered'), 
					);
				if($res_code == 0) {
					$result = json_decode($res_data);
					$res2 = explode(':',$result[0]);
					$res_data = $res2[1];
				} 
				return $res_data;
			break;
			case 'lines':
				$linesArray = json_decode($res_data); 
				$lines = array();
				foreach($linesArray as $line){
					$lines[] = str_replace('+98','',json_decode($line)->number);
				}
				return $lines;
			break;
			case 'send':
				$errorCodes = array(
					'1' =>	$this->l('The message is empty!'),
					'2' =>	$this->l('The user is limited!'),
					'3' =>	$this->l('The Number is not belong to you!'), 
					'4' =>	$this->l('You have no reciver!'),
					'5' =>	$this->l('You do not have enough credit!'),
					'6' =>	$this->l('The message length is not proper!'),
					'7' =>	$this->l('The line is not for send all'),
					'98' => $this->l('The maximum reciver is not alowed!'), 
					'99' => $this->l('The operator is Off!'),
					'962' =>$this->l('The username or password is incorrect!'), 
					'963' =>$this->l('The user is limited!'), 
					);
				if ($res_code == '0') {
					$status = $result = 'sent';
				}else {
					$status = 'failed';
					if (isset($errorCodes[$res_code])) $result = $errorCodes[$res_code];
					else $result = $res_data.' - Error Code2:'.$res_code;
				}
				return array('status' => $status,'result' => $result,'res_data' => $res_data);
			break;
		}
        
	} 
}
