<?php
/**
 * Post Type: FINALISTS
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
    global $finalists;
    $finalists = new TypeFinalists();
}



/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * *  WPHonors Finalists Post Type Class * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


class TypeFinalists {

    // Store the data
    public $meta_fields = array( 'title', 'excerpt', 'ptype', 'posturl', 'postlink', 'post_category', 'twitname' );

    // The post type constructor
    public function TypeFinalists() {

        $finalistArgs = array(
            'labels' => array(
                'name' => __( 'Finalists', 'post type general name' ),
                'singular_name' => __( 'Finalist', 'post type singular name' ),
                'add_new' => __( 'Add New', 'finalist' ),
                'add_new_item' => __( 'Add New Finalist' ),
                'edit_item' => __( 'Edit Finalist' ),
                'new_item' => __( 'New Finalist' ),
                'view_item' => __( 'View Finalist' ),
                'search_items' => __( 'Search Finalists' ),
                'not_found' =>  __( 'No finalists found in search' ),
                'not_found_in_trash' => __( 'No finalists found in Trash' ),
            ),
            'public' => true, 
            'show_ui' => true,
            '_builtin' => false,
			'menu_position' => 4,
            'hierarchical' => false,
            'query_var' => 'finalist',
            'capability_type' => 'post',
            'taxonomies' =>  array( 'category' ),
            'rewrite' => array( 'slug' => 'finalist' ),
            'supports' => array( 'title','excerpt','author','comments' ),
            'menu_icon'  => get_bloginfo( 'template_directory' ).'/images/ratingmedal.png'
        );
		if( current_user_can('edit_others_posts')) {
	        register_post_type( 'finalist', $finalistArgs );
		}

        // Initialize the methods
        add_action( 'admin_init', array( &$this, 'admin_init' ));
        add_action( 'template_redirect', array( &$this, 'template_redirect' ));
        add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );

        add_filter( 'manage_posts_custom_column', array( &$this, 'finalist_custom_columns' ));
        add_action( 'manage_edit-finalist_columns', array( &$this, 'finalist_edit_columns' ));
    }

    // Create the columns and heading title text
    public function finalist_edit_columns($columns) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Finalist Title',
            'posturl' => 'Submission URL',
			'ptype' => 'Post Type',
            'category' => 'Category',
            'post_author' => 'Judged By',
            'finalistimg' => 'Website'
        );
        return $columns;
    }
    // switching cases based on which $column we show the content of it
    public function finalist_custom_columns($column) { 
        global $post, $ptype;
		$ptype = get_post_meta($post->ID, 'ptype', true );
        switch ($column) {
            case 'title' : the_title();
                break;
            case 'posturl' : $b = $this->mshot(90); echo '<a href="'.$b[0].'" target="_blank"><img src='.$b[1].'" width="90" /></a>';
                break;
			case 'ptype' : echo $ptype;
				break;
            case 'category' : ;
                break;
            case 'post_author' : the_author();
                break;
            case 'finalistimg' : $b = $this->mshot(90); echo '<a href="'.$b[2].'" target="_blank"><img src="'.$b[3].'" width="90" /></a>';
                break;
        }
    }

    /**
     * Causes all pages to show a 404 page so commented out for now
    *
    // Template redirect for custom templates
    public function template_redirect() {
        global $wp_query;
        if ( $wp_query->query_vars['post_type'] == 'finalist' ) {
            get_template_part( 'single-site' ); // a custom page-slug.php template
            die();
        }
    }
	*/

    // For inserting new 'finalist' post type posts
    public function wp_insert_post($post_id, $post = null) {
        if ($post->post_type == 'finalist') {
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
		$admin = 'add_users';
		$contrib = 'edit_posts';
		$author = 'publish_posts';
		$editor = 'edit_others_posts';
		$iconpath = get_bloginfo('template_directory') . "/images/ratingmedal.png";
	
		//add_menu_page( __( 'Judges' ), __( 'Judging Criteria' ), $admin, 'judges', 'criteria_finalists_page', $iconpath, 4 );
		
        add_meta_box( 'ptype-meta', 'Post Type', array( &$this, 'ptype_box' ), 'finalist', 'side', 'high' );
        add_meta_box( 'post-url', 'Submission Url', array( &$this, 'post_url' ), 'finalist', 'side', 'high' );
        add_meta_box( 'person-meta', 'Twitter Name', array( &$this, 'twit_options' ), 'finalist', 'normal', 'low' );
    }
	
	public function ptype_box() {
		global $post, $ptype;
		$ptype = get_post_meta($post->ID, 'ptype', true );
		?>
        <p><label for="ptype">Enter the Submission Post Type:<br />
        <select id="ptype" name="ptype">
			<option value="">Select Type</option>
			<option value="Site" <?php if ($ptype == 'Site') echo 'selected="selected"'; ?>>Site</option>
			<option value="Plugin" <?php if ($ptype == 'Plugin') echo 'selected="selected"'; ?>>Plugin</option>
			<option value="Theme" <?php if ($ptype == 'Theme') echo 'selected="selected"'; ?>>Theme</option>
			<option value="Personality" <?php if ($ptype == 'Personality') echo 'selected="selected"'; ?>>Personality</option>
		</select>
		</label></p>
		<?php
	}

    public function post_url() {
        global $post, $posturl, $postlink;
        $purl = trailingslashit( get_post_meta( $post->ID, 'posturl', true ));
		$plink = trailingslashit( get_post_meta( $post->ID, 'postlink', true ));
        if ( $purl != '' && $plink != '' ) {
            if ( preg_match( "/http(s?):\/\//", $purl )) {
                $mshoturl = 'http://s.WordPress.com/mshots/v1/' . urlencode( $purl );
            } else {
                $mshoturl = 'http://s.WordPress.com/mshots/v1/' . urlencode('http://'.$purl);
			}			
			if ( preg_match( "/http(s?):\/\//", $plink )) {
                $mshotlink = 'http://s.WordPress.com/mshots/v1/' . urlencode($plink);
            } else {
                $mshotlink = 'http://s.WordPress.com/mshots/v1/' . urlencode('http://'.$plink);
			}
            $postsrc  = '<img src="' . $mshoturl . '?w=125" width="125" />';
			$postlink  = '<img src="' . $mshotlink . '?w=125" width="125" />';
        } ?>
	
        <p><label for="posturl">Original Submission Url:<br />
        <input id="posturl" size="37" name="posturl" value="<?php echo $purl; ?>" /></label></p>
		<p><label for="postlink">Website for Nominee:<br />
        <input id="postlink" size="37" name="postlink" value="<?php echo $plink; ?>" /></label></p>
		<p>
		<?php echo '<a href="' . $purl . '" target="_blank">' . $postsrc . '</a>'; ?>
		<?php echo '<a href="' . $plink . '" target="_blank">' . $postlink . '</a>'; ?>
		</p>

    <?php
    }

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
    
     public function mshot($mshotsize) {
        global $post, $posturl, $postlink;
        $imgWidth = $mshotsize;
		$purl = get_post_meta( $post->ID, 'posturl', true );
		$plink = get_post_meta( $post->ID, 'postlink', true );
        if ( $purl != '' ) {
			$mshoturl = 'http://s.WordPress.com/mshots/v1/' . urlencode( $purl );
		} else {
			$mshoturl = 'http://s.WordPress.com/mshots/v1/' . urlencode('http://'.$purl);
		}
		if ( $plink != '' ) {
			$mshotlink = 'http://s.wordpress.com/mshots/v1/' . urlencode( $plink );
		} else {
			$mshotlink = 'http://s.wordpress.com/mshots/v1/' . urlencode('http://'.$plink );
		}
        $mshotu = $mshoturl . '?w=' . $imgWidth;
		$mshotl = $mshotlink . '?w=' . $imgWidth;
        return array( $purl, $mshotu, $plink, $mshotl );
    }
    public function twitshot() {
        global $post, $twitname;
        $twitname = get_post_meta($post->ID, 'twitname', true);
        if ( $twitname != '' ) {
            $twiturl  = 'http://twitter.com/'. $twitname;
            $twitimg = 'http://img.tweetimag.es/i/'. $twitname .'_o';
            $twitname = $twitname;
        }        
        return array( $twiturl, $twitimg, $twitname );
    }

	/**
	 * Create judging criteria info page
	 */
	public function criteria_finalists_page() { ?>
		<div class="wrap">
			<div class="icon32" id="icon-prize"><br/></div>
			
			<h2><?php _e( 'Judging Criteria for Choosing Finalists' ); ?></h2>
			<p>Below is the criteria what each category of submissions should be judged based on. Use your best judgement, and try to remain nuetral in your final decisions for what you think should be chosen as a final nominee.</p>
			<p style="color:red; font-size:11px; width:500px; border:1px solid #999; padding:5px;">NOTE: When choosing finalists please keep in mind that the number of votes a particular submission has should be one of the last deciding factors to consider for each category which chosen finalists are picked from.</p>
			
			<h2><?php _e( 'The Judges' ); ?></h2>
			<table width="90%" align="center" cellpadding="5" cellspacing="0" style="padding: 5px; border:1px solid #ccc;">
				<tr>
					<td align="center"><a href="http://wpcandy.com" target="_blank">Ryan Imel</a> - <a href="http://twitter.com/wpcandy" target="_blank">@WPCandy</a></td>
					<td align="center"><a href="http://chuckreynolds.us" target="_blank">Chuck Reynolds</a> - <a href="http://twitter.com/ChuckReynolds" target="_blank">@ChuckReynolds</a></td>
				</tr><tr>
					<td align="center"><a href="http://page.ly" target="_blank">Joshua Strebel</a> - <a href="http://twitter.com/strebel" target="_blank">@strebel</a></td>
					<td align="center"><a href="http://wpbeginner.com" target="_blank">Syed Balkhi</a> - <a href="http://twitter.com/syedbalkhi" target="_blank">@syedbalkhi</a></td>
				</tr>
				<tr>
					<td align="center"><a href="http://lisasabin-wilson.com" target="_blank">Lisa Sabin Wilson</a> - <a href="http://twitter.com/LisaSabinWilson" target="_blank">@LisaSabinWilson</a></td>
					<td align="center"><a href="#" target="_blank">Name</a> - <a href="http://twitter.com/" target="_blank">@name</a></td>
				</tr>
		  </table>
	
			<h2><?php _e( 'Judging Criteria' ); ?></h2>
			<p>You can use the checkboxes to check off things as you go if you want to. If that makes it easier to keep track of things.</p>
	
			<table width="100%" border="0" align="center" cellpadding="7" cellspacing="0">
				<tr>
					<th bgcolor="#fff200">Sites</th>
					<th bgcolor="#3dd9f0">Plugins</th>
				</tr><tr>
					<td valign="top" bgcolor="#f8fc8e">
						<h4>Personal Sites</h4>
						<ul>
							<li><input type="checkbox" /> Is powered by WordPress</li>
							<li><input type="checkbox" /> Creative/Unique/Extended use of WordPress</li>
						  <li><input type="checkbox" /> Is powered by WordPress</li>
							<li><input type="checkbox" /> Usefulness</li>
						  <li><input type="checkbox" /> Navigation and appearance</li>
						</ul>			
						<h4>Business Sites</h4>
						<ul>
							<li><input type="checkbox" /> WordPress products/services</li>
							<li><input type="checkbox" /> Makes use of WordPress</li>
							<li><input type="checkbox" /> Quality of product/service</li>
							<li><input type="checkbox" /> Support</li>
							<li><input type="checkbox" /> Usefulness</li>
							<li><input type="checkbox" /> Appearance</li>
							<li><input type="checkbox" /> Qaulity of branding and consistency</li>
						</ul>
						<h4>WordPress Related</h4>
						<ul>
							<li><input type="checkbox" /> Uses WordPress</li>
							<li><input type="checkbox" /> Relates to WordPress</li>
							<li><input type="checkbox" /> Originality</li>
							<li><input type="checkbox" /> Usefulness</li>
							<li><input type="checkbox" /> Navigation and appearance</li>
						</ul>
				  </td>
				  <td valign="top" bgcolor="#dceefc">
						<h4>Free Plugins</h4>
						<ul>
							<li><input type="checkbox" /> Functionality</li>
							<li><input type="checkbox" /> Ease of use</li>
							<li><input type="checkbox" /> Serves a useful purpose</li>						
							<li><input type="checkbox" /> Coding standards</li>
							<li><input type="checkbox" /> WordPress best practices</li>
							<li><input type="checkbox" /> Is updated regularly</li>
						</ul>
						<h4>Commercial Plugins</h4>
						<ul>
							<li><input type="checkbox" /> Functionality</li>
							<li><input type="checkbox" /> Ease of use</li>
							<li><input type="checkbox" /> Serves a useful purpose</li>						
							<li><input type="checkbox" /> Coding standards</li>
							<li><input type="checkbox" /> WordPress best practices</li>
							<li><input type="checkbox" /> Reasonable pricing</li>
							<li><input type="checkbox" /> Support</li>
							<li><input type="checkbox" /> Is updated regularly</li>
							<li><input type="checkbox" /> Documentation</li>
							<li><input type="checkbox" /> Features/Options</li>
							<li><input type="checkbox" /> Security</li>
						</ul>					
				  </td>
				</tr><tr>
					<th bgcolor="#3df056">Themes</th>
					<th bgcolor="#f75dbb">Personalities</th>
				</tr><tr>
					<td valign="top" bgcolor="#bcffc2">
						<h4>Free Themes</h4>
						<ul>
							<li><input type="checkbox" /> Functionality</li>
						  <li><input type="checkbox" /> Ease of use</li>
							<li><input type="checkbox" /> Look and feel</li>
							<li><input type="checkbox" /> Coding standards</li>
							<li><input type="checkbox" /> WordPress best practices</li>
							<li><input type="checkbox" /> Is updated regularly</li>
						</ul>			
						<h4>Commercial Themes</h4>
						<ul>
							<li><input type="checkbox" /> Functionality</li>
							<li><input type="checkbox" /> Ease of use</li>
							<li><input type="checkbox" /> Look and feel</li>
							<li><input type="checkbox" /> Mutliple styles</li>
							<li><input type="checkbox" /> Image source files</li>						
							<li><input type="checkbox" /> Coding standards/commenting</li>
							<li><input type="checkbox" /> WordPress best practices</li>
							<li><input type="checkbox" /> Reasonable pricing</li>
							<li><input type="checkbox" /> Support</li>
							<li><input type="checkbox" /> Is updated regularly</li>
							<li><input type="checkbox" /> Documentation</li>
							<li><input type="checkbox" /> Features/Options</li>
							<li><input type="checkbox" /> Security</li>
						</ul>			
						<h4>Theme Frameworks</h4>
						<ul>
							<li><input type="checkbox" /> Functionality</li>
							<li><input type="checkbox" /> Ease of use</li>
							<li><input type="checkbox" /> Look and feel</li>
							<li><input type="checkbox" /> Coding standards/commenting</li>
							<li><input type="checkbox" /> WordPress best practices</li>
							<li><input type="checkbox" /> Is updated regularly</li>
							<li><input type="checkbox" /> Documentation</li>
							<li><input type="checkbox" /> Features/Options</li>
							<li><input type="checkbox" /> Security</li>
						</ul>										
				  </td>
					<td valign="top" bgcolor="#fcd0da">
						<h4>Designers</h4>
						<ul>
							<li><input type="checkbox" /> Has designed themes/WP UIs</li>
						  <li><input type="checkbox" /> Originality</li>
							<li><input type="checkbox" /> Professional appearance</li>
							<li><input type="checkbox" /> Quality of work</li>
							<li><input type="checkbox" /> Attitude and interaction</li>
							<li><input type="checkbox" /> Value of contributions towards WP design community</li>
						</ul>
						<h4>Developers</h4>					
						<ul>
							<li><input type="checkbox" /> Has made themes/plugins</li>
							<li><input type="checkbox" /> Level of complexity with codes developed</li>
							<li><input type="checkbox" /> Attitude and interaction</li>
							<li><input type="checkbox" /> Value of contributions towards WordPress development</li>
						</ul>
						<h4>Blog Authors</h4>
						<ul>
							<li><input type="checkbox" /> Writing quality (grammar, spelling, formatting)</li>
							<li><input type="checkbox" /> Posts frequently</li>
							<li><input type="checkbox" /> Originality of posts</li>
							<li><input type="checkbox" /> Useful/helpful content</li>
							<li><input type="checkbox" /> Responds actively to comments</li>
							<li><input type="checkbox" /> Thorough with detailed explanations</li>
						</ul>
						<h4>WP Community</h4>
						<ul>
							<li><input type="checkbox" /> Qaulity of contributions to community</li>
							<li><input type="checkbox" /> Level of impact in community</li>
							<li><input type="checkbox" />
							 Helpfulness</li>
						</ul>
				  </td>
				</tr>
		  </table>
	
		</div>
		
		<style type="text/css">
		#icon-prize{ background:url('<?php $bigiconpath = get_bloginfo('template_directory') . "/images/prize_winner.png"; echo $bigiconpath; ?>') no-repeat; }
		</style>
		
	<?php }
            
} // end of TypeFinalists{} class
?>