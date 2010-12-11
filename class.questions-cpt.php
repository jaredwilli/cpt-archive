<?php
/**
 * Post Type: QUESTIONS
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
    global $questions;
    $questions = new questionSubmit();
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * * *  Questions Post Type Class  * * * * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/*** Questions Post Type ***/
class questionSubmit {
    public $meta_fields = array("title", "description", "question");

    function questionSubmit() {
        $questionlabels = array(
                'name' => __( 'Questions', 'post type general name' ),
                'singular_name' => __( 'Question', 'post type singular name' ),
                'add_new' => __( 'Add New', 'Question' ),
                'add_new_item' => __( 'Add New Question' ),
                'edit_item' => __( 'Edit Question' ),
                'new_item' => __( 'New Question' ),
                'view_item' => __( 'View Questions' ),
                'search_items' => __( 'Search Questions' ),
                'not_found' =>  __( 'No questions found' ),
                'not_found_in_trash' => __( 'No questions found in Trash' ),
                'parent_item_colon' => ''
        );
        $questionargs = array( 'labels' => $questionlabels,
                'public' => true, 'show_ui' => true, '_builtin' => false, 'capability_type' => 'post', 'hierarchical' => false, 'rewrite' => array('slug' => 'question'), 'query_var' => 'question',
                'supports' => array('title','editor','author','comments')
        );
        register_post_type( 'question', $questionargs );

        add_action( 'admin_init', array(&$this, 'admin_init') );
        add_action( 'template_redirect', array(&$this, 'template_redirect') );
        add_action( 'wp_insert_post', array(&$this, 'wp_insert_post'), 10, 2 );
    }
    function template_redirect() {
        global $wp;
        if ($wp->query_vars["post_type"] == "question") {
            include(TEMPLATEPATH . "/question.php");
            die();
        }
    }
    function admin_init() {
        add_meta_box("question-meta","Question", array(&$this, "meta_options"),"question","advanced");
    }
    function meta_options() {
        global $post;
        $custom = get_post_custom($post->ID);
        $question = $custom["question"][0];
        echo '<h2>Ask Something:</h2><textarea name="question" cols="60" rows="6" /></textarea>';
    }
    function wp_insert_post($post_id, $post = null) {
        if ($post->post_type == "question") {
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