<?php
/**
 * Content types
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

/**
 * Content types class
 */
final class ContentTypes {
	/**
	 * Construct.
	 */
	public function __construct() {
		\add_action( 'init', $this->init( ... ) );
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init(): void {
		\register_post_type(
			'orbis_person',
			[
				'label'            => \__( 'Persons', 'orbis-persons' ),
				'labels'           => [
					'name'                     => \__( 'Persons', 'orbis-persons' ),
					'singular_name'            => \__( 'Person', 'orbis-persons' ),
					'add_new'                  => \_x( 'Add New', 'orbis_person', 'orbis-persons' ),
					'add_new_item'             => \__( 'Add New Person', 'orbis-persons' ),
					'edit_item'                => \__( 'Edit Person', 'orbis-persons' ),
					'new_item'                 => \__( 'New Person', 'orbis-persons' ),
					'view_item'                => \__( 'View Person', 'orbis-persons' ),
					'view_items'               => \__( 'View Persons', 'orbis-persons' ),
					'search_items'             => \__( 'Search Persons', 'orbis-persons' ),
					'not_found'                => \__( 'No persons found.', 'orbis-persons' ),
					'not_found_in_trash'       => \__( 'No persons found in Trash.', 'orbis-persons' ),
					'parent_item_colon'        => \__( 'Parent Person:', 'orbis-persons' ),
					'all_items'                => \__( 'All Persons', 'orbis-persons' ),
					'archives'                 => \__( 'Person Archives', 'orbis-persons' ),
					'attributes'               => \__( 'Person Attributes', 'orbis-persons' ),
					'insert_into_item'         => \__( 'Insert into person', 'orbis-persons' ),
					'uploaded_to_this_item'    => \__( 'Uploaded to this person', 'orbis-persons' ),
					'featured_image'           => \__( 'Featured image', 'orbis-persons' ),
					'set_featured_image'       => \__( 'Set featured image', 'orbis-persons' ),
					'remove_featured_image'    => \__( 'Remove featured image', 'orbis-persons' ),
					'use_featured_image'       => \__( 'Use as featured image', 'orbis-persons' ),
					'menu_name'                => \__( 'Persons', 'orbis-persons' ),
					'filter_items_list'        => \__( 'Filter persons list', 'orbis-persons' ),
					'filter_by_date'           => \__( 'Filter by date', 'orbis-persons' ),
					'items_list_navigation'    => \__( 'Persons list navigation', 'orbis-persons' ),
					'items_list'               => \__( 'Persons list', 'orbis-persons' ),
					'item_published'           => \__( 'Person published.', 'orbis-persons' ),
					'item_published_privately' => \__( 'Person published privately.', 'orbis-persons' ),
					'item_reverted_to_draft'   => \__( 'Person reverted to draft.', 'orbis-persons' ),
					'item_trashed'             => \__( 'Person trashed.', 'orbis-persons' ),
					'item_scheduled'           => \__( 'Person scheduled.', 'orbis-persons' ),
					'item_updated'             => \__( 'Person updated.', 'orbis-persons' ),
					'item_link'                => \_x( 'Person Link', 'navigation link block title', 'orbis-persons' ),
					'item_link_description'    => \_x( 'A link to a person.', 'navigation link block description', 'orbis-persons' ),
					'name_admin_bar'           => \__( 'Person', 'orbis-persons' ),
					'template_name'            => \__( 'Single item: Person', 'orbis-persons' ),
				],
				'public'           => true,
				'menu_position'    => 30,
				'menu_icon'        => 'dashicons-businessman',
				'supports'         => [
					'title',
					'editor',
					'author',
					'comments',
					'thumbnail',
					'custom-fields',
					'revisions',
					'orbis-contact',
				],
				'has_archive'      => true,
				'delete_with_user' => false,
				'show_in_rest'     => true,
				'rest_base'        => 'orbis/persons',
				'rewrite'          => [
					'slug' => \_x( 'persons', 'slug', 'orbis-persons' ),
				],
			]
		);

		\register_taxonomy(
			'orbis_gender',
			[ 'orbis_person' ],
			[
				'hierarchical'       => true,
				'labels'             => [
					'name'              => \_x( 'Genders', 'taxonomy general name', 'orbis-persons' ),
					'singular_name'     => \_x( 'Gender', 'taxonomy singular name', 'orbis-persons' ),
					'search_items'      => \__( 'Search Genders', 'orbis-persons' ),
					'all_items'         => \__( 'All Genders', 'orbis-persons' ),
					'parent_item'       => \__( 'Parent Gender', 'orbis-persons' ),
					'parent_item_colon' => \__( 'Parent Gender:', 'orbis-persons' ),
					'edit_item'         => \__( 'Edit Gender', 'orbis-persons' ),
					'update_item'       => \__( 'Update Gender', 'orbis-persons' ),
					'add_new_item'      => \__( 'Add New Gender', 'orbis-persons' ),
					'new_item_name'     => \__( 'New Gender Name', 'orbis-persons' ),
					'menu_name'         => \__( 'Genders', 'orbis-persons' ),
				],
				'public'             => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_tagcloud'      => false,
				'show_in_quick_edit' => false,
				'meta_box_cb'        => false,
				'query_var'          => true,
				'rewrite'            => [
					'slug' => \_x( 'genders', 'slug', 'orbis-persons' ),
				],
			]
		);
	}
}
