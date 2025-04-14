{*
*  2007-2022 MsgBucket
*
*  @author    msgbucket <sales@msgbucket.com>
*  @copyright 2012-2022 msgbucket
*  @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
*  International Registered Trademark & Property of msgbucket.com
*
*  You are allowed to modify this copy for your own use only. You must not redistribute it. License
*  is permitted for one Prestashop instance only but you can install it on your test instances.
*}


<div class="panel" id="fieldset_0" style="text-align:center; background-color: #FCFDFE;">

	<div class="row">

		<div class="col-lg-12">

			<img src="{$path|escape:'htmlall':'UTF-8'}views/img/logo.png" style="max-width: 200px; border-radius: 58px; padding: 0; margin: 10px 10px 0 10px;">

			<h1 style="color: 324a5e;margin: 0;font-size: 50px; margin: 20px 0">

				{l s='FREE SMS & Whatsapp Notifications and Marketing Campaigns'  mod='freesms'}

			</h1>

		</div>

	</div>

</div>

<div class="alert alert-warning">

	<p><i class="icon-info"></i> {l s='You need to be sure that the phone_mobile input is added to the address format of your shop.' mod='freesms'} <strong><a href="{$path|escape:'htmlall':'UTF-8'}views/img/phone_mobile.jpg" target="_blank">{l s='Example here' mod='freesms'}</a></strong>.</p>
	<p><i class="icon-info"></i> {l s='This module will use only the phone_mobile field to send notifications for your customers' mod='freesms'}. {l s='If your shop stored only the Home Phone field you can copy this informations to the phone_mobile field also by clicking' mod='freesms'} <strong><a href="{$module_page|escape:'html':'UTF-8'}&initiate=phone_mobile">{l s='here' mod='freesms'}</a></strong> {l s='and get the best one!' mod='freesms'}</p>

</div>

<script>
setTimeout(
	function version_status()
	{
		$('.needs-confirmation').on('click', function () {
	        return confirm('{l s='Are you sure yo want to delete the entire SMS and Whatsapp history log?' mod='freesms'}');
	    });

	  
	},
1000);
</script>