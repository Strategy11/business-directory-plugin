<?php
/**
 * Form fields API.
 *
 * @package BDP/Form Fields API
 */

if ( ! class_exists( 'WPBDP_FieldValidation' ) ) {

	class WPBDP_FieldValidation {

		private static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Get the set of publicly available validators.
		 *
		 * @return array associative array with validator name as key and display name as value
		 */
		public function get_validators() {
			$validators = array(
				'email'          => __( 'Email Validator', 'business-directory-plugin' ),
				'url'            => __( 'URL Validator', 'business-directory-plugin' ),
				'no_url'         => __( 'Don\'t Allow Urls', 'business-directory-plugin' ),
				'integer_number' => __( 'Whole Number Validator', 'business-directory-plugin' ),
				'decimal_number' => __( 'Decimal Number Validator', 'business-directory-plugin' ),
				'date_'          => __( 'Date Validator', 'business-directory-plugin' ),
				'word_number'    => __( 'Word Count Validator', 'business-directory-plugin' ),
				'tel'            => __( 'Telephone Number Validator', 'business-directory-plugin' ),
			);

			return $validators;
		}

		public function validate_field( $field, $value, $validator, $args = array() ) {
			$args['field-label'] = is_object( $field ) ? apply_filters( 'wpbdp_render_field_label', $field->get_label(), $field ) : __( 'Field', 'business-directory-plugin' );
			$args['field']       = $field;

			return call_user_func( array( $this, $validator ), $value, $args );
		}

		public function validate_value( $value, $validator, $args = array() ) {
			return ! is_wp_error( $this->validate_field( null, $value, $validator, $args ) );
		}

		/* Required validator */
		private function required( $value, $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'allow_whitespace' => false,
					'field'            => null,
				)
			);

			if ( $args['field'] && $args['field']->get_association() == 'category' ) {
				if ( is_array( $value ) && count( $value ) == 1 && ! $value[0] ) {
					return WPBDP_ValidationError(
						sprintf(
							/* translators: %s: field label */
							esc_html__( '%s is required.', 'business-directory-plugin' ),
							esc_html( $args['field-label'] )
						)
					);
				}
			}

			if ( ( $args['field'] && $args['field']->is_empty_value( $value ) ) || ! $value || ( is_string( $value ) && ! $args['allow_whitespace'] && ! trim( $value ) ) ) {
				return WPBDP_ValidationError(
					sprintf(
						/* translators: %s: field label */
						esc_html__( '%s is required.', 'business-directory-plugin' ),
						esc_attr( $args['field-label'] )
					)
				);
			}
		}

		/* URL Validator */
		private function url( $value, $args = array() ) {
			if ( is_array( $value ) ) {
				$value = $value[0];
			}

			if ( esc_url_raw( $value ) !== $value ) {
				return WPBDP_ValidationError(
					sprintf(
						/* translators: %s: field label */
						esc_html__( '%s is badly formatted. Valid URL format required. Include http://', 'business-directory-plugin' ),
						esc_attr( $args['field-label'] )
					)
				);
			}
		}

		/**
		 * Don't allow URLS that include http, www., or .com.
		 *
		 * @since 5.12.1
		 */
		private function no_url( $value, $args = array() ) {
			if ( is_array( $value ) ) {
				$value = implode( ' ', $value );
			}

			$has_url = preg_match( '/http(s)?:/s', $value ) || preg_match( '/\.com(\w)?/s', $value );
			$has_url = $has_url || strpos( $value, 'www.' ) !== false;
			if ( $has_url ) {
				return WPBDP_ValidationError( esc_html__( 'URLs are not allowed.', 'business-directory-plugin' ) );
			}
		}

		/* EmailValidator */
		private function email( $value, $args = array() ) {
			if ( '' === $value ) {
				// Don't check formatting on an empty value.
				return;
			}

			$valid = false;

			if ( function_exists( 'filter_var' ) ) {
				$valid = filter_var( $value, FILTER_VALIDATE_EMAIL );
			} else {
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength
				$valid = (bool) preg_match( '/^(?!(?>\x22?(?>\x22\x40|\x5C?[\x00-\x7F])\x22?){255,})(?!(?>\x22?\x5C?[\x00-\x7F]\x22?){65,}@)(?>[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+|(?>\x22(?>[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|\x5C[\x00-\x7F])*\x22))(?>\.(?>[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+|(?>\x22(?>[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|\x5C[\x00-\x7F])*\x22)))*@(?>(?>(?!.*[^.]{64,})(?>(?>xn--)?[a-z0-9]+(?>-[a-z0-9]+)*\.){0,126}(?>xn--)?[a-z0-9]+(?>-[a-z0-9]+)*)|(?:\[(?>(?>IPv6:(?>(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){7})|(?>(?!(?:.*[a-f0-9][:\]]){8,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?)))|(?>(?>IPv6:(?>(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){5}:)|(?>(?!(?:.*[a-f0-9]:){6,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4})?::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4}:)?)))?(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}))\]))$/isD', $value );
			}

			if ( ! $valid ) {
				return WPBDP_ValidationError(
					sprintf(
						/* translators: %s: field label */
						__( '%s is badly formatted. Valid Email format required.', 'business-directory-plugin' ),
						esc_attr( $args['field-label'] )
					)
				);
			}
		}

		/* IntegerNumberValidator */
		private function integer_number( $value, $args = array() ) {
			if ( ! ctype_digit( $value ) ) {
				return WPBDP_ValidationError(
					sprintf(
						/* translators: %s: field label */
						esc_html__( '%s must be a number. Decimal values are not allowed.', 'business-directory-plugin' ),
						esc_attr( $args['field-label'] )
					)
				);
			}
		}

		/* DecimalNumberValidator */
		private function decimal_number( $value, $args = array() ) {
			if ( ! is_numeric( $value ) ) {
				return WPBDP_ValidationError(
					sprintf(
						/* translators: %s: field label */
						__( '%s must be a number.', 'business-directory-plugin' ),
						esc_attr( $args['field-label'] )
					)
				);
			}
		}

		/* DateValidator */
		private function date_( $value, $args = array() ) {
			$args   = wp_parse_args(
				$args,
				array(
					'format'   => 'dd/mm/yyyy',
					'messages' => array(),
				)
			);
			$format = $args['format'];

			// Normalize separators.
			$format_ = str_replace( array( '/', '.', '-' ), '', $format );
			$value_  = str_replace( array( '/', '.', '-' ), '', $value );

			if ( strlen( $format_ ) != strlen( $value_ ) ) {
				return WPBDP_ValidationError(
					! empty( $args['messages']['incorrect_format'] ) ?
					$args['messages']['incorrect_format'] :
					sprintf(
						/* translators: %1$s: field label, %2$s: format */
						esc_html__( '%1$s must be in the format %2$s.', 'business-directory-plugin' ),
						esc_html( $args['field-label'] ),
						esc_html( $format )
					)
				);
			}

			$d = '0';
			$m = '0';
			$y = '0';

			switch ( $format_ ) {
				case 'ddmmyy':
					$d = substr( $value_, 0, 2 );
					$m = substr( $value_, 2, 2 );
					$y = substr( $value_, 4, 2 );
					break;
				case 'ddmmyyyy':
					$d = substr( $value_, 0, 2 );
					$m = substr( $value_, 2, 2 );
					$y = substr( $value_, 4, 4 );
					break;
				case 'mmddyy':
					$m = substr( $value_, 0, 2 );
					$d = substr( $value_, 2, 2 );
					$y = substr( $value_, 4, 2 );
					break;
				case 'mmddyyyy':
					$m = substr( $value_, 0, 2 );
					$d = substr( $value_, 2, 2 );
					$y = substr( $value_, 4, 4 );
					break;
				case 'yyyymmdd':
					$m = substr( $value_, 4, 2 );
					$d = substr( $value_, 6, 2 );
					$y = substr( $value_, 0, 4 );
					break;
				default:
					break;
			}

			if ( ! ctype_digit( $m ) || ! ctype_digit( $d ) || ! ctype_digit( $y ) || ! checkdate( (int) $m, (int) $d, (int) $y ) ) {
				/* translators: %s: field label */
				return WPBDP_ValidationError( ! empty( $args['messages']['invalid'] ) ? $args['messages']['invalid'] : sprintf( esc_html__( '%s must be a valid date.', 'business-directory-plugin' ), esc_html( $args['field-label'] ) ) );
			}
		}

		/* Image Caption Validator */
		private function caption_( $value, $args = array() ) {
			if ( $args['caption_required'] && empty( $value[1] ) ) {
				return WPBDP_ValidationError(
					! empty( $args['messages']['caption_required'] ) ?
					$args['messages']['caption_required'] :
					sprintf(
						/* translators: %s: field label */
						esc_html__( 'Caption for %s is required.', 'business-directory-plugin' ),
						esc_html( $args['field-label'] )
					)
				);
			}
		}

		/* Word Number Validator */
		private function word_number( $value, $args = array() ) {
			$word_count = $args['field']->data( 'word_count' );

			if ( empty( $word_count ) ) {
				return;
			}

			$no_html_text = preg_replace( '/(<[^>]+>)/i', '', $value );
			$input_array  = preg_split( '/[\s,]+/', $no_html_text );

			if ( $word_count < count( $input_array ) ) {
				/* translators: %1$s: field label, %2$d: max word count */
				return WPBDP_ValidationError( sprintf( esc_html__( '%1$s must have less than %2$d words.', 'business-directory-plugin' ), esc_attr( $args['field-label'] ), $word_count ) );
			}
		}

		private function any_of( $value, $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'values'    => array(),
					'formatter' => function ( $x ) {
						return join( ',', $x );
					},
				)
			);
			extract( $args, EXTR_SKIP );

			if ( is_string( $values ) ) {
				$values = explode( ',', $values );
			}

			if ( ! in_array( $value, $values ) ) {
				/* translators: %1$s: field label, %2$s allowed values */
				return WPBDP_ValidationError( sprintf( __( '%1$s is invalid. Value most be one of %2$s.', 'business-directory-plugin' ), esc_attr( $args['field-label'] ), esc_html( call_user_func( $formatter, $values ) ) ) );
			}
		}

		/**
		 * Telephone number validator
		 */
		private function tel( $value, $args = array() ) {
			if ( '' === $value ) {
				// Don't check formatting on an empty value.
				return;
			}
			$valid = (bool) preg_match( '/^((\+\d{1,3}(-|.| )?\(?\d\)?(-| |.)?\d{1,5})|(\(?\d{2,6}\)?))(-|.| )?(\d{3,4})(-|.| )?(\d{4})(( x| ext)\d{1,5}){0,1}$/', $value );
			if ( ! $valid ) {
				return WPBDP_ValidationError(
					sprintf(
						/* translators: %s: field label */
						__( '%s is badly formatted. Valid Phone Number format required.', 'business-directory-plugin' ),
						esc_attr( $args['field-label'] )
					)
				);
			}
		}
	}
}

// phpcs:ignore
function WPBDP_ValidationError( $msg, $stop_validation = false ) {
	if ( $stop_validation ) {
		return new WP_Error( 'wpbdp-validation-error-stop', $msg );
	}

	return new WP_Error( 'wpbdp-validation-error', $msg );
}

/**
 * Validates a value against a given validator.
 *
 * @since 2.3
 * @see WPBDP_FieldValidation::validate_value()
 *
 * @param mixed  $value
 * @param string $validator one of the registered validators.
 * @param array  $args optional arguments to be passed to the validator.
 *
 * @return bool True if value validates, False otherwise.
 */
function wpbdp_validate_value( $value, $validator, $args = array() ) {
	$validation = WPBDP_FieldValidation::instance();
	return $validation->validate_value( $value, $validator, $args );
}
