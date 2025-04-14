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

<div class="card mt-2 d-print-none">
    <div class="card-header">
        <div class="row">
            <div class="col-md-12">
                <h3 class="card-header-title">
                    {l s='Send SMS & Whatsapp message to customer' mod='freesms'}
                </h3>
            </div>
        </div>
    </div>
    <div class="card-body">

        <form action="" method="post">

            <div id="sms" class="form-horizontal">

                <div class="form-group">

                    <label class="control-label col-lg-12">{l s='To' mod='freesms' }:</label>

                    <div class="col-lg-12">

                        <input type="text" name="send-to" class="form-control" value="{$customer_mobile|escape:'htmlall':'UTF-8'}">

                    </div>

                </div>

                <div class="form-group">

                    <label class="control-label col-lg-12">{l s='Message' mod='freesms' }</label>

                    <div class="col-lg-12">

                        <textarea id="sms_msg" class="textarea-autosize" name="sms-message" style="overflow: hidden;word-wrap: break-word;width: 100%;" rows="3"></textarea>

                        <p id="nbchars"></p>

                    </div>

                </div>

                <div class="form-group">

                    <input type="hidden" name="id_order" value="{$theorderid|escape:'htmlall':'UTF-8'}">

                    <input type="hidden" name="id_customer" value="{$thecustomerid|escape:'htmlall':'UTF-8'}">

                </div>

                <div class="text-right">

                    <button type="submit" id="submitSmsMessage" class="btn btn-primary pull-right" name="submitSmsMessage">

                        <i class="icon-envelope"></i> {l s='Send message' mod='freesms' }

                    </button>

                </div>

            </div>

        </form>
    </div>

</div>