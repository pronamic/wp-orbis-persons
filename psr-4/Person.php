<?php
/**
 * Person
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

use DateTimeImmutable;
use Pronamic\Orbis\Contacts\ContactJson;
use WP_Post;

/**
 * Person class
 */
final class Person {
	/**
	 * Construct.
	 *
	 * All fields are optional, so a new person can be created with
	 * `new Person()` and filled afterwards. A `null` value means the field
	 * is unknown or not set.
	 *
	 * @param int|null                                                           $post_id         Post ID, `null` for a person that is not saved yet.
	 * @param int|null                                                           $orbis_id        ID in the `{prefix}orbis_persons` table.
	 * @param int|null                                                           $contact_id      ID in the `{prefix}orbis_contacts` table.
	 * @param string|null                                                        $display_name    Display name, the post title.
	 * @param string|null                                                        $first_name      First names.
	 * @param string|null                                                        $last_name       Last names.
	 * @param string|null                                                        $maiden_name     Last name at birth.
	 * @param string|null                                                        $content         Description, the post content.
	 * @param string|null                                                        $job_title       Job title.
	 * @param string|null                                                        $organization    Organization.
	 * @param string|null                                                        $department      Department.
	 * @param array<array{email: string, label: string}>|null                    $email_addresses Email addresses from Orbis Contacts.
	 * @param array<array{input: string, label: string, e164: string|null}>|null $phone_numbers Phone numbers from Orbis Contacts.
	 * @param string|null                                                        $address         Address.
	 * @param string|null                                                        $postcode        Postcode.
	 * @param string|null                                                        $city            City.
	 * @param string|null                                                        $country         Country.
	 * @param Gender|null                                                        $gender          Gender.
	 * @param BirthDate|null                                                     $birth_date      Birth date.
	 * @param int|null                                                           $father_post_id       Post ID of the father.
	 * @param int|null                                                           $mother_post_id       Post ID of the mother.
	 * @param string|null                                                        $iban            IBAN.
	 * @param string|null                                                        $twitter         Twitter username.
	 * @param string|null                                                        $facebook        Facebook URL.
	 * @param string|null                                                        $linkedin        LinkedIn URL.
	 * @param string|null                                                        $created         Created date, the post date.
	 * @param string|null                                                        $modified        Modified date, the post modified date.
	 * @param int|null                                                           $comment_count   Number of comments.
	 */
	public function __construct(
		public ?int $post_id = null,
		public ?int $orbis_id = null,
		public ?int $contact_id = null,
		public ?string $display_name = null,
		public ?string $first_name = null,
		public ?string $last_name = null,
		public ?string $maiden_name = null,
		public ?string $content = null,
		public ?string $job_title = null,
		public ?string $organization = null,
		public ?string $department = null,
		public ?array $email_addresses = null,
		public ?array $phone_numbers = null,
		public ?string $address = null,
		public ?string $postcode = null,
		public ?string $city = null,
		public ?string $country = null,
		public ?Gender $gender = null,
		public ?BirthDate $birth_date = null,
		public ?int $father_post_id = null,
		public ?int $mother_post_id = null,
		public ?string $iban = null,
		public ?string $twitter = null,
		public ?string $facebook = null,
		public ?string $linkedin = null,
		public ?string $created = null,
		public ?string $modified = null,
		public ?int $comment_count = null
	) {
	}

	/**
	 * Create person from post.
	 *
	 * Empty values are set to `null`.
	 *
	 * @param WP_Post $post Post.
	 * @return self
	 */
	public static function from_post( WP_Post $post ): self {
		$get_meta = static function ( string $key ) use ( $post ): ?string {
			$value = \get_post_meta( $post->ID, $key, true );

			return \is_string( $value ) && '' !== $value ? $value : null;
		};

		$person = new self();

		$person->post_id       = $post->ID;
		$person->display_name  = '' === $post->post_title ? null : $post->post_title;
		$person->first_name    = $get_meta( '_orbis_first_name' );
		$person->last_name     = $get_meta( '_orbis_last_name' );
		$person->maiden_name   = $get_meta( '_orbis_maiden_name' );
		$person->content       = '' === $post->post_content ? null : $post->post_content;
		$person->job_title     = $get_meta( '_orbis_title' );
		$person->organization  = $get_meta( '_orbis_organization' );
		$person->department    = $get_meta( '_orbis_department' );
		$person->address       = $get_meta( '_orbis_address' );
		$person->postcode      = $get_meta( '_orbis_postcode' );
		$person->city          = $get_meta( '_orbis_city' );
		$person->country       = $get_meta( '_orbis_country' );
		$person->iban          = $get_meta( '_orbis_iban' );
		$person->twitter       = $get_meta( '_orbis_twitter' );
		$person->facebook      = $get_meta( '_orbis_facebook' );
		$person->linkedin      = $get_meta( '_orbis_linkedin' );
		$person->created       = $post->post_date;
		$person->modified      = $post->post_modified;
		$person->comment_count = (int) $post->comment_count;

		$orbis_id = $get_meta( '_orbis_person_id' );

		$person->orbis_id = null !== $orbis_id && \ctype_digit( $orbis_id ) ? (int) $orbis_id : null;

		$person->gender = Gender::tryFrom( (string) $get_meta( '_orbis_gender' ) );

		$birth_date = BirthDate::from_post( $post->ID );

		$person->birth_date = $birth_date->is_empty() ? null : $birth_date;

		$get_post_id = static function ( string $key ) use ( $post ): ?int {
			$value = \get_post_meta( $post->ID, $key, true );

			return \is_numeric( $value ) && (int) $value > 0 ? (int) $value : null;
		};

		$person->father_post_id = $get_post_id( '_orbis_person_father_post_id' );
		$person->mother_post_id = $get_post_id( '_orbis_person_mother_post_id' );

		if ( \class_exists( ContactJson::class ) ) {
			$contact = ContactJson::get( $post->ID );

			$person->contact_id = \is_array( $contact ) && \is_int( $contact['contact_id'] ?? null ) ? $contact['contact_id'] : null;

			$person->email_addresses = self::get_email_addresses( ContactJson::get_email_addresses( $post->ID ) );
			$person->phone_numbers   = self::get_phone_numbers( ContactJson::get_phone_numbers( $post->ID ) );
		}

		return $person;
	}

	/**
	 * Get the email addresses from the Orbis Contacts data.
	 *
	 * @param mixed $items Email addresses from `ContactJson::get_email_addresses()`.
	 * @return array<array{email: string, label: string}>
	 */
	private static function get_email_addresses( mixed $items ): array {
		$email_addresses = [];

		foreach ( \is_array( $items ) ? $items : [] as $item ) {
			if ( ! \is_array( $item ) || ! \is_string( $item['email'] ?? null ) ) {
				continue;
			}

			$email_addresses[] = [
				'email' => $item['email'],
				'label' => \is_string( $item['label'] ?? null ) ? $item['label'] : '',
			];
		}

		return $email_addresses;
	}

	/**
	 * Get the phone numbers from the Orbis Contacts data.
	 *
	 * @param mixed $items Phone numbers from `ContactJson::get_phone_numbers()`.
	 * @return array<array{input: string, label: string, e164: string|null}>
	 */
	private static function get_phone_numbers( mixed $items ): array {
		$phone_numbers = [];

		foreach ( \is_array( $items ) ? $items : [] as $item ) {
			if ( ! \is_array( $item ) || ! \is_string( $item['input'] ?? null ) ) {
				continue;
			}

			$phone_numbers[] = [
				'input' => $item['input'],
				'label' => \is_string( $item['label'] ?? null ) ? $item['label'] : '',
				'e164'  => \is_string( $item['e164'] ?? null ) ? $item['e164'] : null,
			];
		}

		return $phone_numbers;
	}

	/**
	 * Get the permalink.
	 *
	 * @return string|null Permalink, `null` for a person that is not saved yet.
	 */
	public function get_permalink(): ?string {
		if ( null === $this->post_id ) {
			return null;
		}

		$permalink = \get_permalink( $this->post_id );

		return false === $permalink ? null : $permalink;
	}

	/**
	 * To array.
	 *
	 * The age and next birthday are calculated for today in the timezone
	 * of the site.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$birth_date = null;

		if ( null !== $this->birth_date ) {
			$today = new DateTimeImmutable( 'today', \wp_timezone() );

			$age_range = $this->birth_date->get_age_range( $today );

			$birth_date = [
				'day'           => $this->birth_date->day,
				'month'         => $this->birth_date->month,
				'year'          => $this->birth_date->year,
				'formatted'     => $this->birth_date->format(),
				'age_min'       => null === $age_range ? null : $age_range[0],
				'age_max'       => null === $age_range ? null : $age_range[1],
				'next_birthday' => $this->birth_date->get_next_birthday( $today )?->format( 'Y-m-d' ),
			];
		}

		return [
			'post_id'         => $this->post_id,
			'id'              => $this->orbis_id,
			'contact_id'      => $this->contact_id,
			'display_name'    => $this->display_name,
			'first_name'      => $this->first_name,
			'last_name'       => $this->last_name,
			'maiden_name'     => $this->maiden_name,
			'content'         => $this->content,
			'job_title'       => $this->job_title,
			'organization'    => $this->organization,
			'department'      => $this->department,
			'email_addresses' => $this->email_addresses,
			'phone_numbers'   => $this->phone_numbers,
			'address'         => $this->address,
			'postcode'        => $this->postcode,
			'city'            => $this->city,
			'country'         => $this->country,
			'gender'          => $this->gender?->value,
			'birth_date'      => $birth_date,
			'father_post_id'  => $this->father_post_id,
			'mother_post_id'  => $this->mother_post_id,
			'iban'            => $this->iban,
			'twitter'         => $this->twitter,
			'facebook'        => $this->facebook,
			'linkedin'        => $this->linkedin,
			'created'         => $this->created,
			'modified'        => $this->modified,
			'comment_count'   => $this->comment_count,
		];
	}
}
