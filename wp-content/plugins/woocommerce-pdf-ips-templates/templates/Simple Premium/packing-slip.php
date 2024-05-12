<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>

<table class="head container">
	<tr>
		<td class="header">
		<?php
		if ( $this->has_header_logo() ) {
			do_action( 'wpo_wcpdf_before_shop_logo', $this->get_type(), $this->order );
			$this->header_logo();
			do_action( 'wpo_wcpdf_after_shop_logo', $this->get_type(), $this->order );
		} else {
			$this->title();
		}
		?>
		</td>
		<td class="shop-info">
			<?php do_action( 'wpo_wcpdf_before_shop_name', $this->get_type(), $this->order ); ?>
			<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<?php do_action( 'wpo_wcpdf_after_shop_name', $this->get_type(), $this->order ); ?>
			<?php do_action( 'wpo_wcpdf_before_shop_address', $this->get_type(), $this->order ); ?>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
			<?php do_action( 'wpo_wcpdf_after_shop_address', $this->get_type(), $this->order ); ?>
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>

<h1 class="document-type-label">
	<?php if ( $this->has_header_logo() ) $this->title(); ?>
</h1>

<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

<table class="order-data-addresses">
	<tr>
		<td class="address shipping-address">
			<!-- <h3><?php _e( 'Shipping Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3> -->
			<?php do_action( 'wpo_wcpdf_before_shipping_address', $this->get_type(), $this->order ); ?>
			<?php $this->shipping_address(); ?>
			<?php do_action( 'wpo_wcpdf_after_shipping_address', $this->get_type(), $this->order ); ?>
			<?php if ( isset( $this->settings['display_email'] ) ) : ?>
				<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php endif; ?>
			<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
				<div class="shipping-phone"><?php $this->shipping_phone( ! $this->show_billing_address() ); ?></div>
			<?php endif; ?>
		</td>
		<td class="address billing-address">
			<?php if ( $this->show_billing_address() ) : ?>
				<h3><?php _e( 'Billing Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
				<?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
				<?php $this->billing_address(); ?>
				<?php do_action( 'wpo_wcpdf_after_billing_address', $this->get_type(), $this->order ); ?>
				<?php if ( isset( $this->settings['display_phone'] ) && ! empty( $this->get_billing_phone() ) ) : ?>
					<div class="billing-phone"><?php $this->billing_phone(); ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</td>
		<td class="order-data">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
				<tr class="order-number">
					<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
				<?php if ( ! empty( $this->get_shipping_method() ) ) : ?>
					<tr class="shipping-method">
						<th><?php _e( 'Shipping Method:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td><?php $this->shipping_method(); ?></td>
					</tr>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
			</table>			
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_order_details', $this->get_type(), $this->order ); ?>

<?php if ( $headers = wpo_wcpdf_templates_get_table_headers( $this ) ) : ?>
	<table class="order-details">
		<thead>
			<tr>
				<?php 
				foreach ( $headers as $column_key => $header_data ) {
					printf( '<th class="%s"><span>%s</span></th>', $header_data['class'], $header_data['title'] );
				}
				?>
			</tr>
		</thead>
		<?php if ( $body = wpo_wcpdf_templates_get_table_body( $this ) ) : ?>
			<tbody>
				<?php
				foreach ( $body as $item_id => $item_columns ) {
					do_action( 'wpo_wcpdf_templates_before_order_details_row', $this, $item_id, $item_columns );
					$row_class = apply_filters( 'wpo_wcpdf_item_row_class', 'item-'.$item_id, $this->get_type(), $this->order, $item_id );
					printf( '<tr class="%s">', $row_class );
					foreach ( $item_columns as $column_key => $column_data ) {
						printf( '<td class="%s"><span>%s</span></td>', $column_data['class'], $column_data['data'] );
					}
					echo '</tr>';
					do_action( 'wpo_wcpdf_templates_after_order_details_row', $this, $item_id, $item_columns );
				}
				?>
			</tbody>
		<?php endif; ?>
	</table>
<?php endif; ?>

<div class="bottom-spacer"></div>

<div class="notes-totals-container">
	<div class="totals">
		<?php if ( $totals = wpo_wcpdf_templates_get_totals( $this ) ) : ?>
			<table>
				<tbody>
					<?php foreach ( $totals as $total_key => $total_data ) : ?>
						<tr class="<?php echo $total_data['class']; ?>">
							<th class="description"><span><?php echo $total_data['label']; ?></span></th>
							<td class="price"><span class="totals-price"><?php echo $total_data['value']; ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<div class="notes">
		<div class="no-borders wrapper">
			<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>
			<div class="customer-notes">
				<?php if ( $this->get_shipping_notes() ) : ?>
					<h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
					<?php $this->shipping_notes(); ?>
				<?php endif; ?>
			</div>				
			<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>
		</div>
	</div>
</div>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>

<?php if ( $this->get_footer() ) : ?>
	<div id="footer">
		<!-- hook available: wpo_wcpdf_before_footer -->
		<?php $this->footer(); ?>
		<!-- hook available: wpo_wcpdf_after_footer -->
	</div><!-- #letter-footer -->
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>