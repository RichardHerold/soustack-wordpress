<?php
/**
 * Soustack publisher hooks.
 */

if ( ! class_exists( 'Soustack_Publisher' ) ) {
	/**
	 * Handles rewrite rules, output, and validation.
	 */
	class Soustack_Publisher {
		const QUERY_VAR = 'soustack_slug';

		/**
		 * @var Soustack_Settings
		 */
		private $settings;

		/**
		 * Set dependencies.
		 *
		 * @param Soustack_Settings $settings Settings handler.
		 */
		public function __construct( Soustack_Settings $settings ) {
			$this->settings = $settings;
		}

		/**
		 * Register WordPress hooks.
		 */
		public function register() {
			add_action( 'init', array( $this, 'register_rewrite' ) );
			add_filter( 'query_vars', array( $this, 'register_query_var' ) );
			add_action( 'template_redirect', array( $this, 'maybe_serve_soustack' ) );
			add_action( 'wp_head', array( $this, 'render_alternate_link' ) );
			add_action( 'transition_post_status', array( $this, 'validate_on_publish' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
		}

		/**
		 * Register rewrite rule for the sidecar endpoint.
		 */
		public function register_rewrite() {
			add_rewrite_rule(
				'^soustack/([^/]+)\\.soustack\\.json/?$',
				'index.php?' . self::QUERY_VAR . '=$matches[1]',
				'top'
			);
		}

		/**
		 * Register the Soustack query variable.
		 *
		 * @param array $vars Current vars.
		 * @return array
		 */
		public function register_query_var( $vars ) {
			$vars[] = self::QUERY_VAR;
			return $vars;
		}

		/**
		 * Serve Soustack JSON when the query var is present.
		 */
		public function maybe_serve_soustack() {
			$slug = get_query_var( self::QUERY_VAR );

			if ( empty( $slug ) ) {
				return;
			}

			$post = $this->locate_post( $slug );

			if ( ! $post ) {
				status_header( 404 );
				wp_send_json(
					array(
						'error' => 'Soustack payload not found.',
					),
					404
				);
			}

			$payload = $this->build_payload( $post );

			if ( function_exists( 'apply_filters' ) ) {
				/** Allow the payload to be customized. */
				$payload = apply_filters( 'soustack_payload', $payload, $post );
			}

			nocache_headers();
			header( 'Content-Type: application/vnd.soustack+json; charset=' . get_bloginfo( 'charset' ) );
			wp_send_json( $payload );
		}

		/**
		 * Locate a post/page/custom post by slug.
		 *
		 * @param string $slug Post name/slug.
		 * @return WP_Post|false
		 */
		private function locate_post( $slug ) {
			$post_types = get_post_types(
				array(
					'public' => true,
				)
			);

			return get_page_by_path( sanitize_title_for_query( $slug ), OBJECT, $post_types );
		}

		/**
		 * Build the Soustack payload for a post.
		 *
		 * @param WP_Post $post Post object.
		 * @return array<string, mixed>
		 */
		public function build_payload( $post ) {
			$post = get_post( $post );

			if ( ! $post ) {
				return array();
			}

			$excerpt = get_the_excerpt( $post );
			if ( empty( $excerpt ) ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 55 );
			}

			$payload = array(
				'id'            => (string) $post->ID,
				'slug'          => $post->post_name,
				'title'         => get_the_title( $post ),
				'excerpt'       => $excerpt,
				'content'       => apply_filters( 'the_content', $post->post_content ),
				'url'           => get_permalink( $post ),
				'feature_image' => get_the_post_thumbnail_url( $post, 'full' ),
				'author'        => get_the_author_meta( 'display_name', $post->post_author ),
				'published_at'  => get_post_time( DATE_ATOM, true, $post ),
				'updated_at'    => get_post_modified_time( DATE_ATOM, true, $post ),
			);

			return $payload;
		}

		/**
		 * Render the alternate link for Soustack discovery.
		 */
		public function render_alternate_link() {
			if ( ! is_singular() ) {
				return;
			}

			global $post;
			if ( ! $post instanceof WP_Post ) {
				return;
			}

			$href = esc_url( $this->build_endpoint_url( $post->post_name ) );

			printf(
				'<link rel="alternate" type="application/vnd.soustack+json" href="%s" />' . "\n",
				$href
			);
		}

		/**
		 * Build endpoint URL for a slug.
		 *
		 * @param string $slug Post slug.
		 * @return string
		 */
		public function build_endpoint_url( $slug ) {
			return home_url( sprintf( '/soustack/%s.soustack.json', rawurlencode( $slug ) ) );
		}

		/**
		 * Validate Soustack payload as part of the publishing workflow.
		 *
		 * @param string  $new_status New status.
		 * @param string  $old_status Old status.
		 * @param WP_Post $post       Post object.
		 */
		public function validate_on_publish( $new_status, $old_status, $post ) {
			if ( 'publish' !== $new_status ) {
				return;
			}

			$payload = $this->build_payload( $post );
			$result  = Soustack_Core::validate( $payload );
			$strict  = (bool) get_option( Soustack_Settings::OPTION_STRICT_MODE, false );

			update_post_meta(
				$post->ID,
				'_soustack_validation_errors',
				array(
					'errors'   => $result->errors,
					'warnings' => $result->warnings,
					'strict'   => $strict,
				)
			);

			if ( $result->is_valid( $strict ) ) {
				$this->clear_notice();
				return;
			}

			$message_lines = array();
			if ( ! empty( $result->errors ) ) {
				$message_lines[] = 'Errors: ' . implode( '; ', $result->errors );
			}
			if ( ! empty( $result->warnings ) ) {
				$message_lines[] = 'Warnings: ' . implode( '; ', $result->warnings );
			}

			$message = implode( ' | ', $message_lines );

			if ( $strict ) {
				wp_die(
					wp_kses_post( 'Soustack validation failed: ' . $message ),
					esc_html__( 'Soustack validation failed', 'soustack' ),
					array(
						'response' => 400,
					)
				);
			}

			$this->push_notice(
				array(
					'errors'   => $result->errors,
					'warnings' => $result->warnings,
					'message'  => $message,
				)
			);
		}

		/**
		 * Store a transient notice for the current user.
		 *
		 * @param array $notice Notice payload.
		 */
		private function push_notice( $notice ) {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return;
			}

			set_transient( $this->notice_key( $user_id ), $notice, MINUTE_IN_SECONDS * 5 );
		}

		/**
		 * Delete stored notice.
		 */
		private function clear_notice() {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return;
			}

			delete_transient( $this->notice_key( $user_id ) );
		}

		/**
		 * Render admin notices for validation warnings/errors.
		 */
		public function render_admin_notices() {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return;
			}

			$notice = get_transient( $this->notice_key( $user_id ) );
			if ( empty( $notice ) ) {
				return;
			}

			delete_transient( $this->notice_key( $user_id ) );

			$has_errors   = ! empty( $notice['errors'] );
			$has_warnings = ! empty( $notice['warnings'] );

			$class = $has_errors ? 'notice notice-error' : 'notice notice-warning';
			?>
			<div class="<?php echo esc_attr( $class ); ?>">
				<p><strong><?php esc_html_e( 'Soustack validation notice', 'soustack' ); ?></strong></p>
				<?php if ( $has_errors ) : ?>
					<ul>
						<?php foreach ( $notice['errors'] as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $has_warnings ) : ?>
					<p><?php esc_html_e( 'Warnings', 'soustack' ); ?>:</p>
					<ul>
						<?php foreach ( $notice['warnings'] as $warning ) : ?>
							<li><?php echo esc_html( $warning ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( ! empty( $notice['message'] ) ) : ?>
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Get user-specific transient key.
		 *
		 * @param int $user_id User ID.
		 * @return string
		 */
		private function notice_key( $user_id ) {
			return 'soustack_validation_notice_' . $user_id;
		}
	}
}
