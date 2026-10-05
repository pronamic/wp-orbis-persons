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
		\register_activation_hook( $this->plugin_file, $this->activate( ... ) );

		\add_action( 'init', $this->init( ... ), 0 );
		\add_action( 'wp_after_insert_post', $this->sync_contact_id( ... ), 20, 2 );

		new ContentTypes();

		if ( \is_admin() ) {
			new AdminPersonPostType();
		}
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init(): void {
		$version = '1.1.0';

		if ( \get_option( 'orbis_persons_db_version' ) !== $version ) {
			$this->install();

			$this->add_foreign_keys();

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
	 * Sync contact ID.
	 *
	 * The Orbis Contacts plugin inserts the contact row on
	 * `wp_after_insert_post` with priority 10, so this runs afterwards.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	private function sync_contact_id( int $post_id, \WP_Post $post ): void {
		global $wpdb;

		/**
		 * WordPress database abstraction object.
		 *
		 * @var \wpdb $wpdb
		 */

		if ( 'orbis_person' !== $post->post_type ) {
			return;
		}

		if ( ! \class_exists( \Pronamic\Orbis\Contacts\ContactsTable::class ) ) {
			return;
		}

		$contact_id = \Pronamic\Orbis\Contacts\ContactsTable::get_contact_id( $post_id );

		if ( null === $contact_id ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . 'orbis_persons',
			[ 'contact_id' => $contact_id ],
			[ 'post_id' => $post_id ],
			[ '%d' ],
			[ '%d' ]
		);
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
