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

{capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">{l s='My account' mod='freesms'}</a><span class="navigation-pipe">{$navigationPipe|escape:'html':'UTF-8'}</span><span class="navigation_page">{l s='Order Confirmation' mod='freesms'}</span>{/capture}


<h1 class="page-heading">{l s='Confirm your order' mod='freesms'}</h1>



{block name='page_content'}

    <br/>

	    {if $confirmation == false}
	    
	    	<div class="container">

	    		<div class="row">
	    			<br>
	    			<h2  style="text-align: center;">{l s='You need to confirm your order by pressing the button below' mod='freesms'}</h2>
	    		</div>

	    		<br><hr><br>

	    		<form method="POST" class="std">
			    	<div class="row">
				    	<div class="col-md-6 center-block" style="text-align: center;">
				    		<input class="btn btn-primary center-block" name="confirmOrder" type="submit" value="{l s='Confirm my order' mod='freesms'}">
				    	</div>
				        
				        <div class="col-md-6 center-block" style="text-align: center;">
				        	<input class="btn btn-secondary center-block" name="cancelOrder" type="submit" value="{l s='Cancel my order' mod='freesms'}">
				        </div>
				    </div>
				</form>

			    {if $product_data && count(product_data)>0}
			    <div class="row">

			    	<br><br>

	    			<h2  style="text-align: center;">{l s='Order summary:' mod='freesms'}</h2>

	    			<ul>
	    				<li>{l s='Order total:' mod='freesms'} {$order_data_paid|escape:'html':'UTF-8'}</li>
	    				<li>{l s='Pament method:' mod='freesms'} {$order_data_payment|escape:'html':'UTF-8'}</li>
	    			</ul>

	    			<br><hr><br>

	    			<table class="table">
						<thead>
							<tr>
								<th>{l s='Product' mod='freesms'}</th>
								<th>{l s='Quantity' mod='freesms'}</th>
								<th>{l s='Unit Price' mod='freesms'}</th>
								<th>{l s='Total Price' mod='freesms'}</th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$product_data item=product}
								<tr>
									<td>{$product.product_name|escape:'html':'UTF-8'}</td>
									<td>{$product.product_quantity|escape:'html':'UTF-8'}</td>
									<td>{$product.unit_price_tax_incl|escape:'html':'UTF-8'}</td>
									<td>{$product.total_price_tax_incl|escape:'html':'UTF-8'}</td>
								</tr>
		    				{/foreach}
						</tbody>
					</table>
	    		</div>
	    		{/if}

	    		<br><hr><br>

	    		<form method="POST" class="std">
			    	<div class="row">
				    	<div class="col-md-6 center-block" style="text-align: center;">
				    		<input class="btn btn-primary center-block" name="confirmOrder" type="submit" value="{l s='Confirm my order' mod='freesms'}">
				    	</div>
				        
				        <div class="col-md-6 center-block" style="text-align: center;">
				        	<input class="btn btn-secondary center-block" name="cancelOrder" type="submit" value="{l s='Cancel my order' mod='freesms'}">
				        </div>
				    </div>
				</form>

				<br><hr><br>

			</div>

	    {else}

	    	{if $confirmation == "confirmOrder"}
		    	<div class="alert alert-success" role="alert">
			    	{l s='Your order has been successfully confirmed! Thank you!' mod='freesms'}
			    </div>
			{else}
				<div class="alert alert-info" role="alert">
			    	{l s='Your order has been successfully canceled! Thank you!' mod='freesms'}
			    </div>
			{/if}

	    {/if}

	{else}

	    <div class="alert alert-danger" role="alert">

	      {l s='There is no order to confirm' mod='freesms'}

	    </div>

	{/if}



{/block}