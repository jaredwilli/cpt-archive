<?php
/**
 * Post Type: STATUS UPDATES
 * Custom Post Type Class for http://tweakurpages.com
 *
 * Developed by Jared Williams - http://new2wp.com
 * jaredwilli@gmail.com
 * 
 * All Rights Reserved
 */

/* Initialize Poat Types */
add_action('init', 'pTypesInit');
function pTypesInit() {
    global $status;
    $status	= new statusSubmit();
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * Status Updates Post Type Class  * * * * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** Status Post Type **/
class statusSubmit {
    public $meta_fields = array("title", "status");

    function statusSubmit() {
        $statuslabels = array(
                'name' => __( 'Status Updates', 'post type general name' ),
                'singular_name' => __( 'Status', 'post type singular name' ),
                'add_new' => __( 'Add New', 'status' ),
                'add_new_item' => __( 'Add New Status Update' ),
                'edit_item' => __( 'Edit Status Update' ),
                'new_item' => __( 'New Status Update' ),
                'view_item' => __( 'View Status Updates' ),
                'search_items' => __( 'Search Status Updates' ),
                'not_found' =>  __( 'No status updates found' ),
                'not_found_in_trash' => __( 'No status updates found in Trash' ),
                'parent_item_colon' => ''
        );
        $statusargs = array( 'labels' => $statuslabels,
                'public' => true, 'show_ui' => true, '_builtin' => false, 'capability_type' => 'post', 'hierarchical' => false, 'rewrite' => array('slug' => 'status'), 'query_var' => 'status',
                'supports' => array('title','author','comments')
        );
        register_post_type( 'status', $statusargs );

        add_action( 'admin_init', array(&$this, 'admin_init') );
        add_action( 'template_redirect', array(&$this, 'template_redirect') );
        add_action( 'wp_insert_post', array(&$this, 'wp_insert_post'), 10, 2 );
    }
    function template_redirect() {
        global $wp;
        if ($wp->query_vars["post_type"] == "status") {
            include(TEMPLATEPATH . "/status.php");
            die();
        }
    }
    function admin_init() {
        add_meta_box("status-meta", "Status", array(&$this, "meta_options"), "status", "normal");
    }
    function meta_options() {
        global $post;
        $custom = get_post_custom($post->ID);
        $status = $custom["status"][0];
        echo '<h2>Update Your Status:</h2><textarea name="status" cols="60" rows="6" /></textarea>';
    }
    function wp_insert_post($post_id, $post = null) {
        if ($post->post_type == "status") {
            foreach ($this->meta_fields as $key) {
                $value = @$_POST[$key];
                if (empty($value)) {
                    delete_post_meta($post_id, $key);
                    continue;
                }
                if (!is_array($value)) {
                    if (!update_post_meta($post_id, $key, $value)) {
                        add_post_meta($post_id, $key, $value);
                    }
                } else {
                    delete_post_meta($post_id, $key);
                    foreach ($value as $entry) add_post_meta($post_id, $key, $entry);
                }
            }
        }
    }
}
?>