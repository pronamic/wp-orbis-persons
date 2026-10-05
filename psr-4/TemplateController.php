<?php
/**
 * Template controller
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

/**
 * Template controller class
 */
final class TemplateController {
	/**
	 * Construct.
	 */
	public function __construct() {
		\add_filter( 'template_include', $this->template_include( ... ) );
	}

	/**
	 * Template include.
	 *
	 * Uses the single and archive person templates of this plugin, unless the theme has one.
	 *
	 * @param string $template Template.
	 * @return string
	 */
	private function template_include( $template ) {
		if ( \is_singular( 'orbis_person' ) && '' === \locate_template( 'single-orbis_person.php' ) ) {
			return __DIR__ . '/../templates/single-orbis_person.php';
		}

		if ( \is_post_type_archive( 'orbis_person' ) && '' === \locate_template( 'archive-orbis_person.php' ) ) {
			return __DIR__ . '/../templates/archive-orbis_person.php';
		}

		return $template;
	}
}
