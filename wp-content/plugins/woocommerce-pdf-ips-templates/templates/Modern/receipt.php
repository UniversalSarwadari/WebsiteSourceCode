<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>

<table class="head container">
	<tr class="underline">
		<td class="header">
			<div class="header-stretcher">
			<?php
			if ( $this->has_header_logo() ) {
				do_action( 'wpo_wcpdf_before_shop_logo', $this->get_type(), $this->order );
				$this->header_logo();
				do_action( 'wpo_wcpdf_after_shop_logo', $this->get_type(), $this->order );
			} else {
				do_action( 'wpo_wcpdf_before_shop_name', $this->get_type(), $this->order );
				$this->shop_name();
				do_action( 'wpo_wcpdf_after_shop_name', $this->get_type(), $this->order );
			}
			?>
			</div>
		</td>
		<td class="shop-info">
			<?php do_action( 'wpo_wcpdf_before_shop_address', $this->get_type(), $this->order ); ?>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
			<?php do_action( 'wpo_wcpdf_after_shop_address', $this->get_type(), $this->order ); ?>
		</td>
	</tr>
</table>

<table class="order-data-addresses">
	<tr>
		<td class="address billing-address">
			<h3>&nbsp;<!-- empty spacer to keep adjecent cell content aligned --></h3>
			<?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
			<?php $this->billing_address(); ?>
			<?php do_action( 'wpo_wcpdf_after_billing_address', $this->get_type(), $this->order ); ?>
			<?php if ( isset( $this->settings['display_email'] ) ) : ?>
				<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php endif; ?>
			<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
				<div class="billing-phone"><?php $this->billing_phone(); ?></div>
			<?php endif; ?>
		</td>
		<td class="address shipping-address">
			<?php if ( $this->show_shipping_address() ) : ?>
				<h3><?php _e( 'Ship To:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
				<?php do_action( 'wpo_wcpdf_before_shipping_address', $this->get_type(), $this->order ); ?>
				<?php $this->shipping_address(); ?>
				<?php do_action( 'wpo_wcpdf_after_shipping_address', $this->get_type(), $this->order ); ?>
				<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
					<div class="shipping-phone"><?php $this->shipping_phone(); ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</td>
		<td class="order-data">
			<?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>
			<h3 class="document-type-label"><?php $this->title(); ?></h3>
			<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
				<?php if ( isset( $this->settings['display_number'] ) ) : ?>
					<tr class="receipt-number">
						<th><?php $this->number_title(); ?></th>
						<td><?php $this->number( $this->get_type() ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( isset( $this->settings['display_date'] ) ) : ?>
					<tr class="receipt-date">
						<th><?php $this->date_title(); ?></th>
						<td><?php $this->date( $this->get_type() ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( isset( $this->settings['display_invoice_number'] ) && ! empty( $this->get_number( 'invoice' ) ) ) : ?>
				<tr class="invoice-number">
					<th><?php _e( 'Invoice Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->number( 'invoice' ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( is_callable( array( $this, 'payment_date' ) ) ) : ?>
					<tr class="payment-date">
						<th><?php _e( 'Payment Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td><?php $this->payment_date(); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( is_callable( array( $this, 'payment_method' ) ) ) : ?>
					<tr class="payment-method">
						<th><?php _e( 'Payment Method:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td><?php $this->payment_method(); ?></td>
					</tr>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
			</table>
		</td>
	</tr>
</table><!-- head container -->

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
	</table><!-- order-details -->
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>

<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>
<?php if ( $this->get_shipping_notes() ) : ?>
	<div class="notes customer-notes">
		<h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
		<?php $this->shipping_notes(); ?>
	</div>
<?php endif; ?>
<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>

<div class="cut-off"></div>

<div class="foot">
	<table class="footer container">
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>
				<?php if ( $totals = wpo_wcpdf_templates_get_totals( $this ) ) : ?>
					<table class="totals">
						<tfoot>
							<?php foreach ( $totals as $total_key => $total_data ) : ?>
								<tr class="<?php echo $total_data['class']; ?>">
									<th class="description"><span><?php echo $total_data['label']; ?></span></th>
									<td class="price"><span class="totals-price"><?php echo $total_data['value']; ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tfoot>
					</table>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="bluebox">
				<?php do_action( 'wpo_wcpdf_before_footer_bar', $this->get_type(), $this->order ); ?>
				<?php if ( ! empty( $this->get_shipping_method() ) ) : ?>
					<div class="shipping-method"><span class="shipping-method-label"><?php _e( 'Shipping method', 'woocommerce-pdf-invoices-packing-slips' ); ?>:</span><span class="shipping-method-name"><?php $this->shipping_method(); ?></span></div>
				<?php endif; ?>	
				<?php if ( ! empty( $this->get_payment_method() ) ) : ?>
					<div class="payment-method"><span class="payment-method-label"><?php _e( 'Payment method', 'woocommerce-pdf-invoices-packing-slips' ); ?>:</span><span class="payment-method-name"><?php $this->payment_method(); ?></span></div>
				<?php endif; ?>	
				<?php do_action( 'wpo_wcpdf_after_footer_bar', $this->get_type(), $this->order ); ?>
			</td>
		</tr>
		<tr>
			<td class="footer-column-1">
				<div class="wrapper"><?php $this->extra_1(); ?></div>
			</td>
			<td class="footer-column-2">
				<div class="wrapper"><?php $this->extra_2(); ?></div>
			</td>
			<td class="footer-column-3">
				<div class="wrapper"><?php $this->extra_3(); ?></div>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="footer-wide-row">
				<!-- hook available: wpo_wcpdf_before_footer -->
				<?php $this->footer(); ?>
				<!-- hook available: wpo_wcpdf_after_footer -->
			</td>
		</tr>
	</table>
</div><!-- #letter-footer -->

<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>