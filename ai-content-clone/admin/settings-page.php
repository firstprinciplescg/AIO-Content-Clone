<?php
/**
 * @package    AIO_Content_Clone
 * @subpackage Settings_Page
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function() {
	add_options_page(
		__( 'AIO Content Clone', 'aio-content-clone' ),
		__( 'Content Clone', 'aio-content-clone' ),
		'manage_options',
		'aio-content-clone',
		'aio_cc_render_settings_page'
	);
});

add_action( 'admin_init', function() {
	// --- Register all settings ---
	register_setting(
		'aio_cc_settings',
		'md_clone_post_types',
		[
			'type'              => 'array',
			'sanitize_callback' => function( $val ) {
				return array_map( 'sanitize_text_field', (array) $val );
			},
			'default'           => [ 'post' ],
		]
	);

	register_setting(
		'aio_cc_settings',
		'md_clone_bot_agents',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => "ChatGPT\n",
		]
	);

	register_setting(
		'aio_cc_settings',
		'md_clone_output_dir',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_file_name',
			'default'           => 'md-clones',
		]
	);

	// Yes/no toggles
	foreach ( [ 'llms', 'txt', 'json', 'metadata' ] as $feature ) {
		register_setting(
			'aio_cc_settings',
			"md_clone_enable_{$feature}",
			[
				'type'              => 'string',
				'sanitize_callback' => function( $val ) {
					return in_array( $val, [ 'yes', 'no' ], true ) ? $val : 'no';
				},
				'default'           => ( $feature === 'json' || $feature === 'metadata' ) ? 'yes' : 'no',
			]
		);
	}

	register_setting(
		'aio_cc_settings',
		'md_clone_metadata_fields',
		[
			'type'              => 'array',
			'sanitize_callback' => function( $val ) {
				return array_map( 'sanitize_text_field', (array) $val );
			},
			'default'           => [
				'id', 'slug', 'title', 'author', 'date',
				'modified', 'permalink', 'post_type',
				'categories', 'tags', 'excerpt', 'json_url',
			],
		]
	);

	// --- Settings section ---
	add_settings_section(
		'aio_cc_main',
		__( 'Main Settings', 'aio-content-clone' ),
		'__return_null',
		'aio-content-clone'
	);

	// --- Settings fields ---

	// Post Types
	add_settings_field(
		'md_clone_post_types',
		__( 'Post Types', 'aio-content-clone' ),
		function() {
			$post_types = get_post_types( [ 'public' => true ], 'objects' );
			$selected   = (array) get_option( 'md_clone_post_types', [ 'post' ] );
			foreach ( $post_types as $pt ) {
				printf(
					'<label><input type="checkbox" name="md_clone_post_types[]" value="%1$s"%2$s> %3$s</label><br>',
					esc_attr( $pt->name ),
					checked( in_array( $pt->name, $selected, true ), true, false ),
					esc_html( $pt->label )
				);
			}
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	// LLM Bot User-Agents
	add_settings_field(
		'md_clone_bot_agents',
		__( 'LLM Bot User-Agents', 'aio-content-clone' ),
		function() {
			$value = get_option( 'md_clone_bot_agents', "ChatGPT\n" );
			printf(
				'<textarea name="md_clone_bot_agents" rows="5" cols="50" class="large-text">%s</textarea>',
				esc_textarea( $value )
			);
			echo '<p class="description">' . esc_html__( 'List one User-Agent string per line.', 'aio-content-clone' ) . '</p>';
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	// Output Directory
	add_settings_field(
		'md_clone_output_dir',
		__( 'Output Directory', 'aio-content-clone' ),
		function() {
			$value = get_option( 'md_clone_output_dir', 'md-clones' );
			printf(
				'<input type="text" name="md_clone_output_dir" value="%s" class="regular-text">',
				esc_attr( $value )
			);
			echo '<p class="description">' . esc_html__( 'Relative to your uploads folder.', 'aio-content-clone' ) . '</p>';
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	// Enable LLMs
	add_settings_field(
		'md_clone_enable_llms',
		__( 'Allow LLM Access', 'aio-content-clone' ),
		function() {
			$value = get_option( 'md_clone_enable_llms', 'yes' );
			printf(
				'<label><input type="checkbox" name="md_clone_enable_llms" value="yes"%s> %s</label>',
				checked( $value, 'yes', false ),
				esc_html__( 'Allow configured bots to fetch .md files.', 'aio-content-clone' )
			);
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	// Generate .txt fallback
	add_settings_field(
		'md_clone_enable_txt',
		__( 'Generate .txt Fallback', 'aio-content-clone' ),
		function() {
			$value = get_option( 'md_clone_enable_txt', 'no' );
			printf(
				'<label><input type="checkbox" name="md_clone_enable_txt" value="yes"%s> %s</label>',
				checked( $value, 'yes', false ),
				esc_html__( 'Also create a .txt version of each Markdown file.', 'aio-content-clone' )
			);
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	// Generate .json export
	add_settings_field(
		'md_clone_enable_json',
		__( 'Generate .json Export', 'aio-content-clone' ),
		function() {
			$value = get_option( 'md_clone_enable_json', 'yes' );
			printf(
				'<label><input type="checkbox" name="md_clone_enable_json" value="yes"%s> %s</label>',
				checked( $value, 'yes', false ),
				esc_html__( 'Also create a .json structured export of each post.', 'aio-content-clone' )
			);
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	// Embed Metadata
	add_settings_field(
		'md_clone_enable_metadata',
		__( 'Embed Metadata', 'aio-content-clone' ),
		function() {
			$value = get_option( 'md_clone_enable_metadata', 'yes' );
			printf(
				'<label><input type="checkbox" name="md_clone_enable_metadata" value="yes"%s> %s</label>',
				checked( $value, 'yes', false ),
				esc_html__( 'Embed metadata in front matter (and JSON).', 'aio-content-clone' )
			);
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	// Fields to Include
	add_settings_field(
		'md_clone_metadata_fields',
		__( 'Fields to Include', 'aio-content-clone' ),
		function() {
			$all      = [ 'id','slug','title','author','date','modified','permalink','post_type','categories','tags','excerpt','json_url' ];
			$selected = (array) get_option( 'md_clone_metadata_fields', $all );
			foreach ( $all as $field ) {
				printf(
					'<label style="margin-right:15px;"><input type="checkbox" name="md_clone_metadata_fields[]" value="%1$s"%2$s> %3$s</label>',
					esc_attr( $field ),
					checked( in_array( $field, $selected, true ), true, false ),
					esc_html( ucfirst( str_replace( '_', ' ', $field ) ) )
				);
			}
			echo '<p class="description">' . esc_html__( 'Choose which metadata fields get embedded.', 'aio-content-clone' ) . '</p>';
		},
		'aio-content-clone',
		'aio_cc_main'
	);
});

function aio_cc_render_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'AIO Content Clone Settings', 'aio-content-clone' ); ?></h1>
		<form method="post" action="options.php">
			<?php
				settings_fields( 'aio_cc_settings' );
				do_settings_sections( 'aio-content-clone' );
				submit_button();
			?>
		</form>
	</div>
	<?php
}
