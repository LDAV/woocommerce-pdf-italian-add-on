<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>
<table class="head container">
	<tr class="underline">
		<td class="shop-info">
			<div class="shop-address">
				<?php $this->shop_address(); ?>
			</div>
		</td>
		<td class="shop-info">
			<div class="shop-contact">
				<?php $this->extra_1(); ?>
			</div>
		</td>
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
	</tr>
</table>

<h1 class="document-type-label">
<?php if( $this->has_header_logo() ) echo $this->get_title(); ?>
</h1>

<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

<table class="addresses">
	<tr>
		<td class="address billing-address">
			<!-- <h3><?php _e( 'Billing Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3> -->
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
	</tr>
</table>

<table class="order-data">
	<tr>
		<td class="numbers">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
				<?php if ( isset($this->settings['display_number']) ) { ?>
				<tr class="invoice-number">
					<th><?php _e( 'Receipt Number:', 'woocommerce-pdf-italian-add-on' ); ?></th>
					<td><?php $this->receipt_number(); ?></td>
				</tr>
				<?php } ?>
				<tr class="order-number">
					<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
			</table>
		</td>
		<td class="dates">
			<table>
				<?php if ( isset($this->settings['display_date']) ) { ?>
				<tr class="invoice-date">
					<th><?php _e( 'Receipt Date:', 'woocommerce-pdf-italian-add-on' ); ?></th>
					<td><?php $this->receipt_date(); ?></td>
				</tr>
				<?php } ?>
				<tr class="order-date">
					<th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

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
</table>

<table class="notes-totals">
	<tr>
		<td class="notes">
			<?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>
			<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>
			<div class="customer-notes">
				<?php if ( $this->get_shipping_notes() ) : ?>
					<h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
					<?php $this->shipping_notes(); ?>
				<?php endif; ?>
			</div>
			<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>
		</td>
		<td class="totals">
			<table class="totals-table">
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
			</table>
		</td>
	</tr>
</table>

<?php if ( $this->get_footer() ): ?>
<div id="footer">
	<?php $this->footer(); ?>
</div>
<?php endif; ?>
<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>