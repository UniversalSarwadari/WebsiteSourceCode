<?php
/**
 * Admin View: Notice - Update
 *
 * @package WooCommerce\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$update_url = wp_nonce_url(
	add_query_arg( 'do_update_woocommerce_bulk_variations', 'true', admin_url( 'admin.php?page=wc-settings&tab=products&section=bulk-variations' ) ),
	'wcbv_db_update',
	'wcbv_db_update_nonce'
);

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p>
		<strong><?php esc_html_e( 'WooCommerce Bulk Variations database update required', 'woocommerce-bulk-variations' ); ?></strong>
	</p>
	<p>
		<?php
			esc_html_e( 'WooCommerce Bulk Variations has been updated! To keep things running smoothly, we have to update your database to the newest version. We recommend backing up your site first.', 'woocommerce-bulk-variations' );
		?>
	</p>
	<p class="submit">
		<a href="<?php echo esc_url( $update_url ); ?>" class="wc-update-now button-primary">
			<?php esc_html_e( 'Update WooCommerce Bulk Variations Database', 'woocommerce-bulk-variations' ); ?>
		</a>
	</p>
</div>
