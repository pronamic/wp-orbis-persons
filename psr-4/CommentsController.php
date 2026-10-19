<?php
/**
 * Comments controller
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

use WP_Comment;

/**
 * Comments controller class
 */
final class CommentsController {
	/**
	 * Construct.
	 */
	public function __construct() {
		\add_filter( 'comment_text', $this->comment_text( ... ), 20, 2 );
	}

	/**
	 * Comment text.
	 *
	 * Shows the AI provider and model that wrote a person comment.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/comment_text/
	 * @param string          $comment_text Text of the comment.
	 * @param WP_Comment|null $comment      The comment object. Null if not found.
	 * @return string
	 */
	private function comment_text( $comment_text, $comment ) {
		if ( ! $comment instanceof WP_Comment ) {
			return $comment_text;
		}

		return $comment_text . $this->get_ai_html( (int) $comment->comment_ID );
	}

	/**
	 * Get AI HTML.
	 *
	 * @param int $comment_id Comment ID.
	 * @return string
	 */
	private function get_ai_html( int $comment_id ): string {
		$provider_id   = $this->get_meta_string( $comment_id, '_orbis_person_ai_provider_id' );
		$provider_name = $this->get_meta_string( $comment_id, '_orbis_person_ai_provider_name' );
		$model_id      = $this->get_meta_string( $comment_id, '_orbis_person_ai_model_id' );
		$model_name    = $this->get_meta_string( $comment_id, '_orbis_person_ai_model_name' );

		$provider = ( '' === $provider_name ) ? $provider_id : $provider_name;
		$model    = ( '' === $model_name ) ? $model_id : $model_name;

		if ( '' === $provider && '' === $model ) {
			return '';
		}

		if ( '' === $model ) {
			$text = \sprintf(
				/* translators: %s: AI provider. */
				\__( 'Written with AI by %s.', 'orbis-persons' ),
				'<span title="' . \esc_attr( $provider_id ) . '">' . \esc_html( $provider ) . '</span>'
			);
		} elseif ( '' === $provider ) {
			$text = \sprintf(
				/* translators: %s: AI model. */
				\__( 'Written with AI by %s.', 'orbis-persons' ),
				'<span title="' . \esc_attr( $model_id ) . '">' . \esc_html( $model ) . '</span>'
			);
		} else {
			$text = \sprintf(
				/* translators: 1: AI model, 2: AI provider. */
				\__( 'Written with AI by %1$s (%2$s).', 'orbis-persons' ),
				'<span title="' . \esc_attr( $model_id ) . '">' . \esc_html( $model ) . '</span>',
				'<span title="' . \esc_attr( $provider_id ) . '">' . \esc_html( $provider ) . '</span>'
			);
		}

		return '<p class="small text-muted">' . $text . '</p>';
	}

	/**
	 * Get comment meta string.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $meta_key   Meta key.
	 * @return string
	 */
	private function get_meta_string( int $comment_id, string $meta_key ): string {
		$value = \get_comment_meta( $comment_id, $meta_key, true );

		return \is_string( $value ) ? $value : '';
	}
}
