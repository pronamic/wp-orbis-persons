<?php
/**
 * Person details
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

namespace Pronamic\Orbis\Persons;

use DateTimeImmutable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$get_meta = static function ( string $key ): string {
	$value = \get_post_meta( (int) \get_the_ID(), $key, true );

	return \is_string( $value ) ? $value : '';
};

$first_name  = $get_meta( '_orbis_first_name' );
$last_name   = $get_meta( '_orbis_last_name' );
$maiden_name = $get_meta( '_orbis_maiden_name' );

$organization = \implode(
	', ',
	\array_filter(
		[
			$get_meta( '_orbis_title' ),
			$get_meta( '_orbis_organization' ),
			$get_meta( '_orbis_department' ),
		]
	)
);

$email         = $get_meta( '_orbis_email' );
$phone_number  = $get_meta( '_orbis_phone_number' );
$mobile_number = $get_meta( '_orbis_mobile_number' );

$address  = $get_meta( '_orbis_address' );
$postcode = $get_meta( '_orbis_postcode' );
$city     = $get_meta( '_orbis_city' );
$country  = $get_meta( '_orbis_country' );

$iban = $get_meta( '_orbis_iban' );

$genders = \get_the_terms( (int) \get_the_ID(), 'orbis_gender' );
$genders = \is_array( $genders ) ? \wp_list_pluck( $genders, 'name' ) : [];

$birth_date = BirthDate::from_post( (int) \get_the_ID() );

$today = new DateTimeImmutable( 'today', \wp_timezone() );

$next_birthday = $birth_date->get_next_birthday( $today );

$age_range = $birth_date->get_age_range( $today );

/**
 * For historical persons an age is not meaningful.
 */
if ( null !== $age_range && $age_range[1] > 150 ) {
	$age_range = null;
}

$age_on_next_birthday = null === $age_range ? null : $birth_date->get_age_on_next_birthday( $today );

$twitter  = $get_meta( '_orbis_twitter' );
$facebook = $get_meta( '_orbis_facebook' );
$linkedin = $get_meta( '_orbis_linkedin' );

?>
<dl>
	<?php if ( '' !== $first_name ) : ?>

		<dt><?php \esc_html_e( 'First Names', 'orbis-persons' ); ?></dt>
		<dd><?php echo \esc_html( $first_name ); ?></dd>

	<?php endif; ?>

	<?php if ( '' !== $last_name ) : ?>

		<dt><?php \esc_html_e( 'Last Names', 'orbis-persons' ); ?></dt>
		<dd><?php echo \esc_html( $last_name ); ?></dd>

	<?php endif; ?>

	<?php if ( '' !== $maiden_name ) : ?>

		<dt><?php \esc_html_e( 'Last Name at Birth', 'orbis-persons' ); ?></dt>
		<dd><?php echo \esc_html( $maiden_name ); ?></dd>

	<?php endif; ?>

	<?php if ( '' !== $organization ) : ?>

		<dt><?php \esc_html_e( 'Organization', 'orbis-persons' ); ?></dt>
		<dd><?php echo \esc_html( $organization ); ?></dd>

	<?php endif; ?>

	<?php if ( '' !== $phone_number ) : ?>

		<dt><?php \esc_html_e( 'Phone Number', 'orbis-persons' ); ?></dt>
		<dd>
			<a href="<?php echo \esc_url( 'tel:' . $phone_number ); ?>" class="anchor-tooltip" title="<?php \esc_attr_e( 'Call this number', 'orbis-persons' ); ?>"><?php echo \esc_html( $phone_number ); ?></a>
		</dd>

	<?php endif; ?>

	<?php if ( '' !== $mobile_number ) : ?>

		<dt><?php \esc_html_e( 'Mobile Number', 'orbis-persons' ); ?></dt>
		<dd>
			<a href="<?php echo \esc_url( 'tel:' . $mobile_number ); ?>" class="anchor-tooltip" title="<?php \esc_attr_e( 'Call this number', 'orbis-persons' ); ?>"><?php echo \esc_html( $mobile_number ); ?></a>
		</dd>

	<?php endif; ?>

	<?php if ( '' !== $email ) : ?>

		<dt><?php \esc_html_e( 'Email Address', 'orbis-persons' ); ?></dt>
		<dd>
			<a href="<?php echo \esc_url( 'mailto:' . $email ); ?>"><?php echo \esc_html( $email ); ?></a>
		</dd>

	<?php endif; ?>

	<?php if ( [] !== $genders ) : ?>

		<dt><?php \esc_html_e( 'Gender', 'orbis-persons' ); ?></dt>
		<dd><?php echo \esc_html( \implode( ', ', $genders ) ); ?></dd>

	<?php endif; ?>

	<?php if ( '' !== $address || '' !== $postcode || '' !== $city || '' !== $country ) : ?>

		<dt><?php \esc_html_e( 'Address', 'orbis-persons' ); ?></dt>
		<dd>
			<?php echo \esc_html( $address ); ?><br />
			<?php echo \esc_html( \trim( $postcode . ' ' . $city ) ); ?><br />
			<?php echo \esc_html( $country ); ?>
		</dd>

	<?php endif; ?>

	<?php if ( '' !== $iban ) : ?>

		<dt><?php \esc_html_e( 'IBAN', 'orbis-persons' ); ?></dt>
		<dd><?php echo \esc_html( $iban ); ?></dd>

	<?php endif; ?>

	<?php if ( ! $birth_date->is_empty() ) : ?>

		<dt><?php \esc_html_e( 'Birth Date', 'orbis-persons' ); ?></dt>
		<dd><?php echo \esc_html( $birth_date->format() ); ?></dd>

	<?php endif; ?>

	<?php if ( null !== $next_birthday ) : ?>

		<?php

		$days = (int) $today->diff( $next_birthday )->days;

		$next_birthday_parts = [
			0 === $days ? \__( 'Today!', 'orbis-persons' ) : \wp_date( \__( 'j F Y', 'orbis-persons' ), $next_birthday->getTimestamp() ),
		];

		if ( 0 !== $days ) {
			/* translators: %d: number of days */
			$next_birthday_parts[] = \sprintf( \_n( 'in %d day', 'in %d days', $days, 'orbis-persons' ), $days );
		}

		if ( null !== $age_on_next_birthday ) {
			/* translators: %d: age */
			$next_birthday_parts[] = \sprintf( \__( 'turns %d', 'orbis-persons' ), $age_on_next_birthday );
		}

		?>

		<dt><?php \esc_html_e( 'Next Birthday', 'orbis-persons' ); ?></dt>
		<dd><?php echo \esc_html( \implode( ' · ', $next_birthday_parts ) ); ?></dd>

	<?php endif; ?>

	<?php if ( null !== $age_range ) : ?>

		<dt><?php \esc_html_e( 'Age', 'orbis-persons' ); ?></dt>
		<dd>
			<?php

			echo \esc_html(
				$age_range[0] === $age_range[1]
					? (string) $age_range[0]
					/* translators: 1: minimum age, 2: maximum age */
					: \sprintf( \__( '%1$d or %2$d', 'orbis-persons' ), $age_range[0], $age_range[1] )
			);

			?>
		</dd>

	<?php endif; ?>

	<?php if ( \class_exists( 'Orbis_VCard' ) ) : ?>

		<dt><?php \esc_html_e( 'vCard', 'orbis-persons' ); ?></dt>
		<dd>
			<a href="<?php echo \esc_url( \trailingslashit( (string) \get_permalink() ) . 'vcard/' ); ?>"><?php \esc_html_e( 'Download vCard', 'orbis-persons' ); ?></a>
		</dd>

	<?php endif; ?>

	<?php if ( '' !== $twitter || '' !== $facebook || '' !== $linkedin ) : ?>

		<dt><?php \esc_html_e( 'Social Media', 'orbis-persons' ); ?></dt>
		<dd>
			<ul class="social clearfix">
				<?php if ( '' !== $twitter ) : ?>

					<li class="twitter">
						<a href="<?php echo \esc_url( 'https://twitter.com/' . $twitter ); ?>" target="_blank">
							<i class="fab fa-twitter"></i>

							<span class="visually-hidden"><?php \esc_html_e( 'Twitter', 'orbis-persons' ); ?></span>
						</a>
					</li>

				<?php endif; ?>

				<?php if ( '' !== $facebook ) : ?>

					<li class="facebook">
						<a href="<?php echo \esc_url( $facebook ); ?>" target="_blank">
							<i class="fab fa-facebook"></i>

							<span class="visually-hidden"><?php \esc_html_e( 'Facebook', 'orbis-persons' ); ?></span>
						</a>
					</li>

				<?php endif; ?>

				<?php if ( '' !== $linkedin ) : ?>

					<li class="linkedin">
						<a href="<?php echo \esc_url( $linkedin ); ?>" target="_blank">
							<i class="fab fa-linkedin"></i>

							<span class="visually-hidden"><?php \esc_html_e( 'LinkedIn', 'orbis-persons' ); ?></span>
						</a>
					</li>

				<?php endif; ?>
			</ul>
		</dd>

	<?php endif; ?>
</dl>
