<?php
/**
 * Post Type: PRODUCTS
 * Custom Post Type Class for https://store.portcandle.com
 *
 * Developed by Jared Williams - http://new2wp.com
 * jaredwilli@gmail.com
 * 
 * All Rights Reserved
 */

// Initialize the Class and add the action
add_action('init', 'pTypesInit');
function pTypesInit() {
    global $products;
    $products = new TypeProducts();
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * *  Shop Products Post Type Class  * * * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Create a post type class for 'Product' posts
 */
class TypeProducts {

    // Store the data
    public $prod_meta_fields = array( 
		'title', 'description', 'product_type', 
		'post_tag', 'p_webonly', 'p_price', 'p_img'
	);
	public $attach_meta_fields = array( 'p_image' );
	
	
    // The post type constructor
    public function TypeProducts() {
        $productArgs = array(
            'labels' => array(
                'name' => __( 'Products', 'post type general name' ),
                'singular_name' => __( 'Product', 'post type singular name' ),
                'add_new' => __( 'Add New Product', 'product' ),
                'add_new_item' => __( 'Add New Product' ),
                'edit_item' => __( 'Edit Product' ),
                'new_item' => __( 'New Product' ),
                'view_item' => __( 'View Product' ),
                'search_items' => __( 'Search Products' ),
                'not_found' =>  __( 'No products found in search' ),
                'not_found_in_trash' => __( 'No products found in Trash' ),
            ),
            'public' => true, 
            'show_ui' => true,
            '_builtin' => false,
            'hierarchical' => false,
			'menu_position' => 3,
            'query_var' => 'product',
            'capability_type' => 'post',
			'exclude_from_search' => false,
            'rewrite' => array( 'slug' => 'product' ),
            'menu_icon'=> site_url('wp-content/plugins/phpurchase/images/phpurchase_logo_16.gif'),
            'taxonomies' =>  array( 'post_tag','category' ),
            'supports' => array( 'title','editor','excerpt','thumbnail','sticky' ) 
			// 'page-attributes', 'custom-fields'
        );
        register_post_type( 'product', $productArgs );    

		/* Product Type Taxonomy */		
		$prod_tax = array(
			'labels' => array(
				'name' => __( 'Product Types', 'taxonomy general name' ),
				'singular_name' => __( 'Product Type', 'taxonomy singular name' ),
				'search_items' =>  __( 'Search Product Types' ),
				'popular_items' => __( 'Popular Product Types' ),
				'all_items' => __( 'All Product Types' ),
				'parent_item' => null, 'parent_item_colon' => null,
				'edit_item' => __( 'Edit Product Type' ), 
				'update_item' => __( 'Update Product Type' ),
				'add_new_item' => __( 'Add New Product Type' ),
				'new_item_name' => __( 'New Product Type Name' ),
				'separate_items_with_commas' => __( 'Separate product types with commas' ),
				'add_or_remove_items' => __( 'Add or remove product type' ),
				'choose_from_most_used' => __( 'Choose from the most used product types' )
			), 
			'show_ui' => true,
			'hierarchical' => true, 
			'show_tagcloud' => true,
			'query_var' => 'product_type', 
			'rewrite' => array( 'slug' => 'product_type' )
		);
		// register_taxonomy( 'product_type', 'product', $prod_tax );
	
        // Initialize the methods
        add_action( 'admin_init', array( &$this, 'product_meta_boxes' ));
        //add_action( 'template_redirect', array( &$this, 'template_redirect' ));
        add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );
		add_action( 'wp_insert_attachment', array( &$this, 'wp_insert_attachment' ), 10, 2 );
        add_filter( 'manage_posts_custom_column', array( &$this, 'product_custom_columns' ));
        add_action( 'manage_edit-product_columns', array( &$this, 'product_edit_columns' ));
    }

    // Create the columns and heading title text
    public function product_edit_columns($columns) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Product',
            'category' => 'Category',
            'post_tag' => 'Tags',
			/* 'p_imgs' => 'Product Images', */
            'thumbnail' => ''
        );
        return $columns;
    }
    /**
	 * switching cases based on which $column we show the content of it
	 */
    public function product_custom_columns($column) { 
        global $post, $p_price, $product_type;		
		//$p_price = get_post_meta( $post->ID, 'p_price', true );
		//$product_type = get_post_meta( $post->ID, 'product_type', true );
        switch ($column) {
            case 'title' : the_title(); break;
            case 'category' : the_category($post->ID); break;                
            case 'post_tag' : the_tags(' ', ', '); break;
			/* case 'p_imgs' : $img = $this->get_alt_images(30); echo $img; break; */
            case 'thumbnail' : ; break;
        }
    }
	
    /**
	 * Template redirect for custom templates
	 */
	public function template_redirect() {
		global $wp, $wp_query;
		if ($wp->query_vars["post_type"] == "product") {
			if (have_posts()) {	
				get_template_part( 'single-product' ); die(); 
			} else { 
				$wp_query->is_404 = true; 
			}
		}
	}

    /**
	 * For inserting new 'product' post type posts
	 */
    public function wp_insert_post($post_id, $post = null) {
        if ($post->post_type == 'product') {
            foreach ($this->prod_meta_fields as $key) {
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
	
	public function wp_insert_attachment($post_id, $post = null) {
        if ($post->post_type == 'attachment') {
            foreach ($this->attach_meta_fields as $key) {
                $value = @$_POST[$key];
                if (empty($value)) {
                    delete_post_meta($post_id, $key);
                    continue;
                }
                if (!is_array($value)) {
                    if (!wp_update_attachment_metadata($post_id, $key, $value)) {
                        add_post_meta($post_id, $key, $value);
                    }
                } else {
                    delete_post_meta($post_id, $key);
                    foreach ($value as $entry) {
						add_post_meta($post_id, $key, $entry);
					}
                }
            }
        }
	}


    /**
	 * Add product meta boxes
	 */
    function product_meta_boxes() {
		add_meta_box( 'products-price', 'Product Price', array( &$this, 'prod_price_box' ), 'product', 'side', 'high' );
		add_meta_box( 'products-online', 'Only Sold Online', array( &$this, 'prod_online_box' ), 'product', 'side', 'high' );
		add_meta_box( 'products-images', 'Product Images', array( &$this, 'prod_images_box' ), 'product', 'side', 'high' );
		
	    add_meta_box( 'product-image', 'Product Image', array( &$this, 'product_image_box' ), 'product', 'side', 'high' );
    }
	
	/**
	 * Product images box
	 */
    public function prod_images_box() {
        global $post, $p_img;
        $p_imgs = get_post_meta( $post->ID, 'p_img' ); ?>
		<div id="p_images">
		<small><em>To add alternate product images:</em> Upload an image, Right click the thumbnail of it, (copy image location/url. or copy shortcut in IE). Click Add Another Image and paste the url in the textbox. Then delete the <strong>-150x150</strong> from the end of url.</small>
		<?php foreach( $p_imgs as $key => $p_img ) { ?>
			<p>
		<label for="p_img_<?php echo $key; ?>">
				<?php if($p_img != '') { echo '<img src="' . $p_img . '" width="20" style="float:left; margin:0 5px 0 0;" />'; } ?><input id="p_img_<?php echo $key; ?>" name="p_img[]" value="<?php echo $p_img; ?>" size="15" class="altinput" />
				</label> <a href="#" class="remImg">Remove</a>
			</p>
		<?php } ?>
		</div>
	    <h4><a href="#" class="addImg">Add Another Image</a></h4>

		<script src="http://code.jquery.com/jquery-1.4.4.min.js"></script>
		<script type="text/javascript">
		$(function() {
			var imgDiv = $('#p_images');
			var j = $('#p_images p').size() + 1;
			$('.addImg').live('click', function() {
				$('<p><label for="p_img_' + j + '"><strong>url:</strong> <input id="p_img_' + j + '" name="p_img[]" size="15" value="" class="altinput" /></label> <a href="#" class="remImg">Remove</a></p>').appendTo(imgDiv);
				j++;
				return false;
			});
			$('.remImg').live('click', function() { 
				if( j > 2 ) {
					$(this).parents('p').remove(); 
					j--;
				} return false; 
			});
		});

		</script>
	<?php
	}

	/**
	 * Return alternate images
	 */
	public function get_alt_images($imgsize) {
        global $post, $p_img;
        $p_imgs = get_post_meta( $post->ID, 'p_img' ); 
		
		if ($p_imgs != '' || $p_imgs != NULL) { ?>
			<ul class="mini-thumbs">
			<?php
			foreach( $p_imgs as $key => $p_img ) { 
				$altimgsrc = $p_img;
				$altimghtml = '<img src="'.$p_img.'" id="p_img_'.$key.'" alt="p_img_'.$key.'" width="'.$imgsize.'" height="'.$imgsize.'" />'; ?>
				<li><a href="<?php echo $altimgsrc; ?>" title="<?php the_title(); ?>" rel="alternate">
					<?php echo $altimghtml; ?>
				</a></li>
			<?php } ?>
			</ul>
		<?php }
		return;
	}

    /**
	 * Product sold only online checkbox
	 */
    public function prod_online_box() {
        global $post, $p_webonly;	
		$p_webonly = get_post_meta( $post->ID, 'p_webonly', true ); ?>
	
		<p><label for="p_webonly"><input type="checkbox" id="p_webonly" name="p_webonly" 
			<?php if ($p_webonly) { echo 'checked="checked"'; } ?> /> <strong>This product only sold online</strong></label></p>	
		
	<?php } // end product price boxes

    /**
	 * Return if product sold only online
	 */
    public function prod_only_online( $message ) {
        global $post, $p_webonly;	
		$p_webonly = get_post_meta( $post->ID, 'p_webonly', true );
		if ($p_webonly) {
			echo '<span class="only-online">' . $message . '</span>';
		}
	} // end product sold online only

    /**
	 * Admin product sku and price
	 */
    public function prod_price_box() {
        global $post, $p_price;	
		$p_price = get_post_meta( $post->ID, 'p_price', true ); ?>
	
		<p><label for="p_price"><strong>Price $:</strong> <input id="p_price" size="20" name="p_price" value="<?php echo $p_price; ?>" /></label></p>	
		
	<?php } // end product price boxes


	/**
	 * Image Uploader metabox
	 */
	public function product_image_box() {
	    global $post, $p_image, $p_title;
    	$p_images = get_post_meta( $post->ID, 'p_image' ); ?>
		
	    <h4><a href="#" class="addImage">Upload Another Image</a></h4>
		<div id="prod_images">
		<?php foreach( $p_images as $key => $p_image ) { ?>
			<p>
				<label for="p_image_<?php echo $key; ?>">
					<?php 
					if($p_image != '') { echo '<img src="' . $p_image . '" width="20" style="float:left; margin:0 5px 0 0;" />';} ?>
					<input type="file" size="10" id="p_image_<?php echo $key; ?>" name="p_image[]" value="<?php echo $p_image; ?>" class="altupload" />
				</label> <a href="#" class="remImage">Remove</a>
			</p>
		<?php } ?>
		</div>

		<script type="text/javascript">
	    jQuery(document).ready(function(){
    	    jQuery('form#post').attr('enctype','multipart/form-data');
        	jQuery('form#post').attr('encoding','multipart/form-data');
	    
			var imgDiv = jQuery('#prod_images'),
				size = jQuery('#prod_images p').size() + 1;
			jQuery('.addImage').live('click', function() {
				jQuery('<p><label for="p_image_' + size + '"> <input type="file" size="10" id="p_image_' + size + '" name="p_image[]" value="" class="altupload" /></label> <a href="#" class="remImage">Remove</a></p>').appendTo(imgDiv);
				size++;
				return false;
			});
			jQuery('.remImage').live('click', function() { 
				if( size > 1 ) {
					jQuery(this).parents('p').remove(); 
					size--;
				} return false; 
			});
			
			jQuery('.altupload').change(function() {
				$.ajax({
					url: '<?php site_url("wp-content/themes/candles/functions/upload.php"); ?>',
					type: 'POST',
					data: jQuery('this').val(),
					success: function(msg) {
						$('#DataGoesHere').html(msg);
					}
				})
				var imgFile = 
				
			});
		});
    	</script>
	<?php
	}
	

	/**
	 * Image Uploader handler
	 */
	public function product_images_handle() {
	    global $post, $p_image;
		update_post_meta($post->ID, "p_image", $_POST["p_image"]);

		// Handle new upload
		if( !empty( $_FILES['p_image']['name'] )) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			$override['action'] = 'editpost';
			$uploaded_file = wp_handle_upload($_FILES['p_image'], $override);
		
			$post_id = $post->ID;
			$attachment = array(
				'post_title' => $_FILES['p_image']['name'],
				'post_content' => '',
				'post_type' => 'attachment',
				'post_parent' => $post_id,
				'post_mime_type' => $_FILES['p_image']['type'],
				'guid' => $uploaded_file['url']
			);
			
			// Save the data
			$id = wp_insert_attachment( $attachment,$_FILES['p_image'][ 'file' ], $post_id);
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $_FILES['p_image']['file'] ));
			update_post_meta($post->ID, "p_image", $uploaded_file['url']);
		}
	}
	
	/*
	add_action('save_post', 'update_purchase_url');

	function update_purchase_url(){
		global $post;
		update_post_meta($post->ID, "purchase_url", $_POST["purchase_url"]);
		update_post_meta($post->ID, "product_price", $_POST["product_price"]);
		update_post_meta($post->ID, "product_image", $_POST["product_image"]);
		
		if( !empty( $_FILES['product_image']['name'] )) { //New upload
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			$override['action'] = 'editpost';
			
			$uploaded_file = wp_handle_upload($_FILES['product_image'], $override);
			
			$post_id = $post->ID;
			$attachment = array(
				'post_title' => $_FILES['product_image']['name'],
				'post_content' => '',
				'post_type' => 'attachment',
				'post_parent' => $post_id,
				'post_mime_type' => $_FILES['product_image']['type'],
				'guid' => $uploaded_file['url']
			);
			
			// Save the data
			$id = wp_insert_attachment( $attachment,$_FILES['product_image'][ 'file' ], $post_id );
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $_FILES['product_image']['file'] ));
			update_post_meta($post->ID, "product_image", $uploaded_file['url']);
		}
	}
	*/	


	function fileupload_metabox_header(){ ?>
		<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('form#post').attr('enctype','multipart/form-data');
			jQuery('form#post').attr('encoding','multipart/form-data');
		});
		</script>
	<?php
	}
	add_action( 'admin_head', 'fileupload_metabox_header' );
	
} // end of TypeProducts{} class

?>