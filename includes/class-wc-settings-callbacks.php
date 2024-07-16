<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_Italian_add_on_Settings_Callbacks' ) ) :

class WooCommerce_Italian_add_on_Settings_Callbacks {

	private function get_wcpdf_option ( $args ) {
		$option = get_option(is_array($args['option_name']) ? $args['option_name'][0] : $args['option_name'] ) ;
		if(is_array($args['option_name'])) $option = $option[$args['option_name'][1]];
		return $option;
	}

	private function get_setting_name ( $args ) {
		$setting_name = is_array($args['option_name']) ? $args['option_name'][0] . "[" . $args['option_name'][1] . "]" : $args['option_name'];
		return $setting_name;
	}

	private function get_current ( $args ) {
		$option = $this->get_wcpdf_option( $args );
		if ( isset( $option[$args["id"]] ) ) {
			$current = $option[$args["id"]];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}
		return $current;
	}

	// Text element callback.
		public function text_element_callback( $args ) {
			$current = $this->get_current($args);
			$setting_name = $this->get_setting_name($args);
			$id = $args['id'];
			$size = isset( $args['size'] ) ? $args['size'] : '400px';
			$maxlength = empty($args['maxlength']) ? "" : sprintf(' maxlength="%s"', $args["maxlength"]);
			$placeholder = empty($args["placeholder"]) ? "" : sprintf(' placeholder="%s"', $args["placeholder"]);
			$type = isset( $args['type'] ) ? $args['type'] : "text";
		
			$html = sprintf( '<input type="' .$type . '" id="%1$s" name="%2$s[%1$s]" value="%3$s" style="width:%4$s"%5$s%6$s/>', $id, $setting_name, $current, $size, $placeholder, $maxlength );
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}

			if (isset($args['additional'])) {
				$additional = $args['additional'];

				$additional_id = $id.'_additional';

				printf( '<div id="%s" style="margin-top:4px">', $additional_id );

				switch ($additional['callback']) {
					case 'text_element_callback':
						$this->text_element_callback( $additional['args'] );
						break;		
					case 'select_element_callback':
						$this->select_element_callback( $additional['args'] );
						break;		
					case 'multiple_text_element_callback':
						$this->multiple_text_element_callback( $additional['args'] );
						break;		
					case 'multiple_checkbox_element_callback':
						$this->multiple_checkbox_element_callback( $additional['args'] );
						break;		
					default:
						break;
				}

				echo '</div>';
			}

			echo $html;
		}
		
		public function textarea_element_callback( $args ) {
			$current = $this->get_current($args);
			$setting_name = $this->get_setting_name($args);
			$id = $args['id'];
			$width = $args['width'];
			$height = $args['height'];
			$placeholder = empty($args["placeholder"]) ? "" : sprintf(' placeholder="%s"', $args["placeholder"]);
		
			$html = sprintf( '<textarea id="%1$s" name="%2$s[%1$s]" cols="%4$s" rows="%5$s"%6$s/>%3$s</textarea>', $id, $setting_name, $current, $width, $height, $placeholder );
		
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
			$current = $this->get_current($args);
			$setting_name = $this->get_setting_name($args);
			$id = $args['id'];
			$value = isset( $args['value'] ) ? $args['value'] : 1;
			$label = isset( $args['label'] ) ? $args['label'] : "";
		
			$html = sprintf( '<label><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="%3$s"%4$s /> %5$s</label>', $id, $setting_name, $value, checked( $value, $current, false ), $label );
		
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
			$setting_name = $this->get_setting_name($args);
			$id = $args['id'];
		
			$options = $this->get_wcpdf_option( $args );
		
			foreach ( $args['options'] as $key => $label ) {
				$current = ( isset( $options[$id][$key] ) ) ? $options[$id][$key] : '';
				printf( '<input type="checkbox" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="1"%4$s /> %5$s<br/>', $setting_name, $id, $key, checked( 1, $current, false ), $label );
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
			$setting_name = $this->get_setting_name($args);
			$id = $args['id'];

			$options = $this->get_wcpdf_option( $args );

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
							$name = sprintf('%1$s[%2$s_%3$s][%4$s]', $setting_name, $id, $column, $row);
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
			$current = $this->get_current($args);
			$setting_name = $this->get_setting_name($args);
			$id = $args['id'];
			$size = empty($args["size"]) ? "" : sprintf(' style="width:%s"', $args["size"]);
			$class = empty($args["class"]) ? "" : sprintf(' class="%s"', $args["class"]);
			$placeholder = empty($args["placeholder"]) ? "" : sprintf(' data-placeholder="%s"', $args["placeholder"]);
		
			printf( '<select id="%1$s" name="%2$s[%1$s]"%3$s%4$s%5$s>', $id, $setting_name, $size, $placeholder,$class );
	
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
		
			if (isset($args['additional'])) {
				$additional = $args['additional'];

				$additional_id = $id.'_additional';

				printf( '<div id="%s" style="margin-top:4px">', $additional_id );

				switch ($additional['callback']) {
					case 'text_element_callback':
						$this->text_element_callback( $additional['args'] );
						break;		
					case 'select_element_callback':
						$this->select_element_callback( $additional['args'] );
						break;		
					case 'multiple_text_element_callback':
						$this->multiple_text_element_callback( $additional['args'] );
						break;		
					case 'multiple_checkbox_element_callback':
						$this->multiple_checkbox_element_callback( $additional['args'] );
						break;		
					default:
						break;
				}

				echo '</div>';
			}

		}
		
		/**
		 * Displays a radio settings field
		 *
		 * @param array   $args settings field args
		 */
		public function radio_element_callback( $args ) {
			$current = $this->get_current($args);
			$setting_name = $this->get_setting_name($args);
			$id = $args['id'];
	
			$html = '';
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $setting_name, $id, $key, checked( $current, $key, false ) );
				$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s</label><br>', $setting_name, $id, $key, $label);
			}
			
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
	
			echo $html;
		}

		public function radio_element_explained_callback( $args ) {
			$current = $this->get_current($args);
			$setting_name = $this->get_setting_name($args);
			$id = $args['id'];
	
			$html = '';
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
			
			$html .= "<p>";
			
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $setting_name, $id, $key, checked( $current, $key, false ) );
				$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s<br>', $setting_name, $id, $key, $label[0]);
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
		public function section_options_callback($args) {
			if( !empty(WCPDF_IT()->settings->setting_descriptions[$args["id"]]) ) printf( '<p>%s</p>', WCPDF_IT()->settings->setting_descriptions[$args["id"]] );
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


}


endif; // class_exists

return new WooCommerce_Italian_add_on_Settings_Callbacks();
?>