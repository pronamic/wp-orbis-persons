<?php
/**
 * Abilities controller
 *
 * @author    Pronamic
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Persons
 */

declare(strict_types=1);

namespace Pronamic\Orbis\Persons;

use WP_Comment;
use WP_Error;

/**
 * Abilities controller class
 *
 * @link https://developer.wordpress.org/news/2025/11/introducing-the-wordpress-abilities-api/
 */
final readonly class AbilitiesController {
	/**
	 * Construct.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct(
		/**
		 * Plugin.
		 */
		private Plugin $plugin
	) {
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup(): void {
		\add_action( 'wp_abilities_api_categories_init', $this->register_ability_categories( ... ) );
		\add_action( 'wp_abilities_api_init', $this->register_abilities( ... ) );

		\add_filter( 'orbis_mcp_server_tools', $this->mcp_server_tools( ... ) );
	}

	/**
	 * Add abilities to the Orbis MCP server tools.
	 *
	 * @link https://github.com/pronamic/orbis-mcp-server
	 * @param string[] $tools Ability names.
	 * @return string[]
	 */
	private function mcp_server_tools( array $tools ): array {
		$tools[] = 'orbis-persons/search';
		$tools[] = 'orbis-persons/get';
		$tools[] = 'orbis-persons/create';
		$tools[] = 'orbis-persons/update';
		$tools[] = 'orbis-persons/comment';

		return $tools;
	}

	/**
	 * Register ability categories.
	 *
	 * @return void
	 */
	private function register_ability_categories(): void {
		\wp_register_ability_category(
			'orbis-persons',
			[
				'label'       => \__( 'Orbis Persons', 'orbis-persons' ),
				'description' => \__( 'Abilities for working with Orbis persons.', 'orbis-persons' ),
			]
		);
	}

	/**
	 * Register abilities.
	 *
	 * @return void
	 */
	private function register_abilities(): void {
		$nullable_integer = [
			'type' => [ 'integer', 'null' ],
		];

		$nullable_string = [
			'type' => [ 'string', 'null' ],
		];

		$link_schema = [
			'type'       => 'object',
			'properties' => [
				'href'  => [ 'type' => 'string' ],
				'title' => [ 'type' => 'string' ],
			],
		];

		$ai_schema = [
			'type'                 => 'object',
			'required'             => [ 'provider_id', 'model_id' ],
			'properties'           => [
				'provider_id'   => [
					'type'        => 'string',
					'description' => \__( 'ID of the AI provider, for example "anthropic", "openai" or "google".', 'orbis-persons' ),
					'minLength'   => 1,
				],
				'provider_name' => [
					'type'        => 'string',
					'description' => \__( 'Name of the AI provider, for example "Anthropic", "OpenAI" or "Google".', 'orbis-persons' ),
				],
				'model_id'      => [
					'type'        => 'string',
					'description' => \__( 'ID of the AI model, for example "claude-opus-4-1", "gpt-5" or "gemini-2.5-pro".', 'orbis-persons' ),
					'minLength'   => 1,
				],
				'model_name'    => [
					'type'        => 'string',
					'description' => \__( 'Name of the AI model, for example "Claude Opus 4.1", "GPT-5" or "Gemini 2.5 Pro".', 'orbis-persons' ),
				],
			],
			'additionalProperties' => false,
		];

		$log_comment_description = \__( 'Optional explanation of the change, for example the source of the data. It is added to the comment that logs the change.', 'orbis-persons' );

		$person_properties = [
			'name'          => [
				'type'        => 'string',
				'description' => \__( 'Full name of the person.', 'orbis-persons' ),
				'minLength'   => 1,
			],
			'content'       => [
				'type'        => 'string',
				'description' => \__( 'Description of or notes about the person.', 'orbis-persons' ),
			],
			'job_title'     => [
				'type'        => 'string',
				'description' => \__( 'Job title, for example "Developer".', 'orbis-persons' ),
			],
			'organization'  => [
				'type'        => 'string',
				'description' => \__( 'Name of the organization the person works for.', 'orbis-persons' ),
			],
			'department'    => [
				'type'        => 'string',
				'description' => \__( 'Department within the organization.', 'orbis-persons' ),
			],
			'email'         => [
				'type'        => 'string',
				'description' => \__( 'Email address.', 'orbis-persons' ),
			],
			'phone_number'  => [
				'type'        => 'string',
				'description' => \__( 'Phone number.', 'orbis-persons' ),
			],
			'mobile_number' => [
				'type'        => 'string',
				'description' => \__( 'Mobile phone number.', 'orbis-persons' ),
			],
			'address'       => [
				'type'        => 'string',
				'description' => \__( 'Street and house number.', 'orbis-persons' ),
			],
			'postcode'      => [
				'type'        => 'string',
				'description' => \__( 'Postcode.', 'orbis-persons' ),
			],
			'city'          => [
				'type'        => 'string',
				'description' => \__( 'City.', 'orbis-persons' ),
			],
			'country'       => [
				'type'        => 'string',
				'description' => \__( 'Country.', 'orbis-persons' ),
			],
			'gender'        => [
				'type'        => 'string',
				'description' => \__( 'Gender: the ID, slug or name of a term of the gender taxonomy. An empty string removes the gender.', 'orbis-persons' ),
			],
			'birth_day'     => [
				'type'        => [ 'integer', 'null' ],
				'description' => \__( 'Day of the month of the birth date. Each part of the birth date is optional, but a day requires a month. Null removes the day.', 'orbis-persons' ),
				'minimum'     => 1,
				'maximum'     => 31,
			],
			'birth_month'   => [
				'type'        => [ 'integer', 'null' ],
				'description' => \__( 'Month (1-12) of the birth date. Null removes the month.', 'orbis-persons' ),
				'minimum'     => 1,
				'maximum'     => 12,
			],
			'birth_year'    => [
				'type'        => [ 'integer', 'null' ],
				'description' => \__( 'Year of the birth date. Null removes the year.', 'orbis-persons' ),
				'minimum'     => 1,
			],
			'iban'          => [
				'type'        => 'string',
				'description' => \__( 'IBAN bank account number.', 'orbis-persons' ),
			],
			'twitter'       => [
				'type'        => 'string',
				'description' => \__( 'Twitter (X) username.', 'orbis-persons' ),
			],
			'facebook'      => [
				'type'        => 'string',
				'description' => \__( 'Facebook URL.', 'orbis-persons' ),
			],
			'linkedin'      => [
				'type'        => 'string',
				'description' => \__( 'LinkedIn URL.', 'orbis-persons' ),
			],
		];

		$person_schema = [
			'type'       => 'object',
			'properties' => [
				'_links'          => [
					'type'        => 'object',
					'description' => \__( 'HAL links of the person. Each link has an href and a title that describes what the link is for.', 'orbis-persons' ),
					'properties'  => [
						'self' => $link_schema,
						'edit' => $link_schema,
					],
				],
				'post_id'         => $nullable_integer,
				'id'              => $nullable_integer,
				'contact_id'      => $nullable_integer,
				'name'            => $nullable_string,
				'content'         => $nullable_string,
				'job_title'       => $nullable_string,
				'organization'    => $nullable_string,
				'department'      => $nullable_string,
				'email'           => $nullable_string,
				'email_addresses' => [
					'type'        => [ 'array', 'null' ],
					'description' => \__( 'Email addresses of the person from Orbis Contacts, each with a label.', 'orbis-persons' ),
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'email' => [ 'type' => 'string' ],
							'label' => [ 'type' => 'string' ],
						],
					],
				],
				'phone_number'    => $nullable_string,
				'mobile_number'   => $nullable_string,
				'address'         => $nullable_string,
				'postcode'        => $nullable_string,
				'city'            => $nullable_string,
				'country'         => $nullable_string,
				'gender'          => [
					'type'       => [ 'object', 'null' ],
					'properties' => [
						'id'   => [ 'type' => 'integer' ],
						'slug' => [ 'type' => 'string' ],
						'name' => [ 'type' => 'string' ],
					],
				],
				'birth_date'      => [
					'type'        => [ 'object', 'null' ],
					'description' => \__( 'Birth date, null when nothing of it is known. Each part can be unknown: when only the year (and month) is known, the age is a range from age_min to age_max. The next birthday is today or later.', 'orbis-persons' ),
					'properties'  => [
						'day'           => $nullable_integer,
						'month'         => $nullable_integer,
						'year'          => $nullable_integer,
						'formatted'     => [ 'type' => 'string' ],
						'age_min'       => $nullable_integer,
						'age_max'       => $nullable_integer,
						'next_birthday' => $nullable_string,
					],
				],
				'iban'            => $nullable_string,
				'twitter'         => $nullable_string,
				'facebook'        => $nullable_string,
				'linkedin'        => $nullable_string,
				'created'         => $nullable_string,
				'modified'        => $nullable_string,
				'comment_count'   => $nullable_integer,
			],
		];

		$comment_schema = [
			'type'       => [ 'object', 'null' ],
			'properties' => [
				'id'       => [ 'type' => 'integer' ],
				'date'     => [ 'type' => 'string' ],
				'author'   => [ 'type' => 'string' ],
				'content'  => [ 'type' => 'string' ],
				'approved' => [ 'type' => 'boolean' ],
				'_links'   => [
					'type'       => 'object',
					'properties' => [
						'self'   => $link_schema,
						'person' => $link_schema,
					],
				],
			],
		];

		$change_output_schema = [
			'type'       => 'object',
			'properties' => [
				'person'  => $person_schema,
				'comment' => $comment_schema,
			],
		];

		\wp_register_ability(
			'orbis-persons/search',
			[
				'label'               => \__( 'Search persons', 'orbis-persons' ),
				'description'         => \__( 'Searches Orbis persons and returns their contact details, organization, gender and birth date. Without a search term all persons are returned, ordered by name. Upcoming birthdays are persons ordered by "birthday": the person whose birthday is next comes first. The output contains the current date of the site. Use orbis-persons/get for the full description of a person and orbis/search-comments for the comments of a person.', 'orbis-persons' ),
				'category'            => 'orbis-persons',
				'input_schema'        => [
					'type'                 => 'object',
					'default'              => [],
					'properties'           => [
						'search'   => [
							'type'        => 'string',
							'description' => \__( 'Search term, matched against the name, the email addresses, the organization and the phone numbers.', 'orbis-persons' ),
						],
						'gender'   => [
							'type'        => 'string',
							'description' => \__( 'Limit the results to persons with this gender: the ID, slug or name of a term of the gender taxonomy.', 'orbis-persons' ),
						],
						'orderby'  => [
							'type'        => 'string',
							'description' => \__( 'Sort the persons by name, creation date, modification date or upcoming birthday. Persons without a known birthday come last when sorting by birthday.', 'orbis-persons' ),
							'enum'        => [ 'name', 'created', 'modified', 'birthday' ],
							'default'     => 'name',
						],
						'order'    => [
							'type'        => 'string',
							'description' => \__( 'Sort order. Defaults to ascending for the name and descending for the dates. Not used when sorting by birthday.', 'orbis-persons' ),
							'enum'        => [ 'asc', 'desc' ],
						],
						'per_page' => [
							'type'        => 'integer',
							'description' => \__( 'Maximum number of persons to return.', 'orbis-persons' ),
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 50,
						],
						'page'     => [
							'type'        => 'integer',
							'description' => \__( 'Page of results to return.', 'orbis-persons' ),
							'minimum'     => 1,
							'default'     => 1,
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'today'    => [ 'type' => 'string' ],
						'total'    => [ 'type' => 'integer' ],
						'page'     => [ 'type' => 'integer' ],
						'per_page' => [ 'type' => 'integer' ],
						'persons'  => [
							'type'  => 'array',
							'items' => $person_schema,
						],
					],
				],
				'execute_callback'    => $this->search_persons( ... ),
				'permission_callback' => fn () => \current_user_can( 'edit_posts' ),
				'meta'                => [
					'show_in_rest' => true,
					'annotations'  => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
				],
			]
		);

		\wp_register_ability(
			'orbis-persons/get',
			[
				'label'               => \__( 'Get person', 'orbis-persons' ),
				'description'         => \__( 'Returns a single Orbis person by its post ID (post_id of the orbis-persons/search output), with the full description.', 'orbis-persons' ),
				'category'            => 'orbis-persons',
				'input_schema'        => [
					'type'                 => 'object',
					'required'             => [ 'post_id' ],
					'properties'           => [
						'post_id' => [
							'type'        => 'integer',
							'description' => \__( 'Post ID of the person.', 'orbis-persons' ),
							'minimum'     => 1,
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => $person_schema,
				'execute_callback'    => $this->get_person( ... ),
				'permission_callback' => fn () => \current_user_can( 'edit_posts' ),
				'meta'                => [
					'show_in_rest' => true,
					'annotations'  => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
				],
			]
		);

		\wp_register_ability(
			'orbis-persons/create',
			[
				'label'               => \__( 'Create person', 'orbis-persons' ),
				'description'         => \__( 'Creates an Orbis person. Only set the fields the user provided. The current user becomes the author of the person. A comment is added to the person that logs the creation, with the AI provider and model. AI assistants must always pass their AI provider and model in the ai input. Use orbis-persons/search first to check that the person does not exist yet.', 'orbis-persons' ),
				'category'            => 'orbis-persons',
				'input_schema'        => [
					'type'                 => 'object',
					'required'             => [ 'name', 'ai' ],
					'properties'           => $person_properties + [
						'comment' => [
							'type'        => 'string',
							'description' => $log_comment_description,
						],
						'ai'      => [
							...$ai_schema,
							'description' => \__( 'The AI provider and model that created this person. AI assistants must always provide this, with the exact provider and model they run on, so that it is clear which persons were created by which AI model.', 'orbis-persons' ),
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => $change_output_schema,
				'execute_callback'    => $this->create_person( ... ),
				'permission_callback' => fn () => \current_user_can( 'edit_posts' ) && \current_user_can( 'publish_posts' ),
				'meta'                => [
					'show_in_rest' => true,
					'annotations'  => [
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					],
				],
			]
		);

		\wp_register_ability(
			'orbis-persons/update',
			[
				'label'               => \__( 'Update person', 'orbis-persons' ),
				'description'         => \__( 'Updates an Orbis person. Only the fields in the input are changed: an empty string clears a field and null clears a part of the birth date. Only change the fields the user asked for. A comment listing the changed fields is added to the person, with the AI provider and model. AI assistants must always pass their AI provider and model in the ai input. Use orbis-persons/search to find the post ID of the person.', 'orbis-persons' ),
				'category'            => 'orbis-persons',
				'input_schema'        => [
					'type'                 => 'object',
					'required'             => [ 'post_id', 'ai' ],
					'properties'           => [
						'post_id' => [
							'type'        => 'integer',
							'description' => \__( 'Post ID of the person, the post_id in the orbis-persons/search output.', 'orbis-persons' ),
							'minimum'     => 1,
						],
					] + $person_properties + [
						'comment' => [
							'type'        => 'string',
							'description' => $log_comment_description,
						],
						'ai'      => [
							...$ai_schema,
							'description' => \__( 'The AI provider and model that updated this person. AI assistants must always provide this, with the exact provider and model they run on, so that it is clear which changes were made by which AI model.', 'orbis-persons' ),
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => $change_output_schema,
				'execute_callback'    => $this->update_person( ... ),
				'permission_callback' => fn () => \current_user_can( 'edit_posts' ),
				'meta'                => [
					'show_in_rest' => true,
					'annotations'  => [
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					],
				],
			]
		);

		\wp_register_ability(
			'orbis-persons/comment',
			[
				'label'               => \__( 'Comment on person', 'orbis-persons' ),
				'description'         => \__( 'Adds a comment from the current user to an Orbis person, for example a note about a conversation with the person. AI assistants must always pass their AI provider and model in the ai input. Use orbis-persons/search to find the post ID of the person and orbis/search-comments for the existing comments of a person.', 'orbis-persons' ),
				'category'            => 'orbis-persons',
				'input_schema'        => [
					'type'                 => 'object',
					'required'             => [ 'post_id', 'content', 'ai' ],
					'properties'           => [
						'post_id' => [
							'type'        => 'integer',
							'description' => \__( 'Post ID of the person, the post_id in the orbis-persons/search output.', 'orbis-persons' ),
							'minimum'     => 1,
						],
						'content' => [
							'type'        => 'string',
							'description' => \__( 'Text of the comment.', 'orbis-persons' ),
							'minLength'   => 1,
						],
						'ai'      => [
							...$ai_schema,
							'description' => \__( 'The AI provider and model that wrote this comment. AI assistants must always provide this, with the exact provider and model they run on, so that it is clear which comments were written by which AI model.', 'orbis-persons' ),
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'comment' => $comment_schema,
						'person'  => $person_schema,
					],
				],
				'execute_callback'    => $this->comment_on_person( ... ),
				'permission_callback' => fn () => \current_user_can( 'edit_posts' ),
				'meta'                => [
					'show_in_rest' => true,
					'annotations'  => [
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					],
				],
			]
		);
	}

	/**
	 * Search persons.
	 *
	 * @param mixed $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	private function search_persons( mixed $input = [] ): array|WP_Error {
		$input = \is_array( $input ) ? $input : [];

		$args = [];

		foreach ( [ 'search', 'gender', 'orderby', 'order' ] as $key ) {
			if ( \is_string( $input[ $key ] ?? null ) ) {
				$args[ $key ] = $input[ $key ];
			}
		}

		foreach ( [ 'per_page', 'page' ] as $key ) {
			if ( \is_int( $input[ $key ] ?? null ) ) {
				$args[ $key ] = $input[ $key ];
			}
		}

		$result = $this->plugin->persons->search( $args );

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		return [
			'today'    => \wp_date( 'Y-m-d' ),
			'total'    => $result['total'],
			'page'     => $result['page'],
			'per_page' => $result['per_page'],
			'persons'  => \array_map( fn ( Person $person ): array => $this->format_person( $person, true ), $result['persons'] ),
		];
	}

	/**
	 * Get person.
	 *
	 * @param mixed $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	private function get_person( mixed $input = [] ): array|WP_Error {
		$input = \is_array( $input ) ? $input : [];

		$post_id = $this->get_post_id( $input );

		if ( $post_id instanceof WP_Error ) {
			return $post_id;
		}

		$person = $this->get_readable_person( $post_id );

		if ( $person instanceof WP_Error ) {
			return $person;
		}

		return $this->format_person( $person, false );
	}

	/**
	 * Create person.
	 *
	 * @param mixed $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	private function create_person( mixed $input = [] ): array|WP_Error {
		$input = \is_array( $input ) ? $input : [];

		$comment_meta = $this->get_ability_meta( 'orbis-persons/create', $input );

		if ( $comment_meta instanceof WP_Error ) {
			return $comment_meta;
		}

		$person = $this->plugin->persons->create( $this->get_person_data( $input ) );

		if ( $person instanceof WP_Error ) {
			return $person;
		}

		if ( null === $person->post_id ) {
			return new WP_Error( 'orbis_persons_create_failed', \__( 'The person could not be created.', 'orbis-persons' ) );
		}

		$comment = $this->add_log_comment(
			$person->post_id,
			$this->get_log_text( \__( 'Person created.', 'orbis-persons' ), $input ),
			$comment_meta
		);

		return $this->get_change_output( $person->post_id, $comment );
	}

	/**
	 * Update person.
	 *
	 * @param mixed $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	private function update_person( mixed $input = [] ): array|WP_Error {
		$input = \is_array( $input ) ? $input : [];

		$comment_meta = $this->get_ability_meta( 'orbis-persons/update', $input );

		if ( $comment_meta instanceof WP_Error ) {
			return $comment_meta;
		}

		$post_id = $this->get_post_id( $input );

		if ( $post_id instanceof WP_Error ) {
			return $post_id;
		}

		$before = $this->get_readable_person( $post_id );

		if ( $before instanceof WP_Error ) {
			return $before;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'orbis_persons_cannot_edit',
				\__( 'You are not allowed to edit this person.', 'orbis-persons' ),
				[ 'status' => 403 ]
			);
		}

		$data = $this->get_person_data( $input );

		if ( [] === $data ) {
			return new WP_Error(
				'orbis_persons_nothing_to_update',
				\__( 'No fields to update, pass at least one person field.', 'orbis-persons' ),
				[ 'status' => 400 ]
			);
		}

		$after = $this->plugin->persons->update( $post_id, $data );

		if ( $after instanceof WP_Error ) {
			return $after;
		}

		$changes = $this->get_changes( $before, $after );

		$comment = null;

		if ( [] !== $changes ) {
			$comment = $this->add_log_comment(
				$post_id,
				$this->get_log_text(
					/* translators: %s: comma-separated list of changed fields */
					\sprintf( \__( 'Person updated: %s.', 'orbis-persons' ), \implode( ', ', $changes ) ),
					$input
				),
				$comment_meta
			);
		}

		return $this->get_change_output( $post_id, $comment );
	}

	/**
	 * Comment on person.
	 *
	 * @param mixed $input Input.
	 * @return array<string, mixed>|WP_Error
	 */
	private function comment_on_person( mixed $input = [] ): array|WP_Error {
		$input = \is_array( $input ) ? $input : [];

		$comment_meta = $this->get_ability_meta( 'orbis-persons/comment', $input );

		if ( $comment_meta instanceof WP_Error ) {
			return $comment_meta;
		}

		$post_id = $this->get_post_id( $input );

		if ( $post_id instanceof WP_Error ) {
			return $post_id;
		}

		$person = $this->get_readable_person( $post_id );

		if ( $person instanceof WP_Error ) {
			return $person;
		}

		if ( ! \comments_open( $post_id ) ) {
			return new WP_Error( 'orbis_persons_comments_closed', \__( 'Comments are closed for this person.', 'orbis-persons' ), [ 'status' => 400 ] );
		}

		$content = \is_string( $input['content'] ?? null ) ? \trim( $input['content'] ) : '';

		if ( '' === $content ) {
			return new WP_Error( 'orbis_persons_missing_content', \__( 'The comment text is required.', 'orbis-persons' ), [ 'status' => 400 ] );
		}

		$user = \wp_get_current_user();

		/**
		 * Use `wp_new_comment()` instead of `wp_insert_comment()`, so that
		 * comments added via this ability are handled like comments from
		 * the comment form, including filters and notifications.
		 */
		$comment_id = \wp_new_comment(
			\wp_slash(
				[
					'comment_post_ID'      => $post_id,
					'comment_content'      => $content,
					'comment_type'         => 'comment',
					'comment_parent'       => 0,
					'user_id'              => $user->ID,
					'comment_author'       => $user->display_name,
					'comment_author_email' => $user->user_email,
					'comment_author_url'   => $user->user_url,
					'comment_meta'         => $comment_meta,
				]
			),
			true
		);

		if ( $comment_id instanceof WP_Error ) {
			return $comment_id;
		}

		$comment = false === $comment_id ? null : \get_comment( $comment_id );

		if ( ! $comment instanceof WP_Comment ) {
			return new WP_Error( 'orbis_persons_comment_failed', \__( 'The comment could not be added.', 'orbis-persons' ) );
		}

		$output = $this->get_change_output( $post_id, $comment );

		return [
			'comment' => $output['comment'],
			'person'  => $output['person'],
		];
	}

	/**
	 * Get the post ID from the `post_id` input.
	 *
	 * @param array<mixed> $input Input.
	 * @return int|WP_Error
	 */
	private function get_post_id( array $input ): int|WP_Error {
		if ( ! \is_int( $input['post_id'] ?? null ) ) {
			return new WP_Error( 'orbis_persons_missing_post_id', \__( 'The post ID of the person is required.', 'orbis-persons' ), [ 'status' => 400 ] );
		}

		return $input['post_id'];
	}

	/**
	 * Get the person that the current user can read.
	 *
	 * @param int $post_id Post ID.
	 * @return Person|WP_Error
	 */
	private function get_readable_person( int $post_id ): Person|WP_Error {
		$person = $this->plugin->persons->get( $post_id );

		if ( null === $person || ! \current_user_can( 'read_post', $post_id ) ) {
			return new WP_Error(
				'orbis_persons_person_not_found',
				/* translators: %d: post ID */
				\sprintf( \__( 'Person with post ID %d not found.', 'orbis-persons' ), $post_id ),
				[ 'status' => 404 ]
			);
		}

		return $person;
	}

	/**
	 * Get the person fields from the input.
	 *
	 * @param array<mixed> $input Input.
	 * @return array<string, mixed>
	 */
	private function get_person_data( array $input ): array {
		$keys = \array_merge(
			[ 'name', 'content', 'gender', 'birth_day', 'birth_month', 'birth_year' ],
			\array_keys( $this->plugin->persons->get_meta_keys() )
		);

		$data = [];

		foreach ( $keys as $key ) {
			if ( \array_key_exists( $key, $input ) ) {
				$data[ $key ] = $input[ $key ];
			}
		}

		return $data;
	}

	/**
	 * Get the labels of the fields that differ between two versions of a person.
	 *
	 * @param Person $before Person before the update.
	 * @param Person $after  Person after the update.
	 * @return string[]
	 */
	private function get_changes( Person $before, Person $after ): array {
		$labels = [
			'name'          => \__( 'Name', 'orbis-persons' ),
			'content'       => \__( 'Description', 'orbis-persons' ),
			'job_title'     => \_x( 'Title', 'person', 'orbis-persons' ),
			'organization'  => \_x( 'Organization', 'person', 'orbis-persons' ),
			'department'    => \_x( 'Department', 'person', 'orbis-persons' ),
			'gender'        => \__( 'Gender', 'orbis-persons' ),
			'email'         => \__( 'Email Address', 'orbis-persons' ),
			'phone_number'  => \__( 'Phone Number', 'orbis-persons' ),
			'mobile_number' => \__( 'Mobile Number', 'orbis-persons' ),
			'address'       => \__( 'Address', 'orbis-persons' ),
			'postcode'      => \__( 'Postcode', 'orbis-persons' ),
			'city'          => \__( 'City', 'orbis-persons' ),
			'country'       => \__( 'Country', 'orbis-persons' ),
			'birth_date'    => \__( 'Birth Date', 'orbis-persons' ),
			'iban'          => \__( 'IBAN', 'orbis-persons' ),
			'twitter'       => \__( 'Twitter Username', 'orbis-persons' ),
			'facebook'      => \__( 'Facebook URL', 'orbis-persons' ),
			'linkedin'      => \__( 'LinkedIn URL', 'orbis-persons' ),
		];

		$before = $before->to_array();
		$after  = $after->to_array();

		$changes = [];

		foreach ( $labels as $key => $label ) {
			if ( $before[ $key ] !== $after[ $key ] ) {
				$changes[] = $label;
			}
		}

		return $changes;
	}

	/**
	 * Get the text of a log comment, with the optional explanation from the `comment` input.
	 *
	 * @param string       $text  Text.
	 * @param array<mixed> $input Input.
	 * @return string
	 */
	private function get_log_text( string $text, array $input ): string {
		$explanation = \is_string( $input['comment'] ?? null ) ? \trim( \sanitize_textarea_field( $input['comment'] ) ) : '';

		if ( '' === $explanation ) {
			return $text;
		}

		return $text . "\n\n" . $explanation;
	}

	/**
	 * Add a comment that logs a change made through an ability.
	 *
	 * Uses `wp_insert_comment()`, because `wp_new_comment()` rejects
	 * identical comments, for example two updates of the same field, as
	 * duplicates. Log comments are added even when comments are closed.
	 *
	 * @param int                   $post_id      Post ID.
	 * @param string                $content      Content.
	 * @param array<string, string> $comment_meta Comment meta.
	 * @return WP_Comment|null
	 */
	private function add_log_comment( int $post_id, string $content, array $comment_meta ): ?WP_Comment {
		$user = \wp_get_current_user();

		$comment_id = \wp_insert_comment(
			\wp_slash(
				[
					'comment_post_ID'      => $post_id,
					'comment_content'      => $content,
					'comment_type'         => 'comment',
					'comment_approved'     => 1,
					'user_id'              => $user->ID,
					'comment_author'       => $user->display_name,
					'comment_author_email' => $user->user_email,
					'comment_author_url'   => $user->user_url,
					'comment_meta'         => $comment_meta,
				]
			)
		);

		$comment = false === $comment_id ? null : \get_comment( $comment_id );

		return $comment instanceof WP_Comment ? $comment : null;
	}

	/**
	 * Get ability meta, with the ability name and the AI provider and model from the input.
	 *
	 * The AI provider and model are required.
	 *
	 * @param string       $ability_name Ability name.
	 * @param array<mixed> $input        Input.
	 * @return array<string, string>|WP_Error
	 */
	private function get_ability_meta( string $ability_name, array $input ): array|WP_Error {
		$meta = [
			'_orbis_person_ability_name' => $ability_name,
		];

		$ai = \is_array( $input['ai'] ?? null ) ? $input['ai'] : [];

		$ai_meta_keys = [
			'provider_id'   => '_orbis_person_ai_provider_id',
			'provider_name' => '_orbis_person_ai_provider_name',
			'model_id'      => '_orbis_person_ai_model_id',
			'model_name'    => '_orbis_person_ai_model_name',
		];

		foreach ( $ai_meta_keys as $key => $meta_key ) {
			$value = \is_string( $ai[ $key ] ?? null ) ? \sanitize_text_field( $ai[ $key ] ) : '';

			if ( '' !== $value ) {
				$meta[ $meta_key ] = $value;
			}
		}

		if ( ! \array_key_exists( '_orbis_person_ai_provider_id', $meta ) || ! \array_key_exists( '_orbis_person_ai_model_id', $meta ) ) {
			return new WP_Error(
				'orbis_persons_missing_ai',
				\__( 'The AI provider and model are required: pass the provider_id and model_id of the exact AI provider and model you run on in the ai input.', 'orbis-persons' ),
				[ 'status' => 400 ]
			);
		}

		return $meta;
	}

	/**
	 * Get the output of a change, the person and the comment that logs the change.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_Comment|null $comment Comment.
	 * @return array{person: array<string, mixed>|null, comment: array<string, mixed>|null}
	 */
	private function get_change_output( int $post_id, ?WP_Comment $comment ): array {
		$person = $this->plugin->persons->get( $post_id );

		return [
			'person'  => null === $person ? null : $this->format_person( $person, false ),
			'comment' => null === $comment ? null : $this->format_comment( $comment ),
		];
	}

	/**
	 * Format person for ability output.
	 *
	 * @param Person $person  Person.
	 * @param bool   $excerpt Whether to shorten the description to an excerpt.
	 * @return array<string, mixed>
	 */
	private function format_person( Person $person, bool $excerpt ): array {
		$data = $person->to_array();

		if ( $excerpt && null !== $person->content ) {
			$data['content'] = \wp_html_excerpt( \wp_strip_all_tags( $person->content ), 500, '…' );
		}

		$links = [];

		$permalink = $person->get_permalink();

		if ( null !== $permalink ) {
			$links['self'] = [
				'href'  => $permalink,
				'title' => \__( 'Orbis page of this person. Show this link to the user so they can open the person directly.', 'orbis-persons' ),
			];
		}

		$edit_link = null === $person->post_id ? null : \get_edit_post_link( $person->post_id, 'raw' );

		if ( \is_string( $edit_link ) ) {
			$links['edit'] = [
				'href'  => $edit_link,
				'title' => \__( 'WordPress admin page to edit this person.', 'orbis-persons' ),
			];
		}

		return [ '_links' => $links ] + $data;
	}

	/**
	 * Format comment for ability output.
	 *
	 * @param WP_Comment $comment Comment.
	 * @return array<string, mixed>
	 */
	private function format_comment( WP_Comment $comment ): array {
		return [
			'id'       => (int) $comment->comment_ID,
			'date'     => $comment->comment_date,
			'author'   => $comment->comment_author,
			'content'  => $comment->comment_content,
			'approved' => '1' === (string) $comment->comment_approved,
			'_links'   => [
				'self'   => [
					'href'  => \get_comment_link( $comment ),
					'title' => \__( 'Web page of the comment. Show this link to the user so they can open the comment directly.', 'orbis-persons' ),
				],
				'person' => [
					'href'  => (string) \get_permalink( (int) $comment->comment_post_ID ),
					'title' => \__( 'Orbis page of the person.', 'orbis-persons' ),
				],
			],
		];
	}
}
