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

use DateTimeImmutable;
use WP_Post;

/**
 * Admin person post type class
 */
final class AdminPersonPostType {
	/**
	 * Construct.
	 */
	public function __construct() {
		\add_filter( 'manage_edit-orbis_person_columns', $this->edit_columns( ... ) );

		\add_action( 'manage_orbis_person_posts_custom_column', $this->custom_columns( ... ), 10, 2 );

		\add_action( 'add_meta_boxes', $this->add_meta_boxes( ... ), 20 );

		\add_action( 'save_post_orbis_person', $this->save_person( ... ) );
		\add_action( 'save_post_orbis_person', $this->save_person_sync( ... ), 500, 2 );
	}

	/**
	 * Edit columns.
	 *
	 * @return array<string, string>
	 */
	private function edit_columns(): array {
		return [
			'cb'                        => '<input type="checkbox" />',
			'title'                     => \__( 'Title', 'orbis-persons' ),
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

		$fields = [
			'_orbis_title'             => \sanitize_text_field( ... ),
			'_orbis_organization'      => \sanitize_text_field( ... ),
			'_orbis_department'        => \sanitize_text_field( ... ),
			'_orbis_email'             => \sanitize_email( ... ),
			'_orbis_phone_number'      => \sanitize_text_field( ... ),
			'_orbis_mobile_number'     => \sanitize_text_field( ... ),
			'_orbis_address'           => \sanitize_text_field( ... ),
			'_orbis_postcode'          => \sanitize_text_field( ... ),
			'_orbis_city'              => \sanitize_text_field( ... ),
			'_orbis_country'           => \sanitize_text_field( ... ),
			'_orbis_birth_date_string' => \sanitize_text_field( ... ),
			'_orbis_iban'              => \sanitize_text_field( ... ),
			'_orbis_twitter'           => \sanitize_text_field( ... ),
			'_orbis_facebook'          => \sanitize_text_field( ... ),
			'_orbis_linkedin'          => \sanitize_text_field( ... ),
		];

		foreach ( $fields as $key => $sanitize ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by the field specific callback.
			$value = \array_key_exists( $key, $_POST ) && \is_string( $_POST[ $key ] ) ? $sanitize( \wp_unslash( $_POST[ $key ] ) ) : '';

			if ( '' === $value ) {
				\delete_post_meta( $post_id, $key );
			} else {
				\update_post_meta( $post_id, $key, $value );
			}
		}

		$this->save_birth_date( $post_id );
	}

	/**
	 * Save birth date.
	 *
	 * The birth date is entered as `d-m-Y` string, `_orbis_birth_date` and
	 * `_orbis_birth_date_timestamp` are derived for sorting and querying.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function save_birth_date( int $post_id ): void {
		$date = DateTimeImmutable::createFromFormat( '!d-m-Y', $this->get_meta( $post_id, '_orbis_birth_date_string' ) );

		if ( false === $date ) {
			\delete_post_meta( $post_id, '_orbis_birth_date' );
			\delete_post_meta( $post_id, '_orbis_birth_date_timestamp' );

			return;
		}

		\update_post_meta( $post_id, '_orbis_birth_date', $date->format( 'Y-m-d' ) );
		\update_post_meta( $post_id, '_orbis_birth_date_timestamp', $date->getTimestamp() );
	}

	/**
	 * Sync person with Orbis tables.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	private function save_person_sync( int $post_id, WP_Post $post ): void {
		global $wpdb;

		/**
		 * WordPress database abstraction object.
		 *
		 * @var \wpdb $wpdb
		 */

		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( \wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$email = $this->get_meta( $post_id, '_orbis_email' );

		$email = '' === $email ? null : $email;

		$orbis_id = \get_post_meta( $post_id, '_orbis_person_id', true );

		$now = \current_time( 'mysql', true );

		if ( ! empty( $orbis_id ) ) {
			$wpdb->update(
				$wpdb->prefix . 'orbis_persons',
				[
					'name'       => $post->post_title,
					'email'      => $email,
					'updated_at' => $now,
				],
				[ 'id' => $orbis_id ],
				[ '%s', '%s', '%s' ],
				[ '%d' ]
			);

			return;
		}

		$result = $wpdb->insert(
			$wpdb->prefix . 'orbis_persons',
			[
				'post_id'    => $post_id,
				'name'       => $post->post_title,
				'email'      => $email,
				'created_at' => $now,
				'updated_at' => $now,
			],
			[
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		if ( false !== $result ) {
			\update_post_meta( $post_id, '_orbis_person_id', $wpdb->insert_id );
		}
	}
}
