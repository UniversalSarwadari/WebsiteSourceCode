<?php

use Barn2\Plugin\WC_Bulk_Variations\Util\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields            = Util::get_product_fields_for_filters();
$compare_operators = Util::get_compare_operators_for_filters();

?>

<div class="wcbvp-filter-item">
	<button type="button" class="wcbvp-remove-filter-item wcbvp-button-icon button btn-secondary"></button>
	<select class="filter-meta-key" name="key[]">
		<?php
		foreach ( $fields as $key => $field ) {
			$placeholder = isset( $field['placeholder'] ) ? 'data-placeholder=' . $field['placeholder'] : '';
			?>
			<option value="<?php echo esc_attr( $key ); ?>" data-type="<?php echo esc_attr( $field['type'] ); ?>" <?php echo esc_attr( $placeholder ); ?>>
				<?php echo esc_html( $field['label'] ); ?>
			</option>
			<?php
		}
		?>
	</select>
	<select class="filter-compare" name="compare[]" disabled>
		<?php
		foreach ( $compare_operators as $compare_operator ) {
			$values = 1;

			if ( isset( $compare_operator['values'] ) ) {
				$values = $compare_operator['values'];
			}

			?>
			<option value="<?php echo esc_attr( $compare_operator['value'] ); ?>" data-type="<?php echo esc_attr( $compare_operator['type'] ); ?>" data-values="<?php echo esc_attr( $values ); ?>" hidden>
				<?php echo esc_html( $compare_operator['label'] ); ?>
			</option>
			<?php
		}
		?>
	</select>
	<input type="number" class="wcbvp-filter-value" name="value[0][]" placeholder="$0.00">
	<span class="wcbvp-filter-second-value">
		<?php esc_html_e( 'and', 'woocommerce-bulk-variations' ); ?>
	</span>
	<input type="number" class="wcbvp-filter-second-value" name="value[1][]" placeholder="$0.00">
	<select class="stock-status-options" multiple="multiple">
		<?php
		foreach ( wc_get_product_stock_status_options() as $option => $label ) {
			?>
			<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php
		}
		?>
	<select>

	<?php
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );
		wc_enqueue_js(
			"
			if ( jQuery().selectWoo ) {
				var wcbvp_stock_status = function() {
					jQuery( '.stock-status-options' ).selectWoo( {
						placeholder: '" . esc_js( __( 'Select a stock status', 'woocommerce-bulk-variations' ) ) . "',
						minimumResultsForSearch: 1,
						width: '100%',
						allowClear: true,
						language: {
							noResults: function() {
								return '" . esc_js( _x( 'No matches found', 'enhanced select', 'woocommerce-bulk-variations' ) ) . "';
							}
						}
					} );
				};
				wcbvp_stock_status();
			}
		"
		);
		?>
</div>
