<?php
/**
 * Admin person post type
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

use WP_Post;

/**
 * Admin person post type class
 */
final readonly class AdminPersonPostType {
	/**
	 * Construct.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct(
		/**
		 * Plugin.
		 */
		private Plugin $plugin
	) {
		\add_filter( 'manage_edit-orbis_person_columns', $this->edit_columns( ... ) );

		\add_action( 'manage_orbis_person_posts_custom_column', $this->custom_columns( ... ), 10, 2 );

		\add_action( 'add_meta_boxes', $this->add_meta_boxes( ... ), 20 );

		\add_filter( 'enter_title_here', $this->enter_title_here( ... ), 10, 2 );

		\add_filter( 'wp_insert_post_data', $this->insert_post_data( ... ) );

		\add_action( 'save_post_orbis_person', $this->save_person( ... ) );

		\add_action( 'admin_notices', $this->admin_notices( ... ) );

		\add_filter( '_wp_post_revision_fields', $this->revision_fields( ... ), 10, 2 );

		\add_filter( '_wp_post_revision_field__orbis_birth_date', $this->revision_field_birth_date( ... ), 10, 3 );
	}

	/**
	 * Revision fields.
	 *
	 * Adds the person details to the revisions screen. The values are read
	 * from the revisioned meta by the `WP_Post` magic getter.
	 *
	 * WordPress keeps the filtered fields in a static variable, so the fields
	 * are removed again for other post types.
	 *
	 * @param array<string, string> $fields Fields.
	 * @param mixed                 $post   Post array, `null` for an unknown post.
	 * @return array<string, string>
	 */
	private function revision_fields( array $fields, mixed $post ): array {
		$person_fields = [
			'_orbis_first_name'    => \__( 'First Names', 'orbis-persons' ),
			'_orbis_last_name'     => \__( 'Last Names', 'orbis-persons' ),
			'_orbis_maiden_name'   => \__( 'Last Name at Birth', 'orbis-persons' ),
			'_orbis_title'         => \_x( 'Title', 'person', 'orbis-persons' ),
			'_orbis_organization'  => \_x( 'Organization', 'person', 'orbis-persons' ),
			'_orbis_department'    => \_x( 'Department', 'person', 'orbis-persons' ),
			'_orbis_email'         => \__( 'Email Address', 'orbis-persons' ),
			'_orbis_phone_number'  => \__( 'Phone Number', 'orbis-persons' ),
			'_orbis_mobile_number' => \__( 'Mobile Number', 'orbis-persons' ),
			'_orbis_address'       => \__( 'Address', 'orbis-persons' ),
			'_orbis_postcode'      => \__( 'Postcode', 'orbis-persons' ),
			'_orbis_city'          => \__( 'City', 'orbis-persons' ),
			'_orbis_country'       => \__( 'Country', 'orbis-persons' ),
			'_orbis_birth_date'    => \__( 'Birth Date', 'orbis-persons' ),
			'_orbis_iban'          => \__( 'IBAN', 'orbis-persons' ),
			'_orbis_twitter'       => \__( 'Twitter Username', 'orbis-persons' ),
			'_orbis_facebook'      => \__( 'Facebook URL', 'orbis-persons' ),
			'_orbis_linkedin'      => \__( 'LinkedIn URL', 'orbis-persons' ),
		];

		$fields = \array_diff_key( $fields, $person_fields );

		if ( ! \is_array( $post ) || 'orbis_person' !== ( $post['post_type'] ?? null ) ) {
			return $fields;
		}

		return $fields + $person_fields;
	}

	/**
	 * Revision field birth date.
	 *
	 * Shows the known parts of the birth date instead of the derived full
	 * date meta, which is only stored when the day, month and year are known.
	 *
	 * @param mixed   $value    Value.
	 * @param string  $field    Field.
	 * @param WP_Post $revision Revision.
	 * @return string
	 */
	private function revision_field_birth_date( mixed $value, string $field, WP_Post $revision ): string {
		return BirthDate::from_post( $revision->ID )->format();
	}

	/**
	 * Edit columns.
	 *
	 * @return array<string, string>
	 */
	private function edit_columns(): array {
		return [
			'cb'                        => '<input type="checkbox" />',
			'title'                     => \__( 'Display Name', 'orbis-persons' ),
			'orbis_person_organization' => \__( 'Organization', 'orbis-persons' ),
			'orbis_person_contact'      => \__( 'Contact', 'orbis-persons' ),
			'author'                    => \__( 'Author', 'orbis-persons' ),
			'comments'                  => \__( 'Comments', 'orbis-persons' ),
			'date'                      => \__( 'Date', 'orbis-persons' ),
		];
	}

	/**
	 * Custom columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	private function custom_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'orbis_person_organization':
				$lines = \array_filter(
					[
						$this->get_meta( $post_id, '_orbis_title' ),
						$this->get_meta( $post_id, '_orbis_organization' ),
						$this->get_meta( $post_id, '_orbis_department' ),
					]
				);

				echo \wp_kses_post( \implode( '<br />', \array_map( \esc_html( ... ), $lines ) ) );

				break;
			case 'orbis_person_contact':
				$links = [];

				$email = $this->get_meta( $post_id, '_orbis_email' );

				if ( '' !== $email ) {
					$links[] = \sprintf(
						'<a href="%s">%s</a>',
						\esc_url( 'mailto:' . $email ),
						\esc_html( $email )
					);
				}

				foreach ( [ '_orbis_phone_number', '_orbis_mobile_number' ] as $key ) {
					$number = $this->get_meta( $post_id, $key );

					if ( '' !== $number ) {
						$links[] = \sprintf(
							'<a href="%s">%s</a>',
							\esc_url( 'tel:' . $number, [ 'tel' ] ),
							\esc_html( $number )
						);
					}
				}

				echo \wp_kses_post( \implode( '<br />', $links ) );

				break;
		}
	}

	/**
	 * Get post meta value as string.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @return string
	 */
	private function get_meta( int $post_id, string $key ): string {
		$value = \get_post_meta( $post_id, $key, true );

		return \is_string( $value ) ? $value : '';
	}

	/**
	 * Add meta boxes.
	 *
	 * @return void
	 */
	private function add_meta_boxes(): void {
		\add_meta_box(
			'orbis_person_details',
			\__( 'Person Details', 'orbis-persons' ),
			$this->meta_box( ... ),
			'orbis_person',
			'normal',
			'high'
		);
	}

	/**
	 * Meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	private function meta_box( WP_Post $post ): void {
		include __DIR__ . '/../admin/meta-box-person-details.php';
	}

	/**
	 * Enter title here.
	 *
	 * @param string  $text Placeholder text.
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private function enter_title_here( string $text, WP_Post $post ): string {
		if ( 'orbis_person' !== $post->post_type ) {
			return $text;
		}

		return \__( 'Display Name', 'orbis-persons' );
	}

	/**
	 * Insert post data.
	 *
	 * Composes the display name (post title) from the first names and last
	 * names when it is left empty in the person details meta box.
	 *
	 * @param array<string, mixed> $data Slashed, sanitized and processed post data.
	 * @return array<string, mixed>
	 */
	private function insert_post_data( array $data ): array {
		if ( 'orbis_person' !== ( $data['post_type'] ?? null ) || '' !== \trim( \is_string( $data['post_title'] ?? null ) ? $data['post_title'] : '' ) ) {
			return $data;
		}

		$nonce = \array_key_exists( 'orbis_persons_details_meta_box_nonce', $_POST ) && \is_string( $_POST['orbis_persons_details_meta_box_nonce'] ) ? \sanitize_text_field( \wp_unslash( $_POST['orbis_persons_details_meta_box_nonce'] ) ) : '';

		if ( ! \wp_verify_nonce( $nonce, 'orbis_persons_save_details' ) ) {
			return $data;
		}

		$parts = [];

		foreach ( [ '_orbis_first_name', '_orbis_last_name' ] as $key ) {
			$parts[] = \array_key_exists( $key, $_POST ) && \is_string( $_POST[ $key ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ $key ] ) ) : '';
		}

		$display_name = $this->plugin->persons->compose_display_name( ...$parts );

		if ( '' !== $display_name ) {
			$data['post_title'] = \wp_slash( $display_name );
		}

		return $data;
	}

	/**
	 * Save person.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function save_person( int $post_id ): void {
		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$nonce = \array_key_exists( 'orbis_persons_details_meta_box_nonce', $_POST ) && \is_string( $_POST['orbis_persons_details_meta_box_nonce'] ) ? \sanitize_text_field( \wp_unslash( $_POST['orbis_persons_details_meta_box_nonce'] ) ) : '';

		if ( ! \wp_verify_nonce( $nonce, 'orbis_persons_save_details' ) ) {
			return;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$data = [];

		foreach ( $this->plugin->persons->get_meta_keys() as $key => $meta_key ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by `PersonRepository::sanitize()`.
			$data[ $key ] = \array_key_exists( $meta_key, $_POST ) && \is_string( $_POST[ $meta_key ] ) ? \wp_unslash( $_POST[ $meta_key ] ) : '';
		}

		$this->plugin->persons->save_meta( $post_id, $this->plugin->persons->sanitize( $data ) );

		$birth_date_input = [];

		foreach ( [ '_orbis_birth_day', '_orbis_birth_month', '_orbis_birth_year' ] as $key ) {
			$birth_date_input[] = \array_key_exists( $key, $_POST ) && \is_string( $_POST[ $key ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ $key ] ) ) : '';
		}

		$birth_date = BirthDate::from_input( ...$birth_date_input );

		if ( null === $birth_date ) {
			\add_filter(
				'redirect_post_location',
				static fn ( string $location ): string => \add_query_arg( 'orbis_persons_invalid_birth_date', '1', $location )
			);

			return;
		}

		$birth_date->save( $post_id );
	}

	/**
	 * Admin notices.
	 *
	 * @return void
	 */
	private function admin_notices(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used to show a notice after saving.
		if ( ! \array_key_exists( 'orbis_persons_invalid_birth_date', $_GET ) ) {
			return;
		}

		$screen = \get_current_screen();

		if ( null === $screen || 'orbis_person' !== $screen->post_type ) {
			return;
		}

		\wp_admin_notice(
			\__( 'The birth date is invalid and has not been saved.', 'orbis-persons' ),
			[
				'type'        => 'error',
				'dismissible' => true,
			]
		);
	}
}
