<?php
/**
 * Meta box person personal details
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

$person = Person::from_post( $post );

$father_post_id = $person->father_post_id;
$mother_post_id = $person->mother_post_id;

$birth_day   = $get_meta( '_orbis_birth_day' );
$birth_month = $get_meta( '_orbis_birth_month' );
$birth_year  = $get_meta( '_orbis_birth_year' );

$death_day   = $get_meta( '_orbis_death_day' );
$death_month = $get_meta( '_orbis_death_month' );
$death_year  = $get_meta( '_orbis_death_year' );

?>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_gender"><?php \esc_html_e( 'Gender', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<select id="orbis_person_gender" name="_orbis_gender">
					<option value=""><?php \esc_html_e( '— Select Gender —', 'orbis-persons' ); ?></option>

					<?php foreach ( Gender::cases() as $gender ) : ?>

						<option value="<?php echo \esc_attr( $gender->value ); ?>" <?php \selected( $person->gender === $gender ); ?>><?php echo \esc_html( $gender->label() ); ?></option>

					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_birth_day"><?php \esc_html_e( 'Birth Date', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php \esc_html_e( 'Birth Date', 'orbis-persons' ); ?></legend>

					<label for="orbis_person_birth_day" class="screen-reader-text"><?php \esc_html_e( 'Day', 'orbis-persons' ); ?></label>
					<input type="text" id="orbis_person_birth_day" name="_orbis_birth_day" value="<?php echo \esc_attr( $birth_day ); ?>" inputmode="numeric" pattern="[0-9]*" size="2" maxlength="2" placeholder="<?php echo \esc_attr( \_x( 'dd', 'birth day placeholder', 'orbis-persons' ) ); ?>" />

					<label for="orbis_person_birth_month" class="screen-reader-text"><?php \esc_html_e( 'Month', 'orbis-persons' ); ?></label>
					<input type="text" id="orbis_person_birth_month" name="_orbis_birth_month" value="<?php echo \esc_attr( $birth_month ); ?>" inputmode="numeric" pattern="[0-9]*" size="2" maxlength="2" placeholder="<?php echo \esc_attr( \_x( 'mm', 'birth month placeholder', 'orbis-persons' ) ); ?>" />

					<label for="orbis_person_birth_year" class="screen-reader-text"><?php \esc_html_e( 'Year', 'orbis-persons' ); ?></label>
					<input type="text" id="orbis_person_birth_year" name="_orbis_birth_year" value="<?php echo \esc_attr( $birth_year ); ?>" inputmode="numeric" pattern="[0-9]*" size="4" maxlength="4" placeholder="<?php echo \esc_attr( \_x( 'yyyy', 'birth year placeholder', 'orbis-persons' ) ); ?>" />

					<p class="description"><?php \esc_html_e( 'Fill in what is known, each part is optional.', 'orbis-persons' ); ?></p>
				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_death_day"><?php \esc_html_e( 'Death Date', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php \esc_html_e( 'Death Date', 'orbis-persons' ); ?></legend>

					<label for="orbis_person_death_day" class="screen-reader-text"><?php \esc_html_e( 'Day', 'orbis-persons' ); ?></label>
					<input type="text" id="orbis_person_death_day" name="_orbis_death_day" value="<?php echo \esc_attr( $death_day ); ?>" inputmode="numeric" pattern="[0-9]*" size="2" maxlength="2" placeholder="<?php echo \esc_attr( \_x( 'dd', 'death day placeholder', 'orbis-persons' ) ); ?>" />

					<label for="orbis_person_death_month" class="screen-reader-text"><?php \esc_html_e( 'Month', 'orbis-persons' ); ?></label>
					<input type="text" id="orbis_person_death_month" name="_orbis_death_month" value="<?php echo \esc_attr( $death_month ); ?>" inputmode="numeric" pattern="[0-9]*" size="2" maxlength="2" placeholder="<?php echo \esc_attr( \_x( 'mm', 'death month placeholder', 'orbis-persons' ) ); ?>" />

					<label for="orbis_person_death_year" class="screen-reader-text"><?php \esc_html_e( 'Year', 'orbis-persons' ); ?></label>
					<input type="text" id="orbis_person_death_year" name="_orbis_death_year" value="<?php echo \esc_attr( $death_year ); ?>" inputmode="numeric" pattern="[0-9]*" size="4" maxlength="4" placeholder="<?php echo \esc_attr( \_x( 'yyyy', 'death year placeholder', 'orbis-persons' ) ); ?>" />

					<p class="description"><?php \esc_html_e( 'Fill in what is known, each part is optional.', 'orbis-persons' ); ?></p>
				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_father"><?php \esc_html_e( 'Father', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="hidden" id="orbis_person_father_post_id" name="_orbis_person_father_post_id" value="<?php echo \esc_attr( null === $father_post_id ? '' : (string) $father_post_id ); ?>" />

				<input type="text" id="orbis_person_father" value="<?php echo \esc_attr( null === $father_post_id ? '' : \get_the_title( $father_post_id ) ); ?>" class="regular-text orbis-person-parent-control" data-target="orbis_person_father_post_id" data-exclude-gender="female" data-exclude="<?php echo \esc_attr( (string) $post->ID ); ?>" placeholder="<?php \esc_attr_e( 'Search a person…', 'orbis-persons' ); ?>" autocomplete="off" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="orbis_person_mother"><?php \esc_html_e( 'Mother', 'orbis-persons' ); ?></label>
			</th>
			<td>
				<input type="hidden" id="orbis_person_mother_post_id" name="_orbis_person_mother_post_id" value="<?php echo \esc_attr( null === $mother_post_id ? '' : (string) $mother_post_id ); ?>" />

				<input type="text" id="orbis_person_mother" value="<?php echo \esc_attr( null === $mother_post_id ? '' : \get_the_title( $mother_post_id ) ); ?>" class="regular-text orbis-person-parent-control" data-target="orbis_person_mother_post_id" data-exclude-gender="male" data-exclude="<?php echo \esc_attr( (string) $post->ID ); ?>" placeholder="<?php \esc_attr_e( 'Search a person…', 'orbis-persons' ); ?>" autocomplete="off" />

				<p class="description"><?php \esc_html_e( 'Type at least 2 characters of the name and choose a person. Empty the field to remove the father or mother.', 'orbis-persons' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>
