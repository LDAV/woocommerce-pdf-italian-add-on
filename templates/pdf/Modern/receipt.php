<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>
<table class="head container">
	<tr class="underline">
		<td class="header">
			<div class="header-stretcher">
			<?php
			if ($this->has_header_logo()) {
				$this->header_logo();
			} else {
				$this->shop_name();
			}
			?>
			</div>
		</td>
		<td class="shop-info">
			<div class="shop-address"><?php $this->shop_address(); ?></div>
		</td>
	</tr>
</table>

<table class="order-data-addresses">
	<tr>
		<td class="address billing-address">
			<h3>&nbsp;<!-- empty spacer to keep adjecent cell content aligned --></h3>
			<?php $this->billing_address(); ?>
			<?php if ( isset($this->settings['display_email']) ) { ?>
			<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php } ?>
			<?php if ( isset($this->settings['display_phone']) ) { ?>
			<div class="billing-phone"><?php $this->billing_phone(); ?></div>
			<?php } ?>
		</td>
		<td class="address shipping-address">
			<?php if ( isset($this->settings['display_shipping_address']) && $this->ships_to_different_address()) { ?>
			<h3><?php _e( 'Ship To:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
			<?php $this->shipping_address(); ?>
			<?php } ?>
		</td>
		<td class="order-data">
			<h3 class="document-type-label">
			<?php echo $this->get_title(); ?>
			</h3>
			<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
				<?php if ( isset($this->settings['display_number']) ) { ?>
				<tr class="invoice-number">
					<th><?php _e( 'Receipt Number:', 'woocommerce-pdf-italian-add-on' ); ?></th>
					<td><?php $this->receipt_number(); ?></td>
				</tr>
				<?php } ?>
				<?php if ( isset($this->settings['display_date']) ) { ?>
				<tr class="invoice-date">
					<th><?php _e( 'Receipt Date:', 'woocommerce-pdf-italian-add-on' ); ?></th>
					<td><?php $this->receipt_date(); ?></td>
				</tr>
				<?php } ?>
				<tr class="order-number">
					<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
				<?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
			</table>
		</td>
	</tr>
</table><!-- head container -->

<?php do_action( 'wpo_wcpdf_before_order_details', $this->get_type(), $this->order ); ?>

<table class="order-details">
	<thead>
		<tr>
			<?php 
			foreach ( wpo_wcpdf_templates_get_table_headers( $this ) as $column_key => $header_data ) {
				printf('<th class="%s"><span>%s</span></th>', $header_data['class'], $header_data['title']);
			}
			?>
		</tr>
	</thead>
	<tbody>
		<?php
		$tbody = wpo_wcpdf_templates_get_table_body( $this );
		if( sizeof( $tbody ) > 0 ) {
			foreach( $tbody as $item_id => $item_columns ) {
				$row_class = apply_filters( 'wpo_wcpdf_item_row_class', $item_id, $this->get_type(), $this->order, $item_id );
				printf('<tr class="%s">', $row_class);
				foreach ($item_columns as $column_key => $column_data) {
					printf('<td class="%s"><span>%s</span></td>', $column_data['class'], $column_data['data']);
				}
				echo '</tr>';
			}
		}
		?>
	</tbody>
</table><!-- order-details -->

<?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>

<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>

<?php if ( $this->get_shipping_notes() ) : ?>
<div class="notes customer-notes">
	<h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
	<?php $this->shipping_notes(); ?>
</div>
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>

<div class="foot">
	<table class="footer container">
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>
				<table class="totals">
					<tfoot>
						<?php
						$totals = wpo_wcpdf_templates_get_totals( $this );
						if( sizeof( $totals ) > 0 ) {
							foreach( $totals as $total_key => $total_data ) {
								?>
								<tr class="<?php echo $total_data['class']; ?>">
									<th class="description"><span><?php echo $total_data['label']; ?></span></th>
									<td class="price"><span class="totals-price"><?php echo $total_data['value']; ?></span></td>
								</tr>
								<?php
							}
						}
						?>
					</tfoot>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="bluebox">
				<span class="shipping-method-label"><?php _e( 'Shipping method', 'woocommerce-pdf-invoices-packing-slips' ); ?>:</span><span class="shipping-method"><?php $this->shipping_method(); ?></span><br/>
				<span class="payment-method-label"><?php _e( 'Payment method', 'woocommerce-pdf-invoices-packing-slips' ); ?>:</span><span class="payment-method"><?php $this->payment_method(); ?></span>
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
				<?php $this->footer(); ?>
			</td>
		</tr>
	</table>
</div><!-- #letter-footer -->
<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>