<?php
/**
 * Meta box person basic details
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

$orbis_id = $get_meta( '_orbis_person_id' );

$first_name  = $get_meta( '_orbis_first_name' );
$last_name   = $get_meta( '_orbis_last_name' );
$maiden_name = $get_meta( '_orbis_maiden_name' );

$job_title    = $get_meta( '_orbis_title' );
$organization = $get_meta( '_orbis_organization' );
$department   = $get_meta( '_orbis_department' );

$address  = $get_meta( '_orbis_address' );
$postcode = $get_meta( '_orbis_postcode' );
$city     = $get_meta( '_orbis_city' );
$country  = $get_meta( '_orbis_country' );

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
				<label for="orbis_person_id"><?php \esc_html_e( 'Orbis ID', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input id="orbis_person_id" name="_orbis_person_id" value="<?php echo \esc_attr( $orbis_id ); ?>" type="text" class="regular-text" readonly="readonly" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_first_name"><?php \esc_html_e( 'First Names', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_first_name" name="_orbis_first_name" value="<?php echo \esc_attr( $first_name ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_last_name"><?php \esc_html_e( 'Last Names', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_last_name" name="_orbis_last_name" value="<?php echo \esc_attr( $last_name ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_maiden_name"><?php \esc_html_e( 'Last Name at Birth', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="text" id="orbis_person_maiden_name" name="_orbis_maiden_name" value="<?php echo \esc_attr( $maiden_name ); ?>" class="regular-text" />
			</td>
		</tr>
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
