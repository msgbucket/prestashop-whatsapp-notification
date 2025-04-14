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



{if $delay_enabled == 1}
<div class="alert alert-warning">
	<p><i class="icon-star"></i> <strong>{l s='Warning!' mod='freesms'}</strong></p>
	<p>{l s='SMS & Whatsapp delay option is enabled, the SMS are sent unsing the CRON JOB functionality!' mod='freesms'}</p>
</div>
{/if}