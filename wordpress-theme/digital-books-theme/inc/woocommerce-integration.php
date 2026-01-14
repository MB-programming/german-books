<?php
/**
 * WooCommerce Integration
 *
 * @package Digital_Books_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Declare WooCommerce support
 */
function dbt_woocommerce_support() {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'dbt_woocommerce_support');

/**
 * Create WooCommerce product when book is created
 */
function dbt_create_woocommerce_product_for_book($post_id, $post, $update) {
    // Only for new books or when product_id is empty
    $product_id = get_post_meta($post_id, '_dbt_product_id', true);

    if ($update && $product_id) {
        // Update existing product
        dbt_update_woocommerce_product($post_id, $product_id);
        return;
    }

    if (!$update || !$product_id) {
        // Create new product
        $product_id = dbt_create_new_woocommerce_product($post_id);

        if ($product_id) {
            update_post_meta($post_id, '_dbt_product_id', $product_id);
        }
    }
}
add_action('save_post_book', 'dbt_create_woocommerce_product_for_book', 20, 3);

/**
 * Create new WooCommerce product
 */
function dbt_create_new_woocommerce_product($book_id) {
    $book = get_post($book_id);

    if (!$book) {
        return false;
    }

    // Create product
    $product = new WC_Product_Simple();

    // Set product data
    $product->set_name($book->post_title);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_description($book->post_content);
    $product->set_short_description(wp_trim_words($book->post_content, 20));
    $product->set_sku('BOOK-' . $book_id);

    // Set price (default 0 for free books)
    $product->set_regular_price(0);
    $product->set_price(0);

    // Set as virtual and downloadable
    $product->set_virtual(true);
    $product->set_downloadable(true);

    // Set stock status
    $product->set_stock_status('instock');
    $product->set_manage_stock(false);

    // Save product
    $product_id = $product->save();

    // Set product thumbnail
    if (has_post_thumbnail($book_id)) {
        $thumbnail_id = get_post_thumbnail_id($book_id);
        set_post_thumbnail($product_id, $thumbnail_id);
    }

    // Link book categories to product categories
    $book_categories = wp_get_post_terms($book_id, 'book_category', array('fields' => 'ids'));
    if (!empty($book_categories)) {
        wp_set_object_terms($product_id, $book_categories, 'product_cat');
    }

    // Add custom meta to link product to book
    update_post_meta($product_id, '_dbt_book_id', $book_id);

    return $product_id;
}

/**
 * Update existing WooCommerce product
 */
function dbt_update_woocommerce_product($book_id, $product_id) {
    $book = get_post($book_id);
    $product = wc_get_product($product_id);

    if (!$book || !$product) {
        return false;
    }

    // Update product data
    $product->set_name($book->post_title);
    $product->set_description($book->post_content);
    $product->set_short_description(wp_trim_words($book->post_content, 20));

    // Update thumbnail
    if (has_post_thumbnail($book_id)) {
        $thumbnail_id = get_post_thumbnail_id($book_id);
        set_post_thumbnail($product_id, $thumbnail_id);
    }

    // Update categories
    $book_categories = wp_get_post_terms($book_id, 'book_category', array('fields' => 'ids'));
    if (!empty($book_categories)) {
        wp_set_object_terms($product_id, $book_categories, 'product_cat');
    }

    $product->save();

    return true;
}

/**
 * Add PDF download link after purchase
 */
function dbt_add_pdf_download_after_purchase($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        $book_id = get_post_meta($product_id, '_dbt_book_id', true);

        if ($book_id) {
            // Get PDF file info
            $unique_filename = get_post_meta($book_id, '_dbt_unique_filename', true);

            if ($unique_filename) {
                $upload_dir = wp_upload_dir();
                $pdf_url = $upload_dir['baseurl'] . '/dbt-books/' . $unique_filename;
                $pdf_path = $upload_dir['basedir'] . '/dbt-books/' . $unique_filename;

                // Add downloadable file to order
                $download = new WC_Product_Download();
                $download->set_id(md5($pdf_url));
                $download->set_name(get_the_title($book_id) . '.pdf');
                $download->set_file($pdf_url);

                $product = wc_get_product($product_id);
                $downloads = $product->get_downloads();
                $downloads[$download->get_id()] = $download;
                $product->set_downloads($downloads);
                $product->save();
            }
        }
    }
}
add_action('woocommerce_order_status_completed', 'dbt_add_pdf_download_after_purchase');
add_action('woocommerce_order_status_processing', 'dbt_add_pdf_download_after_purchase');

/**
 * Add read online button to order items
 */
function dbt_add_read_online_button_to_order($item_id, $item, $order) {
    $product_id = $item->get_product_id();
    $book_id = get_post_meta($product_id, '_dbt_book_id', true);

    if ($book_id) {
        $read_url = add_query_arg('book_id', $book_id, home_url('/read-book/'));
        echo '<br><a href="' . esc_url($read_url) . '" class="button" style="margin-top: 10px;">' . __('قراءة أونلاين', 'digital-books-theme') . '</a>';
    }
}
add_action('woocommerce_order_item_meta_end', 'dbt_add_read_online_button_to_order', 10, 3);

/**
 * Add "Read Online" button to My Account > Downloads
 */
function dbt_add_read_button_to_downloads($downloads, $order) {
    foreach ($downloads as $key => $download) {
        $product_id = $download['product_id'];
        $book_id = get_post_meta($product_id, '_dbt_book_id', true);

        if ($book_id) {
            $read_url = add_query_arg('book_id', $book_id, home_url('/read-book/'));
            $downloads[$key]['read_url'] = $read_url;
        }
    }

    return $downloads;
}
add_filter('woocommerce_customer_available_downloads', 'dbt_add_read_button_to_downloads', 10, 2);

/**
 * Customize product display for books
 */
function dbt_customize_book_product_display() {
    // Remove default add to cart button
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

    // Add custom button
    add_action('woocommerce_single_product_summary', 'dbt_custom_add_to_cart_button', 30);
}
add_action('wp', 'dbt_customize_book_product_display');

/**
 * Custom Add to Cart button for books
 */
function dbt_custom_add_to_cart_button() {
    global $product;

    $book_id = get_post_meta($product->get_id(), '_dbt_book_id', true);

    if (!$book_id) {
        woocommerce_template_single_add_to_cart();
        return;
    }

    // Check if user already purchased
    if (is_user_logged_in() && dbt_user_has_purchased_book(get_current_user_id(), $product->get_id())) {
        $read_url = add_query_arg('book_id', $book_id, home_url('/read-book/'));
        echo '<a href="' . esc_url($read_url) . '" class="button alt btn-read-online">' . __('قراءة الآن', 'digital-books-theme') . '</a>';
    } else {
        // Show regular add to cart
        woocommerce_template_single_add_to_cart();
    }
}

/**
 * Add book preview section to product page
 */
function dbt_add_book_preview_section() {
    global $product;

    $book_id = get_post_meta($product->get_id(), '_dbt_book_id', true);

    if (!$book_id) {
        return;
    }

    $unique_filename = get_post_meta($book_id, '_dbt_unique_filename', true);

    if ($unique_filename) {
        ?>
        <div class="book-preview-section">
            <h3><?php _e('معاينة الكتاب', 'digital-books-theme'); ?></h3>
            <p><?php _e('يمكنك معاينة بعض صفحات الكتاب قبل الشراء', 'digital-books-theme'); ?></p>
            <button type="button" class="button preview-book" data-book-id="<?php echo esc_attr($book_id); ?>">
                <?php _e('معاينة الكتاب', 'digital-books-theme'); ?>
            </button>
        </div>
        <?php
    }
}
add_action('woocommerce_after_single_product_summary', 'dbt_add_book_preview_section', 5);

/**
 * Modify "My Account" menu to add "My Books" link
 */
function dbt_add_my_books_endpoint() {
    add_rewrite_endpoint('my-books', EP_ROOT | EP_PAGES);
}
add_action('init', 'dbt_add_my_books_endpoint');

/**
 * Add "My Books" to My Account menu
 */
function dbt_add_my_books_menu_item($items) {
    $items['my-books'] = __('كتبي', 'digital-books-theme');
    return $items;
}
add_filter('woocommerce_account_menu_items', 'dbt_add_my_books_menu_item');

/**
 * Display My Books content
 */
function dbt_my_books_content() {
    if (!is_user_logged_in()) {
        echo '<p>' . __('يجب تسجيل الدخول لعرض كتبك', 'digital-books-theme') . '</p>';
        return;
    }

    $user_id = get_current_user_id();

    // Get purchased books
    $orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('wc-completed', 'wc-processing'),
        'limit' => -1,
    ));

    $book_ids = array();
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $book_id = get_post_meta($product_id, '_dbt_book_id', true);
            if ($book_id) {
                $book_ids[] = $book_id;
            }
        }
    }

    $book_ids = array_unique($book_ids);

    if (empty($book_ids)) {
        echo '<p>' . __('لم تشترِ أي كتب بعد', 'digital-books-theme') . '</p>';
        return;
    }

    $books_query = new WP_Query(array(
        'post_type' => 'book',
        'post__in' => $book_ids,
        'posts_per_page' => -1,
    ));

    if ($books_query->have_posts()) {
        echo '<div class="books-grid">';
        while ($books_query->have_posts()) {
            $books_query->the_post();
            get_template_part('template-parts/content', 'book-card');
        }
        echo '</div>';
        wp_reset_postdata();
    }
}
add_action('woocommerce_account_my-books_endpoint', 'dbt_my_books_content');

/**
 * Redirect to read page after purchase
 */
function dbt_redirect_to_read_after_purchase($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    // Get first book from order
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $book_id = get_post_meta($product_id, '_dbt_book_id', true);

        if ($book_id) {
            $read_url = add_query_arg('book_id', $book_id, home_url('/read-book/'));
            // Store in session to redirect after thank you page
            WC()->session->set('dbt_redirect_to_book', $read_url);
            break;
        }
    }
}
add_action('woocommerce_thankyou', 'dbt_redirect_to_read_after_purchase', 1);

/**
 * Show "Read Now" button on thank you page
 */
function dbt_show_read_button_on_thankyou($order_id) {
    $read_url = WC()->session->get('dbt_redirect_to_book');

    if ($read_url) {
        echo '<div class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received" style="text-align: center; padding: 20px; margin-top: 20px;">';
        echo '<p style="font-size: 18px; margin-bottom: 20px;">' . __('شكراً لشرائك! يمكنك البدء في القراءة الآن', 'digital-books-theme') . '</p>';
        echo '<a href="' . esc_url($read_url) . '" class="button alt" style="font-size: 18px; padding: 15px 40px;">' . __('📖 ابدأ القراءة الآن', 'digital-books-theme') . '</a>';
        echo '</div>';

        WC()->session->__unset('dbt_redirect_to_book');
    }
}
add_action('woocommerce_thankyou', 'dbt_show_read_button_on_thankyou', 20);

/**
 * Add book info to cart item
 */
function dbt_add_book_info_to_cart_item($cart_item_data, $product_id, $variation_id) {
    $book_id = get_post_meta($product_id, '_dbt_book_id', true);

    if ($book_id) {
        $cart_item_data['dbt_book_id'] = $book_id;
    }

    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'dbt_add_book_info_to_cart_item', 10, 3);

/**
 * Display book info in cart
 */
function dbt_display_book_info_in_cart($item_data, $cart_item) {
    if (isset($cart_item['dbt_book_id'])) {
        $book_id = $cart_item['dbt_book_id'];
        $book = get_post($book_id);

        if ($book) {
            $item_data[] = array(
                'name' => __('نوع المنتج', 'digital-books-theme'),
                'value' => __('كتاب رقمي', 'digital-books-theme'),
            );
        }
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'dbt_display_book_info_in_cart', 10, 2);
