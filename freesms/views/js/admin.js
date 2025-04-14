/*
* 2007-2022 MsgBucket
*
*  @author    msgbucket <sales@msgbucket.com>
*  @copyright 2012-2022 msgbucket
*  @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
*  International Registered Trademark & Property of msgbucket.com
*
*  You are allowed to modify this copy for your own use only. You must not redistribute it. License
*  is permitted for one Prestashop instance only but you can install it on your test instances.
*/

var explode = function(){

	$.fn.maxlength = function(options){

		var t = $(this);

		t.each(function(){

			options = $.extend(

				{},

				{

					counterContainer: false,

					text: '%left characters left' // %length %maxlength %left

				},

				options

			);

			var t = $(this),

				data = {

					options: options,

					field: t,

					counter: $('<div class="maxlength"></div>'),

					maxLength: parseInt(t.attr("maxlength"), 10),

					lastLength: null,

					updateCounter: function(){

						var length = this.field.val().length,

							text = this.options.text.replace(/\B%(length|maxlength|left)\b/g, $.proxy(function(match, p){

								return (p == 'length')? length : (p == 'maxlength')? this.maxLength : (this.maxLength - length);

							}, this));

						this.counter.html(text);

						if(length != this.lastLength){

							this.updateLength(length);

						}

					},

					updateLength: function(length){

						this.field.trigger("update.maxlength", [

							this.field,

							this.lastLength,

							length,

							this.maxLength,

							this.maxLength - length

						]);

						this.lastLength = length;

					}

				};

			if(data.maxLength){

				data.field

					.data("maxlength", data)

					.bind({

						"keyup change": function(e){

							$(this).data("maxlength").updateCounter();

						},

						"cut paste drop": function(e){

							setTimeout($.proxy(function(){

								$(this).data("maxlength").updateCounter();

							}, this), 1);

						}

					});

				if(options.counterContainer){

					options.counterContainer.append(data.counter);

				} else {

					data.field.after(data.counter);

				}

				data.updateCounter();

			}

		});

		return t;

	};





	

	function setCron() {

		var cronos = $('.interval_select').val();



		if (cronos==1) {

			cron = '* * * * * wget'

		} else if (cronos==2) {

			cron = '*/2 * * * * wget'

		} else if (cronos==3) {

			cron = '*/5 * * * * wget'

		} else if (cronos==4) {

			cron = '*/10 * * * * wget'

		} else if (cronos==5) {

			cron = '*/30 * * * * wget'

		} else if (cronos==6) {

			cron = '0 * * * * wget'

		} else if (cronos==7) {

			cron = '0 */2 * * * wget'

		} else if (cronos==8) {

			cron = '0 */6 * * * wget'

		}

		$('.cron-target').html(cron);

	}

	setCron();

	$("textarea").maxlength();

	$('.interval_select').change(function(){

		setCron();

	});

	function enableCron() {
		if ($('input[type=radio][name=enable_delay]').val() == 1) {
	        $('#WL_FREE_SMS_INTERVAL').parent().parent().show();
	        $('.cron-command').parent().parent().parent().show();
	    }
	    else if ($('input[type=radio][name=enable_delay]').val() == 0) {
	        $('#WL_FREE_SMS_INTERVAL').parent().parent().hide();
	        $('.cron-command').parent().parent().parent().hide();
	    }
	}

	enableCron();

	$('input[type=radio][name=enable_delay]').change(function() {
	    if (this.value == 1) {
	        $('#WL_FREE_SMS_INTERVAL').parent().parent().show();
	        $('.cron-command').parent().parent().parent().show();
	    }
	    else if (this.value == 0) {
	        $('#WL_FREE_SMS_INTERVAL').parent().parent().hide();
	        $('.cron-command').parent().parent().parent().hide();
	    }
	});


};

setTimeout(explode, 1500);