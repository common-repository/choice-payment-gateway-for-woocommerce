<fieldset>
	<?php if ( $this->description ) : ?>
		<div class="woo-choice-payment-content">
			<p class="woo-choice-payment-description">
				<?php echo esc_html( $this->description ); ?>
			</p>
		</div>
		<hr/>
	<?php endif; ?>

	<div id="woo-choice-sandbox-cc-prefill" class="woo-choice-sandbox-cc-prefill" style="display:none;">
		<p>Sandbox Mode</p>
		<p>DO NOT ENTER REAL ACCOUNT INFO!</p>
		<i>This message is not shown in production mode.</i>
		<button id="woo-choice-sandbox-cc-prefill-btn" class="button button-large">Prefill Test Card</button>
	</div>

	<div class="woo-choice-payment-content new-card-content <?php echo esc_attr( $newClass ); ?>"
		 style="<?php echo esc_attr( $styletag ); ?>">
		<div class="woo-choice-payment_new_card">
			<div class="woo-choice-payment_new_card_info">
				<div class="form-row form-row-wide no-bottom-margin hideable">
					<label for="woo-choice-payment_card_number">
						<?php _e( 'Credit Card number', 'choicepaynt_gateway' ); ?>
						<span class="required">*</span>
					</label>
					<div class="cc-number">
						<input id="woo-choice-payment_card_number" type="tel" autocomplete="off"
							   class="input-text card-number" placeholder="•••• •••• •••• ••••"/>
					</div>
				</div>
				<div class="clear"></div>
				<div class="form-row hideable no-bottom-margin">
					<div class="form-row-first half-row">
						<label for="woo-choice-payment_card_expiration">
							<?php _e( 'Expiration Date', 'choicepaynt_gateway' ); ?>
							<span class="required">*</span>
						</label>
						<input name="woo-choice-payment_card_expiration" id="woo-choice-payment_card_expiration"
							   type="tel" autocomplete="off" class="input-text expiry-date" placeholder="MM / YYYY"/>
					</div>
					<div class="form-row-last half-row">
						<label for="woo-choice-payment_card_cvv">
							<?php _e( 'Security code', 'choicepaynt_gateway' ); ?>
							<span class="required">*</span>
						</label>
						<div>
							<input type="tel" name="woo-choice-payment_card_cvv" id="woo-choice-payment_card_cvv"
								   maxlength="4" autocomplete="off"
								   class="input-text card-cvc" placeholder="CVV"/>
						</div>
						<span class="help woo-choice-payment_card_csc_description"></span>
					</div>
				</div>
				<div class="clear"></div>

				<?php if ( $this->allow_card_saving == 'yes' ) : ?>
					<div class="form-row form-row-wide no-top-margin no-top-padding no-bottom-margin">
						<p class="form-row form-row-wide woo-choice-payment-save-cards">
							<input type="checkbox" autocomplete="off" id="save_card" name="save_card" value="true"
								   style="display:inline"/>
							<label for="save_card" style="display: inline;">
								<?php _e( 'Save Credit Card for Future Use', 'choicepaynt_gateway' ); ?>
							</label>
						</p>
					</div>
				<?php endif; ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>

	<input type="hidden" name="paynt_access_token" id="paynt_access_token"/>
	<input type="hidden" name="paynt_card_token" id="paynt_card_token"/>

</fieldset>

<script>
	jQuery(function ($) {
		if (window.woo_choice_payment_params.sandbox_mode) {
			$('.woo-choice-sandbox-cc-prefill').attr('style', 'display:block;');

			$('#woo-choice-sandbox-cc-prefill-btn').click(function (e) {
				e.preventDefault();
				$('#woo-choice-payment_card_number').val('5486477674539426');
				$('#woo-choice-payment_card_expiration').val('07/2027');
				$('#woo-choice-payment_card_cvv').val('998')
			});
		}
	})
</script>
