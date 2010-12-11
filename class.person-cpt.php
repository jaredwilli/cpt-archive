<?php
/**
 * Post Type: PERSONALITIES
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
    global $persons;
    $persons = new TypePersons();
}



/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * *  Best Personalities Post Type Class * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

// To use as a bookmarking post type for persons you want to save/share.
class TypePersons {

    // Store the data
    public $meta_fields = array( 'title', 'post_content', 'twitname', 'personurl', 'cat', 'post_tags' );
    
    public $twitname = '';
    private $personurl = '';
    
    // The post type constructor
    public function TypePersons() {

        $personArgs = array(
            'labels' => array(
                'name' => __( 'Personalities', 'post type general name' ),
                'singular_name' => __( 'Personality', 'post type singular name' ),
                'add_new' => __( 'Add New', 'personality' ),
                'add_new_item' => __( 'Add New Personality' ),
                'edit_item' => __( 'Edit Personality' ),
                'new_item' => __( 'New Personality' ),
                'view_item' => __( 'View' ),
                'search_items' => __( 'Search Personalities' ),
                'not_found' =>  __( 'No one found in search' ),
                'not_found_in_trash' => __( 'No one found in Trash' ),
            ),
            'public' => true, 
            'show_ui' => true,
            '_builtin' => false,
            'hierarchical' => false,
            'query_var' => 'person',
            'capability_type' => 'post',
            'rewrite' => array( 'slug' => 'person' ), // Permalinks. Fixes a 404 bug
            'menu_icon'  => get_bloginfo( 'template_directory' ).'/images/persons-icon.png',
            'taxonomies' =>  array( 'category', 'post_tag' ), // Add tags and categories taxonomies
            'supports' => array( 'title','editor','author','comments' )
        );
        register_post_type( 'person', $personArgs );    

        // Initialize the methods
        add_action( 'admin_init', array( &$this, 'admin_init' ));
        add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );
        add_action( 'template_redirect', array( &$this, 'template_redirect' ));

        add_filter( 'manage_posts_custom_column', array( &$this, 'person_custom_columns' ));
        add_action( 'manage_edit-person_columns', array( &$this, 'person_edit_columns' ));
    }

    // Create the columns and heading title text
    public function person_edit_columns($columns) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Personality',
            'twitprof' => 'Twitter Profile',
            'category' => 'Category',
            'post_tag' => 'Tags',
            'twitimage' => 'Avatar',
            'personurl' => 'Website',
        );
        return $columns;
    }
    // switching cases based on which $column we show the content of it
    public function person_custom_columns($column) { 
        global $post, $pname;
        switch ($column) {
            case 'title' : the_title();
                break;
            case 'twitprof' : $m = $this->twitshot(); echo '<a href="'.$m[0].'" target="_blank">'. $m[0] .'</a>';
                break;
            case 'category' : the_category();
                break;                
            case 'post_tag' : the_tags('',', ');
                break;
            case 'twitimage' : $m = $this->twitshot(); echo '<img src="'. $m[1] .'" width="90" height="90" />';
                break;
            case "personurl" : $w = $this->pshot(90); echo '<img src="'. $w[1] .'" width="90" />';
                break;
        }
    }

    // Template redirect for custom templates
    public function template_redirect() {
        global $wp_query;
        if ( $wp_query->query_vars['post_type'] == 'person' ) {
            get_template_part( 'single-person' ); // a custom page-slug.php template
            die();
        }
    }

    // For inserting new 'person' post type posts
    public function wp_insert_post($post_id, $post = null) {
        if ($post->post_type == 'person') {
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
        //add_meta_box( 'name-meta', 'Name (required)', array( &$this, 'pname' ), 'person', 'normal', 'high' );
        add_meta_box( 'person-meta', 'Twitter Name', array( &$this, 'twit_options' ), 'person', 'normal', 'high' );
        add_meta_box( 'website-meta', 'Web Site Url', array( &$this, 'person_website' ), 'person', 'normal', 'high' );
    }

/*
    public function pname() {
        global $post, $pname;
        $pname = get_post_meta( $post->ID, 'pname', true );
        ?>
        <label class="hide-if-no-js" style="" id="title-prompt-text" for="pname">Name of Person here</label>
        <input name="pname" size="30" tabindex="1" value="<?php echo $pname; ?>" id="title" autocomplete="off" type="text" />
	    <?php
        return $pname;
    }
*/    
    // Admin twit contents
    public function twit_options() {
        global $post, $twitname;
        $twiturl = '';
        $twitname = get_post_meta( $post->ID, 'twitname', true );
        if ( $twitname != '' ) {
            // Check if url has http:// or not so works either way
            $twiturl  = 'http://twitter.com/'. $twitname;
            $twitimg = 'http://img.tweetimag.es/i/'. $twitname .'_o';
        } ?>

        <div style="float:right; overflow:hidden; height:90px;"><?php echo '<a href="' . $twiturl . '"><img src="' . $twitimg . '" width="73" height="73" /></a>'; ?></div>
        <div style="height:75px; "><label>Persons Twitter handle: @<input id="twitname" size="30" name="twitname" value="<?php echo $twitname; ?>" /></label></div>

    <?php
    } // end meta options

    public function twitshot() {
        global $post, $twitname;
        $twiturl = '';
        $twitimg = '';
        $twitname = get_post_meta($post->ID, 'twitname', true);
        if ( $twitname != '' ) {
            $twiturl  = 'http://twitter.com/'. $twitname;
            $twitimg = 'http://img.tweetimag.es/i/'. $twitname .'_o';
            $twitname = $twitname;
        }
        
        return array( $twiturl, $twitimg, $twitname );
    }
    
    
    // Admin post meta contents
    public function person_website() {
        global $post, $personurl;
        $pshoturl = '';

        $personurl = trailingslashit( get_post_meta( $post->ID, 'personurl', true ) );
        if ( $personurl != '' ) {
            // Check if url has http:// or not so works either way
            if ( preg_match( "/http(s?):\/\//", $personurl )) {
                $personurl = get_post_meta( $post->ID, 'personurl', true );
                $pshoturl = 'http://s.WordPress.com/mshots/v1/' . urlencode( $personurl );
            } else {
                $personurl = 'http://' . get_post_meta( $post->ID, 'personurl', true );
                $pshoturl = 'http://s.WordPress.com/mshots/v1/' . urlencode('http://'.$personurl);
            }
            $pshotimgsrc  = '<img src="' . $pshoturl . '?w=250" width="250" />';
        } ?>

        <p><label>Persons Website:<br />
        <input id="personurl" size="37" name="personurl" value="<?php echo $personurl; ?>" /></label></p>
        <p><?php echo '<a href="' . $personurl . '">' . $pshotimgsrc . '</a>'; ?></p>

    <?php
    } // end meta options

    public function pshot($pshotsize) {
        global $post;
        $pimgWidth = $pshotsize;        
        $personurl = trailingslashit( get_post_meta( $post->ID, 'personurl', true ) );

        $pshoturl = '';
        if ( $personurl  != '' ) {
            if ( preg_match( "/http(s?):\/\//", $personurl  )) {
                $personurl = get_post_meta( $post->ID, 'personurl', true );
                $pshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode( $personurl ) .'?w='. $pimgWidth;
            } else {
                $personurl = 'http://' . get_post_meta( $post->ID, 'personurl', true );
                $pshoturl = 'http://s.wordpress.com/mshots/v1/' . urlencode('http://'.$personurl ) .'?w='. $pimgWidth;
            }
        }
        return array( $personurl, $pshoturl );
    }

} // end of TypePerson{} class
?>