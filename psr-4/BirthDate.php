<?php
/**
 * Birth date
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Birth date class
 *
 * A birth date of which the day, month and year are each optional, for
 * example only the year, only the month and year or only the birthday (day
 * and month) without year.
 */
final readonly class BirthDate {
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
	 * Create birth date from user input.
	 *
	 * Each part is optional, a day without month is invalid. A day and month
	 * without year may be 29 February.
	 *
	 * @param string $day   Day.
	 * @param string $month Month.
	 * @param string $year  Year.
	 * @return self|null Birth date or `null` if the input is invalid.
	 */
	public static function from_input( string $day, string $month, string $year ): ?self {
		$parts = [];

		foreach ( [ $day, $month, $year ] as $value ) {
			$value = \trim( $value );

			if ( '' === $value ) {
				$parts[] = null;

				continue;
			}

			if ( ! \ctype_digit( $value ) ) {
				return null;
			}

			$parts[] = (int) $value;
		}

		[ $day, $month, $year ] = $parts;

		$today = new DateTimeImmutable( 'today', \wp_timezone() );

		if ( null !== $day && null === $month ) {
			return null;
		}

		if ( null !== $month && ( $month < 1 || $month > 12 ) ) {
			return null;
		}

		if ( null !== $year && ( $year < 1 || $year > (int) $today->format( 'Y' ) ) ) {
			return null;
		}

		if ( null !== $day && ! \checkdate( $month, $day, $year ?? 2000 ) ) {
			return null;
		}

		$birth_date = new self( $day, $month, $year );

		$date = $birth_date->get_date();

		if ( null !== $date && $date > $today ) {
			return null;
		}

		return $birth_date;
	}

	/**
	 * Create birth date from post meta.
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
			$get_meta( '_orbis_birth_day' ),
			$get_meta( '_orbis_birth_month' ),
			$get_meta( '_orbis_birth_year' )
		) ?? new self( null, null, null );
	}

	/**
	 * Save birth date to post meta.
	 *
	 * Besides the day, month and year, the birthday (`m-d`) and the full
	 * date (`Y-m-d` and timestamp) are stored for querying and sorting.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( int $post_id ): void {
		$date = $this->get_date();

		$values = [
			'_orbis_birth_day'            => $this->day,
			'_orbis_birth_month'          => $this->month,
			'_orbis_birth_year'           => $this->year,
			'_orbis_birthday'             => $this->get_birthday(),
			'_orbis_birth_date'           => $date?->format( 'Y-m-d' ),
			'_orbis_birth_date_timestamp' => $date?->getTimestamp(),
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
	 * Check if nothing of the birth date is known.
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return null === $this->day && null === $this->month && null === $this->year;
	}

	/**
	 * Get the full birth date, only when day, month and year are known.
	 *
	 * @return DateTimeImmutable|null
	 */
	public function get_date(): ?DateTimeImmutable {
		if ( null === $this->day || null === $this->month || null === $this->year ) {
			return null;
		}

		return ( new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) ) )->setDate( $this->year, $this->month, $this->day );
	}

	/**
	 * Get the birthday in `m-d` format, only when day and month are known.
	 *
	 * @return string|null
	 */
	public function get_birthday(): ?string {
		if ( null === $this->day || null === $this->month ) {
			return null;
		}

		return \sprintf( '%02d-%02d', $this->month, $this->day );
	}

	/**
	 * Format the known parts of the birth date.
	 *
	 * @return string
	 */
	public function format(): string {
		global $wp_locale;

		/**
		 * WordPress locale object.
		 *
		 * @var \WP_Locale $wp_locale
		 */

		$month = null === $this->month ? '' : $wp_locale->get_month( $this->month );

		return match ( true ) {
			/* translators: 1: day, 2: month name, 3: year */
			null !== $this->day && null !== $this->year => \sprintf( \__( '%1$d %2$s %3$d', 'orbis-persons' ), $this->day, $month, $this->year ),
			/* translators: 1: day, 2: month name */
			null !== $this->day => \sprintf( \__( '%1$d %2$s', 'orbis-persons' ), $this->day, $month ),
			/* translators: 1: month name, 2: year */
			null !== $this->month && null !== $this->year => \sprintf( \__( '%1$s %2$d', 'orbis-persons' ), $month, $this->year ),
			null !== $this->year => (string) $this->year,
			default => '',
		};
	}

	/**
	 * Get the next birthday, today included.
	 *
	 * A birthday on 29 February is celebrated on 28 February in non-leap
	 * years.
	 *
	 * @param DateTimeImmutable $today Today.
	 * @return DateTimeImmutable|null
	 */
	public function get_next_birthday( DateTimeImmutable $today ): ?DateTimeImmutable {
		if ( null === $this->day || null === $this->month ) {
			return null;
		}

		$today = $today->setTime( 0, 0 );

		$year = (int) $today->format( 'Y' );

		$birthday = $this->get_birthday_in_year( $today, $year, $this->month, $this->day );

		if ( $birthday < $today ) {
			$birthday = $this->get_birthday_in_year( $today, $year + 1, $this->month, $this->day );
		}

		return $birthday;
	}

	/**
	 * Get birthday in year.
	 *
	 * @param DateTimeImmutable $today Today.
	 * @param int               $year  Year.
	 * @param int               $month Month.
	 * @param int               $day   Day.
	 * @return DateTimeImmutable
	 */
	private function get_birthday_in_year( DateTimeImmutable $today, int $year, int $month, int $day ): DateTimeImmutable {
		if ( 2 === $month && 29 === $day && ! \checkdate( 2, 29, $year ) ) {
			$day = 28;
		}

		return $today->setDate( $year, $month, $day );
	}

	/**
	 * Get the age on the next birthday, only when the year is known.
	 *
	 * @param DateTimeImmutable $today Today.
	 * @return int|null
	 */
	public function get_age_on_next_birthday( DateTimeImmutable $today ): ?int {
		$next_birthday = $this->get_next_birthday( $today );

		if ( null === $next_birthday || null === $this->year ) {
			return null;
		}

		return (int) $next_birthday->format( 'Y' ) - $this->year;
	}

	/**
	 * Get the age range.
	 *
	 * When only the year (and month) is known the exact age can be unknown,
	 * the range then contains the minimum and maximum age. With a full birth
	 * date both values are equal.
	 *
	 * @param DateTimeImmutable $today Today.
	 * @return array{0: int, 1: int}|null
	 */
	public function get_age_range( DateTimeImmutable $today ): ?array {
		if ( null === $this->year ) {
			return null;
		}

		$age = (int) $today->format( 'Y' ) - $this->year;

		$current = [ (int) $today->format( 'n' ), (int) $today->format( 'j' ) ];

		if ( null !== $this->month && null !== $this->day ) {
			$age = $current < [ $this->month, $this->day ] ? $age - 1 : $age;

			return [ $age, $age ];
		}

		if ( null !== $this->month && $current[0] !== $this->month ) {
			$age = $current[0] < $this->month ? $age - 1 : $age;

			return [ $age, $age ];
		}

		return [ \max( 0, $age - 1 ), $age ];
	}
}
