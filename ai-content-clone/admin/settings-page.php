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
	// Register all settings
	register_setting(
		'aio_cc_settings',
		'md_clone_post_types',
		[
			'type'              => 'array',
			'sanitize_callback' => function( $val ) {
				return array_map( 'sanitize_text_field', (array) $val );
			},
			'default' => [ 'post' ],
		]
	);

	register_setting(
		'aio_cc_settings',
		'md_clone_bot_agents',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default' => "ChatGPT\n",
		]
	);

	register_setting(
		'aio_cc_settings',
		'md_clone_output_dir',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_file_name',
			'default' => 'md-clones',
		]
	);

	foreach ( [ 'llms', 'txt', 'json', 'metadata' ] as $feature ) {
		register_setting(
			'aio_cc_settings',
			"md_clone_enable_{$feature}",
			[
				'type'              => 'string',
				'sanitize_callback' => function( $val ) {
					return in_array( $val, [ 'yes', 'no' ], true ) ? $val : 'no';
				},
				'default' => ( $feature === 'json' || $feature === 'metadata' ) ? 'yes' : 'no',
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
			'default' => [
				'id', 'slug', 'title', 'author', 'date',
				'modified', 'permalink', 'post_type',
				'categories', 'tags', 'excerpt', 'json_url',
			],
		]
	);

	// LLMs.txt Manifest settings
	register_setting(
		'aio_cc_settings',
		'md_clone_generate_llms_manifest',
		[
			'type'              => 'string',
			'sanitize_callback' => function( $v ) {
				return in_array( $v, [ 'yes', 'no' ], true ) ? $v : 'no';
			},
			'default' => 'no',
		]
	);

	register_setting(
		'aio_cc_settings',
		'md_clone_llms_filename',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_file_name',
			'default' => 'llms.txt',
		]
	);

	register_setting(
		'aio_cc_settings',
		'md_clone_llms_location',
		[
			'type'              => 'string',
			'sanitize_callback' => function( $v ) {
				return in_array( $v, [ 'upload', 'root' ], true ) ? $v : 'upload';
			},
			'default' => 'upload',
		]
	);

	// Main settings section
	add_settings_section( 'aio_cc_main', __( 'Main Settings', 'aio-content-clone' ), '__return_null', 'aio-content-clone' );

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

	foreach ( [
		'md_clone_enable_llms'     => 'Allow LLM Access',
		'md_clone_enable_txt'      => 'Generate .txt Fallback',
		'md_clone_enable_json'     => 'Generate .json Export',
		'md_clone_enable_metadata' => 'Embed Metadata',
	] as $option => $label ) {
		add_settings_field(
			$option,
			__( $label, 'aio-content-clone' ),
			function() use ( $option, $label ) {
				$value = get_option( $option, 'no' );
				printf(
					'<label><input type="checkbox" name="%1$s" value="yes"%2$s> %3$s</label>',
					esc_attr( $option ),
					checked( $value, 'yes', false ),
					esc_html__( $label, 'aio-content-clone' )
				);
			},
			'aio-content-clone',
			'aio_cc_main'
		);
	}

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

	// LLMs.txt specific settings
	add_settings_field(
		'md_clone_generate_llms_manifest',
		__( 'Generate llms.txt', 'aio-content-clone' ),
		function() {
			$val = get_option( 'md_clone_generate_llms_manifest', 'no' );
			printf(
				'<label><input type="checkbox" name="md_clone_generate_llms_manifest" value="yes"%s> %s</label>',
				checked( $val, 'yes', false ),
				esc_html__( 'Enable auto-generation of llms.txt based on bot list.', 'aio-content-clone' )
			);
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	add_settings_field(
		'md_clone_llms_filename',
		__( 'Manifest Filename', 'aio-content-clone' ),
		function() {
			$value = get_option( 'md_clone_llms_filename', 'llms.txt' );
			printf(
				'<input type="text" name="md_clone_llms_filename" value="%s" class="regular-text">',
				esc_attr( $value )
			);
		},
		'aio-content-clone',
		'aio_cc_main'
	);

	add_settings_field(
		'md_clone_llms_location',
		__( 'Manifest Location', 'aio-content-clone' ),
		function() {
			$val = get_option( 'md_clone_llms_location', 'upload' );
			?>
			<select name="md_clone_llms_location">
				<option value="upload" <?php selected( $val, 'upload' ); ?>><?php esc_html_e( 'Uploads folder', 'aio-content-clone' ); ?></option>
				<option value="root"   <?php selected( $val, 'root' ); ?>><?php esc_html_e( 'Site root (if writable)', 'aio-content-clone' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Choose where the manifest will be written.', 'aio-content-clone' ); ?></p>
			<?php
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

		<?php
		// Show current manifest location
		$enabled  = get_option( 'md_clone_generate_llms_manifest', 'no' );
		if ( 'yes' === $enabled ) {
			$filename = sanitize_file_name( get_option( 'md_clone_llms_filename', 'llms.txt' ) );
			$location = get_option( 'md_clone_llms_location', 'upload' );
			$upload   = wp_upload_dir();
			$url      = ( $location === 'upload' )
				? trailingslashit( $upload['baseurl'] ) . $filename
				: site_url( '/' . $filename );
			?>
			<hr>
			<h2><?php esc_html_e( 'LLMs.txt Manifest Preview', 'aio-content-clone' ); ?></h2>
			<p>
				<strong><?php esc_html_e( 'Public URL:', 'aio-content-clone' ); ?></strong><br>
				<a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_html( $url ); ?></a>
			</p>
			<?php
		}
		?>
	</div>
	<?php
}
