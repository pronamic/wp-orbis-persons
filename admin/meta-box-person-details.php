<?php
/**
 * Meta box person details
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

/**
 * Post.
 *
 * @var \WP_Post $post
 */

$get_meta = static function ( string $key ) use ( $post ): string {
	$value = \get_post_meta( $post->ID, $key, true );

	return \is_string( $value ) ? $value : '';
};

$job_title    = $get_meta( '_orbis_title' );
$organization = $get_meta( '_orbis_organization' );
$department   = $get_meta( '_orbis_department' );

$email         = $get_meta( '_orbis_email' );
$phone_number  = $get_meta( '_orbis_phone_number' );
$mobile_number = $get_meta( '_orbis_mobile_number' );

$address  = $get_meta( '_orbis_address' );
$postcode = $get_meta( '_orbis_postcode' );
$city     = $get_meta( '_orbis_city' );
$country  = $get_meta( '_orbis_country' );

$birth_date = $get_meta( '_orbis_birth_date_string' );

$iban = $get_meta( '_orbis_iban' );

$twitter  = $get_meta( '_orbis_twitter' );
$facebook = $get_meta( '_orbis_facebook' );
$linkedin = $get_meta( '_orbis_linkedin' );

\wp_nonce_field( 'orbis_persons_save_details', 'orbis_persons_details_meta_box_nonce' );

?>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_organization"><?php \esc_html_e( 'Organization', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_title" name="_orbis_title" value="<?php echo \esc_attr( $job_title ); ?>" class="regular-text" placeholder="<?php echo \esc_attr( \_x( 'Title', 'person', 'orbis-persons' ) ); ?>" style="width: 10em;" />

				<input type="text" id="orbis_person_organization" name="_orbis_organization" value="<?php echo \esc_attr( $organization ); ?>" class="regular-text" placeholder="<?php echo \esc_attr( \_x( 'Organization', 'person', 'orbis-persons' ) ); ?>" />

				<input type="text" id="orbis_person_department" name="_orbis_department" value="<?php echo \esc_attr( $department ); ?>" class="regular-text" placeholder="<?php echo \esc_attr( \_x( 'Department', 'person', 'orbis-persons' ) ); ?>" style="width: 10em;" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_gender"><?php \esc_html_e( 'Gender', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<?php

				$terms = \get_the_terms( $post->ID, 'orbis_gender' );

				$gender_term = \is_array( $terms ) ? \reset( $terms ) : false;

				\wp_dropdown_categories(
					[
						'name'             => 'tax_input[orbis_gender]',
						'id'               => 'orbis_person_gender',
						'show_option_none' => \__( '— Select Gender —', 'orbis-persons' ),
						'hide_empty'       => false,
						'selected'         => $gender_term instanceof \WP_Term ? $gender_term->term_id : 0,
						'taxonomy'         => 'orbis_gender',
					]
				);

				?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_email"><?php \esc_html_e( 'Email Address', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="email" id="orbis_person_email" name="_orbis_email" value="<?php echo \esc_attr( $email ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_phone_number"><?php \esc_html_e( 'Phone Number', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="tel" id="orbis_person_phone_number" name="_orbis_phone_number" value="<?php echo \esc_attr( $phone_number ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_mobile_number"><?php \esc_html_e( 'Mobile Number', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="tel" id="orbis_person_mobile_number" name="_orbis_mobile_number" value="<?php echo \esc_attr( $mobile_number ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="orbis_person_address"><?php \esc_html_e( 'Address', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input id="orbis_person_address" name="_orbis_address" placeholder="<?php echo \esc_attr( \__( 'Address', 'orbis-persons' ) ); ?>" value="<?php echo \esc_attr( $address ); ?>" type="text" size="42" />
				<br />
				<input id="orbis_person_postcode" name="_orbis_postcode" placeholder="<?php echo \esc_attr( \__( 'Postcode', 'orbis-persons' ) ); ?>" value="<?php echo \esc_attr( $postcode ); ?>" type="text" size="10" />
				<input id="orbis_person_city" name="_orbis_city" placeholder="<?php echo \esc_attr( \__( 'City', 'orbis-persons' ) ); ?>" value="<?php echo \esc_attr( $city ); ?>" type="text" size="25" />
				<br />
				<input id="orbis_person_country" name="_orbis_country" placeholder="<?php echo \esc_attr( \__( 'Country', 'orbis-persons' ) ); ?>" value="<?php echo \esc_attr( $country ); ?>" type="text" size="42" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_birth_date"><?php \esc_html_e( 'Birth Date', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_birth_date" name="_orbis_birth_date_string" value="<?php echo \esc_attr( $birth_date ); ?>" class="regular-text" placeholder="<?php echo \esc_attr( \_x( 'dd-mm-yyyy', 'birth date format', 'orbis-persons' ) ); ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_iban"><?php \esc_html_e( 'IBAN', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_iban" name="_orbis_iban" value="<?php echo \esc_attr( $iban ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_twitter"><?php \esc_html_e( 'Twitter Username', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_twitter" name="_orbis_twitter" value="<?php echo \esc_attr( $twitter ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_facebook"><?php \esc_html_e( 'Facebook URL', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_facebook" name="_orbis_facebook" value="<?php echo \esc_attr( $facebook ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_linkedin"><?php \esc_html_e( 'LinkedIn URL', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_linkedin" name="_orbis_linkedin" value="<?php echo \esc_attr( $linkedin ); ?>" class="regular-text" />
			</td>
		</tr>
	</tbody>
</table>
