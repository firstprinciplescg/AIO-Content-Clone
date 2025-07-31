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
        // Check if LLM access is enabled and validate user agent
        if ( 'yes' === get_option( 'md_clone_enable_llms', 'no' ) ) {
            $allowed_agents = array_filter( array_map( 'trim', preg_split( '/\r?\n/', get_option( 'md_clone_bot_agents', '' ) ) ) );
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $is_allowed = false;
            foreach ( $allowed_agents as $agent ) {
                if ( stripos( $user_agent, $agent ) !== false ) {
                    $is_allowed = true;
                    break;
                }
            }
            
            if ( ! $is_allowed && ! current_user_can( 'edit_posts' ) ) {
                wp_die( __( 'Access denied. This content is only available to authorized LLMs.', 'aio-content-clone' ) );
            }
        }
        
        serve_markdown_to_llm( intval( $_GET['md_clone_download'] ) );
        exit;
    }
});

