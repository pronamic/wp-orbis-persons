<?php
/**
 * Archive persons
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

$today = new DateTimeImmutable( 'today', \wp_timezone() );

\get_header();

?>
<div class="card">
	<?php \get_template_part( 'templates/search_form' ); ?>

	<?php if ( \have_posts() ) : ?>

		<div class="table-responsive">
			<table class="table table-striped table-condense table-hover">
				<col width="84" />

				<thead>
					<tr>
						<th><span class="visually-hidden"><?php \esc_html_e( 'Photo', 'orbis-persons' ); ?></span></th>
						<th><?php \esc_html_e( 'Name', 'orbis-persons' ); ?></th>
						<th><?php \esc_html_e( 'Organization', 'orbis-persons' ); ?></th>
						<th><?php \esc_html_e( 'Email Addresses', 'orbis-persons' ); ?></th>
						<th><?php \esc_html_e( 'Phone Numbers', 'orbis-persons' ); ?></th>
						<th><?php \esc_html_e( 'Address', 'orbis-persons' ); ?></th>
						<th><?php \esc_html_e( 'Birth Date', 'orbis-persons' ); ?></th>
						<th><?php \esc_html_e( 'Author', 'orbis-persons' ); ?></th>
						<th><span class="visually-hidden"><?php \esc_html_e( 'Actions', 'orbis-persons' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php

					while ( \have_posts() ) :
						\the_post();

						$organization = \implode(
							', ',
							\array_filter(
								[
									$get_meta( '_orbis_title' ),
									$get_meta( '_orbis_organization' ),
								]
							)
						);

						$address  = $get_meta( '_orbis_address' );
						$postcode = $get_meta( '_orbis_postcode' );
						$city     = $get_meta( '_orbis_city' );

						$birth_date = BirthDate::from_post( (int) \get_the_ID() );

						$death_date = DeathDate::from_post( (int) \get_the_ID() );

						$is_deceased = ! $death_date->is_empty();

						/**
						 * For a deceased person the age is the age at death.
						 */
						$age_range = $is_deceased ? $birth_date->get_age_range_at_death( $death_date ) : $birth_date->get_age_range( $today );

						/**
						 * For historical persons an age is not meaningful.
						 */
						if ( ! $is_deceased && null !== $age_range && $age_range[1] > 150 ) {
							$age_range = null;
						}

						$avatar_url = \get_template_directory_uri() . '/placeholders/avatar.png';

						if ( \has_post_thumbnail() ) {
							$avatar_url = (string) \get_the_post_thumbnail_url( null, 'avatar' );
						}

						?>

						<tr id="post-<?php \the_ID(); ?>" <?php \post_class(); ?>>
							<td>
								<img src="<?php echo \esc_url( $avatar_url ); ?>" alt="" class="rounded-circle" />
							</td>
							<td>
								<a href="<?php \the_permalink(); ?>"><?php \the_title(); ?></a>

								<?php \get_template_part( 'templates/table-cell-comments' ); ?>
							</td>
							<td>
								<?php echo \esc_html( $organization ); ?>
							</td>
							<td>
								<?php

								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block output is escaped in the render callback.
								echo \render_block(
									[
										'blockName' => 'orbis-contacts/email-addresses',
									]
								);

								?>
							</td>
							<td>
								<?php

								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block output is escaped in the render callback.
								echo \render_block(
									[
										'blockName' => 'orbis-contacts/phone-numbers',
									]
								);

								?>
							</td>
							<td>
								<?php

								echo \esc_html( $address ), '<br />', \esc_html( \trim( $postcode . ' ' . $city ) );

								?>
							</td>
							<td>
								<?php echo \esc_html( $birth_date->format() ); ?>

								<?php if ( $is_deceased ) : ?>

									<br />
									<?php

									/* translators: %s: death date */
									echo \esc_html( \sprintf( \__( '† %s', 'orbis-persons' ), $death_date->format() ) );

									?>

								<?php endif; ?>

								<?php if ( null !== $age_range ) : ?>

									<br />
									<span class="text-muted">
										<?php

										$age = $age_range[0] === $age_range[1]
											? (string) $age_range[0]
											/* translators: 1: minimum age, 2: maximum age */
											: \sprintf( \__( '%1$d or %2$d', 'orbis-persons' ), $age_range[0], $age_range[1] );

										/* translators: %s: age */
										echo \esc_html( \sprintf( \__( '%s years', 'orbis-persons' ), $age ) );

										?>
									</span>

								<?php endif; ?>
							</td>
							<td>
								<?php \the_author(); ?>
							</td>
							<td>
								<?php \get_template_part( 'templates/table-cell-actions' ); ?>
							</td>
						</tr>

					<?php endwhile; ?>
				</tbody>
			</table>
		</div>

	<?php else : ?>

		<div class="card-body">
			<?php \get_template_part( 'templates/content-none' ); ?>
		</div>

	<?php endif; ?>
</div>

<?php

if ( \function_exists( 'orbis_content_nav' ) ) {
	\orbis_content_nav();
} else {
	\the_posts_pagination();
}

\get_footer();
