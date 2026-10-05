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

		new ContentTypes();

		if ( \is_admin() ) {
			new AdminPersonPostType();
		}
	}

	/**
	 * Activate.
	 *
	 * @return void
	 */
	private function activate(): void {
		\delete_option( 'rewrite_rules' );
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init(): void {
		\load_plugin_textdomain( 'orbis-persons', false, \dirname( \plugin_basename( $this->plugin_file ) ) . '/languages' );
	}
}
