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
	 *
	 * Orbis core still registers the `orbis_person` post type on `init`
	 * priority 10, this plugin registers it again on priority 20 so that
	 * this registration wins.
	 */
	public function __construct() {
		\add_action( 'init', $this->init( ... ), 20 );
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
				'label'         => \__( 'Persons', 'orbis-persons' ),
				'labels'        => [
					'name'               => \__( 'Persons', 'orbis-persons' ),
					'singular_name'      => \__( 'Person', 'orbis-persons' ),
					'add_new'            => \_x( 'Add New', 'orbis_person', 'orbis-persons' ),
					'add_new_item'       => \__( 'Add New Person', 'orbis-persons' ),
					'edit_item'          => \__( 'Edit Person', 'orbis-persons' ),
					'new_item'           => \__( 'New Person', 'orbis-persons' ),
					'all_items'          => \__( 'All Persons', 'orbis-persons' ),
					'view_item'          => \__( 'View Person', 'orbis-persons' ),
					'search_items'       => \__( 'Search Persons', 'orbis-persons' ),
					'not_found'          => \__( 'No persons found.', 'orbis-persons' ),
					'not_found_in_trash' => \__( 'No persons found in Trash.', 'orbis-persons' ),
					'parent_item_colon'  => \__( 'Parent Person:', 'orbis-persons' ),
					'menu_name'          => \__( 'Persons', 'orbis-persons' ),
					'name_admin_bar'     => \__( 'Person', 'orbis-persons' ),
				],
				'public'        => true,
				'menu_position' => 30,
				'menu_icon'     => 'dashicons-businessman',
				'supports'      => [
					'title',
					'editor',
					'author',
					'comments',
					'thumbnail',
					'custom-fields',
					'revisions',
					'orbis-contact',
				],
				'has_archive'   => true,
				'show_in_rest'  => true,
				'rest_base'     => 'orbis/persons',
				'rewrite'       => [
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

		\register_taxonomy(
			'orbis_person_category',
			[ 'orbis_person' ],
			[
				'hierarchical' => true,
				'labels'       => [
					'name'              => \_x( 'Categories', 'taxonomy general name', 'orbis-persons' ),
					'singular_name'     => \_x( 'Category', 'taxonomy singular name', 'orbis-persons' ),
					'search_items'      => \__( 'Search Categories', 'orbis-persons' ),
					'all_items'         => \__( 'All Categories', 'orbis-persons' ),
					'parent_item'       => \__( 'Parent Category', 'orbis-persons' ),
					'parent_item_colon' => \__( 'Parent Category:', 'orbis-persons' ),
					'edit_item'         => \__( 'Edit Category', 'orbis-persons' ),
					'update_item'       => \__( 'Update Category', 'orbis-persons' ),
					'add_new_item'      => \__( 'Add New Category', 'orbis-persons' ),
					'new_item_name'     => \__( 'New Category Name', 'orbis-persons' ),
					'menu_name'         => \__( 'Categories', 'orbis-persons' ),
				],
				'show_ui'      => true,
				'query_var'    => true,
				'rewrite'      => [
					'slug' => \_x( 'person-category', 'slug', 'orbis-persons' ),
				],
			]
		);
	}
}
