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
use Pronamic\Orbis\Contacts\PhoneNumbers;
use WP_Error;
use WP_Post;

/**
 * Person repository class
 *
 * The data access layer for persons, used by the admin interface and the
 * abilities.
 *
 * @phpstan-type PersonData array{
 *     display_name?: string,
 *     first_name?: string,
 *     last_name?: string,
 *     maiden_name?: string,
 *     content?: string,
 *     gender?: string,
 *     birth_day?: int|string|null,
 *     birth_month?: int|string|null,
 *     birth_year?: int|string|null,
 *     death_day?: int|string|null,
 *     death_month?: int|string|null,
 *     death_year?: int|string|null,
 *     father_post_id?: int|string|null,
 *     mother_post_id?: int|string|null,
 *     job_title?: string,
 *     organization?: string,
 *     department?: string,
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
			'first_name'   => '_orbis_first_name',
			'last_name'    => '_orbis_last_name',
			'maiden_name'  => '_orbis_maiden_name',
			'job_title'    => '_orbis_title',
			'organization' => '_orbis_organization',
			'department'   => '_orbis_department',
			'address'      => '_orbis_address',
			'postcode'     => '_orbis_postcode',
			'city'         => '_orbis_city',
			'country'      => '_orbis_country',
			'iban'         => '_orbis_iban',
			'twitter'      => '_orbis_twitter',
			'facebook'     => '_orbis_facebook',
			'linkedin'     => '_orbis_linkedin',
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
	 *     @type string $search   Search term, matched against the display name, the first names, the last names, the email addresses, the organization and the phone numbers.
	 *     @type string $gender   Gender, `male` or `female`.
	 *     @type string $orderby  Order by `name`, `created`, `modified` or `birthday` (upcoming birthdays first, deceased persons last).
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

			/*
			 * Phone numbers are stored in E.164, so a search term like
			 * `06 12345678` is standardized to find `+31612345678`.
			 */
			$e164 = \class_exists( PhoneNumbers::class ) ? PhoneNumbers::to_e164( $search ) : null;

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
								(
									meta_key IN ( '_orbis_first_name', '_orbis_last_name', '_orbis_maiden_name', '_orbis_contact_email', '_orbis_organization', '_orbis_contact_phone' )
										AND
									meta_value LIKE %s
								)
									OR
								(
									meta_key = '_orbis_contact_phone'
										AND
									meta_value = %s
								)
						)
					)
					SQL,
				$like,
				$wpdb->postmeta,
				$like,
				$e164 ?? ''
			);
		}

		$gender = $args['gender'] ?? '';

		if ( '' !== $gender ) {
			$gender = $this->get_gender( $gender );

			if ( $gender instanceof WP_Error ) {
				return $gender;
			}

			$conditions[] = (string) $wpdb->prepare(
				"post.ID IN ( SELECT post_id FROM %i WHERE meta_key = '_orbis_gender' AND meta_value = %s )",
				$wpdb->postmeta,
				$gender->value
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
					ON (
						birthday.post_id = post.ID
							AND
						birthday.meta_key = '_orbis_birthday'
							AND
						NOT EXISTS (
							SELECT
								1
							FROM
								$wpdb->postmeta AS death
							WHERE
								death.post_id = post.ID
									AND
								death.meta_key IN ( '_orbis_death_day', '_orbis_death_month', '_orbis_death_year' )
						)
					)
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

		$keys = \array_merge( [ 'display_name', 'content', 'gender' ], \array_keys( $this->get_meta_keys() ) );

		foreach ( $keys as $key ) {
			if ( ! \array_key_exists( $key, $data ) ) {
				continue;
			}

			$value = \is_scalar( $data[ $key ] ) ? (string) $data[ $key ] : '';

			$sanitized[ $key ] = match ( $key ) {
				'content' => \wp_kses_post( $value ),
				default   => \sanitize_text_field( $value ),
			};
		}

		return $sanitized;
	}

	/**
	 * Compose a display name from the first names and last names.
	 *
	 * @param string $first_name First names.
	 * @param string $last_name  Last names.
	 * @return string Display name, empty when both are empty.
	 */
	public function compose_display_name( string $first_name, string $last_name ): string {
		return \implode( ' ', \array_filter( [ \trim( $first_name ), \trim( $last_name ) ], static fn ( string $part ): bool => '' !== $part ) );
	}

	/**
	 * Validate the post IDs of the father and mother.
	 *
	 * The father cannot be a woman and the mother cannot be a man, a parent
	 * without a known gender is allowed.
	 *
	 * @param mixed    $father_post_id Post ID of the father, empty, `null` or `0` for no father.
	 * @param mixed    $mother_post_id Post ID of the mother, empty, `null` or `0` for no mother.
	 * @param int|null $child_id  Post ID of the child, `null` when creating a person.
	 * @return array{father_post_id: int, mother_post_id: int}|WP_Error Post IDs, `0` for no parent.
	 */
	public function validate_parents( mixed $father_post_id, mixed $mother_post_id, ?int $child_id ): array|WP_Error {
		$father_post_id = $this->validate_parent_id( $father_post_id, $child_id, Gender::Male );

		if ( $father_post_id instanceof WP_Error ) {
			return $father_post_id;
		}

		$mother_post_id = $this->validate_parent_id( $mother_post_id, $child_id, Gender::Female );

		if ( $mother_post_id instanceof WP_Error ) {
			return $mother_post_id;
		}

		if ( 0 !== $father_post_id && $father_post_id === $mother_post_id ) {
			return new WP_Error( 'orbis_persons_same_parents', \__( 'The father and mother cannot be the same person.', 'orbis-persons' ), [ 'status' => 400 ] );
		}

		return [
			'father_post_id' => $father_post_id,
			'mother_post_id' => $mother_post_id,
		];
	}

	/**
	 * Save the post IDs of the father and mother.
	 *
	 * @param int                                             $post_id Post ID of the child.
	 * @param array{father_post_id: int, mother_post_id: int} $parents Validated post IDs, `0` for no parent.
	 * @return void
	 */
	public function save_parents( int $post_id, array $parents ): void {
		$meta = [
			'_orbis_person_father_post_id' => $parents['father_post_id'],
			'_orbis_person_mother_post_id' => $parents['mother_post_id'],
		];

		foreach ( $meta as $meta_key => $parent_id ) {
			if ( 0 === $parent_id ) {
				\delete_post_meta( $post_id, $meta_key );

				continue;
			}

			\update_post_meta( $post_id, $meta_key, $parent_id );
		}
	}

	/**
	 * Validate the post ID of a father or mother.
	 *
	 * @param mixed    $value    Post ID of the parent, empty, `null` or `0` to remove the parent.
	 * @param int|null $child_id Post ID of the child, `null` when creating a person.
	 * @param Gender   $gender   Gender of the parent, `Gender::Male` for the father and `Gender::Female` for the mother.
	 * @return int|WP_Error Post ID of the parent, `0` to remove the parent.
	 */
	private function validate_parent_id( mixed $value, ?int $child_id, Gender $gender ): int|WP_Error {
		if ( null === $value || '' === $value || 0 === $value || '0' === $value ) {
			return 0;
		}

		if ( ! \is_int( $value ) && ! ( \is_string( $value ) && \ctype_digit( $value ) ) ) {
			return new WP_Error(
				'orbis_persons_invalid_parent',
				/* translators: %s: value */
				\sprintf( \__( 'The father or mother "%s" is not a valid post ID of a person.', 'orbis-persons' ), \is_scalar( $value ) ? (string) $value : '' ),
				[ 'status' => 400 ]
			);
		}

		$parent_id = (int) $value;

		if ( $parent_id === $child_id ) {
			return new WP_Error( 'orbis_persons_own_parent', \__( 'A person cannot be their own father or mother.', 'orbis-persons' ), [ 'status' => 400 ] );
		}

		$parent = $this->get( $parent_id );

		if ( null === $parent ) {
			return new WP_Error(
				'orbis_persons_parent_not_found',
				/* translators: %d: post ID */
				\sprintf( \__( 'The father or mother with post ID %d is not found.', 'orbis-persons' ), $parent_id ),
				[ 'status' => 400 ]
			);
		}

		if ( null !== $parent->gender && $gender !== $parent->gender ) {
			return match ( $gender ) {
				Gender::Male   => new WP_Error(
					'orbis_persons_father_not_male',
					/* translators: %s: display name */
					\sprintf( \__( 'The father cannot be a woman, "%s" is female.', 'orbis-persons' ), (string) $parent->display_name ),
					[ 'status' => 400 ]
				),
				Gender::Female => new WP_Error(
					'orbis_persons_mother_not_female',
					/* translators: %s: display name */
					\sprintf( \__( 'The mother cannot be a man, "%s" is male.', 'orbis-persons' ), (string) $parent->display_name ),
					[ 'status' => 400 ]
				),
			};
		}

		return $parent_id;
	}

	/**
	 * Get the children of a person, the persons with this person as father or mother.
	 *
	 * @param int $post_id Post ID of the parent.
	 * @return Person[]
	 */
	public function get_children( int $post_id ): array {
		$posts = \get_posts(
			[
				'post_type'      => 'orbis_person',
				'post_status'    => 'publish',
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page, WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- A person has few children, all of them are shown.
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The children are queried by the parent meta.
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'   => '_orbis_person_father_post_id',
						'value' => $post_id,
						'type'  => 'NUMERIC',
					],
					[
						'key'   => '_orbis_person_mother_post_id',
						'value' => $post_id,
						'type'  => 'NUMERIC',
					],
				],
			]
		);

		return \array_map( Person::from_post( ... ), $posts );
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

		/*
		 * Until Orbis Contacts has a "primary" email address, the first
		 * email address is used.
		 */
		$email = Person::from_post( $post )->email_addresses[0]['email'] ?? null;

		$data = [
			'name'       => $post->post_title,
			'email'      => $email,
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
	 * @return array{post: array{post_title?: string, post_content?: string}, meta: array<string, string>, gender?: Gender|null, birth_date?: BirthDate, death_date?: DeathDate, parents?: array{father_post_id: int, mother_post_id: int}}|WP_Error
	 */
	private function prepare( array $data, ?Person $person ): array|WP_Error {
		$sanitized = $this->sanitize( $data );

		$prepared = [
			'post' => [],
			'meta' => \array_intersect_key( $sanitized, $this->get_meta_keys() ),
		];

		if ( \array_key_exists( 'display_name', $sanitized ) || null === $person ) {
			$display_name = \trim( $sanitized['display_name'] ?? '' );

			if ( '' === $display_name ) {
				$display_name = $this->compose_display_name(
					$sanitized['first_name'] ?? $person->first_name ?? '',
					$sanitized['last_name'] ?? $person->last_name ?? ''
				);
			}

			if ( '' === $display_name ) {
				return new WP_Error( 'orbis_persons_missing_name', \__( 'The display name is required, or fill in the first names or last names.', 'orbis-persons' ), [ 'status' => 400 ] );
			}

			$prepared['post']['post_title'] = $display_name;
		}

		if ( \array_key_exists( 'content', $sanitized ) ) {
			$prepared['post']['post_content'] = $sanitized['content'];
		}

		if ( \array_key_exists( 'gender', $sanitized ) ) {
			$gender = null;

			if ( '' !== $sanitized['gender'] ) {
				$gender = $this->get_gender( $sanitized['gender'] );

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

		$death_keys = [
			'death_day'   => $person?->death_date?->day,
			'death_month' => $person?->death_date?->month,
			'death_year'  => $person?->death_date?->year,
		];

		if ( [] !== \array_intersect_key( $data, $death_keys ) ) {
			$parts = [];

			foreach ( $death_keys as $key => $current ) {
				$value = \array_key_exists( $key, $data ) ? $data[ $key ] : $current;

				$parts[] = \is_int( $value ) || \is_string( $value ) ? (string) $value : '';
			}

			$death_date = DeathDate::from_input( ...$parts );

			if ( null === $death_date ) {
				return new WP_Error(
					'orbis_persons_invalid_death_date',
					/* translators: 1: day, 2: month, 3: year */
					\sprintf( \__( 'The death date (day "%1$s", month "%2$s", year "%3$s") is invalid. Each part is optional, but a day requires a month and the date cannot be in the future.', 'orbis-persons' ), ...$parts ),
					[ 'status' => 400 ]
				);
			}

			$prepared['death_date'] = $death_date;
		}

		if ( \array_key_exists( 'birth_date', $prepared ) || \array_key_exists( 'death_date', $prepared ) ) {
			$birth_date = $prepared['birth_date'] ?? $person?->birth_date;
			$death_date = $prepared['death_date'] ?? $person?->death_date;

			if ( null !== $birth_date && null !== $death_date && $death_date->is_before( $birth_date ) ) {
				return new WP_Error(
					'orbis_persons_death_date_before_birth_date',
					/* translators: 1: death date, 2: birth date */
					\sprintf( \__( 'The death date (%1$s) cannot be before the birth date (%2$s).', 'orbis-persons' ), $death_date->format(), $birth_date->format() ),
					[ 'status' => 400 ]
				);
			}
		}

		if ( \array_key_exists( 'father_post_id', $data ) || \array_key_exists( 'mother_post_id', $data ) ) {
			$parents = $this->validate_parents(
				\array_key_exists( 'father_post_id', $data ) ? $data['father_post_id'] : $person?->father_post_id,
				\array_key_exists( 'mother_post_id', $data ) ? $data['mother_post_id'] : $person?->mother_post_id,
				$person?->post_id
			);

			if ( $parents instanceof WP_Error ) {
				return $parents;
			}

			$prepared['parents'] = $parents;
		}

		return $prepared;
	}

	/**
	 * Save prepared person data, except for the post fields.
	 *
	 * @param int                                                                                                                                                                                                                          $post_id  Post ID.
	 * @param array{post: array{post_title?: string, post_content?: string}, meta: array<string, string>, gender?: Gender|null, birth_date?: BirthDate, death_date?: DeathDate, parents?: array{father_post_id: int, mother_post_id: int}} $prepared Prepared data.
	 * @return void
	 */
	private function save( int $post_id, array $prepared ): void {
		$this->save_meta( $post_id, $prepared['meta'] );

		if ( \array_key_exists( 'gender', $prepared ) ) {
			$this->save_gender( $post_id, $prepared['gender'] );
		}

		if ( \array_key_exists( 'birth_date', $prepared ) ) {
			$prepared['birth_date']->save( $post_id );
		}

		if ( \array_key_exists( 'death_date', $prepared ) ) {
			$prepared['death_date']->save( $post_id );
		}

		if ( \array_key_exists( 'parents', $prepared ) ) {
			$this->save_parents( $post_id, $prepared['parents'] );
		}

		/*
		 * When creating a person, the `wp_after_insert_post` sync runs before
		 * the meta is saved, so sync again with the saved meta.
		 */
		$this->sync_table( $post_id );
	}

	/**
	 * Save the gender.
	 *
	 * @param int         $post_id Post ID.
	 * @param Gender|null $gender  Gender, `null` to remove the gender.
	 * @return void
	 */
	public function save_gender( int $post_id, ?Gender $gender ): void {
		if ( null === $gender ) {
			\delete_post_meta( $post_id, '_orbis_gender' );

			return;
		}

		\update_post_meta( $post_id, '_orbis_gender', $gender->value );
	}

	/**
	 * Get gender from input.
	 *
	 * @param string $value Gender, `male` or `female`, or a common alias like `m`, `man`, `v` or `vrouw`.
	 * @return Gender|WP_Error
	 */
	private function get_gender( string $value ): Gender|WP_Error {
		$gender = Gender::from_input( $value );

		if ( null !== $gender ) {
			return $gender;
		}

		return new WP_Error(
			'orbis_persons_invalid_gender',
			\sprintf(
				/* translators: 1: gender, 2: available genders */
				\__( 'Gender "%1$s" is invalid. Available genders: %2$s.', 'orbis-persons' ),
				$value,
				\implode( ', ', Gender::values() )
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
