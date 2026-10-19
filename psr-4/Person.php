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
use WP_Term;

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
	 * @param int|null                                        $post_id         Post ID, `null` for a person that is not saved yet.
	 * @param int|null                                        $orbis_id        ID in the `{prefix}orbis_persons` table.
	 * @param int|null                                        $contact_id      ID in the `{prefix}orbis_contacts` table.
	 * @param string|null                                     $name            Name, the post title.
	 * @param string|null                                     $content         Description, the post content.
	 * @param string|null                                     $job_title       Job title.
	 * @param string|null                                     $organization    Organization.
	 * @param string|null                                     $department      Department.
	 * @param string|null                                     $email           Email address.
	 * @param array<array{email: string, label: string}>|null $email_addresses Email addresses from Orbis Contacts.
	 * @param string|null                                     $phone_number    Phone number.
	 * @param string|null                                     $mobile_number   Mobile number.
	 * @param string|null                                     $address         Address.
	 * @param string|null                                     $postcode        Postcode.
	 * @param string|null                                     $city            City.
	 * @param string|null                                     $country         Country.
	 * @param WP_Term|null                                    $gender          Gender term.
	 * @param BirthDate|null                                  $birth_date      Birth date.
	 * @param string|null                                     $iban            IBAN.
	 * @param string|null                                     $twitter         Twitter username.
	 * @param string|null                                     $facebook        Facebook URL.
	 * @param string|null                                     $linkedin        LinkedIn URL.
	 * @param string|null                                     $created         Created date, the post date.
	 * @param string|null                                     $modified        Modified date, the post modified date.
	 * @param int|null                                        $comment_count   Number of comments.
	 */
	public function __construct(
		public ?int $post_id = null,
		public ?int $orbis_id = null,
		public ?int $contact_id = null,
		public ?string $name = null,
		public ?string $content = null,
		public ?string $job_title = null,
		public ?string $organization = null,
		public ?string $department = null,
		public ?string $email = null,
		public ?array $email_addresses = null,
		public ?string $phone_number = null,
		public ?string $mobile_number = null,
		public ?string $address = null,
		public ?string $postcode = null,
		public ?string $city = null,
		public ?string $country = null,
		public ?WP_Term $gender = null,
		public ?BirthDate $birth_date = null,
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
		$person->name          = '' === $post->post_title ? null : $post->post_title;
		$person->content       = '' === $post->post_content ? null : $post->post_content;
		$person->job_title     = $get_meta( '_orbis_title' );
		$person->organization  = $get_meta( '_orbis_organization' );
		$person->department    = $get_meta( '_orbis_department' );
		$person->email         = $get_meta( '_orbis_email' );
		$person->phone_number  = $get_meta( '_orbis_phone_number' );
		$person->mobile_number = $get_meta( '_orbis_mobile_number' );
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

		$terms = \get_the_terms( $post, 'orbis_gender' );

		$gender = \is_array( $terms ) ? \reset( $terms ) : false;

		$person->gender = $gender instanceof WP_Term ? $gender : null;

		$birth_date = BirthDate::from_post( $post->ID );

		$person->birth_date = $birth_date->is_empty() ? null : $birth_date;

		if ( \class_exists( ContactJson::class ) ) {
			$contact = ContactJson::get( $post->ID );

			$person->contact_id = \is_array( $contact ) && \is_int( $contact['contact_id'] ?? null ) ? $contact['contact_id'] : null;

			$items = ContactJson::get_email_addresses( $post->ID );

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

			$person->email_addresses = $email_addresses;
		}

		return $person;
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
			'name'            => $this->name,
			'content'         => $this->content,
			'job_title'       => $this->job_title,
			'organization'    => $this->organization,
			'department'      => $this->department,
			'email'           => $this->email,
			'email_addresses' => $this->email_addresses,
			'phone_number'    => $this->phone_number,
			'mobile_number'   => $this->mobile_number,
			'address'         => $this->address,
			'postcode'        => $this->postcode,
			'city'            => $this->city,
			'country'         => $this->country,
			'gender'          => null === $this->gender ? null : [
				'id'   => $this->gender->term_id,
				'slug' => $this->gender->slug,
				'name' => $this->gender->name,
			],
			'birth_date'      => $birth_date,
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
