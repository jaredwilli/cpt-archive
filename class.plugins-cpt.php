<?php
/**
 * Post Type: PLUGINS
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
    global $plugins;
    $plugins = new TypePlugins();
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * *  Best Plugin Post Type Class  * * * * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 *
 *Create a post type class for 'Plugin' posts
 */
class TypePlugins {

    // Store the data
    public $meta_fields = array( 'title', 'description', 'pluginurl', 'category', 'post_tags' );
    private $pluginurl = 'http://';

    // The post type constructor
    public function TypePlugins() {

        $pluginArgs = array(
            'labels' => array(
                'name' => __( 'Plugins', 'post type general name' ),
                'singular_name' => __( 'Plugin', 'post type singular name' ),
                'add_new' => __( 'Add New', 'plugin' ),
                'add_new_item' => __( 'Add New Plugin' ),
                'edit_item' => __( 'Edit Plugin' ),
                'new_item' => __( 'New Plugin' ),
                'view_item' => __( 'View Plugin' ),
                'search_items' => __( 'Search Plugins' ),
                'not_found' =>  __( 'No plugins found in search' ),
                'not_found_in_trash' => __( 'No plugins found in Trash' ),
            ),
            'public' => true, 
            'show_ui' => true,
            '_builtin' => false,
            'hierarchical' => false,
            'query_var' => 'plugin',
            'capability_type' => 'post',
            'rewrite' => array( 'slug' => 'plugin' ),
            'menu_icon'  => get_bloginfo( 'template_directory' ).'/images/plugins-icon.png',
            'taxonomies' =>  array( 'category', 'post_tag' ),
            'supports' => array('title','editor','author','comments','thumbnail')
        );
        register_post_type( 'plugin', $pluginArgs );    

	// Initialize the methods
        add_action( 'admin_init', array( &$this, 'admin_init' ));
        add_action( 'template_redirect', array( &$this, 'template_redirect' ));
        add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );

        add_filter( 'manage_posts_custom_column', array( &$this, 'plugin_custom_columns' ));
        add_action( 'manage_edit-plugin_columns', array( &$this, 'plugin_edit_columns' ));
    }

    // Create the columns and heading title text
    public function plugin_edit_columns($columns) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Plugin Title',
            'pluginurl' => 'URL',
            'category' => 'Category',
            'post_tag' => 'Tags',
            'pluginimg' => 'Screenshot',
        );
        return $columns;
    }
    // switching cases based on which $column we show the content of it
    public function plugin_custom_columns($column) { 
        global $post;
		
        switch ($column) {
            case 'title' : the_title();
                break;
            case 'pluginurl' : $w = $this->pluginshot(); echo '<a href="'.$w[0].'" target="_blank">'.$w[0].'</a>';
                break;
            case 'category' : ;
                break;                
            case 'post_tag' : ;
                break;
            case 'pluginimg' : $w = $this->pluginshot(100); echo '<img src="'.$w[1].'" width="100" />';
                break;
        }
    }

    // Template redirect for custom templates
    public function template_redirect() {
        global $wp_query;
        if ( $wp_query->query_vars['post_type'] == 'plugin' ) {
            get_template_part( 'single-plugin' ); // a custom page-slug.php template
            die();
        }
    }

    // For inserting new 'plugin' post type posts
    public function wp_insert_post($post_id, $post = null) {
        if ($post->post_type == 'plugin') {
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
        add_meta_box( 'plugins-meta', 'Plugin Url (required)', array( &$this, 'meta_options' ), 'plugin', 'side', 'high' );
    }

    // Admin post meta contents
    public function meta_options() {
        global $post, $pluginurl;
        $pluginurl = get_post_custom($post->ID, 'pluginurl', true );
        
        $myurl = trailingslashit( get_post_meta( $post->ID, 'pluginurl', true ) );
        if ( $myurl != '' ) {
            // Check if url has http:// or not so works either way
            if ( preg_match( "/http(s?):\/\//", $myurl )) {
                $pluginurl = get_post_meta( $post->ID, 'pluginurl', true );
		$pluginshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode( $pluginurl );
            } else {
                $pluginurl = 'http://' . get_post_meta( $post->ID, 'pluginurl', true );
		$pluginshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode('http://'.$pluginurl );
            }
	    $pimgsrc  = '<img src="' . $pluginshoturl . '?w=250" width="250" />';

        } ?>

        <p><label for="pluginurl">Enter a valid Url below:<br />
        <input id="pluginurl" size="37" name="pluginurl" value="<?php echo $pluginurl; ?>" /></label></p>
	<p><?php echo '<a href="' . $pluginurl . '">' . $pimgsrc . '</a>'; ?></p>

    <?php
    } // end meta options

    public function pluginshot($pluginshotsize) {
        global $post;
        $pluginurl = trailingslashit( get_post_meta( $post->ID, 'pluginurl', true ) );

        global $post;
        $pimgWidth = $pluginshotsize;        

        $pluginshoturl = '';
        if ( $pluginurl  != '' ) {
            if ( preg_match( "/http(s?):\/\//", $pluginurl  )) {
                $pluginurl = get_post_meta( $post->ID, 'pluginurl', true );
                $pluginshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode( $pluginurl ) .'?w='. $pimgWidth;
            } else {
                $pluginurl = 'http://' . get_post_meta( $post->ID, 'pluginurl', true );
                $pluginshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode('http://'.$pluginurl ) .'?w='. $pimgWidth;
            }
        }
        return array( $pluginurl, $pluginshoturl );
    }
            
} // end of TypePlugins{} class
?>