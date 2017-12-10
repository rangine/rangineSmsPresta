{**
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
*  @file is used for display header messages on module config page.
*}
<div class="alert alert-{$alerttype|escape:'htmlall':'UTF-8'}">
	<img src="/modules/ranginesmspresta/logo.png" style="float:left; margin-right:15px;" width="50" height="50">
	<p>{l s='This module allows you to send sms from your site to your customers' mod='ranginesmspresta'}</p>
	{if $auth.ok == demo}
	<p>{l s='Now, You use our demo account. Notice that your site can send only 3 sms in minute and totaly you can send 100 sms page. If you have not account on ' mod='ranginesmspresta'}<a href="http://sms.rangine.ir" target="_BLANK">sms.rangine.ir</a>, {l s='you can go there and use your acount login information here.' mod='ranginesmspresta'}</p>
	{elseif $auth.ok == user}
	<p>{l s='Welcome to your account. You use your panel web service to send SMS from your site.' mod='ranginesmspresta'}<br/>{l s='If you have a question on using this module, go ahead and call our support team.' mod='ranginesmspresta'}</p>
	{else}
	<p>{l s='If you have not account on' mod='ranginesmspresta'} <a href="http://sms.rangine.ir" target="_BLANK">sms.rangine.ir</a>, {l s='you can go there and use your acount login information here.' mod='ranginesmspresta'}<br/>{l s='If you want to try our service put "demo" in username and password fields and type 0 in SMS Number field' mod='ranginesmspresta'}</p>
	{/if}
</div>
