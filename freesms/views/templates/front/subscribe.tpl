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



{capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">{l s='My account' mod='freesms'}</a><span class="navigation-pipe">{$navigationPipe|escape:'html':'UTF-8'}</span><span class="navigation_page">{l s='SMS & Whatsapp Notifications' mod='freesms'}</span>{/capture}



<h1 class="page-heading">{l s='My SMS & Whatsapp Notifications' mod='freesms'}</h1>



{block name='page_content'}


    <br/>



    {if isset($customerid) && $customerid > 0}


	    <form method="POST" class="std">



	    	<fieldset>
	            <div class="clearfix">
	                <label>{l s='Receive SMS & Whatsapp notifications about the status of your orders' mod='freesms'}</label>
	                <br />

                    <div class="radio-inline">
                        <label for="get-sms" class="top">
                        <input type="radio" name="get-sms-notifications" id="get-sms" value="1" {if isset($getsms) && $getsms == 1}checked="checked"{/if} />
                        {l s='Yes' mod='freesms'}</label>
                    </div>

                    <div class="radio-inline">
                        <label for="get-sms" class="top">
                        <input type="radio" name="get-sms-notifications" id="get-sms" value="0" {if isset($getsms) && $getsms == 0}checked="checked"{/if} />
                        {l s='No' mod='freesms'}</label>
                    </div>

	            </div>
	        </fieldset>

	        <input class="btn btn-primary float-xs-right hidden-xs-down pull-right" name="savesms" type="submit" value="{l s='Save' mod='freesms'}">

	    </form>



	{else}

	    <div class="alert alert-danger" role="alert">

	      {l s='You need login to view this page!' mod='freesms'}

	    </div>

	{/if}



{/block}