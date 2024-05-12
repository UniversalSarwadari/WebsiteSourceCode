<?php

defined( 'ABSPATH' ) || exit;

?>

<div id="variation_filters" class="woocommerce_variation_filters">
<h3>
	<strong>Filters:</strong>
	<?php

	foreach ( $product->get_attributes( 'edit' ) as $attribute ) {
		if ( ! $attribute->get_variation() ) {
			continue;
		}
		?>
		<select class="wcbvp-attribute-filters" data-attribute_name="attribute_<?php echo esc_attr( sanitize_title( $attribute->get_name() ) ); ?>">
			<option value="">
				<?php
				/* translators: %s: attribute label */
				printf( esc_html__( 'Any %s&hellip;', 'woocommerce' ), wc_attribute_label( $attribute->get_name() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</option>
			<?php if ( $attribute->is_taxonomy() ) : ?>
				<?php foreach ( $attribute->get_terms() as $option ) : ?>
					<option value="<?php echo esc_attr( $option->slug ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $option->name, $option, $attribute->get_name(), $product ) ); ?></option>
				<?php endforeach; ?>
			<?php else : ?>
				<?php foreach ( $attribute->get_options() as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute->get_name(), $product ) ); ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<?php
	}
	?>
	<a class="button additional-filters"><?php esc_html_e( 'Advanced', 'woocommerce-bulk-variations' ); ?></a>
	<a class="button reset_filters hidden"><?php esc_html_e( 'Reset', 'woocommerce-bulk-variations' ); ?></a>
</h3>
<?php
	require_once 'html-meta-boxes-additional-filters.php';
?>
</div>
