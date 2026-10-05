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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$get_meta = static function ( string $key ): string {
	$value = \get_post_meta( (int) \get_the_ID(), $key, true );

	return \is_string( $value ) ? $value : '';
};

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
						<th><?php \esc_html_e( 'Address', 'orbis-persons' ); ?></th>
						<th><?php \esc_html_e( 'Author', 'orbis-persons' ); ?></th>
						<th><span class="visually-hidden"><?php \esc_html_e( 'Actions', 'orbis-persons' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php

					while ( \have_posts() ) :
						\the_post();

						$email        = $get_meta( '_orbis_email' );
						$phone_number = $get_meta( '_orbis_phone_number' );

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

								<span class="orbis-person-meta">
									<?php if ( '' !== $email ) : ?>

										<br /><a href="<?php echo \esc_url( 'mailto:' . $email ); ?>"><?php echo \esc_html( $email ); ?></a>

									<?php endif; ?>

									<?php if ( '' !== $phone_number ) : ?>

										<br /><a href="<?php echo \esc_url( 'tel:' . $phone_number ); ?>"><?php echo \esc_html( $phone_number ); ?></a>

									<?php endif; ?>
								</span>
							</td>
							<td>
								<?php echo \esc_html( $organization ); ?>
							</td>
							<td>
								<?php

								echo \esc_html( $address ), '<br />', \esc_html( \trim( $postcode . ' ' . $city ) );

								?>
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
