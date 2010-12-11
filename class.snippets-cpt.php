<?php
/**
 * Post Type: CODE SNIPPETS
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
    global $snips;
    $snips = new snipSubmit();
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * * Code Snippets Post Type Class * * * * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/*** Code Snippets Post Type ***/
class snipSubmit {
    public $meta_fields = array("title", "description", "snipS");

    function snipSubmit() {
        $sniplabels = array(
                'name' => __( 'Snippets', 'post type general name' ),
                'singular_name' => __( 'Snippet', 'post type singular name' ),
                'add_new' => __( 'Add New', 'snippet' ),
                'add_new_item' => __( 'Add New Snippet' ),
                'edit_item' => __( 'Edit Snippet' ),
                'new_item' => __( 'New Snippet' ),
                'view_item' => __( 'View Snippet' ),
                'search_items' => __( 'Search Snippets' ),
                'not_found' =>  __( 'No snippets found in search' ),
                'not_found_in_trash' => __( 'No snippets found in Trash' ),
                'parent_item_colon' => ''
        );
        $snipargs = array( 'labels' => $sniplabels,
                'public' => true, 'show_ui' => true, '_builtin' => false, 'capability_type' => 'post', 'hierarchical' => false, 'rewrite' => array('slug' => 'snippet'), 'query_var' => 'snippet', 'supports' => array('title','editor','excerpt','author','comments')
        );
		/* Code Type Taxonomy */		
		$sniptax = array(
			'name' => __( 'Syntax', 'taxonomy general name' ),
			'singular_name' => __( 'Syntax', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Syntaxes' ),
			'popular_items' => __( 'Popular Syntaxes' ),
			'all_items' => __( 'All Syntaxes' ),
			'parent_item' => null, 'parent_item_colon' => null,
			'edit_item' => __( 'Edit Syntax' ), 
			'update_item' => __( 'Update Syntax' ),
			'add_new_item' => __( 'Add New Syntax' ),
			'new_item_name' => __( 'New Syntax Name' ),
			'separate_items_with_commas' => __( 'Separate syntax with commas' ),
			'add_or_remove_items' => __( 'Add or remove syntax' ),
			'choose_from_most_used' => __( 'Choose from the most used syntax' )
		);
		/* Register Syntax Taxonomy - Hierarchical */
		register_taxonomy( 'syntax', 'snip', array( 'hierarchical' => true, 'labels' => $sniptax, 'show_ui' => true, 'query_var' => 'syntax', 'rewrite' => array( 'slug' => 'syntax' )
		));
        register_post_type( 'snip', $snipargs );

        add_action( 'admin_init', array(&$this, 'admin_init') );
        add_action( 'template_redirect', array(&$this, 'template_redirect') );
        add_action( 'wp_insert_post', array(&$this, 'wp_insert_post'), 10, 2 );
    }
    function template_redirect() {
        global $wp;
        if ($wp->query_vars["post_type"] == "snip") {
            include(TEMPLATEPATH . "/snip.php");
            die();
        }
    }
    function admin_init() {
        add_meta_box("snipS-meta", "Snippet", array(&$this, "meta_options"), "snip", "normal");
    }
    function meta_options() {
        global $post;
        $custom = get_post_custom($post->ID);
        $snip = $custom["snip"][0];
        echo '<h2>Snippet:</h2><textarea name="snip" cols="60" rows="6" /></textarea>';
    }
    function wp_insert_post($post_id, $post = null) {
        if ($post->post_type == "snip") {
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