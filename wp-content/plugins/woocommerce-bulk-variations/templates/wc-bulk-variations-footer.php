<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

?>

<div id="wcbvp_wrapper_<?php esc_attr( $grid_id ); ?>" class="wcbvp-total-wrapper">
	<div class="wcbvp-variation-pool"></div>
	<div class="wcbvp-total-left">
		<p>
			<span class="wcbvp_total_label"><?php esc_html_e( 'Items', 'woocommerce-bulk-variations' ); ?></span>:
			<span class="wcbvp_total_items">0</span>
		</p>
		<p>
			<span class="wcbvp_total_label"><?php esc_html_e( 'Total', 'woocommerce-bulk-variations' ); ?></span>:
			<span class="wcbvp_total_price"><?php echo $price; ?></span>
		</p>
	</div>
	<div class="wcbvp-total-right">
		<button disabled class="single_add_to_cart_button button alt disabled wc-variation-selection-needed">
			<?php echo esc_html( $add_to_cart_text ); ?>
		</button>
	</div>
</div>

<?php

// phpcs:enable