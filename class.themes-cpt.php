<?php
/**
 * Post Type: THEMES
 * 
 * Custom Post Type Class for WPHonors.com
 * Developed by Jared Williams - http://new2wp.com
 * jaredwilli@gmail.com
 * 
 * All Rights Reserved
 */


// Initialize the Class and add the action
add_action('init', 'pTypesInit');
function pTypesInit() {
    global $themes;
    $themes = new TypeThemes();
}



/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * *  Best Themes Post Type Class  * * * * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

// To use as a bookmarking post type for themes you want to save/share.
class TypeThemes {

    // Store the data
    public $meta_fields = array( 'title', 'description', 'theme', 'themeurl', 'category', 'post_tags' );
    private $themeurl = 'http://';

    // The post type constructor
    public function TypeThemes() {

        $themeArgs = array(
            'labels' => array(
                'name' => __( 'Themes', 'post type general name' ),
                'singular_name' => __( 'Theme', 'post type singular name' ),
                'add_new' => __( 'Add New', 'theme' ),
                'add_new_item' => __( 'Add New Theme' ),
                'edit_item' => __( 'Edit Theme' ),
                'new_item' => __( 'New Theme' ),
                'view_item' => __( 'View Theme' ),
                'search_items' => __( 'Search Themes' ),
                'not_found' =>  __( 'No themes found in search' ),
                'not_found_in_trash' => __( 'No themes found in Trash' ),
            ),
            'public' => true, 
            'show_ui' => true,
            '_builtin' => false,
            'hierarchical' => false,
            'query_var' => 'theme',
            'capability_type' => 'post',
            'rewrite' => array( 'slug' => 'theme' ),
            'menu_icon'  => get_bloginfo( 'template_directory' ).'/images/themes-icon.png',
            'taxonomies' =>  array( 'category', 'post_tag' ),
            'supports' => array( 'title','editor','author','comments','thumbnail' )
        );
        register_post_type( 'theme', $themeArgs );    

	// Initialize the methods
        add_action( 'admin_init', array( &$this, 'admin_init' ));
        add_action( 'template_redirect', array( &$this, 'template_redirect' ));
        add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );

        add_filter( 'manage_posts_custom_column', array( &$this, 'theme_custom_columns' ));
        add_action( 'manage_edit-theme_columns', array( &$this, 'theme_edit_columns' ));
    }

    // Create the columns and heading title text
    public function theme_edit_columns($columns) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Theme Title',
            'themeurl' => 'URL',
            'category' => 'Category',
            'post_tag' => 'Tags',
            'themeimg' => 'Screenshot',
        );
        return $columns;
    }
    // switching cases based on which $column we show the content of it
    public function theme_custom_columns($column) { 
        global $post;
        switch ($column) {
            case 'title' : the_title();
                break;
            case 'themeurl' : $t = $this->themeshot(); echo '<a href="'.$t[0].'" target="_blank">'.$t[0].'</a>';
                break;
            case 'category' : ;
                break;                
            case 'post_tag' : ;
                break;
            case 'themeimg' : $t = $this->themeshot(100); echo '<img src="'. $t[1] .'" width="100" />';
                break;
        }
    }

    // Template redirect for custom templates
    public function template_redirect() {
        global $wp_query;
        if ( $wp_query->query_vars['post_type'] == 'theme' ) {
            get_template_part( 'single-theme' ); // a custom page-slug.php template
            die();
        }
    }

    // For inserting new 'theme' post type posts
    public function wp_insert_post($post_id, $post = null) {
        if ($post->post_type == 'theme') {
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
	
    // Add meta box
    function admin_init() {
        add_meta_box( 'themes-meta', 'Theme Url (required)', array( &$this, 'meta_options' ), 'theme', 'side', 'high' );
    }

    // Admin post meta contents
    public function meta_options() {
        global $post, $themeurl;
        $themeurl = get_post_custom($post->ID, 'themeurl', true );
        
        $myurl = trailingslashit( get_post_meta( $post->ID, 'themeurl', true ) );
        if ( $myurl != '' ) {
            // Check if url has http:// or not so works either way
            if ( preg_match( "/http(s?):\/\//", $myurl )) {
                $themeurl = get_post_meta( $post->ID, 'themeurl', true );
		$themeshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode( $themeurl );
            } else {
                $themeurl = 'http://' . get_post_meta( $post->ID, 'themeurl', true );
		$themeshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode('http://'.$themeurl );
            }
	   $timgsrc  = '<img src="' . $themeshoturl . '?w=250" width="250" />';
        } ?>

        <p><label for="themeurl">Enter a valid Url below:<br />
        <input id="themeurl" size="37" name="themeurl" value="<?php echo $themeurl; ?>" /></label></p>
	<p><?php echo '<a href="' . $themeurl . '">' . $timgsrc  . '</a>'; ?></p>

    <?php
    } // end meta options

    public function themeshot($themeshotsize) {
        global $post;
        $themeurl = trailingslashit( get_post_meta( $post->ID, 'themeurl', true ) );

        global $post;
        $timgWidth = $themeshotsize;
        $themeshoturl = '';
        if ( $themeurl  != '' ) {
            if ( preg_match( "/http(s?):\/\//", $themeurl  )) {
                $themeurl = get_post_meta( $post->ID, 'themeurl', true );
                $themeshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode( $themeurl ) .'?w='. $timgWidth;
            } else {
                $themeurl = 'http://' . get_post_meta( $post->ID, 'themeurl', true );
                $themeshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode('http://'.$themeurl ) .'?w='. $timgWidth;
            }
        }
        return array( $themeurl, $themeshoturl );
    }

} // end of TypeThemes{} class
?>