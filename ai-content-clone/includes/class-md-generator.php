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



        // Build the markdown content
        $content_md = self::build_markdown_content( $post );

        // Create output directory
        $upload_dir = get_option( 'md_clone_output_dir', 'md-clones' );
        $upload = wp_upload_dir();
        $dir = trailingslashit( $upload['basedir'] ) . $upload_dir . '/' . date_i18n( 'Y/m' );
        wp_mkdir_p( $dir );

        // Generate files based on settings
        $base_filename = 'post-' . $post_ID;
        
        // Always generate markdown file
        $md_file = $dir . '/' . $base_filename . '.md';
        file_put_contents( $md_file, $content_md );

        // Generate TXT fallback if enabled
        if ( 'yes' === get_option( 'md_clone_enable_txt', 'no' ) ) {
            $txt_file = $dir . '/' . $base_filename . '.txt';
            file_put_contents( $txt_file, strip_tags( $content_md ) );
        }

        // Generate JSON export if enabled
        if ( 'yes' === get_option( 'md_clone_enable_json', 'yes' ) ) {
            $json_data = self::build_json_data( $post, $content_md );
            $json_file = $dir . '/' . $base_filename . '.json';
            file_put_contents( $json_file, json_encode( $json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        }

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

    /**
     * Generate or update llms.txt manifest.
     */
    public static function generate_llms_manifest() {
        // Bail if disabled
        if ( 'yes' !== get_option( 'md_clone_generate_llms_manifest', 'no' ) ) {
            return;
        }

        // Gather & sanitize bot-agent list
        $raw   = get_option( 'md_clone_bot_agents', '' );
        $lines = preg_split( '/\r?\n/', trim( $raw ) );
        $agents = array_filter( array_map( 'trim', $lines ) );

        if ( empty( $agents ) ) {
            return;
        }

        // Build content
        $content = '';
        foreach ( $agents as $ua ) {
            $content .= "User-agent: {$ua}\n";
            $content .= "Disallow:\n\n";
        }

        // Determine path
        $filename = sanitize_file_name( get_option( 'md_clone_llms_filename', 'llms.txt' ) );
        if ( 'root' === get_option( 'md_clone_llms_location', 'upload' ) ) {
            $path = ABSPATH . $filename;
        } else {
            $upload = wp_upload_dir();
            $path   = trailingslashit( $upload['basedir'] ) . $filename;
        }

        // Attempt write
        @file_put_contents( $path, $content );
    }

    /**
     * Build complete markdown content with metadata if enabled.
     */
    private static function build_markdown_content( $post ) {
        $content = '';
        
        // Add metadata header if enabled
        if ( 'yes' === get_option( 'md_clone_enable_metadata', 'yes' ) ) {
            $content .= self::build_metadata_header( $post );
            $content .= "\n---\n\n";
        }
        
        // Add title
        $content .= "# " . $post->post_title . "\n\n";
        
        // Convert HTML content to markdown
        $content .= self::html_to_markdown( $post->post_content );
        
        return $content;
    }

    /**
     * Build metadata header in YAML format.
     */
    private static function build_metadata_header( $post ) {
        $fields = get_option( 'md_clone_metadata_fields', [
            'id', 'slug', 'title', 'author', 'date',
            'modified', 'permalink', 'post_type',
            'categories', 'tags', 'excerpt', 'json_url'
        ] );
        
        $metadata = [];
        
        foreach ( $fields as $field ) {
            switch ( $field ) {
                case 'id':
                    $metadata['id'] = $post->ID;
                    break;
                case 'slug':
                    $metadata['slug'] = $post->post_name;
                    break;
                case 'title':
                    $metadata['title'] = $post->post_title;
                    break;
                case 'author':
                    $author = get_userdata( $post->post_author );
                    $metadata['author'] = $author ? $author->display_name : '';
                    break;
                case 'date':
                    $metadata['date'] = $post->post_date;
                    break;
                case 'modified':
                    $metadata['modified'] = $post->post_modified;
                    break;
                case 'permalink':
                    $metadata['permalink'] = get_permalink( $post->ID );
                    break;
                case 'post_type':
                    $metadata['post_type'] = $post->post_type;
                    break;
                case 'categories':
                    $cats = get_the_category( $post->ID );
                    $metadata['categories'] = array_map( function( $cat ) { return $cat->name; }, $cats );
                    break;
                case 'tags':
                    $tags = get_the_tags( $post->ID );
                    $metadata['tags'] = $tags ? array_map( function( $tag ) { return $tag->name; }, $tags ) : [];
                    break;
                case 'excerpt':
                    $metadata['excerpt'] = $post->post_excerpt;
                    break;
                case 'json_url':
                    $metadata['json_url'] = site_url( '?md_clone_download=' . $post->ID . '&format=json' );
                    break;
            }
        }
        
        $yaml = "---\n";
        foreach ( $metadata as $key => $value ) {
            if ( is_array( $value ) ) {
                $yaml .= "$key:\n";
                foreach ( $value as $item ) {
                    $yaml .= "  - " . addslashes( $item ) . "\n";
                }
            } else {
                $yaml .= "$key: " . addslashes( $value ) . "\n";
            }
        }
        
        return $yaml;
    }

    /**
     * Simple HTML to Markdown converter.
     */
    private static function html_to_markdown( $html ) {
        // Apply WordPress filters to get processed content
        $content = apply_filters( 'the_content', $html );
        
        // Basic HTML to Markdown conversions
        $conversions = [
            // Headers
            '/<h1[^>]*>(.*?)<\/h1>/i' => '# $1',
            '/<h2[^>]*>(.*?)<\/h2>/i' => '## $1',
            '/<h3[^>]*>(.*?)<\/h3>/i' => '### $1',
            '/<h4[^>]*>(.*?)<\/h4>/i' => '#### $1',
            '/<h5[^>]*>(.*?)<\/h5>/i' => '##### $1',
            '/<h6[^>]*>(.*?)<\/h6>/i' => '###### $1',
            
            // Bold and italic
            '/<strong[^>]*>(.*?)<\/strong>/i' => '**$1**',
            '/<b[^>]*>(.*?)<\/b>/i' => '**$1**',
            '/<em[^>]*>(.*?)<\/em>/i' => '*$1*',
            '/<i[^>]*>(.*?)<\/i>/i' => '*$1*',
            
            // Links
            '/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/i' => '[$2]($1)',
            
            // Images
            '/<img[^>]*src=["\']([^"\']*)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*\/?>/i' => '![$2]($1)',
            '/<img[^>]*alt=["\']([^"\']*)["\'][^>]*src=["\']([^"\']*)["\'][^>]*\/?>/i' => '![$1]($2)',
            '/<img[^>]*src=["\']([^"\']*)["\'][^>]*\/?>/i' => '![]($1)',
            
            // Lists
            '/<ul[^>]*>/i' => '',
            '/<\/ul>/i' => '',
            '/<ol[^>]*>/i' => '',
            '/<\/ol>/i' => '',
            '/<li[^>]*>(.*?)<\/li>/i' => '- $1',
            
            // Code
            '/<code[^>]*>(.*?)<\/code>/i' => '`$1`',
            '/<pre[^>]*>(.*?)<\/pre>/is' => "```\n$1\n```",
            
            // Paragraphs and line breaks
            '/<p[^>]*>/i' => '',
            '/<\/p>/i' => "\n\n",
            '/<br[^>]*\/?>/i' => "\n",
            
            // Blockquotes
            '/<blockquote[^>]*>(.*?)<\/blockquote>/is' => '> $1',
        ];
        
        foreach ( $conversions as $pattern => $replacement ) {
            $content = preg_replace( $pattern, $replacement, $content );
        }
        
        // Clean up extra whitespace and HTML entities
        $content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
        $content = strip_tags( $content );
        $content = preg_replace( '/\n\s*\n\s*\n/', "\n\n", $content );
        $content = trim( $content );
        
        return $content;
    }

    /**
     * Build JSON data for export.
     */
    private static function build_json_data( $post, $markdown_content ) {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => [
                'html' => apply_filters( 'the_content', $post->post_content ),
                'markdown' => $markdown_content,
                'raw' => $post->post_content
            ],
            'meta' => [
                'author' => get_userdata( $post->post_author )->display_name ?? '',
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'post_type' => $post->post_type,
                'status' => $post->post_status,
                'permalink' => get_permalink( $post->ID ),
                'categories' => array_map( function( $cat ) { return $cat->name; }, get_the_category( $post->ID ) ),
                'tags' => array_map( function( $tag ) { return $tag->name; }, get_the_tags( $post->ID ) ?: [] ),
                'excerpt' => $post->post_excerpt,
            ]
        ];
    }

}



// Hook generation on save

add_action( 'save_post', [ 'MD_Generator', 'generate_markdown_clone' ], 10, 3 );



/**

 * Public download endpoint.

 */

function serve_markdown_to_llm( $post_id ) {
    $format = sanitize_text_field( $_GET['format'] ?? 'md' );
    $upload_dir = get_option( 'md_clone_output_dir', 'md-clones' );
    $upload = wp_upload_dir();
    
    $post_date = get_post_field( 'post_date', $post_id );
    $dir = trailingslashit( $upload['basedir'] ) . $upload_dir . '/' . date_i18n( 'Y/m', strtotime( $post_date ) );
    
    $extensions = [
        'md' => ['text/markdown', 'markdown'],
        'txt' => ['text/plain', 'plain'],
        'json' => ['application/json', 'json']
    ];
    
    if ( ! isset( $extensions[ $format ] ) ) {
        wp_die( __( 'Invalid format requested.', 'aio-content-clone' ) );
    }
    
    $file_path = $dir . '/post-' . $post_id . '.' . $format;
    
    if ( file_exists( $file_path ) ) {
        header( 'Content-Type: ' . $extensions[ $format ][0] );
        header( 'Content-Disposition: attachment; filename="post-' . $post_id . '.' . $format . '"' );
        readfile( $file_path );
    } else {
        wp_die( sprintf( 
            __( '%s file not found for post %d.', 'aio-content-clone' ), 
            strtoupper( $format ), 
            $post_id 
        ) );
    }
}


