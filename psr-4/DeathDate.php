<?php
/**
 * Death date
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

/**
 * Death date class
 *
 * A death date of which the day, month and year are each optional, just
 * like the birth date. A person with a death date is deceased.
 */
final readonly class DeathDate {
	/**
	 * Construct.
	 *
	 * @param int|null $day   Day of the month (1-31).
	 * @param int|null $month Month (1-12).
	 * @param int|null $year  Year.
	 */
	public function __construct(
		public ?int $day,
		public ?int $month,
		public ?int $year
	) {
	}

	/**
	 * Create death date from user input.
	 *
	 * The same rules as for the birth date apply: each part is optional, a
	 * day without month is invalid and the date cannot be in the future.
	 *
	 * @param string $day   Day.
	 * @param string $month Month.
	 * @param string $year  Year.
	 * @return self|null Death date or `null` if the input is invalid.
	 */
	public static function from_input( string $day, string $month, string $year ): ?self {
		$date = BirthDate::from_input( $day, $month, $year );

		return null === $date ? null : new self( $date->day, $date->month, $date->year );
	}

	/**
	 * Create death date from post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return self
	 */
	public static function from_post( int $post_id ): self {
		$get_meta = static function ( string $key ) use ( $post_id ): string {
			$value = \get_post_meta( $post_id, $key, true );

			return \is_string( $value ) ? $value : '';
		};

		return self::from_input(
			$get_meta( '_orbis_death_day' ),
			$get_meta( '_orbis_death_month' ),
			$get_meta( '_orbis_death_year' )
		) ?? new self( null, null, null );
	}

	/**
	 * Save death date to post meta.
	 *
	 * Besides the day, month and year, the full date (`Y-m-d`) is stored for
	 * querying and sorting.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( int $post_id ): void {
		$values = [
			'_orbis_death_day'   => $this->day,
			'_orbis_death_month' => $this->month,
			'_orbis_death_year'  => $this->year,
			'_orbis_death_date'  => ( new BirthDate( $this->day, $this->month, $this->year ) )->get_date()?->format( 'Y-m-d' ),
		];

		foreach ( $values as $key => $value ) {
			if ( null === $value ) {
				\delete_post_meta( $post_id, $key );
			} else {
				\update_post_meta( $post_id, $key, (string) $value );
			}
		}
	}

	/**
	 * Check if nothing of the death date is known.
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return null === $this->day && null === $this->month && null === $this->year;
	}

	/**
	 * Check if the death date is certainly before the birth date.
	 *
	 * Unknown parts get the benefit of the doubt, without both years the
	 * dates cannot be compared.
	 *
	 * @param BirthDate $birth_date Birth date.
	 * @return bool
	 */
	public function is_before( BirthDate $birth_date ): bool {
		if ( null === $this->year || null === $birth_date->year ) {
			return false;
		}

		return [ $this->year, $this->month ?? 12, $this->day ?? 31 ] < [ $birth_date->year, $birth_date->month ?? 1, $birth_date->day ?? 1 ];
	}

	/**
	 * Format the known parts of the death date.
	 *
	 * @return string
	 */
	public function format(): string {
		return ( new BirthDate( $this->day, $this->month, $this->year ) )->format();
	}
}
