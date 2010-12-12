<?php
/**
 * Register_CPT
 * @usage register_cpt( $post_type, $args_array, $custom_plural, $meta_fields );
 *
 * Built by Matt Wiebe
 * http://somadesign.ca/projects/smarter-custom-post-types/
 *
 * Modified by Jared Williams
 * http://new2wp.com/
 * 
 * @param string $post_type The post type to register
 * @param array $args The arguments to pass into @link register_post_type(). 
 * Some defaults provided to ensure the UI is available.
 * @param string $custom_plural The plural name to be used in rewriting 
   (http://yourdomain.com/custom_plural/ ). If left off, an "s" will be 
   appended to your post type, which will break some words.
 **/

if ( !class_exists('Register_CPT' )) {

	class Register_CPT {

		private $post_type;
		private $post_slug;
		private $post_type_object;
		private $args;

		private $defaults = array(
            'show_ui' => true,
            'taxonomies' => array( 'category', 'post_tag' ),
            'supports' => array( 'title', 'editor', 'excerpt', 'author', 'comments', 'thumbnail' )
		);
		
		private function set_defaults() {
			$plural = ucwords( $this->post_slug );
			$post_type = ucwords( $this->post_type );
			
			$this->defaults['labels'] = array(
				'name' => $plural,
				'singular_name' => $post_type,
                'add_new' => __( 'Add New', $post_type ),
				'add_new_item' => 'Add New ' . $post_type,
				'edit_item' => 'Edit ' . $post_type,
				'new_item' => 'New ' . $post_type,
				'view_item' => 'View ' . $post_type,
				'search_items' => 'Search ' . $plural,
				'not_found' => 'No ' . $plural . ' found in search',
				'not_found_in_trash' => 'No ' . $plural . ' found in Trash'
			);
		}

		/**
		 * Constructor method
		 */
		public function __construct( $post_type = null, $args = array(), $custom_plural = false ) {
			if ( !$post_type ) {
				return;
			}
			
			// Post Type, post type slug
			$this->post_type = $post_type;
			$this->post_slug = ( $custom_plural ) ? $custom_plural : $post_type . 's';
			
			// a few extra defaults. Mostly for labels. Overridden if proper $args present.
			$this->set_defaults();
			// sort out those $args
			$this->args = wp_parse_args( $args, $this->defaults );
						
			// add hooks
			$this->add_actions();
			$this->add_filters();

		}
		
		/**
		 * Adding actions
		 */
		public function add_actions() {
			add_action( 'init', array( &$this, 'register_post_type' )); // gettin' jiggy wit it
			add_action( 'template_redirect', array( &$this, 'template_redirect' ));
	        add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 ); // inserter
			add_action( 'right_now_content_table_end', array( &$this, 'cpt_right_now_widget' ));
		}
		
		/**
		 * Adding filters
		 */
		public function add_filters() {
			add_filter( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules' ));
			add_filter( 'template_include', array( &$this, 'template_include' ));
			add_filter( 'the_search_query', array( &$this, 'custom_cpt_search' ));
			add_filter( 'request', array( &$this, 'custom_cpt_requests' ));
			add_filter( 'body_class', array( &$this, 'body_classes' ));
		}

		/**
		 * Template redirect for custom page templates
		*/
		public function template_redirect() {
			global $wp_query;
			if ( $wp_query->query_vars['post_type'] == $this->post_type ) {
				get_template_part( 'single-' . $this->post_type ); 
				die();
			}
		}

		/**
		 * For inserting new 'post type' post type posts
		 */
		public function wp_insert_post( $post_id, $post = null ) {
			if ( $post->post_type == $this->post_type ) {
				foreach( (array) $this->meta_fields as $key ) {
					$value = @$_POST[$key];
					// delete post if empty
					if ( empty( $value )) {
						delete_post_meta( $post_id, $key );
						continue;
					}
					// add post
					if ( !is_array( $value )) {
						if ( !update_post_meta( $post_id, $key, $value )) {
							add_post_meta( $post_id, $key, $value );
						}
					} else {
						// update post
						delete_post_meta( $post_id, $key );
						foreach( $value as $entry ) {
							add_post_meta( $post_id, $key, $entry );
						}
					}
				}
			}
		}
		
		/**
		 * Rewrite
		 */
		public function add_rewrite_rules( $wp_rewrite ) {
			$new_rules = array();
			$new_rules[$this->post_slug . '/page/?([0-9]{1,})/?$'] = '?post_type=' 
				. $this->post_type . '&paged=' . $wp_rewrite->preg_index(1);
			$new_rules[$this->post_slug . '/(feed|rdf|rss|rss2|atom)/?$'] = '?post_type=' 
				. $this->post_type . '&feed=' . $wp_rewrite->preg_index(1);
			$new_rules[$this->post_slug . '/?$'] = '?post_type=' . $this->post_type;

			$wp_rewrite->rules = array_merge( $new_rules, $wp_rewrite->rules );
			return $wp_rewrite;
		}

		/**
		 * Register post type
		 */
		public function register_post_type() {
			register_post_type( $this->post_type, $this->args );		
		}

		/**
		 * Load template
		 */
		public function template_include( $template ) {
			if ( get_query_var('post_type') == $this->post_type ) {
				if ( is_single() ) {
					if ( $single = locate_template( array('single-' . $this->post_type . '.php') ))
						return $single;
				} else {
					return locate_template( array(
						'page-' . $this->post_type . '.php',
						$this->post_type . '.php', 
						'index.php' 
					));
				}
			}
			return $template;
		}

		/**
		 * Add custom post types to the main RSS feed of site
		 * Show any custom post type on taxonomy/term pages
		 */
		function custom_cpt_requests( $vars ) {
			if ( isset($vars['feed'] ) && !isset( $vars['post_type'] ))
				$vars['post_type'] = $this->post_type;

			if ( isset( $vars['taxonomy'] ) || isset( $vars['term'] ))
				$vars['post_type'] = $this->post_type;

			return $vars;
		}
		
		/**
		 * Search custom post types by default for all public post types
		 */
		function custom_cpt_search( $query ) {
			$post_types = get_post_types( array( 'public' => true ), 'names', 'and' );
			if ( $query->is_search ) { 
				$query->set( 'post_type', $post_types );
			}
			return $query;
		}

		/**
		 * Add classes to body tag
		 */
		public function body_classes( $c ) {
			if ( get_query_var('post_type') === $this->post_type ) {
				$c[] = $this->post_type;
				$c[] = 'type-' . $this->post_type;
			}
			return $c;
		}

		/**
		 * Add custom post types and taxonomies to the 'Right Now' dashboard widget
		 */		
		function cpt_right_now_widget() {
				// post types				
				$post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'object', 'and' );
				foreach( $post_types as $post_type ) {
				$num_posts = wp_count_posts( $post_type->name );
				$num = number_format_i18n( $num_posts->publish );
				$text = _n( $post_type->labels->singular_name, $post_type->labels->name, intval( $num_posts->publish ));
				if ( current_user_can( 'edit_posts' )) {
					$num = "<a href='edit.php?post_type=$post_type->name'>$num</a>";
						$text = "<a href='edit.php?post_type=$post_type->name'>$text</a>";
				}
				echo '<tr><td class="first b b-' . $post_type->name . '">' . $num . '</td>';
				echo '<td class="t ' . $post_type->name . '">' . $text . '</td></tr>';
			}
		}

	} // end Register_CPT class
	
	
	/**
	 * @uses Register_CPT class
	 * @param string $post_type The post type to register
	 * @param array $args The arguments to pass into @link register_post_type().
	   Some defaults provided to ensure the UI is available.
	 * @param string $custom_plural The plural name to be used in rewriting
	   http://yourdomain.com/custom_plural/. If left off, an "s" will be appended 
	   to the post type, which will break some words. (person, box, ox. Oh, English.)
	 **/

	if ( !function_exists( 'register_cpt' ) && class_exists( 'Register_CPT' )) {
		function register_cpt( $post_type = null, $args = array(), $custom_plural = false ) {
			$custom_post = new Register_CPT($post_type, $args, $custom_plural);
		}
	}

}



/*
class TypePerson extends JW_Register_CPT {
	
	private $post_type = 'person';
	private $custom_plural = 'people';
	private $args_array = array(
		'public' => true, 
		'show_ui' => true,
		'_builtin' => false,
		'hierarchical' => false,
		'capability_type' => 'post',
		'supports' => array( 'title', 'editor', 'thumbnail', 'comments' )
	);
	public $meta_fields = array( 'title', 'description', 'checkbox', 'post_category' );
	private $columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => 'Title',
		'category' => 'Category',
		'post_tag' => 'Tags',
		'checkbox' => 'Checkbox',
		'thumbnail' => 'Thumbnail'
	);
	
	public function __construct() {

		// add hooks
		$this->add_actions();
		// $this->add_filters();
	}

	public function add_actions() {
		add_action( 'admin_init', array( &$this, 'metabox_init' ));
		add_action( 'quick_edit_custom_box', array( &$this, 'quick_edit_custom' ), 10, 2);
		add_action( 'admin_head-edit.php', array( &$this, 'quick_edit_script' ));
	}
	
		
	public function gettin_jiggy_init() {
		jw_register_cpt( $post_type, $args_array, $meta_fields, $custom_plural, $columns );
        add_meta_box( 'checkbox-meta', 'Checkbox', array( &$this, 'meta_checkbox' ), 'person', 'side', 'high' );
    }
	
	
	public function meta_checkbox() {
        global $post, $checkbox;	
		$checkbox = get_post_meta( $post->ID, 'checkbox' );
		if ( $checkbox ) { $checked = 'checked="checked"'; } else { $checked = ''; }
		
		echo '<p><label for="checkbox">
				<input type="checkbox" id="checkbox" name="checkbox" ' . $checked . ' />
				<strong>' . _e( "This product only sold online" ) . '</strong>
			  </label></p>';
	}

	/**
	 * Quick Edit metaboxes
	 *
	public function quick_edit_custom( $col, $type ) {
        global $post, $checkbox;
		if ( $col != 'checkbox' || $type != $this->post_type ) {
			return;
		}
		$checkbox = get_post_meta( $post->ID, 'checkbox' );
		if ( $checkbox ) { $checked = 'checked="checked"'; } else { $checked = ''; } ?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<div class="inline-edit-group">
					<label class="alignleft">
						<input type="checkbox" name="checkbox" id="checkbox" <?php echo $checked;?> />
						<span class="checkbox-title"><?php _e( 'Title' ); ?></span>
					</label>
				</div>
			</div>
		</fieldset><?php
	}
	
	/**
	 * Quick Edit add script
	 *
	public function quick_edit_script() { ?>	
		<script type="text/javascript">
		jQuery(function() {
			jQuery('a.editinline').live('click', function() {
				var id = inlineEditPost.getId(this),
					val = parseInt( jQuery('#inline_' + id + '_cpt').text() );
				jQuery('#checkbox').attr('checked', !!val);
			});
		});
		</script><?php	
	}
	
}



function pTypeIt() {
	global $person;

	try {
		$person = new TypePerson();
	} catch((Exception $e) {
		echo $e->getMessage();
	}
}
add_action( 'init', 'pTypeIt' );
*/
?>