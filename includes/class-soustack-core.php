<?php
/**
 * Lightweight Soustack validation helpers.
 *
 * The real soustack-core package is not bundled; this mirrors its intent so the plugin can
 * validate payloads before publishing.
 */

if ( ! class_exists( 'Soustack_Core_Validation_Result' ) ) {
	/**
	 * Represents validation output with error and warning buckets.
	 */
	class Soustack_Core_Validation_Result {
		/**
		 * @var array<int, string>
		 */
		public $errors = array();

		/**
		 * @var array<int, string>
		 */
		public $warnings = array();

		/**
		 * Whether the payload is acceptable.
		 *
		 * @param bool $strict When true, warnings are treated as blockers.
		 * @return bool
		 */
		public function is_valid( $strict = false ) {
			if ( ! empty( $this->errors ) ) {
				return false;
			}

			return ! $strict || empty( $this->warnings );
		}
	}
}

if ( ! class_exists( 'Soustack_Core' ) ) {
	/**
	 * Small validator intended to be API-compatible with a future soustack-core package.
	 */
	class Soustack_Core {
		/**
		 * Validate the payload and return structured results.
		 *
		 * @param array $payload Soustack payload to validate.
		 * @return Soustack_Core_Validation_Result
		 */
		public static function validate( $payload ) {
			$result = new Soustack_Core_Validation_Result();

			$required = array( 'id', 'slug', 'title', 'url', 'content' );
			foreach ( $required as $field ) {
				if ( empty( $payload[ $field ] ) ) {
					$result->errors[] = sprintf( 'Missing required field: %s', $field );
				}
			}

			if ( ! empty( $payload['url'] ) && ! filter_var( $payload['url'], FILTER_VALIDATE_URL ) ) {
				$result->errors[] = 'Invalid URL provided.';
			}

			if ( ! empty( $payload['feature_image'] ) && ! filter_var( $payload['feature_image'], FILTER_VALIDATE_URL ) ) {
				$result->warnings[] = 'Feature image is not a valid URL.';
			}

			$optional_strings = array(
				'excerpt' => 'Excerpt is missing; consider adding a summary.',
				'author'  => 'Author is missing; add an author display name.',
			);

			foreach ( $optional_strings as $field => $warning ) {
				if ( empty( $payload[ $field ] ) ) {
					$result->warnings[] = $warning;
				}
			}

			return $result;
		}
	}
}
