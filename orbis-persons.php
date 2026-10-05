<?php
/**
 * Orbis Persons
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 *
 * @wordpress-plugin
 * Plugin Name:       Orbis Persons
 * Plugin URI:        https://wp.pronamic.directory/plugins/orbis-persons/
 * Description:       WordPress plugin for Orbis that adds persons, with personal details such as birth date and gender, built on top of Orbis Contacts.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.3
 * Requires Plugins:  orbis-contacts
 * Author:            Pronamic
 * Author URI:        https://www.pronamic.eu/
 * Text Domain:       orbis-persons
 * Domain Path:       /languages/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://wp.pronamic.directory/plugins/orbis-persons/
 * GitHub URI:        https://github.com/pronamic/wp-orbis-persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

( static function (): void {
	$autoload_path = __DIR__ . '/vendor/autoload_packages.php';

	if ( \file_exists( $autoload_path ) ) {
		require_once $autoload_path;
	}

	Plugin::instance( __FILE__ );
} )();
