<?php
/**
 * Person repository
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

use DateTimeImmutable;
use Pronamic\Orbis\Contacts\ContactsTable;
use WP_Error;
use WP_Post;
use WP_Term;

/**
 * Person repository class
 *
 * The data access layer for persons, used by the admin interface and the
 * abilities.
 *
 * @phpstan-type PersonData array{
 *     name?: string,
 *     content?: string,
 *     gender?: string,
 *     birth_day?: int|string|null,
 *     birth_month?: int|string|null,
 *     birth_year?: int|string|null,
 *     job_title?: string,
 *     organization?: string,
 *     department?: string,
 *     email?: string,
 *     phone_number?: string,
 *     mobile_number?: string,
 *     address?: string,
 *     postcode?: string,
 *     city?: string,
 *     country?: string,
 *     iban?: string,
 *     twitter?: string,
 *     facebook?: string,
 *     linkedin?: string
 * }
 */
final readonly class PersonRepository {
	/**
	 * Get the meta keys of the person fields that are stored as post meta.
	 *
	 * @return array<string, string> Field key => meta key.
	 */
	public function get_meta_keys(): array {
		return [
			'job_title'     => '_orbis_title',
			'organization'  => '_orbis_organization',
			'department'    => '_orbis_department',
			'email'         => '_orbis_email',
			'phone_number'  => '_orbis_phone_number',
			'mobile_number' => '_orbis_mobile_number',
			'address'       => '_orbis_address',
			'postcode'      => '_orbis_postcode',
			'city'          => '_orbis_city',
			'country'       => '_orbis_country',
			'iban'          => '_orbis_iban',
			'twitter'       => '_orbis_twitter',
			'facebook'      => '_orbis_facebook',
			'linkedin'      => '_orbis_linkedin',
		];
	}

	/**
	 * Get a person by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return Person|null
	 */
	public function get( int $post_id ): ?Person {
		$post = \get_post( $post_id );

		if ( ! $post instanceof WP_Post || 'orbis_person' !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}

		return Person::from_post( $post );
	}

	/**
	 * Search persons.
	 *
	 * @param array{search?: string, gender?: string, orderby?: string, order?: string, per_page?: int, page?: int} $args {
	 *     Arguments.
	 *
	 *     @type string $search   Search term, matched against the name, the email addresses, the organization and the phone numbers.
	 *     @type string $gender   Gender term ID, slug or name.
	 *     @type string $orderby  Order by `name`, `created`, `modified` or `birthday` (upcoming birthdays first).
	 *     @type string $order    Order, `asc` or `desc`, not used for `birthday`.
	 *     @type int    $per_page Number of persons per page.
	 *     @type int    $page     Page.
	 * }
	 * @return array{total: int, page: int, per_page: int, persons: Person[]}|WP_Error
	 */
	public function search( array $args = [] ): array|WP_Error {
		global $wpdb;

		/**
		 * WordPress database abstraction object.
		 *
		 * @var \wpdb $wpdb
		 */

		$conditions = [
			"post.post_type = 'orbis_person'",
			"post.post_status = 'publish'",
		];

		$search = \trim( $args['search'] ?? '' );

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';

			$conditions[] = (string) $wpdb->prepare(
				<<<'SQL'
					(
						post.post_title LIKE %s
							OR
						post.ID IN (
							SELECT
								post_id
							FROM
								%i
							WHERE
								meta_key IN ( '_orbis_email', '_orbis_contact_email', '_orbis_organization', '_orbis_phone_number', '_orbis_mobile_number' )
									AND
								meta_value LIKE %s
						)
					)
					SQL,
				$like,
				$wpdb->postmeta,
				$like
			);
		}

		$gender = $args['gender'] ?? '';

		if ( '' !== $gender ) {
			$term = $this->get_gender_term( $gender );

			if ( $term instanceof WP_Error ) {
				return $term;
			}

			$conditions[] = (string) $wpdb->prepare(
				'post.ID IN ( SELECT object_id FROM %i WHERE term_taxonomy_id = %d )',
				$wpdb->term_relationships,
				$term->term_taxonomy_id
			);
		}

		$orderby = $args['orderby'] ?? 'name';

		$order = $args['order'] ?? ( 'name' === $orderby ? 'asc' : 'desc' );

		$order = 'asc' === $order ? 'ASC' : 'DESC';

		$today = new DateTimeImmutable( 'today', \wp_timezone() );

		$order_sql = match ( $orderby ) {
			'created'  => "post.post_date $order, post.ID $order",
			'modified' => "post.post_modified $order, post.ID $order",
			'birthday' => (string) $wpdb->prepare( 'birthday.meta_value IS NULL, birthday.meta_value < %s, birthday.meta_value, post.post_title', $today->format( 'm-d' ) ),
			default    => "post.post_title $order, post.ID $order",
		};

		$per_page = \max( 1, \min( 100, $args['per_page'] ?? 50 ) );
		$page     = \max( 1, $args['page'] ?? 1 );
		$offset   = ( $page - 1 ) * $per_page;

		$from = <<<SQL
			$wpdb->posts AS post
				LEFT JOIN
			$wpdb->postmeta AS birthday
					ON birthday.post_id = post.ID AND birthday.meta_key = '_orbis_birthday'
			SQL;

		$where = \implode( ' AND ', $conditions );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The conditions and order are prepared above, the limit and offset are integers.
		$total = (int) $wpdb->get_var( "SELECT COUNT( post.ID ) FROM $from WHERE $where;" );

		$post_ids = \array_map( \intval( ... ), $wpdb->get_col( "SELECT post.ID FROM $from WHERE $where ORDER BY $order_sql LIMIT $per_page OFFSET $offset;" ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		\_prime_post_caches( $post_ids );

		$persons = [];

		foreach ( $post_ids as $post_id ) {
			$person = $this->get( $post_id );

			if ( null !== $person ) {
				$persons[] = $person;
			}
		}

		return [
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'persons'  => $persons,
		];
	}

	/**
	 * Create a person.
	 *
	 * The data is validated before the person is created.
	 *
	 * @param array<string, mixed> $data Data, see `PersonData`.
	 * @return Person|WP_Error
	 */
	public function create( array $data ): Person|WP_Error {
		$prepared = $this->prepare( $data, null );

		if ( $prepared instanceof WP_Error ) {
			return $prepared;
		}

		$post_id = \wp_insert_post(
			\wp_slash(
				[
					'post_type'    => 'orbis_person',
					'post_status'  => 'publish',
					'post_title'   => $prepared['post']['post_title'] ?? '',
					'post_content' => $prepared['post']['post_content'] ?? '',
				]
			),
			true
		);

		if ( $post_id instanceof WP_Error ) {
			return $post_id;
		}

		$this->save( $post_id, $prepared );

		/*
		 * WordPress only saves revisions when updating a post, so save the
		 * initial revision, including the revisioned meta.
		 */
		\wp_save_post_revision( $post_id );

		return $this->get( $post_id ) ?? $this->not_found_error( $post_id );
	}

	/**
	 * Update a person.
	 *
	 * Only the given keys are updated, an empty value clears the field. The
	 * data is validated before the person is updated.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    Data, see `PersonData`.
	 * @return Person|WP_Error
	 */
	public function update( int $post_id, array $data ): Person|WP_Error {
		$person = $this->get( $post_id );

		if ( null === $person ) {
			return $this->not_found_error( $post_id );
		}

		$prepared = $this->prepare( $data, $person );

		if ( $prepared instanceof WP_Error ) {
			return $prepared;
		}

		$this->save( $post_id, $prepared );

		/*
		 * Always update the post after saving the meta, also when only meta
		 * changed. The revision is saved on `wp_after_insert_post` and then
		 * includes the new revisioned meta, and the modified date is updated.
		 */
		$result = \wp_update_post( \wp_slash( [ 'ID' => $post_id ] + $prepared['post'] ), true );

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		return $this->get( $post_id ) ?? $this->not_found_error( $post_id );
	}

	/**
	 * Sanitize the given string fields of person data.
	 *
	 * @param array<string, mixed> $data Data.
	 * @return array<string, string>
	 */
	public function sanitize( array $data ): array {
		$sanitized = [];

		$keys = \array_merge( [ 'name', 'content', 'gender' ], \array_keys( $this->get_meta_keys() ) );

		foreach ( $keys as $key ) {
			if ( ! \array_key_exists( $key, $data ) ) {
				continue;
			}

			$value = \is_scalar( $data[ $key ] ) ? (string) $data[ $key ] : '';

			$sanitized[ $key ] = match ( $key ) {
				'content' => \wp_kses_post( $value ),
				'email'   => \sanitize_email( $value ),
				default   => \sanitize_text_field( $value ),
			};
		}

		return $sanitized;
	}

	/**
	 * Save the given person fields as post meta.
	 *
	 * @param int                   $post_id Post ID.
	 * @param array<string, string> $data    Sanitized data.
	 * @return void
	 */
	public function save_meta( int $post_id, array $data ): void {
		foreach ( $this->get_meta_keys() as $key => $meta_key ) {
			if ( ! \array_key_exists( $key, $data ) ) {
				continue;
			}

			if ( '' === $data[ $key ] ) {
				\delete_post_meta( $post_id, $meta_key );

				continue;
			}

			\update_post_meta( $post_id, $meta_key, \wp_slash( $data[ $key ] ) );
		}
	}

	/**
	 * Sync the person with the `{prefix}orbis_persons` table.
	 *
	 * Only published persons are synced. The Orbis Contacts plugin inserts
	 * the contact row on `wp_after_insert_post` with priority 10, so this
	 * runs afterwards.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function sync_table( int $post_id ): void {
		global $wpdb;

		/**
		 * WordPress database abstraction object.
		 *
		 * @var \wpdb $wpdb
		 */

		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post = \get_post( $post_id );

		if ( ! $post instanceof WP_Post || 'orbis_person' !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		$email = \get_post_meta( $post_id, '_orbis_email', true );

		$data = [
			'name'       => $post->post_title,
			'email'      => \is_string( $email ) && '' !== $email ? $email : null,
			'updated_at' => \current_time( 'mysql', true ),
		];

		$format = [ '%s', '%s', '%s' ];

		if ( \class_exists( ContactsTable::class ) ) {
			$contact_id = ContactsTable::get_contact_id( $post_id );

			if ( null !== $contact_id ) {
				$data['contact_id'] = $contact_id;

				$format[] = '%d';
			}
		}

		$orbis_id = \get_post_meta( $post_id, '_orbis_person_id', true );

		$table = $wpdb->prefix . 'orbis_persons';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The Orbis persons table is a custom table.
		if ( \is_string( $orbis_id ) && \ctype_digit( $orbis_id ) ) {
			$wpdb->update( $table, $data, [ 'id' => (int) $orbis_id ], $format, [ '%d' ] );

			return;
		}

		$result = $wpdb->insert(
			$table,
			[
				'post_id'    => $post_id,
				'created_at' => $data['updated_at'],
			] + $data,
			\array_merge( [ '%d', '%s' ], $format )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false !== $result ) {
			\update_post_meta( $post_id, '_orbis_person_id', $wpdb->insert_id );
		}
	}

	/**
	 * Sanitize and validate person data.
	 *
	 * @param array<string, mixed> $data   Data.
	 * @param Person|null          $person Current person, `null` when creating a person.
	 * @return array{post: array{post_title?: string, post_content?: string}, meta: array<string, string>, gender?: WP_Term|null, birth_date?: BirthDate}|WP_Error
	 */
	private function prepare( array $data, ?Person $person ): array|WP_Error {
		$sanitized = $this->sanitize( $data );

		$prepared = [
			'post' => [],
			'meta' => \array_intersect_key( $sanitized, $this->get_meta_keys() ),
		];

		if ( \array_key_exists( 'name', $sanitized ) || null === $person ) {
			$name = \trim( $sanitized['name'] ?? '' );

			if ( '' === $name ) {
				return new WP_Error( 'orbis_persons_missing_name', \__( 'The name is required.', 'orbis-persons' ), [ 'status' => 400 ] );
			}

			$prepared['post']['post_title'] = $name;
		}

		if ( \array_key_exists( 'content', $sanitized ) ) {
			$prepared['post']['post_content'] = $sanitized['content'];
		}

		$email = $data['email'] ?? '';

		if ( \is_string( $email ) && '' !== \trim( $email ) && ! \is_email( $sanitized['email'] ?? '' ) ) {
			return new WP_Error(
				'orbis_persons_invalid_email',
				/* translators: %s: email address */
				\sprintf( \__( 'The email address "%s" is invalid.', 'orbis-persons' ), $email ),
				[ 'status' => 400 ]
			);
		}

		if ( \array_key_exists( 'gender', $sanitized ) ) {
			$gender = null;

			if ( '' !== $sanitized['gender'] ) {
				$gender = $this->get_gender_term( $sanitized['gender'] );

				if ( $gender instanceof WP_Error ) {
					return $gender;
				}
			}

			$prepared['gender'] = $gender;
		}

		$birth_keys = [
			'birth_day'   => $person?->birth_date?->day,
			'birth_month' => $person?->birth_date?->month,
			'birth_year'  => $person?->birth_date?->year,
		];

		if ( [] !== \array_intersect_key( $data, $birth_keys ) ) {
			$parts = [];

			foreach ( $birth_keys as $key => $current ) {
				$value = \array_key_exists( $key, $data ) ? $data[ $key ] : $current;

				$parts[] = \is_int( $value ) || \is_string( $value ) ? (string) $value : '';
			}

			$birth_date = BirthDate::from_input( ...$parts );

			if ( null === $birth_date ) {
				return new WP_Error(
					'orbis_persons_invalid_birth_date',
					/* translators: 1: day, 2: month, 3: year */
					\sprintf( \__( 'The birth date (day "%1$s", month "%2$s", year "%3$s") is invalid. Each part is optional, but a day requires a month and the date cannot be in the future.', 'orbis-persons' ), ...$parts ),
					[ 'status' => 400 ]
				);
			}

			$prepared['birth_date'] = $birth_date;
		}

		return $prepared;
	}

	/**
	 * Save prepared person data, except for the post fields.
	 *
	 * @param int                                                                                                                                        $post_id  Post ID.
	 * @param array{post: array{post_title?: string, post_content?: string}, meta: array<string, string>, gender?: WP_Term|null, birth_date?: BirthDate} $prepared Prepared data.
	 * @return void
	 */
	private function save( int $post_id, array $prepared ): void {
		$this->save_meta( $post_id, $prepared['meta'] );

		if ( \array_key_exists( 'gender', $prepared ) ) {
			\wp_set_object_terms( $post_id, null === $prepared['gender'] ? [] : [ $prepared['gender']->term_id ], 'orbis_gender' );
		}

		if ( \array_key_exists( 'birth_date', $prepared ) ) {
			$prepared['birth_date']->save( $post_id );
		}

		/*
		 * When creating a person, the `wp_after_insert_post` sync runs before
		 * the meta is saved, so sync again with the saved meta.
		 */
		$this->sync_table( $post_id );
	}

	/**
	 * Get gender term by ID, slug or name.
	 *
	 * @param string $value Term ID, slug or name.
	 * @return WP_Term|WP_Error
	 */
	private function get_gender_term( string $value ): WP_Term|WP_Error {
		$value = \trim( $value );

		$term = \ctype_digit( $value ) ? \get_term_by( 'id', (int) $value, 'orbis_gender' ) : \get_term_by( 'slug', \sanitize_title( $value ), 'orbis_gender' );

		if ( ! $term instanceof WP_Term ) {
			$term = \get_term_by( 'name', $value, 'orbis_gender' );
		}

		if ( $term instanceof WP_Term ) {
			return $term;
		}

		$names = \get_terms(
			[
				'taxonomy'   => 'orbis_gender',
				'hide_empty' => false,
				'fields'     => 'names',
			]
		);

		return new WP_Error(
			'orbis_persons_gender_not_found',
			\sprintf(
				/* translators: 1: gender, 2: available genders */
				\__( 'Gender "%1$s" not found. Available genders: %2$s.', 'orbis-persons' ),
				$value,
				\is_array( $names ) ? \implode( ', ', \array_map( \strval( ... ), $names ) ) : ''
			),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Get not found error.
	 *
	 * @param int $post_id Post ID.
	 * @return WP_Error
	 */
	private function not_found_error( int $post_id ): WP_Error {
		return new WP_Error(
			'orbis_persons_person_not_found',
			/* translators: %d: post ID */
			\sprintf( \__( 'Person with post ID %d not found.', 'orbis-persons' ), $post_id ),
			[ 'status' => 404 ]
		);
	}
}
