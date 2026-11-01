<?php
/**
 * Gender
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

/**
 * Gender enum
 *
 * The gender of a person, stored in the `_orbis_gender` post meta.
 */
enum Gender: string {
	case Male = 'male';

	case Female = 'female';

	/**
	 * Get the label.
	 *
	 * @return string
	 */
	public function label(): string {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- Enum method, PHPCompatibility does not recognize enums.
		return match ( $this ) {
			self::Male   => \__( 'Male', 'orbis-persons' ),
			self::Female => \__( 'Female', 'orbis-persons' ),
		};
	}

	/**
	 * Get the gender from user input.
	 *
	 * Accepts the value, common English and Dutch words and abbreviations,
	 * for example `m`, `man`, `v`, `vrouw` or `f`.
	 *
	 * @param string $value Value.
	 * @return self|null
	 */
	public static function from_input( string $value ): ?self {
		$value = \strtolower( \trim( $value ) );

		return match ( $value ) {
			'male', 'm', 'man', 'men', 'mannelijk', 'heer', 'dhr', 'dhr.', 'mr', 'mr.' => self::Male,
			'female', 'f', 'v', 'woman', 'women', 'vrouw', 'vrouwelijk', 'mevrouw', 'mevr', 'mevr.', 'mw', 'mw.', 'mrs', 'mrs.', 'ms', 'ms.' => self::Female,
			default => null,
		};
	}

	/**
	 * Get the values.
	 *
	 * @return string[]
	 */
	public static function values(): array {
		return \array_column( self::cases(), 'value' );
	}
}
