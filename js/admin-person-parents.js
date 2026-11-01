/**
 * Person parents
 *
 * Search a person for the father or mother field with jQuery UI Autocomplete
 * and the persons REST API. The chosen post ID is stored in a hidden field.
 * Women are left out for the father and men for the mother.
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

( function ( $, apiFetch, addQueryArgs, l10n ) {
	/**
	 * Decode HTML entities in the rendered title.
	 *
	 * @param {string} html HTML.
	 * @return {string} Text.
	 */
	const decode = ( html ) => new DOMParser().parseFromString( html, 'text/html' ).documentElement.textContent;

	$( '.orbis-person-parent-control' ).each( function () {
		const $input = $( this );
		const $postId = $( '#' + $input.data( 'target' ) );

		let selected = {
			postId: $postId.val(),
			label: $input.val(),
		};

		$input.autocomplete( {
			minLength: 2,
			source( request, response ) {
				apiFetch( {
					path: addQueryArgs( '/wp/v2/orbis/persons', {
						search: request.term,
						// Only match the name, the REST API sorts by date and searches the content by default.
						search_columns: [ 'post_title' ],
						orderby: 'relevance',
						exclude: $input.data( 'exclude' ),
						orbis_exclude_gender: $input.data( 'excludeGender' ),
						per_page: 20,
						_fields: 'id,title',
					} ),
				} )
					.then( ( persons ) => {
						response(
							persons.map( ( person ) => ( {
								value: String( person.id ),
								label: decode( person.title.rendered ),
							} ) )
						);
					} )
					.catch( () => response( [] ) );
			},
			focus( event ) {
				// Keep the typed text while navigating the results with the arrow keys.
				event.preventDefault();
			},
			select( event, ui ) {
				event.preventDefault();

				selected = {
					postId: ui.item.value,
					label: ui.item.label,
				};

				$input.val( selected.label );
				$postId.val( selected.postId );
			},
			change() {
				// An empty field removes the parent, other text without a choice restores the current parent.
				if ( '' === $input.val().trim() ) {
					selected = {
						postId: '',
						label: '',
					};

					$postId.val( '' );

					return;
				}

				$input.val( selected.label );
			},
			messages: {
				noResults: l10n.noResults,
				results: ( count ) => ( 1 === count ? l10n.result : l10n.results.replace( '%d', count ) ),
			},
		} );
	} );
} )( jQuery, wp.apiFetch, wp.url.addQueryArgs, window.orbisPersonParents );
