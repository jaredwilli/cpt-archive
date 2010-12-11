<?php
/**
 * Built by Matt Wiebe
 * http://somadesign.ca/projects/smarter-custom-post-types/
 *
 * Modified by Jared Williams
 * http://new2wp.com/
 *
 */
/**
 * JW_Register_CPT class
 * @usage jw_register_cpt( $post_type, $args_array, $custom_plural, $meta_fields );
 *
 * @author Jared Williams
 * @link http://new2wp.com
 * 
 * @param string $post_type The post type to register
 * @param array $args The arguments to pass into @link register_post_type(). 
 * Some defaults provided to ensure the UI is available.
 * @param string $custom_plural The plural name to be used in rewriting (http://yourdomain.com/custom_plural/ ). If left off, an "s" will be appended to your post type, which will break some words. (person, box, ox. Oh, English.)
 **/

if ( !class_exists('JW_Register_CPT' )) {

	class JW_Register_CPT {

		private $post_type;
		private $post_slug;
		private $post_type_object;
		private $args;

		public $meta_fields = array(
			'title', 'description', 'excerpt', 'post_category', 'post_tag'
		);

		private $defaults = array(
            'public' => true, 
            'show_ui' => true,
            '_builtin' => false,
            'hierarchical' => false,
            'capability_type' => 'post',
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
		public function __construct( $post_type = null, $args = array(), $meta_fields = array(), $custom_plural = false ) {
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
			
			// set the fields to insert data into. custom metaboxes can be used this way
			$this->meta_fields = $meta_fields;
						
			// add hooks
			$this->add_actions();
			$this->add_filters();

		}
		
		public function add_actions() {
			add_action( 'init', array( &$this, 'register_post_type' ));
			add_action( 'template_redirect', array( &$this, 'template_redirect' ));
	        add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );
		}

		public function add_filters() {
			add_filter( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules' ));
			add_filter( 'template_include', array( &$this, 'template_include' ));
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
		 * Add classes to body tag
		 */
		public function body_classes( $c ) {
			if ( get_query_var('post_type') === $this->post_type ) {
				$c[] = $this->post_type;
				$c[] = 'type-' . $this->post_type;
			}
			return $c;
		}

	} // end JW_Register_CPT class
	
	
	/**
	 * @uses JW_Register_CPT class
	 * @param string $post_type The post type to register
	 * @param array $args The arguments to pass into @link register_post_type().
	 * Some defaults provided to ensure the UI is available.
	 * @param string $custom_plural The plural name to be used in rewriting
	   http://yourdomain.com/custom_plural/. If left off, an "s" will be appended 
	   to the post type, which will break some words. (person, box, ox. Oh, English.)
	 * @param array $meta_fields The meta fields of the post type to insert the data into. 
	   This is to enable the use of wp_insert_post() and custom metaboxes for post types.
	 **/

	if ( !function_exists( 'jw_register_cpt' ) && class_exists( 'JW_Register_CPT' )) {
		function jw_register_cpt( $post_type = null, $args = array(), $custom_plural = false, $meta_fields = array() ) {
			$custom_post = new JW_Register_CPT($post_type, $args, $custom_plural, $meta_fields);
		}
	}

}
