<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_Italian_add_on_Settings' ) ) {

	class WooCommerce_Italian_add_on_Settings {
	
		public $options_page_hook;
		public $general_settings_key = 'wcpdf_IT_general_settings';
		private $premium_versions_key = 'wcpdf_IT_premium_versions';
		private $plugin_options_key = 'wcpdf_IT_options_page';
		private $plugin_settings_tabs = array();
		

		public function __construct() {
			global $wcpdf_IT;
			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) ); // Add menu.
			add_action( 'admin_init', array( &$this, 'register_general_settings' ) ); // Registers settings
			add_action( 'admin_init', array( &$this, 'register_premium_versions' ) ); // Registers settings
			add_filter( 'plugin_action_links_'. $wcpdf_IT->plugin_basename, array( &$this, 'add_settings_link' ) );
			add_filter( 'plugin_row_meta', array( $this, 'add_support_links' ), 10, 2 );
		}
		
		public function add_admin_menus() {
			$parent_slug = 'woocommerce';
			
			$this->options_page_hook = add_submenu_page(
				$parent_slug,
				__( 'Italian Add-on', WCPDF_IT_DOMAIN ),
				__( 'Italian Add-on', WCPDF_IT_DOMAIN ),
				'manage_woocommerce',
				'wcpdf_IT_options_page',
				array( $this, 'settings_page' )
//				array( $this, 'plugin_options_page' )
			);
		}
		
		function plugin_options_tabs() {
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
			echo '<h2 class="nav-tab-wrapper">';
			echo '<i class="icon icon32"></i>';
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
					$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
					echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
			}
			echo '</h2>';
		}

		public function settings_page() {
			global $wcpdf_IT;
			$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
			echo '<h2>WooCommerce Italian Add-on</h2>';
			echo '<div class="wrap">';
			$this->plugin_options_tabs();
			switch($tab) {
				case $this->premium_versions_key :
					try {
						$s = file_get_contents("http://ldav.it/plugin/woocommerce-italian-add-on/");
						preg_match("/<section\sitemprop=\"articleBody\"[^>]*>(.*?)<\\/section>/si", $s, $m);
						$content = isset($m[0]) ? $m[0] : $s;;
					} catch (Exception $e) {
						$content = "";
					}
?>
<h3><?php echo __( 'Premium Versions', WCPDF_IT_DOMAIN ) ?></h3>
<?php
					echo $content;
				break;
				case $this->general_settings_key :
?>
<form method="post" action="options.php" id="wcpdf-IT-settings">
<?php
settings_fields($tab);
do_settings_sections($tab);
?>

<?php submit_button();?>
</form>
<script type="text/javascript">
jQuery(function() {
jQuery(".form-table .description:eq(1)").append('<br><img src="<?php echo $wcpdf_IT->plugin_url ?>images/invoice_width.png" width="464" height="259" />');
jQuery("#wpfooter").prepend("<p class=\"alignleft\"><em><?php echo sprintf(__("If you like <strong>%1s</strong> please leave us a %2s rating. A huge thank you from %3s team in advance!", WCPDF_IT_DOMAIN ), "WooCommerce Italian Add-on", "<a href='https://wordpress.org/support/view/plugin-reviews/woocommerce-pdf-invoices-italian-add-on?rate=5#postform'>★★★★★</a>", "<a href='http://ldav.it'>laboratorio d'avanguardia</a>"); ?></em></p><div class=\"clear\"></div>");
});
</script>
<?php
				break;
			}
			echo '</div>';
		}
		
		public function register_premium_versions() {
			$this->plugin_settings_tabs[$this->premium_versions_key] = __( 'Premium Versions', WCPDF_IT_DOMAIN );
			register_setting( $this->premium_versions_key, $this->premium_versions_key );
			
			add_settings_section(
				'wcpdf_IT_advances_settings',
				__( 'Premium Versions', WCPDF_IT_DOMAIN ),
				array( &$this, 'section_options_callback' ),
				$this->premium_versions_key
			);

			register_setting( $this->general_settings_key, $this->general_settings_key, array( &$this, 'validate_options' ) );
		}

		public function register_general_settings() {
			$this->plugin_settings_tabs[$this->general_settings_key] = __( 'Settings', WCPDF_IT_DOMAIN );

			if ( false === get_option( $this->general_settings_key ) ) {
				$default = array(
					'invoice_width'	=> 'wide',
					'invoice_required'	=> 'not-required'
				);
				add_option( $this->general_settings_key, $default );
			}

			add_settings_section(
				$this->general_settings_key,
				__( 'General settings', WCPDF_IT_DOMAIN ),
				array( &$this, 'section_options_callback' ),
				$this->general_settings_key
			);

			add_settings_field(
				'invoice_required',
				__( 'Required VAT/Tax Code', WCPDF_IT_DOMAIN ),
				array( &$this, 'radio_element_callback' ),
				$this->general_settings_key,
				$this->general_settings_key,
				array(
					'menu'			=> $this->general_settings_key,
					'id'			=> 'invoice_required',
					'help'			=> 'Choose if you want that VAT/Tax Code is a required field',
					'description' => __( "Choose if you want that VAT/Tax Code is a required field", WCPDF_IT_DOMAIN ),
					'default' => 'not-required',
					'options' 		=> array(
						'required'	=> __( 'required' , WCPDF_IT_DOMAIN ),
						'not-required'	=> __( 'not required' , WCPDF_IT_DOMAIN ),
					),
				)
			);

			add_settings_field(
				'invoice_width',
				__( 'VAT/Tax Code field width', WCPDF_IT_DOMAIN ),
				array( &$this, 'radio_element_callback' ),
				$this->general_settings_key,
				$this->general_settings_key,
				array(
					'menu'			=> $this->general_settings_key,
					'id'			=> 'invoice_width',
					'help'			=> 'Choose if you want that fields VAT/Tax Code and invoice/receipt on the same row in checkout page',
					'description' => __( "Choose if you want that fields VAT/Tax Code and invoice/receipt on the same row in checkout page", WCPDF_IT_DOMAIN ),
					'default' => 'wide',
					'options' 		=> array(
						'wide'	=> __( 'wide' , WCPDF_IT_DOMAIN ),
						'short'	=> __( 'short' , WCPDF_IT_DOMAIN ),
					),
				)
			);

			register_setting( $this->general_settings_key, $this->general_settings_key, array( &$this, 'validate_options' ) );

		}
		
		// Text element callback.
		public function text_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
			$size = isset( $args['size'] ) ? $args['size'] : '25';
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			$html = sprintf( '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" size="%4$s"/>', $id, $menu, $current, $size );
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
		
			echo $html;
		}
		
		// Text element callback.
		public function textarea_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
			$width = $args['width'];
			$height = $args['height'];
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			$html = sprintf( '<textarea id="%1$s" name="%2$s[%1$s]" cols="%4$s" rows="%5$s"/>%3$s</textarea>', $id, $menu, $current, $width, $height );
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
		
			echo $html;
		}
	
	
		/**
		 * Checkbox field callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string	  Checkbox field.
		 */
		public function checkbox_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
			$value = isset( $args['value'] ) ? $args['value'] : 1;
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			$html = sprintf( '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="%3$s"%4$s />', $id, $menu, $value, checked( $value, $current, false ) );
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
		
			echo $html;
		}
		
		/**
		 * Multiple Checkbox field callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string	  Checkbox field.
		 */
		public function multiple_checkbox_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
		
			$options = get_option( $menu );
		
		
			foreach ( $args['options'] as $key => $label ) {
				$current = ( isset( $options[$id][$key] ) ) ? $options[$id][$key] : '';
				printf( '<input type="checkbox" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="1"%4$s /> %5$s<br/>', $menu, $id, $key, checked( 1, $current, false ), $label );
			}

			// Displays option description.
			if ( isset( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', $args['description'] );
			}
		}

		/**
		 * Checkbox fields table callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string	  Checkbox field.
		 */
		public function checkbox_table_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];

			$options = get_option( $menu );

			$rows = $args['rows'];
			$columns = $args['columns'];

			?>
			<table style="">
				<tr>
					<td style="padding:0 10px 5px 0;">&nbsp;</td>
					<?php foreach ( $columns as $column => $title ) { ?>
					<td style="padding:0 10px 5px 0;"><?php echo $title; ?></td>
					<?php } ?>
				</tr>
				<tr>
					<td style="padding: 0;">
						<?php foreach ($rows as $row) {
							echo $row.'<br/>';
						} ?>
					</td>
					<?php foreach ( $columns as $column => $title ) { ?>
					<td style="text-align:center; padding: 0;">
						<?php foreach ( $rows as $row => $title ) {
							$current = ( isset( $options[$id.'_'.$column][$row] ) ) ? $options[$id.'_'.$column][$row] : '';
							$name = sprintf('%1$s[%2$s_%3$s][%4$s]', $menu, $id, $column, $row);
							printf( '<input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s /><br/>', $name, checked( 1, $current, false ) );
						} ?>
					</td>
					<?php } ?>
				</tr>
			</table>

			<?php
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', $args['description'] );
			}
		}

		/**
		 * Select element callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string	  Select field.
		 */
		public function select_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			printf( '<select id="%1$s" name="%2$s[%1$s]">', $id, $menu );
	
			foreach ( $args['options'] as $key => $label ) {
				printf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
			}
	
			echo '</select>';
		

			if (isset($args['custom'])) {
				$custom = $args['custom'];

				$custom_id = $id.'_custom';

				printf( '<br/><br/><div id="%s" style="display:none;">', $custom_id );

				switch ($custom['type']) {
					case 'text_element_callback':
						$this->text_element_callback( $custom['args'] );
						break;		
					case 'multiple_text_element_callback':
						$this->multiple_text_element_callback( $custom['args'] );
						break;		
					case 'multiple_checkbox_element_callback':
						$this->multiple_checkbox_element_callback( $custom['args'] );
						break;		
					default:
						break;
				}

				echo '</div>';

				?>
				<script type="text/javascript">
				jQuery(document).ready(function($) {
					function check_<?php echo $id; ?>_custom() {
						var custom = $('#<?php echo $id; ?>').val();
						if (custom == 'custom') {
							$( '#<?php echo $custom_id; ?>').show();
						} else {
							$( '#<?php echo $custom_id; ?>').hide();
						}
					}

					check_<?php echo $id; ?>_custom();

					$( '#<?php echo $id; ?>' ).change(function() {
						check_<?php echo $id; ?>_custom();
					});

				});
				</script>
				<?php
			}

			// Displays option description.
			if ( isset( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', $args['description'] );
			}

		}
		
		/**
		 * Displays a radio settings field
		 *
		 * @param array   $args settings field args
		 */
		public function radio_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
					
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$html = '';
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $menu, $id, $key, checked( $current, $key, false ) );
				$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s</label><br>', $menu, $id, $key, $label);
			}
			
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
	
			echo $html;
		}

		public function radio_element_explained_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
					
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$html = '';
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
			
			$html .= "<p>";
			
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $menu, $id, $key, checked( $current, $key, false ) );
				$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s<br>', $menu, $id, $key, $label[0]);
				$html .= sprintf( '<span class="description">%1$s</span></label><br><br>', $label[1]);
			}
			
			$html .= "</p>";
	
			echo $html;
		}

		/**
		 * Section null callback.
		 *
		 * @return void.
		 */
		public function section_options_callback() {
		}

		/**
		 * Validate options.
		 *
		 * @param  array $input options to valid.
		 *
		 * @return array		validated options.
		 */
		public function validate_options( $input ) {
			// Create our array for storing the validated options.
			$output = array();

			if (empty($input) || !is_array($input)) {
				return $input;
			}
		
			// Loop through each of the incoming options.
			foreach ( $input as $key => $value ) {
		
				// Check to see if the current option has a value. If so, process it.
				if ( isset( $input[$key] ) ) {
					if ( is_array( $input[$key] ) ) {
						foreach ( $input[$key] as $sub_key => $sub_value ) {
							$output[$key][$sub_key] = $input[$key][$sub_key];
						}
					} else {
						$output[$key] = $input[$key];
					}
				}
			}
		
			// Return the array processing any additional functions filtered by this action.
			return apply_filters( 'wcpdf_IT_validate_input', $output, $input );
		}



		public function add_settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=wcpdf_IT_options_page">'. __( 'Settings', WCPDF_IT_DOMAIN ) . '</a>';
			array_push( $links, $settings_link );
			$settings_link = '<a href="http://ldav.it/plugin/woocommerce-italian-add-on/">'. __( 'Premium Versions', WCPDF_IT_DOMAIN ) . '</a>';
			array_push( $links, $settings_link );
			return $links;
		}

		public function add_support_links( $links, $file ) {
			global $wcpdf_IT;
			if ( !current_user_can( 'install_plugins' ) ) {
				return $links;
			}
		
			if ( $file == $wcpdf_IT->plugin_basename ) {
			 $links[] = '<a href="http://ldav.it/plugin/woocommerce-italian-add-on/" target="_blank" title="' . __( 'More Options', WCPDF_IT_DOMAIN ) . '">' . __( 'More Options', WCPDF_IT_DOMAIN ) . '...</a>';
			}
			return $links;
		}
	


	} // end class WooCommerce_Italian_add_on_Settings

} // end class_exists