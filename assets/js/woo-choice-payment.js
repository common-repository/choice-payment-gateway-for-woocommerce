(function ($, window, document, woo_choice_payment_params) {
	var addHandler = function (){};

	function formHandler(e)
	{
		var choicePayntMethod = document.getElementById(
			'payment_method_choicepaynt'
		);

		var choicePayntMethodAch = document.getElementById(
			'payment_method_choicepaynt_ach'
		);

		var choice_payment_Enabled     = choicePayntMethod && choicePayntMethod.checked;
		var choice_payment_ach_Enabled = choicePayntMethodAch && choicePayntMethodAch.checked;

		// If not Choice credit card or is Choice ACH payment then ignore event.
		if ( ! choice_payment_Enabled || choice_payment_ach_Enabled) {
			return true;
		}

		var a = '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table';

		$( a ).block(
			{
				message: null,
				overlayCSS: {
					background: "#fff",
					opacity: .6
				}
			}
		);

		var temp_token = document.getElementById( 'paynt_access_token' );
		var card_token = document.getElementById( 'paynt_card_token' );

		var tempTokenObtained = temp_token.value !== '';
		var cardTokenObtained = card_token.value !== '';

		if ( ! tempTokenObtained && ! cardTokenObtained ) {
			var data = {
				'action': 'get_temp_token',
			};

			let options = {
				data: data,
				dataType: 'json',
				method: 'POST'
			};

			$.ajax( woo_choice_payment_params.ajax_url, options )
				.done(
					function (response, success, xhr) {
						tempTokenResponseHandler( response, success, xhr );
						var a = '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table';
						$( a ).unblock();
					}
				)
				.fail(
					function (xhr) {
						alert( xhr.responseText );
						var a = '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table';
						$( a ).unblock();
					}
				);

			return false;
		}

		return true;
	}

	function tempTokenResponseHandler(response, success, xhr)
	{
		var access_token_elem = $( "#paynt_access_token" );

		if (access_token_elem) {
			access_token_elem.val( response.data.access_token );
			tokenizeCard();
		}
	}

	function tokenizeCard()
	{
		var a = '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table';

		$( a ).block(
			{
				message: null,
				overlayCSS: {
					background: "#fff",
					opacity: .6
				}
			}
		);

		var temp_token  = document.getElementById( 'paynt_access_token' );
		var card_number = document.getElementById( 'woo-choice-payment_card_number' );
		var card_cvv    = document.getElementById( 'woo-choice-payment_card_cvv' );
		var expiration  = document.getElementById( 'woo-choice-payment_card_expiration' );

		var split = expiration.value.split( '/' );
		var month = split[0].replace( /^\s+|\s+$/g, '' );
		var year  = split[1].replace( /^\s+|\s+$/g, '' ).substr( 2,2 );

		var access_token = '';
		if (temp_token && temp_token.value) {
			access_token = temp_token.value;
		}

		var card_token        = document.getElementById( 'paynt_card_token' );
		var cardTokenObtained = card_token.value !== '';

		var data = {
			"DeviceGuid": woo_choice_payment_params.device_cc_guid,
			"Card": {
				"CardNumber": card_number.value.replace( /\D/g, '' ),
				"ExpirationDate": year + month
			}
		};

		let options = {
			data: JSON.stringify(data),
			dataType: 'json',
			method: 'POST',
			headers: {
				"Authorization": "Bearer " + access_token,
                "Content-Type": 'application/json'
			}
		};

		if ( ! cardTokenObtained ) {
			$.ajax( woo_choice_payment_params.tokenization_url, options )
				.done(
					function (response, success, xhr) {
						processCheckout( response );
						var a = '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table';
						$( a ).unblock();
					}
				)
				.fail(
					function (xhr) {
						alert( xhr.responseText );
						var a = '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table';
						$( a ).unblock();
					}
				);

			return false;
		}

		$( a ).unblock();

		return true;
	}

	function processCheckout(response)
	{
		var form = document.querySelector( 'form.checkout, form#order_review' );

		if ( ! response ) {
			return true;
		}

		if (response.card ) {

			var token  = document.getElementById( 'paynt_card_token' );
			var expire = document.createElement( 'input' );

			token.value = response.card.cardNumber;

			expire.type  = 'hidden';
			expire.id    = 'expiration-date';
			expire.name  = 'expiration-date';
			expire.value = response.card.expirationDate;

			form.appendChild( expire );

			$( form ).submit();

			setTimeout(
				function () {
					document.getElementById( 'paynt_access_token' ).value = '';
					document.getElementById( 'paynt_card_token' ).value   = '';
					var a = '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table';
					$( a ).unblock();
				},
				2000
			);

		}
	}

	$( document ).ready(
		function () {
			if (window.woo_choice_payment_params) {
				if ( ! woo_choice_payment_params.handler) {
					woo_choice_payment_params.handler = formHandler;
				}
			}

			$( 'form#order_review' )
			.off( 'submit', woo_choice_payment_params.handler )
			.on( 'submit', woo_choice_payment_params.handler );
			$( 'form.checkout' )
			.off(
				'checkout_place_order_choicepaynt',
				woo_choice_payment_params.handler
			)
			.on(
				'checkout_place_order_choicepaynt',
				woo_choice_payment_params.handler
			);
		}
	);

	// custom form validation
	// noinspection JSJQueryEfficiency
	$( 'body' ).on(
		'blur change keydown',
		'#woo-choice-payment_card_number',
		function (e) {
			var wrapper = $( this ).closest( '.form-row' );
			if ( ! /^\d*$/.test( $( this ).val() ) ) { // check if contains non-numbers
				wrapper.addClass( 'woocommerce-invalid' ); // error
				wrapper.removeClass( 'woocommerce-validated' );
			} else {
				wrapper.addClass( 'woocommerce-validated' ); // success
				wrapper.removeClass( 'woocommerce-invalid' );
			}

			if ( ! restrictLength( e, 16 )
				|| ! restrictNumeric( e )
			) {
				e.preventDefault ? e.preventDefault() : (e.returnValue = false);
			}

		}
	);

	// noinspection JSJQueryEfficiency
	$( 'body' ).on(
		'blur change keydown',
		'#woo-choice-payment_card_cvv',
		function (e) {
			if ( ! restrictLength( e, 4 )
				|| ! restrictNumeric( e )
			) {
				e.preventDefault ? e.preventDefault() : (e.returnValue = false);
			}
		}
	);

	// noinspection JSJQueryEfficiency
	$( 'body' ).on(
		'keydown',
		'#woo-choice-payment_card_expiration',
		function (e) {
			var target = (e.currentTarget ? e.currentTarget : e.srcElement);

			if ( ! restrictLength( e, 9 )
				|| ! restrictNumeric( e )
			) {
				e.preventDefault ? e.preventDefault() : (e.returnValue = false);
			}

			target.value = formatExpiration( target.value );
		}
	);

	// noinspection JSJQueryEfficiency
	$( 'body' ).on(
		'blur change',
		'#woo-choice-payment_card_expiration',
		function (e) {
			var target   = (e.currentTarget ? e.currentTarget : e.srcElement);
			var wrapper  = $( this ).closest( '.form-row' );
			target.value = formatExpiration( target.value, true );

			if ( ! validateExpiration( target.value )) {
				wrapper.addClass( 'woocommerce-invalid' ); // error
				wrapper.removeClass( 'woocommerce-validated' );
			} else {
				wrapper.addClass( 'woocommerce-validated' ); // success
				wrapper.removeClass( 'woocommerce-invalid' );
			}
		}
	);

	// noinspection JSJQueryEfficiency
	$( 'body' ).on(
		'keydown',
		'#woo-choice-payment_ach_routing',
		function (e) {
			var target = (e.currentTarget ? e.currentTarget : e.srcElement);

			if ( ! restrictLength( e, 9 )
				|| ! restrictNumeric( e )
			) {
				e.preventDefault ? e.preventDefault() : (e.returnValue = false);
			}
		}
	);

	// noinspection JSJQueryEfficiency
	$( 'body' ).on(
		'keydown',
		'#woo-choice-payment_ach_account',
		function (e) {
			var target = (e.currentTarget ? e.currentTarget : e.srcElement);

			if ( ! restrictLength( e, 20 )
				|| ! restrictNumeric( e )
			) {
				e.preventDefault ? e.preventDefault() : (e.returnValue = false);
			}
		}
	);

	function restrictLength(e, length)
	{
		var target = (e.currentTarget ? e.currentTarget : e.srcElement);
		var value  = target.value;
		// allow: backspace, delete, tab, escape and enter
		if (e.which === 46 || e.which === 8 || e.which === 9
			|| e.which === 27 || e.which === 13 || e.which === 110
			// allow: Ctrl+A
			|| (e.which === 65 && e.ctrlKey === true)
			// allow: home, end, left, right
			|| (e.which >= 35 && e.which <= 39)
		) {
			// allow keypress
			return true;
		}
		if (value.length >= length) {
			return false;
		}
		return true;
	}

	function restrictNumeric(e)
	{
		// allow: backspace, delete, tab, escape and enter
		if (e.which === 46 || e.which === 8 || e.which === 9
			|| e.which === 27 || e.which === 13 || e.which === 110
			// allow: Ctrl+A
			|| (e.which === 65 && e.ctrlKey === true)
			// allow: home, end, left, right
			|| (e.which >= 35 && e.which <= 39)
			// allow: weird Android/Chrome issue
			|| (e.which === 229)
		) {
			// allow keypress
			return true;
		}
		// ensure that it is a number and stop the keypress
		return ! ((e.shiftKey || (e.which < 48 || e.which > 57)) && (e.which < 96 || e.which > 105));
	}

	function formatExpiration(exp, final)
	{
		if (final === void 0) {
			final = false; }
		var pat    = /^\D*(\d{1,2})(\D+)?(\d{1,4})?/;
		var groups = exp.match( pat );
		var month;
		var del;
		var year;
		if ( ! groups) {
			return '';
		}
		month = groups[1] || '';
		del   = groups[2] || '';
		year  = groups[3] || '';
		if (year.length > 0) {
			del = ' / ';
		} else if (month.length === 2 || del.length > 0) {
			del = ' / ';
		} else if (month.length === 1 && (month !== '0' && month !== '1')) {
			del = ' / ';
		}
		if (month.length === 1 && del !== '') {
			month = '0' + month;
		}
		if (final && year.length === 2) {
			year = (new Date()).getFullYear().toString().slice( 0, 2 ) + year;
		}
		return month + del + year;
	}

	function validateExpiration(exp)
	{
		var m, y;
		if ( ! exp) {
			return false;
		}
		var split = exp.split( '/' );
		m         = split[0], y = split[1];
		if ( ! m || ! y) {
			return false;
		}
		m = m.replace( /^\s+|\s+$/g, '' );
		y = y.replace( /^\s+|\s+$/g, '' );
		if ( ! /^\d+$/.test( m )) {
			return false;
		}
		if ( ! /^\d+$/.test( y )) {
			return false;
		}
		if (y.length === 2) {
			y = (new Date()).getFullYear().toString().slice( 0, 2 ) + y;
		}
		var month = parseInt( m, 10 );
		var year  = parseInt( y, 10 );
		if ( ! (1 <= month && month <= 12)) {
			return false;
		}
		// creates date as 1 day past end of
		// expiration month since JS months
		// are 0 indexed
		return (new Date( year, month, 1 )) > (new Date());
	}

})( jQuery, window, document, window.woo_choice_payment_params );
