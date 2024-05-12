<?php
	/**
	 * @var $loop
	 * @var $variation_data
	 * @var $variation
	 * @var $post_id
	 */

	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\ProductVariationSettingsController;
?>
<label class="tips" data-tip="<?php esc_attr_e( 'Enable this option if you want to manage quantity input individually for this variation', 'woo-advanced-qty'); ?>">
	<?php esc_html_e( 'WooCommerce Advanced Quantity', 'woo-advanced-qty'); ?>
	<input type="checkbox" class="checkbox individually_variation_control" name="individually_variation_control[<?php echo esc_attr( $loop ); ?>]" <?php checked( ProductVariationSettingsController::getSetting($variation->ID, 'individually_variation_control'), true ); ?> />
</label>
<script>
	jQuery( function( $ ) {
		var woo_advanced_qty_product_variations_actions = {
			init: function() {
				$( '#variable_product_options' )
					.on( 'change', 'input.individually_variation_control', this.individually_variation_control);

				$( 'input.individually_variation_control' ).change();
				$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', this.variations_loaded );
			},

			individually_variation_control: function() {
				$(this).closest('.woocommerce_variation').find('.show_if_individually_variation_control').hide();

				if($(this).is(':checked')) {
					$(this).closest('.woocommerce_variation').find('.show_if_individually_variation_control').show();
				}
			},

			variations_loaded: function( event, needsUpdate ) {
				if ( ! needsUpdate ) {
					$('input.individually_variation_control').change();
				}
			}
		}

		woo_advanced_qty_product_variations_actions.init();
	});
</script>