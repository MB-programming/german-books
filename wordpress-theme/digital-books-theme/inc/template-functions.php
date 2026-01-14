<?php
/**
 * Template Functions
 *
 * @package Digital_Books_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display book card
 */
function dbt_display_book_card($book_id) {
    $book = get_post($book_id);

    if (!$book) {
        return;
    }

    $product_id = get_post_meta($book_id, '_dbt_product_id', true);
    $thumbnail = get_the_post_thumbnail_url($book_id, 'book-cover');
    $read_url = add_query_arg('book_id', $book_id, home_url('/read-book/'));
    $categories = get_the_terms($book_id, 'book_category');

    ?>
    <div class="book-card">
        <?php if ($thumbnail): ?>
            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($book->post_title); ?>" class="book-card-image">
        <?php endif; ?>

        <div class="book-card-content">
            <h3 class="book-card-title"><?php echo esc_html($book->post_title); ?></h3>
            <p class="book-card-description"><?php echo wp_trim_words($book->post_content, 20); ?></p>

            <div class="book-card-meta">
                <?php if ($product_id): ?>
                    <?php
                    $product = wc_get_product($product_id);
                    if ($product && $product->get_price() > 0):
                    ?>
                        <span class="book-card-price"><?php echo $product->get_price_html(); ?></span>
                    <?php else: ?>
                        <span class="book-card-price free"><?php _e('مجاناً', 'digital-books-theme'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($categories && !is_wp_error($categories)): ?>
                    <span class="book-card-category"><?php echo esc_html($categories[0]->name); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="book-card-actions">
            <?php if ($product_id): ?>
                <?php
                $product = wc_get_product($product_id);
                if ($product):
                    // Check if user purchased
                    if (is_user_logged_in() && dbt_user_has_purchased_book(get_current_user_id(), $product_id)):
                ?>
                    <a href="<?php echo esc_url($read_url); ?>" class="btn btn-primary">
                        <?php _e('قراءة الآن', 'digital-books-theme'); ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="btn btn-primary">
                        <?php echo $product->get_price() > 0 ? __('شراء الكتاب', 'digital-books-theme') : __('احصل عليه مجاناً', 'digital-books-theme'); ?>
                    </a>
                <?php
                    endif;
                endif;
                ?>
            <?php else: ?>
                <a href="<?php echo esc_url(get_permalink($book_id)); ?>" class="btn btn-primary">
                    <?php _e('عرض التفاصيل', 'digital-books-theme'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Get PDF viewer HTML
 */
function dbt_get_pdf_viewer($book_id) {
    if (!$book_id) {
        return '<p>' . __('لم يتم تحديد كتاب', 'digital-books-theme') . '</p>';
    }

    $book = get_post($book_id);
    if (!$book) {
        return '<p>' . __('الكتاب غير موجود', 'digital-books-theme') . '</p>';
    }

    // Check permissions
    $product_id = get_post_meta($book_id, '_dbt_product_id', true);
    if ($product_id) {
        $product = wc_get_product($product_id);

        // If paid book, check if user purchased
        if ($product && $product->get_price() > 0) {
            if (!is_user_logged_in()) {
                return '<p>' . __('يجب تسجيل الدخول لقراءة هذا الكتاب', 'digital-books-theme') . '</p>';
            }

            if (!dbt_user_has_purchased_book(get_current_user_id(), $product_id)) {
                $product_url = get_permalink($product_id);
                return '<p>' . __('يجب شراء الكتاب أولاً', 'digital-books-theme') . ' <a href="' . esc_url($product_url) . '">' . __('اشترِ الآن', 'digital-books-theme') . '</a></p>';
            }
        }
    }

    // Get PDF file
    $unique_filename = get_post_meta($book_id, '_dbt_unique_filename', true);
    if (!$unique_filename) {
        return '<p>' . __('ملف PDF غير متوفر', 'digital-books-theme') . '</p>';
    }

    $pdf_url = wp_upload_dir()['baseurl'] . '/dbt-books/' . $unique_filename;

    // Get audio files
    $audio_pages = dbt_get_audio_by_page($book_id);

    // Get reading progress
    $current_page = 1;
    if (is_user_logged_in()) {
        $current_page = dbt_get_reading_progress(get_current_user_id(), $book_id);
    }

    ob_start();
    include get_template_directory() . '/templates/pdf-viewer.php';
    return ob_get_clean();
}

/**
 * Get books by category
 */
function dbt_get_books_by_category($category_slug = '', $limit = 12) {
    $args = array(
        'post_type' => 'book',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ($category_slug) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'book_category',
                'field' => 'slug',
                'terms' => $category_slug,
            ),
        );
    }

    return new WP_Query($args);
}

/**
 * Get user's saved books
 */
function dbt_get_user_saved_books($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'dbt_saved_books';

    $book_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT book_id FROM $table WHERE user_id = %d ORDER BY saved_at DESC",
        $user_id
    ));

    if (empty($book_ids)) {
        return new WP_Query(array('post_type' => 'book', 'post__in' => array(0)));
    }

    return new WP_Query(array(
        'post_type' => 'book',
        'post__in' => $book_ids,
        'orderby' => 'post__in',
        'posts_per_page' => -1,
    ));
}

/**
 * Check if user saved book
 */
function dbt_is_book_saved($user_id, $book_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'dbt_saved_books';

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND book_id = %d",
        $user_id,
        $book_id
    ));

    return $count > 0;
}

/**
 * Breadcrumbs
 */
function dbt_breadcrumbs() {
    if (is_front_page()) {
        return;
    }

    echo '<nav class="breadcrumbs">';
    echo '<a href="' . home_url('/') . '">' . __('الرئيسية', 'digital-books-theme') . '</a>';

    if (is_post_type_archive('book')) {
        echo ' / ' . __('الكتب', 'digital-books-theme');
    } elseif (is_singular('book')) {
        echo ' / <a href="' . get_post_type_archive_link('book') . '">' . __('الكتب', 'digital-books-theme') . '</a>';
        echo ' / ' . get_the_title();
    } elseif (is_page()) {
        echo ' / ' . get_the_title();
    } elseif (is_category() || is_tax()) {
        echo ' / ' . single_cat_title('', false);
    }

    echo '</nav>';
}

/**
 * Pagination
 */
function dbt_pagination() {
    global $wp_query;

    if ($wp_query->max_num_pages <= 1) {
        return;
    }

    $paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;
    $max = intval($wp_query->max_num_pages);

    echo '<nav class="pagination">';

    if ($paged > 1) {
        echo '<a href="' . get_pagenum_link($paged - 1) . '" class="prev">' . __('السابق', 'digital-books-theme') . '</a>';
    }

    for ($i = 1; $i <= $max; $i++) {
        if ($i == $paged) {
            echo '<span class="current">' . $i . '</span>';
        } else {
            echo '<a href="' . get_pagenum_link($i) . '">' . $i . '</a>';
        }
    }

    if ($paged < $max) {
        echo '<a href="' . get_pagenum_link($paged + 1) . '" class="next">' . __('التالي', 'digital-books-theme') . '</a>';
    }

    echo '</nav>';
}
