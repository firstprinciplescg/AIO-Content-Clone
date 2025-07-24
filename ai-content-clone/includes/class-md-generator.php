<?php
/**
 * @package AIO_Content_Clone
 * @subpackage MD_Generator
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Load Parsedown library
require_once plugin_dir_path( __FILE__ ) . 'libs/Parsedown.php';

class MD_Generator {

    /**
     * Hooked to save_post: generate markdown clone.
     */
    public static function generate_markdown_clone( $post_ID, $post, $update ) {
        // Bail on autosaves, revisions, or if user cannot edit
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
          || wp_is_post_revision( $post_ID )
          || ! current_user_can( 'edit_post', $post_ID )
        ) {
            return;
        }

        // Only for selected post types
        $types = get_option( 'md_clone_post_types', ['post'] );
        if ( ! in_array( $post->post_type, $types, true ) ) {
            return;
        }

        // Build the markdown
        $parser = new Parsedown();
        $content_md = $parser->text( $post->post_content );

        // Save into uploads/md-clones/YYYY/MM/post-123.md
        $upload = wp_upload_dir();
        $dir    = trailingslashit( $upload['basedir'] ) . 'md-clones/' . date_i18n( 'Y/m' );
        wp_mkdir_p( $dir );
        $file = $dir . '/post-' . $post_ID . '.md';

        file_put_contents( $file, $content_md );
    }

    /**
     * AJAX handler to regenerate one post’s markdown.
     */
    public static function ajax_regenerate() {
        check_ajax_referer( 'md_clone_regenerate', 'nonce' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( __( 'You cannot regenerate this post.', 'aio-content-clone' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( __( 'Invalid post ID.', 'aio-content-clone' ) );
        }

        self::generate_markdown_clone( $post_id, $post, true );
        wp_send_json_success( __( 'Markdown regenerated.', 'aio-content-clone' ) );
    }
}

// Hook generation on save
add_action( 'save_post', [ 'MD_Generator', 'generate_markdown_clone' ], 10, 3 );

/**
 * Public download endpoint.
 */
function serve_markdown_to_llm( $post_id ) {
    $upload = wp_upload_dir();
    $path   = trailingslashit( $upload['basedir'] ) . 'md-clones/' . date_i18n( 'Y/m', strtotime( get_post_field( 'post_date', $post_id ) ) ) . '/post-' . $post_id . '.md';

    if ( file_exists( $path ) ) {
        header( 'Content-Type: text/markdown' );
        header( 'Content-Disposition: attachment; filename="post-' . $post_id . '.md"' );
        readfile( $path );
    } else {
        wp_die( __( 'Markdown file not found.', 'aio-content-clone' ) );
    }
}
