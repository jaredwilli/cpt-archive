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
            'supports' => array( 
			   'title', 'editor', 'excerpt',
			   'author', 'thumbnail',
			   'comments', 'trackbacks'
			)
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
		
		/** ACTIONS **/
		public function add_actions() {
			add_action( 'init', array( &$this, 'register_post_type' ));
			add_action( 'template_redirect', array( &$this, 'template_redirect' ));
	        add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );
			add_action( 'right_now_content_table_end', array( &$this, 'cpt_right_now_widget' ));
		}

		/** FILTERS **/
		public function add_filters() {
			$manage_edit_columns = 'manage_edit-' . $this->post_type . '_columns';

    	    add_filter( $manage_edit_columns, array( &$this, 'manage_edit_columns' ));
	        add_filter( 'manage_posts_custom_column', array( &$this, 'make_custom_columns' ));
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
		 * Add custom post types and taxonomies to the 'Right Now' dashboard widget
		 */		
		function cpt_right_now_widget() {
			// post types
			$post_types = get_post_types( array( 'public' => true ), 'names', 'and' );
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
			// taxonomies
			$taxonomies = get_taxonomies( array( 'public' => true ), 'names', 'and' );
			foreach( $taxonomies as $taxonomy ) {
				$num_terms  = wp_count_terms( $taxonomy->name );
				$num = number_format_i18n( $num_terms );
				$text = _n( $taxonomy->labels->singular_name, $taxonomy->labels->name , intval( $num_terms ) );
				if ( current_user_can( 'manage_categories' )) {
					$num = "<a href='edit-tags.php?taxonomy=$taxonomy->name'>$num</a>";
					$text = "<a href='edit-tags.php?taxonomy=$taxonomy->name'>$text</a>";
				}
				echo '<tr><td class="first b b-' . $taxonomy->name . '">' . $num . '</td>';
				echo '<td class="t ' . $taxonomy->name . '">' . $text . '</td></tr>';
			}
		}

		/**
		 * Create the columns and heading title text
		 */
		public function manage_edit_columns( $columns ) {
			if ( empty( $columns )) {
				$columns = array(
					'cb' => '<input type="checkbox" />',
					'title' => 'Title',
					'author' => 'Author',
					'category' => 'Category',
					'post_tag' => 'Tags',
					'comments' => '',
					'date' => 'Date',
					'thumbnail' => 'Thumbnail'
				);
			} else {
				$columns = $columns;
			}
			return $columns;
		}

		/**
		 * switching which $column we show the content in
		 */
		public function make_custom_columns( $column ) { 
			global $post;
			switch ($column) {

				case 'title' : 
					the_title();
					break;

				case 'author' : 
					echo '<td '. $attributes .'><a href="edit.php?post_type=' . $post->post_type . '&amp;author=' . the_author_meta("id") . '">' . the_author() . '</a></td>';
					break;

				case 'category' : 
					echo '<td '.$attributes.'>' . $categories = get_the_category();
						if ( !empty( $categories )) {
							$out = array();
							foreach ( $categories as $c ) {
								$out[] = "<a href='edit.php?category_name=$c->slug'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'category', 'display')) . "</a>";
							}
							echo join( ', ', $out );
						} else { 
							_e('Uncategorized'); 
						}
						echo '</td>';
					break;                

				case 'post_tag' : 
					echo '<td '.$attributes.'>'. $tags = get_the_tags($post->ID);
						if ( !empty( $tags )) {
							$out = array();
							foreach ( $tags as $c ) {
								$out[] = "<a href='edit.php?tag=$c->slug'>" . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'post_tag', 'display')) . "</a>";
							}
							echo join( ', ', $out );
						} else { _e('No Tags');	}
						echo '</td>';
					break;

				case 'comments' : 
					echo '<td '.$attributes.'><div class="post-com-count-wrapper">';
						$pending_phrase = sprintf(__('%s pending'), number_format($pending_comments));
						if ( $pending_comments )
							echo '<strong>';
							comments_number("<a href='edit-comments.php?p=$post->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . _x('0', 'comment count') . '</span></a>', "<a href='edit-comments.php?p=$post->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . _x('1', 'comment count') . '</span></a>', "<a href='edit-comments.php?p=$post->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . _x('%', 'comment count') . '</span></a>');
						if ( $pending_comments )
							echo '</strong>';
						echo '</div></td>';
					break;
					
				case 'date' : 
					if ( '0000-00-00 00:00:00' == $post->post_date && 'date' == $column_name ) {
						$t_time = $h_time = __( 'Unpublished' );
						$time_diff = 0;
					} else {
						$t_time = get_the_time( __('Y/m/d g:i:s A') );
						$m_time = $post->post_date;
						$time = get_post_time('G', true, $post);
						$time_diff = time() - $time;		
						if ( $time_diff > 0 && $time_diff < 24*60*60 ) {
							$h_time = sprintf( __('%s ago'), human_time_diff( $time ));
						} else { 
							$h_time = mysql2date(__('Y/m/d'), $m_time ); 
						}
					}
					echo "<td $attributes>";
					if ( 'excerpt' == $mode ) {
						echo apply_filters( 'post_date_column_time', $t_time, $post, $column_name, $mode );
					} else {
						echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, $column_name, $mode) . '</abbr>';
					}
					echo '<br />';
					if ( 'publish' == $post->post_status ) {
						_e( 'Published' );
					} elseif ( 'future' == $post->post_status ) {
						if ( $time_diff > 0 ) {
							echo '<strong class="attention">' . __( 'Missed Schedule' ) . '</strong>';
						} else { 
							_e( 'Scheduled' ); 
						}
					} else { 
						_e( 'Last Modified' ); 
					}
					echo "</td>";
					break;

				case 'thumbnail' : 
					the_post_thumbnail();
					break;
			}
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

/*
class sites extends JW_Register_CPT {

	jw_register_cpt( $post_type, $args_array, $custom_plural, $meta_fields );

}
*/