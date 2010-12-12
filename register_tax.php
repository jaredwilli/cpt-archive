<?php
/**
 * Register_TAX
 * @usage regidsster_tax( $taxonomy, $object_type, $args_array, $custom_plural );
 *
 * By Jared Williams
 * http://new2wp.com/
 * 
 * @param string $post_type The post type to register
 * @param array $args The arguments to pass into @link register_taxonomy(). 
 * Some defaults provided to ensure the UI is available.
 * @param string $custom_plural The plural name to be used in rewriting 
   (http://yourdomain.com/taxonomy/ ). If left off, an "s" will be 
   appended to your post type, which will break some words.
 **/

if ( !class_exists( 'Register_TAX' )) {
	
	class Register_TAX {
		
		private $taxonomy;
		private $object_type;
		private $tax_slug;
		private $taxonomy_object;
		private $args;

		private $defaults = array(
			'public' => true,
		);
			
		private function set_defaults() {
			$plural = ucwords( $this->tax_slug );
			$taxonomy = ucwords( $this->taxonomy );
	
			$this->defaults['labels'] = array(
				'name' => $plural,
				'singular_name' => $taxonomy,
				'add_new_item' => __( 'Add New ' . $taxonomy ),
				'new_item_name' => __( 'New ' . $taxonomy . ' Name' ),
				'edit_item' => __( 'Edit ' . $taxonomy ), 
				'update_item' => __( 'Update ' . $taxonomy ),
				'add_or_remove_items' => __( 'Add or remove ' . $plural ),
				'choose_from_most_used' => __( 'Most used ' . $plural ),
				'search_items' =>  __( 'Search ' . $plural ),
				'popular_items' => __( 'Popular ' . $plural ),
				'all_items' => __( 'All ' . $plural ),
				'separate_items_with_commas' => __( 'Separate ' . $plural . ' with commas' )
			);
		}

		/**
		 * Constructor method
		 */
		public function __construct( $taxonomy = null, $object_type = null, $args = array(), $custom_plural = false ) {
			if ( !$taxonomy ) {
				return;
			}
			
			// Taxonomy and taxonomy slug
			$this->taxonomy = $taxonomy;
			$this->tax_slug = ( $custom_plural ) ? $custom_plural : $taxonomy . 's';
			
			// Post type to add the taxonomy to
			$this->object_type = $object_type;
			
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
			add_action( 'init', array( &$this, 'register_taxonomy' ));
			add_action( 'right_now_content_table_end', array( &$this, 'cpt_right_now_widget' ));
		}
		
		/**
		 * Adding filters
		 */
		public function add_filters() {
			add_filter( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules' ));
			add_filter( 'body_class', array( &$this, 'body_classes' ));
		}
		
		/**
		 * Register taxonomy
		 */
		public function register_taxonomy() {
			register_taxonomy( $this->taxonomy, $this->object_type, $this->args );		
		}
		
		/**
		 * Rewrites
		 */
		public function add_rewrite_rules( $wp_rewrite ) {
			$new_rules = array();
			$new_rules[$this->post_slug . '/page/?([0-9]{1,})/?$'] = '?taxonomy=' 
				. $this->taxonomy . '&paged=' . $wp_rewrite->preg_index(1);
			$new_rules[$this->post_slug . '/(feed|rdf|rss|rss2|atom)/?$'] = '?taxonomy=' 
				. $this->taxonomy . '&feed=' . $wp_rewrite->preg_index(1);
			$new_rules[$this->post_slug . '/?$'] = '?taxonomy=' . $this->taxonomy;

			$wp_rewrite->rules = array_merge( $new_rules, $wp_rewrite->rules );
			return $wp_rewrite;
		}

		/**
		 * Add classes to body tag
		 */
		public function body_classes( $c ) {
			if ( get_query_var('taxonomy') === $this->taxonomy ) {
				$c[] = $this->taxonomy;
				$c[] = 'tax-' . $this->taxonomy;
			}
			return $c;
		}

		/**
		 * Add custom taxonomies to the 'Right Now' dashboard widget
		 */		
		function cpt_right_now_widget() {
			// taxonomies
			$taxonomies = get_taxonomies( $args , $output , $operator );			
			foreach( $taxonomies as $taxonomy ) {
				$num_terms  = wp_count_terms( $taxonomy->name );
				$num = number_format_i18n( $num_terms );
				$text = _n( $taxonomy->labels->singular_name, $taxonomy->labels->name, intval( $num_terms ));
				if ( current_user_can( 'manage_categories' )) {
					$num = "<a href='edit-tags.php?taxonomy=$taxonomy->name'>$num</a>";
					$text = "<a href='edit-tags.php?taxonomy=$taxonomy->name'>$text</a>";
				}
				echo '<tr><td class="first b b-' . $taxonomy->name . '">' . $num . '</td>';
				echo '<td class="t ' . $taxonomy->name . '">' . $text . '</td></tr>';
			}
		}

	} // end Register_TAX class
		
	/**
	 * @uses Register_TAX class
	 * @param string $taxonomy The taxonomy to register
	 * @param string $object_type The object type for the taxonomy
	 * @param array $args The arguments to pass into @link register_taxonomy().
	   Some defaults provided to ensure the UI is available.
	 * @param string $custom_plural The plural name to be used in rewriting
	   http://yourdomain.com/custom_plural/. If left off, an "s" will be appended 
	   to the post type, which will break some words.
	 **/

	if ( !function_exists( 'register_tax' ) && class_exists( 'Register_TAX' )) {
		function register_tax( $taxonomy, $object_type = null, $args = array(), $custom_plural = false ) {
			$custom_tax = new Register_TAX( $taxonomy, $object_type, $args, $custom_plural);
		}
	}

}

