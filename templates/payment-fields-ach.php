<fieldset>
	<?php if ( $this->description ) : ?>
		<div class="woo-choice-payment-ach-content">
			<p class="woo-choice-payment-ach-description">
				<?php echo esc_html( $this->description ); ?>
			</p>
		</div>
		<hr/>
	<?php endif; ?>

	<div id="woo-choice-sandbox-ach-prefill" class="woo-choice-sandbox-ach-prefill" style="display:none;">
		<p>Sandbox Mode</p>
		<p>DO NOT ENTER REAL ACCOUNT INFO!</p>
		<i>This message is not shown in production mode.</i>
		<button id="woo-choice-sandbox-ach-prefill-btn" class="button button-large">Prefill Test Account</button>
	</div>

	<div class="woo-choice-payment-content new-ach-content">
		<div class="woo-choice-payment_new_ach">
			<div class="woo-choice-payment_new_ach_info">

				<div class="form-row form-row-wide no-bottom-margin hideable">
					<label for="woo-choice-payment_ach_routing">
						<?php _e( 'Routing Number', 'choicepaynt_gateway' ); ?>
						<span class="required">*</span>
					</label>
					<input name="woo-choice-payment_ach_routing" id="woo-choice-payment_ach_routing" type="tel"
						   maxlength="9" autocomplete="off" class="input-text ach-routing" placeholder="•••••••••"/>
				</div>
				<div class="clear"></div>
				<div class="form-row form-row-wide no-bottom-margin hideable">
					<label for="woo-choice-payment_ach_account">
						<?php _e( 'Account Number', 'choicepaynt_gateway' ); ?>
						<span class="required">*</span>
					</label>
					<div>
						<input type="tel" name="woo-choice-payment_ach_account" id="woo-choice-payment_ach_account"
							   maxlength="20" autocomplete="off"
							   class="input-text ach-account" placeholder=""/>
					</div>
					<span class="help woo-choice-payment_ach_description"></span>
				</div>

			</div>
		</div>
	</div>

</fieldset>

<script>
	jQuery(function ($) {
		if (window.woo_choice_payment_params.sandbox_mode) {
			$('.woo-choice-sandbox-ach-prefill').attr('style', 'display:block;');

			$('#woo-choice-sandbox-ach-prefill-btn').click(function (e) {
				e.preventDefault();
				$('#woo-choice-payment_ach_routing').val('211274450');
				$('#woo-choice-payment_ach_account').val('441142020');
			});
		}
	})
</script>
