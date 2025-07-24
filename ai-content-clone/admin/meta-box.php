<?php
/**
 * @package AIO_Content_Clone
 * @subpackage Meta_Box
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'md_clone_box',
        __( 'Markdown Clone', 'aio-content-clone' ),
        'aio_cc_render_meta_box',
        get_post_types( [], 'names' ),
        'side'
    );
});

function aio_cc_render_meta_box( $post ) {
    wp_nonce_field( 'md_clone_regenerate', 'md_clone_nonce' );
    echo '<button type="button" id="md-clone-regenerate" data-postid="' . esc_attr( $post->ID ) . '">' . esc_html__( 'Regenerate Markdown', 'aio-content-clone' ) . '</button>';
    ?>
    <script>
    (function(){
        const btn = document.getElementById('md-clone-regenerate');
        btn.addEventListener('click', function(){
            const postId = this.dataset.postid;
            const nonce  = '<?php echo esc_js( wp_create_nonce( 'md_clone_regenerate' ) ); ?>';
            fetch( ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: new Headers({ 'Content-Type': 'application/x-www-form-urlencoded' }),
                body: new URLSearchParams({
                    action: 'md_clone_regenerate',
                    post_id: postId,
                    nonce: nonce
                })
            })
            .then(r => r.json())
            .then(data => alert( data.success ? data.data : data.data ) );
        });
    })();
    </script>
    <?php
}
