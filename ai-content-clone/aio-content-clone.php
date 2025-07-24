<?php

/**

 * Plugin Name:     AIO Content Clone

 * Plugin URI:      https://firstprinciplescg.com/aio-content-clone/

 * Description:     Generates a Markdown “clone” of each post on save, for feeding to LLMs.

 * Version:         1.0.0

 * Author:          Dustin Moore

 * Text Domain:     aio-content-clone

 * Domain Path:     /languages

 */



defined( 'ABSPATH' ) || exit;



// Core class and Parsedown

require_once plugin_dir_path( __FILE__ ) . 'includes/class-md-generator.php';



if ( is_admin() ) {

    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';

    require_once plugin_dir_path( __FILE__ ) . 'admin/meta-box.php';



    // AJAX handler for manual regenerate

    add_action( 'wp_ajax_md_clone_regenerate', [ 'MD_Generator', 'ajax_regenerate' ] );

}

register_activation_hook( __FILE__, [ 'MD_Generator', 'generate_llms_manifest' ] );
// Regenerate manifest when related settings change
add_action( 'update_option_md_clone_bot_agents',               [ 'MD_Generator', 'generate_llms_manifest' ] );
add_action( 'update_option_md_clone_generate_llms_manifest',  [ 'MD_Generator', 'generate_llms_manifest' ] );
add_action( 'update_option_md_clone_llms_filename',           [ 'MD_Generator', 'generate_llms_manifest' ] );
add_action( 'update_option_md_clone_llms_location',           [ 'MD_Generator', 'generate_llms_manifest' ] );




// Serve markdown on a simple endpoint (optional)

add_action( 'init', function() {

    if ( isset( $_GET['md_clone_download'] ) && intval( $_GET['md_clone_download'] ) ) {

        serve_markdown_to_llm( intval( $_GET['md_clone_download'] ) );

        exit;

    }

});

