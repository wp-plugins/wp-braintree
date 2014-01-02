jQuery(document).ready(function($) {
	
	// Hide ALL forms on page load
	$('.dialog-form').hide();
	
	
	// When a "buy now" button is clicked
	$('.submit_buy_now').click(function(e) {
		
		// If form is hidden, display form: else form is shown, so hide form
		if($(this).parent().parent().next('div.dialog-form').css('display') == 'none') {
			$(this).parent().parent().next('div.dialog-form').show();
		} else {
			$(this).parent().parent().next('div.dialog-form').hide();
		}
	});
	
	// Variables used for BrainTree API
	var braintree = Braintree.create(wp_braintree_scripts_front_js_vars.cse_key); 
	braintree.onSubmitEncryptForm("braintree-payment-form"); 
	
	// This is used when the page is submitted; it displays the transaction result
	$( "#dialog-message-success" ).dialog({  // If payment was success; this is the message just before the redirect url
		modal: true,
		width: 600,
		height: 300,
		buttons: {
			Ok: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function (event, ui) {
			$(this).remove();
			// Redirect after alert message
			window.location = wp_braintree_scripts_front_js_vars.success_url;
		}
	});
	$( "#dialog-message-error" ).dialog({  // If payment failed; alert message with only option to go back and fix form to resubmit
		modal: true,
		width: 600,
		height: 300,
		buttons: {
			Back: function() {
				history.back();
			}
		},
		close: function (event, ui) {
				history.back();
		}
	});
	
	// Before we actually submit any form for transaction... we want to check the input fields.
	// This will help since each time the form is submitted.. all fields are cleared.
	// We could get around this using sessions.
	$('.braintree-payment-form').submit(function(e) {
		
		// Get all form variables
		date_month = $(this).find('input.expiration_month').val(),
		date_year = $(this).find('input.expiration_year').val(),
		cvv = $(this).find('input.cvv').val(),
		number = $(this).find('input.number').val(),
		custom_alert = '';
		
		// Card number validation
		if(isNaN(number)) { custom_alert += wp_braintree_scripts_front_js_vars.cc_no_valid+'<br />'; }
		if(number.length>16 || number.length<9) { custom_alert += wp_braintree_scripts_front_js_vars.cc_digits+'<br />'; }
		// CVV Validation
		if(isNaN(cvv)) { custom_alert += wp_braintree_scripts_front_js_vars.cvv_number+'<br />'; }
		if(cvv.length>3 || cvv.length<3) { custom_alert += wp_braintree_scripts_front_js_vars.cvv_digits+'<br />'; }
		// Expiration month validation
		if(isNaN(date_month)) { custom_alert += wp_braintree_scripts_front_js_vars.exp_month_number+'<br />'; }
		if(date_month.length>2 || date_month.length<2) { custom_alert += wp_braintree_scripts_front_js_vars.exp_month_digits+'<br />'; }
		// Expiration year validation
		if(isNaN(date_year)) { custom_alert += wp_braintree_scripts_front_js_vars.exp_year_number+'<br />'; }
		if(date_year.length>4 || date_year.length<4) { custom_alert += wp_braintree_scripts_front_js_vars.exp_year_digits+'<br />'; }
		
		// If validation failed, throw alert message
		if(custom_alert !== '' ) {
			//alert(custom_alert);
			$("<div>"+custom_alert+"</div>").dialog({
				title: wp_braintree_scripts_front_js_vars.val_errors,
				modal: true,
				width: 600,
				height: 300,
				buttons: {
					Ok: function() {
						$( this ).dialog( "close" );
					}
				}
			});
			e.preventDefault();
			return;
		}
		// Else submit the form
		else {
			c = confirm(wp_braintree_scripts_front_js_vars.confirm_trans);
			return c;
		}
	});
	
});