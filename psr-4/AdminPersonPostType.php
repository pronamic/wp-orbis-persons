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

use WP_Error;
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

		\add_action( 'admin_enqueue_scripts', $this->enqueue_scripts( ... ) );

		\add_filter( 'enter_title_here', $this->enter_title_here( ... ), 10, 2 );

		\add_filter( 'wp_insert_post_data', $this->insert_post_data( ... ) );

		\add_action( 'save_post_orbis_person', $this->save_person( ... ) );

		\add_action( 'admin_notices', $this->admin_notices( ... ) );

		\add_filter( '_wp_post_revision_fields', $this->revision_fields( ... ), 10, 2 );

		\add_filter( '_wp_post_revision_field__orbis_birth_date', $this->revision_field_birth_date( ... ), 10, 3 );

		\add_filter( '_wp_post_revision_field__orbis_gender', $this->revision_field_gender( ... ) );

		\add_filter( '_wp_post_revision_field__orbis_person_father_post_id', $this->revision_field_parent( ... ) );

		\add_filter( '_wp_post_revision_field__orbis_person_mother_post_id', $this->revision_field_parent( ... ) );
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
			'_orbis_first_name'            => \__( 'First Names', 'orbis-persons' ),
			'_orbis_last_name'             => \__( 'Last Names', 'orbis-persons' ),
			'_orbis_maiden_name'           => \__( 'Last Name at Birth', 'orbis-persons' ),
			'_orbis_title'                 => \_x( 'Title', 'person', 'orbis-persons' ),
			'_orbis_organization'          => \_x( 'Organization', 'person', 'orbis-persons' ),
			'_orbis_department'            => \_x( 'Department', 'person', 'orbis-persons' ),
			'_orbis_address'               => \__( 'Address', 'orbis-persons' ),
			'_orbis_postcode'              => \__( 'Postcode', 'orbis-persons' ),
			'_orbis_city'                  => \__( 'City', 'orbis-persons' ),
			'_orbis_country'               => \__( 'Country', 'orbis-persons' ),
			'_orbis_gender'                => \__( 'Gender', 'orbis-persons' ),
			'_orbis_birth_date'            => \__( 'Birth Date', 'orbis-persons' ),
			'_orbis_person_father_post_id' => \__( 'Father', 'orbis-persons' ),
			'_orbis_person_mother_post_id' => \__( 'Mother', 'orbis-persons' ),
			'_orbis_iban'                  => \__( 'IBAN', 'orbis-persons' ),
			'_orbis_twitter'               => \__( 'Twitter Username', 'orbis-persons' ),
			'_orbis_facebook'              => \__( 'Facebook URL', 'orbis-persons' ),
			'_orbis_linkedin'              => \__( 'LinkedIn URL', 'orbis-persons' ),
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
	 * Revision field gender.
	 *
	 * Shows the label of the gender instead of the stored value.
	 *
	 * @param mixed $value Gender value.
	 * @return string
	 */
	private function revision_field_gender( mixed $value ): string {
		return Gender::tryFrom( \is_string( $value ) ? $value : '' )?->label() ?? '';
	}

	/**
	 * Revision field parent.
	 *
	 * Shows the display name of the father or mother instead of the post ID.
	 *
	 * @param mixed $value Post ID of the parent.
	 * @return string
	 */
	private function revision_field_parent( mixed $value ): string {
		$parent_id = \is_numeric( $value ) ? (int) $value : 0;

		return $parent_id > 0 ? \get_the_title( $parent_id ) : '';
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
				$person = $this->plugin->persons->get( $post_id );

				$links = [];

				foreach ( $person->email_addresses ?? [] as $item ) {
					$links[] = \sprintf(
						'<a href="%s">%s</a>%s',
						\esc_url( 'mailto:' . $item['email'] ),
						\esc_html( $item['email'] ),
						$this->format_label( $item['label'] )
					);
				}

				foreach ( $person->phone_numbers ?? [] as $item ) {
					$links[] = \sprintf(
						'<a href="%s">%s</a>%s',
						\esc_url( 'tel:' . ( $item['e164'] ?? $item['input'] ), [ 'tel' ] ),
						\esc_html( $item['input'] ),
						$this->format_label( $item['label'] )
					);
				}

				echo \wp_kses_post( \implode( '<br />', $links ) );

				break;
		}
	}

	/**
	 * Format the label of a contact email address or phone number.
	 *
	 * @param string $label Label.
	 * @return string HTML, empty when there is no label.
	 */
	private function format_label( string $label ): string {
		if ( '' === $label ) {
			return '';
		}

		return \sprintf( ' <span class="description">(%s)</span>', \esc_html( $label ) );
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
	 * Enqueue scripts.
	 *
	 * The father and mother fields search persons with jQuery UI
	 * Autocomplete, which WordPress includes and styles in the admin.
	 *
	 * @return void
	 */
	private function enqueue_scripts(): void {
		$screen = \get_current_screen();

		if ( null === $screen || 'post' !== $screen->base || 'orbis_person' !== $screen->post_type ) {
			return;
		}

		$plugin_file = \dirname( __DIR__ ) . '/orbis-persons.php';

		\wp_enqueue_script(
			'orbis-persons-admin-person-parents',
			\plugins_url( 'js/admin-person-parents.js', $plugin_file ),
			[ 'jquery-ui-autocomplete', 'wp-api-fetch', 'wp-url' ],
			(string) \filemtime( \dirname( $plugin_file ) . '/js/admin-person-parents.js' ),
			true
		);

		\wp_localize_script(
			'orbis-persons-admin-person-parents',
			'orbisPersonParents',
			[
				'noResults' => \__( 'No persons found.', 'orbis-persons' ),
				'result'    => \__( '1 person found. Use up and down arrow keys to navigate.', 'orbis-persons' ),
				/* translators: %d: number of persons */
				'results'   => \__( '%d persons found. Use up and down arrow keys to navigate.', 'orbis-persons' ),
			]
		);
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

		$gender = \array_key_exists( '_orbis_gender', $_POST ) && \is_string( $_POST['_orbis_gender'] ) ? \sanitize_text_field( \wp_unslash( $_POST['_orbis_gender'] ) ) : '';

		$this->plugin->persons->save_gender( $post_id, Gender::tryFrom( $gender ) );

		$get_parent_input = static fn ( string $key ): string => \array_key_exists( $key, $_POST ) && \is_string( $_POST[ $key ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ $key ] ) ) : '';

		$parents = $this->plugin->persons->validate_parents(
			$get_parent_input( '_orbis_person_father_post_id' ),
			$get_parent_input( '_orbis_person_mother_post_id' ),
			$post_id
		);

		if ( $parents instanceof WP_Error ) {
			$this->add_redirect_notice( 'orbis_persons_invalid_parent' );
		} else {
			$this->plugin->persons->save_parents( $post_id, $parents );
		}

		$birth_date_input = [];

		foreach ( [ '_orbis_birth_day', '_orbis_birth_month', '_orbis_birth_year' ] as $key ) {
			$birth_date_input[] = \array_key_exists( $key, $_POST ) && \is_string( $_POST[ $key ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ $key ] ) ) : '';
		}

		$birth_date = BirthDate::from_input( ...$birth_date_input );

		if ( null === $birth_date ) {
			$this->add_redirect_notice( 'orbis_persons_invalid_birth_date' );

			return;
		}

		$birth_date->save( $post_id );
	}

	/**
	 * Add a query argument to the redirect after saving, to show a notice.
	 *
	 * @param string $key Query argument.
	 * @return void
	 */
	private function add_redirect_notice( string $key ): void {
		\add_filter(
			'redirect_post_location',
			static fn ( string $location ): string => \add_query_arg( $key, '1', $location )
		);
	}

	/**
	 * Admin notices.
	 *
	 * @return void
	 */
	private function admin_notices(): void {
		$screen = \get_current_screen();

		if ( null === $screen || 'orbis_person' !== $screen->post_type ) {
			return;
		}

		$notices = [
			'orbis_persons_invalid_birth_date' => \__( 'The birth date is invalid and has not been saved.', 'orbis-persons' ),
			'orbis_persons_invalid_parent'     => \__( 'The father or mother is invalid and has not been saved. A person cannot be their own father or mother, the father and mother cannot be the same person, the father cannot be a woman and the mother cannot be a man.', 'orbis-persons' ),
		];

		foreach ( $notices as $key => $message ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used to show a notice after saving.
			if ( ! \array_key_exists( $key, $_GET ) ) {
				continue;
			}

			\wp_admin_notice(
				$message,
				[
					'type'        => 'error',
					'dismissible' => true,
				]
			);
		}
	}
}
