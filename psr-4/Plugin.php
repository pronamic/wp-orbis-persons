<?php
/**
 * Plugin
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

/**
 * Plugin class
 */
final class Plugin {
	/**
	 * Instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Persons.
	 *
	 * @var PersonRepository
	 */
	public readonly PersonRepository $persons;

	/**
	 * Return instance of this class.
	 *
	 * @param string $plugin_file The plugin file.
	 * @return self A single instance of this class.
	 */
	public static function instance( string $plugin_file ): self {
		self::$instance ??= new self( $plugin_file );

		return self::$instance;
	}

	/**
	 * Construct.
	 *
	 * @param string $plugin_file The plugin file.
	 */
	private function __construct(
		/**
		 * Plugin file.
		 */
		private readonly string $plugin_file
	) {
		$this->persons = new PersonRepository();

		\register_activation_hook( $this->plugin_file, $this->activate( ... ) );

		\add_action( 'init', $this->init( ... ), 0 );
		\add_action( 'wp_after_insert_post', $this->after_insert_post( ... ), 20, 2 );

		new ContentTypes();
		new TemplateController();

		( new AbilitiesController( $this ) )->setup();

		if ( \is_admin() ) {
			new AdminPersonPostType( $this );
		}
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init(): void {
		$version = '1.2.0';

		if ( \get_option( 'orbis_persons_db_version' ) !== $version ) {
			$this->install();

			$this->add_foreign_keys();

			$this->migrate_birth_dates();

			\update_option( 'orbis_persons_db_version', $version );
		}
	}

	/**
	 * Install.
	 *
	 * @return void
	 */
	private function install(): void {
		global $wpdb;

		/**
		 * WordPress database abstraction object.
		 *
		 * @var \wpdb $wpdb
		 */

		$table = $wpdb->prefix . 'orbis_persons';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = <<<SQL
			CREATE TABLE $table (
				id BIGINT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id BIGINT(20) UNSIGNED DEFAULT NULL,
				contact_id BIGINT(20) UNSIGNED DEFAULT NULL,
				name VARCHAR(128) NOT NULL,
				email VARCHAR(128) DEFAULT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id),
				UNIQUE KEY contact_id (contact_id)
			) $charset_collate;
			SQL;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		\dbDelta( $sql );

		\maybe_convert_table_to_utf8mb4( $table );
	}

	/**
	 * Add foreign keys.
	 *
	 * `dbDelta` does not support foreign keys, so they are added separately
	 * when they do not exist yet. References that would violate a foreign
	 * key are cleaned up first. The contact foreign key is only added when
	 * the Orbis Contacts table exists.
	 *
	 * @return void
	 */
	private function add_foreign_keys(): void {
		global $wpdb;

		/**
		 * WordPress database abstraction object.
		 *
		 * @var \wpdb $wpdb
		 */

		$table = $wpdb->prefix . 'orbis_persons';

		$contacts_table = $wpdb->prefix . 'orbis_contacts';

		$foreign_keys = [
			[
				'name'      => $wpdb->prefix . 'orbis_persons_post_id',
				'reference' => $wpdb->posts,
				'cleanup'   => "UPDATE $table SET post_id = NULL WHERE post_id IS NOT NULL AND post_id NOT IN ( SELECT ID FROM $wpdb->posts );",
				'sql'       => "ALTER TABLE $table ADD CONSTRAINT {$wpdb->prefix}orbis_persons_post_id FOREIGN KEY ( post_id ) REFERENCES $wpdb->posts ( ID ) ON DELETE SET NULL;",
			],
			[
				'name'      => $wpdb->prefix . 'orbis_persons_contact_id',
				'reference' => $contacts_table,
				'cleanup'   => "UPDATE $table SET contact_id = NULL WHERE contact_id IS NOT NULL AND contact_id NOT IN ( SELECT id FROM $contacts_table );",
				'sql'       => "ALTER TABLE $table ADD CONSTRAINT {$wpdb->prefix}orbis_persons_contact_id FOREIGN KEY ( contact_id ) REFERENCES $contacts_table ( id ) ON DELETE SET NULL;",
			],
		];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- `dbDelta` does not support foreign keys, the queries are built from table names only.
		foreach ( $foreign_keys as $foreign_key ) {
			$reference_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $wpdb->esc_like( $foreign_key['reference'] ) ) );

			if ( null === $reference_exists ) {
				continue;
			}

			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = %s AND CONSTRAINT_NAME = %s AND CONSTRAINT_TYPE = 'FOREIGN KEY';",
					$table,
					$foreign_key['name']
				)
			);

			if ( null !== $exists ) {
				continue;
			}

			$wpdb->query( $foreign_key['cleanup'] );

			$wpdb->query( $foreign_key['sql'] );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Migrate birth dates.
	 *
	 * Birth dates used to be stored as one `d-m-Y` string, now the day, month
	 * and year are stored separately. Strings with only some parts, for
	 * example `1985` or `12-03`, are migrated as well. Strings that cannot be
	 * parsed are kept.
	 *
	 * @return void
	 */
	private function migrate_birth_dates(): void {
		global $wpdb;

		/**
		 * WordPress database abstraction object.
		 *
		 * @var \wpdb $wpdb
		 */

		$query = <<<SQL
			SELECT
				post_id
			FROM
				$wpdb->postmeta
			WHERE
				meta_key = '_orbis_birth_date_string'
			;
			SQL;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- One-time migration, the query is built from table names only.
		$post_ids = \array_map( \intval( ... ), $wpdb->get_col( $query ) );

		foreach ( $post_ids as $post_id ) {
			$value = \get_post_meta( $post_id, '_orbis_birth_date_string', true );

			if ( ! \is_string( $value ) ) {
				continue;
			}

			$value = \trim( $value );

			$patterns = [
				'/^(?<day>\d{1,2})[-\/. ](?<month>\d{1,2})[-\/. ](?<year>\d{4})$/',
				'/^(?<month>\d{1,2})[-\/. ](?<year>\d{4})$/',
				'/^(?<day>\d{1,2})[-\/. ](?<month>\d{1,2})$/',
				'/^(?<year>\d{4})$/',
			];

			$birth_date = null;

			foreach ( $patterns as $pattern ) {
				if ( 1 === \preg_match( $pattern, $value, $matches ) ) {
					$birth_date = BirthDate::from_input( $matches['day'] ?? '', $matches['month'] ?? '', $matches['year'] ?? '' );

					break;
				}
			}

			if ( null === $birth_date ) {
				continue;
			}

			$birth_date->save( $post_id );

			\delete_post_meta( $post_id, '_orbis_birth_date_string' );
		}
	}

	/**
	 * After insert post.
	 *
	 * Syncs the person with the Orbis persons table, after the Orbis
	 * Contacts plugin inserted the contact row on `wp_after_insert_post`
	 * with priority 10.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	private function after_insert_post( int $post_id, \WP_Post $post ): void {
		if ( 'orbis_person' !== $post->post_type ) {
			return;
		}

		$this->persons->sync_table( $post_id );
	}

	/**
	 * Activate.
	 *
	 * @return void
	 */
	private function activate(): void {
		\delete_option( 'rewrite_rules' );
	}
}
