<?php
namespace WPO\IPS\Documents;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( '\\WPO\\IPS\\Documents\\ItReceipt' ) ) :

class ItReceipt extends OrderDocumentMethods {

	/**
	 * Init/load the order object.
	 *
	 * @param int|object|null $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		// set properties
		$this->type  = 'receipt';
		$this->title = __( 'Receipt', WCPDF_IT_DOMAIN );
		$this->icon  = WCPDF_IT()::$plugin_url . 'images/receipt.svg';

		// call parent constructor
		parent::__construct( $order );

		$this->output_formats = apply_filters(
			'wpo_wcpdf_document_output_formats',
			$this->output_formats,
			$this
		);
	}

	/**
	 * Get the document title
	 *
	 * @return string
	 */
	public function get_title(): string {
		// override/not using $this->title to allow for language switching!
		$title = __( 'Receipt', WCPDF_IT_DOMAIN );
		return apply_filters( 'wpo_wcpdf_document_title', $title, $this );
	}

	/**
	 * Get the document number title
	 *
	 * @return string
	 */
	public function get_number_title(): string {
		// override to allow for language switching!
		$title = __( 'Receipt Number:', WCPDF_IT_DOMAIN );
		return apply_filters( 'wpo_wcpdf_document_number_title', $title, $this );
	}

	/**
	 * Get the document date title
	 *
	 * @return string
	 */
	public function get_date_title(): string {
		// override to allow for language switching!
		$title = __( 'Receipt Date:', WCPDF_IT_DOMAIN );
		return apply_filters( 'wpo_wcpdf_document_date_title', $title, $this );
	}

	/**
	 * Determine if historical settings should be used.
	 *
	 * @return bool
	 */
	public function use_historical_settings(): bool {
		$document_settings       = get_option( 'wpo_wcpdf_documents_settings_' . $this->get_type() );
		$use_historical_settings = true;

		// this setting is inverted on the frontend so that it needs to be actively/purposely enabled to be used
		if ( ! empty( $document_settings ) && isset( $document_settings['use_latest_settings'] ) ) {
			$use_historical_settings = false;
		}

		return apply_filters( 'wpo_wcpdf_document_use_historical_settings', $use_historical_settings, $this );
	}

	/**
	 * Determine if the document settings should be stored.
	 *
	 * @return bool
	 */
	public function storing_settings_enabled(): bool {
		return apply_filters( 'wpo_wcpdf_document_store_settings', true, $this );
	}

	/**
	 * Initializes the document.
	 *
	 * @return void
	 */
	public function init(): void {
		// save settings
		$this->save_settings();
		$this->initiate_date();
		$this->initiate_number();

		do_action( 'wpo_wcpdf_init_document', $this );
	}

	/**
	 * Check if the document exists.
	 *
	 * @return bool
	 */
	public function exists(): bool {
		return ! empty( $this->data['number'] );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings(): void {
		// Register settings.
		$page = $option_group = $option_name = 'wpo_wcpdf_documents_settings_' . $this->get_type();

		$settings_fields = array(
			array(
				'type'     => 'section',
				'id'       => $this->get_type(),
				'title'    => '',
				'callback' => 'section',
			),
			array(
				'type'     => 'setting',
				'id'       => 'enabled',
				'title'    => __( 'Enable', WCPDF_IT_DOMAIN ),
				'callback' => 'checkbox',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'enabled',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'attach_to_email_ids',
				'title'    => __( 'Attach to:', WCPDF_IT_DOMAIN ),
				'callback' => 'multiple_checkboxes',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name'     => $option_name,
					'id'              => 'attach_to_email_ids',
					'fields_callback' => array( $this, 'get_wc_emails' ),
					'description'     => ! \WPO_WCPDF()->file_system->is_writable( \WPO_WCPDF()->main->get_tmp_path( 'attachments' ) )
						? '<span class="wpo-warning">' . sprintf(
							/* translators: 1. directory path */
							__( 'It looks like the temp folder (%s) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email documents.', WCPDF_IT_DOMAIN ),
							'<code>' . \WPO_WCPDF()->main->get_tmp_path( 'attachments' ) . '</code>'
						  ) . '</span>'
						: '',
				  )
			),
			array(
				'type'     => 'setting',
				'id'       => 'disable_for_statuses',
				'title'    => __( 'Disable for:', WCPDF_IT_DOMAIN ),
				'callback' => 'select',
				'section'  => $this->get_type(),
				'args'     => array(
					  'option_name'      => $option_name,
					  'id'               => 'disable_for_statuses',
					  'options_callback' => 'wc_get_order_statuses',
					  'multiple'         => true,
					  'enhanced_select'  => true,
					  'placeholder'      => __( 'Select one or more statuses', WCPDF_IT_DOMAIN ),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_shipping_address',
				'title'    => __( 'Display shipping address', WCPDF_IT_DOMAIN ),
				'callback' => 'select',
				'section'  => $this->get_type(),
				'args'     => array(
					  'option_name' => $option_name,
					  'id'          => 'display_shipping_address',
					  'options'     => array(
						''               => __( 'No' , WCPDF_IT_DOMAIN ),
						'when_different' => __( 'Only when different from billing address' , WCPDF_IT_DOMAIN ),
						'always'         => __( 'Always' , WCPDF_IT_DOMAIN ),
					  ),
				  )
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_email',
				'title'    => __( 'Display email address', WCPDF_IT_DOMAIN ),
				'callback' => 'checkbox',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_email',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_phone',
				'title'    => __( 'Display phone number', WCPDF_IT_DOMAIN ),
				'callback' => 'checkbox',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_phone',
				)
			),

			array(
				'type'     => 'setting',
				'id'       => 'display_date',
				'title'    => __( 'Display receipt date', WCPDF_IT_DOMAIN ),
				'callback' => 'checkbox',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_date',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_number',
				'title'    => __( 'Display receipt number', WCPDF_IT_DOMAIN ),
				'callback' => 'checkbox',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_number',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'next_receipt_number',
				'title'    => __( 'Next receipt number (without prefix/suffix etc.)', WCPDF_IT_DOMAIN ),
				'callback' => 'next_number_edit',
				'section'  => $this->get_type(),
				'args'     => array(
					'store_callback' => array( $this, 'get_sequential_number_store' ),
					'size'           => '10',
					'description'    => __( 'This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it lower than the current/highest number, this could create duplicate numbers!', WCPDF_IT_DOMAIN ),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'number_format',
				'title'    => __( 'Number format', WCPDF_IT_DOMAIN ),
				'callback' => 'multiple_text_input',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'number_format',
					'fields'      => array(
						'prefix'  => array(
							'label'       => __( 'Prefix' , WCPDF_IT_DOMAIN ),
							'size'        => 20,
							'description' => __( 'If set, this value will be used as number prefix.' , WCPDF_IT_DOMAIN ) . ' ' . sprintf(
								/* translators: 1. document slug, 2-3 placeholders */
								__( 'You can use the %1$s year and/or month with the %2$s or %3$s placeholders respectively.', WCPDF_IT_DOMAIN ),
								$this->get_type(), '<strong>[' . $this->get_type() . '_year]</strong>', '<strong>[' . $this->get_type() . '_month]</strong>'
							) . ' ' . __( 'Check the Docs article below to see all the available placeholders for prefix/suffix.', WCPDF_IT_DOMAIN ),
						),
						'suffix'  => array(
							'label'       => __( 'Suffix' , WCPDF_IT_DOMAIN ),
							'size'        => 20,
							'description' => __( 'If set, this value will be used as number suffix.' , WCPDF_IT_DOMAIN ) . ' ' . sprintf(
								/* translators: 1. document slug, 2-3 placeholders */
								__( 'You can use the %1$s year and/or month with the %2$s or %3$s placeholders respectively.', WCPDF_IT_DOMAIN ),
								$this->get_type(), '<strong>[' . $this->get_type() . '_year]</strong>', '<strong>[' . $this->get_type() . '_month]</strong>'
							) . ' ' . __( 'Check the Docs article below to see all the available placeholders for prefix/suffix.', WCPDF_IT_DOMAIN ),
						),
						'padding' => array(
							'label'       => __( 'Padding' , WCPDF_IT_DOMAIN ),
							'size'        => 20,
							'type'        => 'number',
							'description' => sprintf(
								/* translators: 1. example number, 2. document type, 3. example number without padding, 4. example number with padding */
								__( 'Enter the number of digits you want to use as padding. For instance, enter %1$s to display the %2$s number %3$s as %4$s, filling it with zeros until the number set as padding is reached.' , WCPDF_IT_DOMAIN ),
								'<code>6</code>',
								$this->get_type(),
								'<code>123</code>',
								'<code>000123</code>'
							),
						),
					),
					'description' => sprintf(
						/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
						__( 'For more information about setting up the number format and see the available placeholders for the prefix and suffix, check this article: %1$sNumber format explained%2$s', WCPDF_IT_DOMAIN ),
						'<a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/number-format-explained/" target="_blank">',
						'</a>'
					) . '.<br><br><strong>' . __( 'Note', WCPDF_IT_DOMAIN ) . '</strong>: ' . sprintf(
						/* translators: document type */
						__( 'Changes made to the number format will only be reflected on new orders. Also, if you have already created a custom %s number format with a filter, the above settings will be ignored.', WCPDF_IT_DOMAIN ),
						$this->get_type()
					),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'reset_number_yearly',
				'title'    => __( 'Reset receipt number yearly', WCPDF_IT_DOMAIN ),
				'callback' => 'checkbox',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'reset_number_yearly',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'my_account_buttons',
				'title'    => __( 'Allow My Account receipt download', WCPDF_IT_DOMAIN ),
				'callback' => 'select',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'my_account_buttons',
					'options'     => array(
						'available'	=> __( 'Only when a receipt is already created/emailed' , WCPDF_IT_DOMAIN ),
						'custom'    => __( 'Only for specific order statuses (define below)' , WCPDF_IT_DOMAIN ),
						'always'    => __( 'Always' , WCPDF_IT_DOMAIN ),
						'never'     => __( 'Never' , WCPDF_IT_DOMAIN ),
					),
					'custom'      => array(
						'type' => 'multiple_checkboxes',
						'args' => array(
							'option_name'     => $option_name,
							'id'              => 'my_account_restrict',
							'fields_callback' => array( $this, 'get_wc_order_status_list' ),
						),
					),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'receipt_number_column',
				'title'    => __( 'Enable receipt number column in the orders list', WCPDF_IT_DOMAIN ),
				'callback' => 'checkbox',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'receipt_number_column',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'use_latest_settings',
				'title'    => __( 'Always use most current settings', WCPDF_IT_DOMAIN ),
				'callback' => 'checkbox',
				'section'  => $this->get_type(),
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'use_latest_settings',
					'description' => sprintf(
						'%1$s<br><strong>%2$s</strong><br><a href="%3$s" target="_blank">%4$s</a>',
						__( 'When enabled, the document will always reflect the most current settings (such as footer text, document name, etc.) rather than using historical settings.', WCPDF_IT_DOMAIN ),
						__( 'Caution: Enabling this will also mean that if you change your company name or address in the future, previously generated documents will also be affected.', WCPDF_IT_DOMAIN ),
						'https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/show-pdf-documents-with-the-latest-settings/',
						__( 'Learn more about this setting', WCPDF_IT_DOMAIN )
					),
				),
			),
		);

		// Legacy filter to allow plugins to alter settings fields.
		$settings_fields = apply_filters( "wpo_wcpdf_settings_fields_documents_{$this->slug}", $settings_fields, $page, $option_group, $option_name );

		// Allow plugins to alter settings fields.
		$settings_fields = apply_filters( "wpo_wcpdf_settings_fields_documents_{$this->get_type()}_pdf", $settings_fields, $page, $option_group, $option_name, $this );

		\WPO_WCPDF()->settings->add_settings_fields( $settings_fields, $page, $option_group, $option_name );
	}

	/**
	 * Get the settings categories.
	 *
	 * @param string $output_format
	 * @return array
	 */
	public function get_settings_categories( string $output_format ): array {
		if ( ! in_array( $output_format, $this->output_formats, true ) ) {
			return array();
		}

		$settings_categories = array(
			'pdf' => array(
				'general' => array(
					'title'   => __( 'General', WCPDF_IT_DOMAIN ),
					'members' => array(
						'enabled',
						'title',
						'attach_to_email_ids',
						'auto_generate_for_statuses',
						'disable_for_statuses',
						'my_account_buttons',
					),
				),
				'document_details' => array(
					'title'   => __( 'Document details', WCPDF_IT_DOMAIN ),
					'members' => array(
						'number_title',
						'date_title',
						'display_email',
						'display_phone',
						'display_customer_notes',
						'display_shop_vat',
						'shop_vat_label',
						'display_shop_coc',
						'shop_coc_label',
						'display_shipping_address',
						'display_date',
						'display_number',
						'next_receipt_number', // this should follow 'display_number'
						'number_format',
						'show_zero_taxes',
					),
				),
				'advanced' => array(
					'title'   => __( 'Advanced', WCPDF_IT_DOMAIN ),
					'members' => array(
						'filename',
						'reset_number_yearly',
						'archive_pdf',
						'disable_free',
						'use_latest_settings',
					),
				),
			),
		);

		return apply_filters( 'wpo_wcpdf_document_settings_categories', $settings_categories[ $output_format ], $output_format, $this );
	}

}

endif; // class_exists

// return the class instance
return new ItReceipt();
