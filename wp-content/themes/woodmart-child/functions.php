<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );

/** Hide Wallet Top-Up Option **/
add_filter('woo_wallet_is_enable_top_up', '__return_false');

/** Hide Wallet Top-Up Option **/
add_filter( 'woodmart_use_custom_order_widget', '__return_false' );
add_filter( 'woodmart_use_custom_price_widget', '__return_false' );

/** Add content to the added tab **/
add_action( 'woocommerce_account_woo-wallet_endpoint', 'woo_wallet_content' ); // Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format

function woo_wallet_content() {
   echo do_shortcode( '[html_block id="9888"]' ); // Here goes your shortcode if needed
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_filter('posts_clauses', 'order_by_stock_status', 2000);
}

function order_by_stock_status($posts_clauses) {
    global $wpdb;
  
    if (is_woocommerce() && (is_shop() || is_product_category() || is_product_tag())) {
	$posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta istockstatus ON ($wpdb->posts.ID = istockstatus.post_id) ";
	$posts_clauses['orderby'] = " istockstatus.meta_value ASC, " . $posts_clauses['orderby'];
	$posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' " . $posts_clauses['where'];
    }
	return $posts_clauses;
}

/**

add_action( 'wpo_wcpdf_after_item_meta', 'wpo_wcpdf_show_product_attributes', 10, 3 );
function wpo_wcpdf_show_product_attributes ( $template_type, $item, $order ) {
    if(empty($item['product'])) return;
    $document = wcpdf_get_document( $template_type, $order );
    printf('<div class="product-attribute">Brand: %s</div>', $document->get_product_attribute('Brand', $item['product']));
	printf('<div class="product-attribute">Color: %s</div>', $document->get_product_attribute('Color', $item['product']));
    printf('<div class="product-attribute">Gender: %s</div>', $document->get_product_attribute('Gender', $item['product']));
}

add_action( 'wpo_wcpdf_custom_styles', 'wpo_wcpdf_custom_styles', 10, 2 );
function wpo_wcpdf_custom_styles ( $document_type, $document ) {	
  if ($document_type == 'invoice'){
    ?>
    .product .meta .sku {
      display: none;
    }	
    <?php
  }
}
*/