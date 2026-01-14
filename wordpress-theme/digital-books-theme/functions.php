<?php
/**
 * Digital Books Theme Functions
 *
 * @package Digital_Books_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme Constants
define('DBT_VERSION', '1.0.0');
define('DBT_THEME_DIR', get_template_directory());
define('DBT_THEME_URI', get_template_directory_uri());

/**
 * Theme Setup
 */
function dbt_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('القائمة الرئيسية', 'digital-books-theme'),
        'footer'  => __('قائمة التذييل', 'digital-books-theme'),
    ));

    // Add image sizes
    add_image_size('book-cover', 400, 600, true);
    add_image_size('book-thumbnail', 280, 400, true);
}
add_action('after_setup_theme', 'dbt_theme_setup');

/**
 * Enqueue Scripts and Styles
 */
function dbt_enqueue_scripts() {
    // Main stylesheet
    wp_enqueue_style('dbt-style', get_stylesheet_uri(), array(), DBT_VERSION);

    // Google Fonts (Arabic support)
    wp_enqueue_style('dbt-fonts', 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap', array(), null);

    // SweetAlert2
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true);

    // PDF.js
    wp_enqueue_script('pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js', array(), '3.11.174', true);
    wp_enqueue_script('pdfjs-worker', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js', array(), '3.11.174', true);

    // Theme scripts
    wp_enqueue_script('dbt-main', DBT_THEME_URI . '/assets/js/main.js', array('jquery'), DBT_VERSION, true);

    // Localize script
    wp_localize_script('dbt-main', 'dbtData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dbt_nonce'),
        'themeUrl' => DBT_THEME_URI,
    ));
}
add_action('wp_enqueue_scripts', 'dbt_enqueue_scripts');

/**
 * Create Custom Database Tables
 */
function dbt_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix . 'dbt_';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Books table
    $sql_books = "CREATE TABLE IF NOT EXISTS {$prefix}books (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        pdf_file varchar(255) NOT NULL,
        unique_filename varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY product_id (product_id)
    ) $charset_collate;";
    dbDelta($sql_books);

    // Audio files table
    $sql_audio = "CREATE TABLE IF NOT EXISTS {$prefix}audio (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        book_id bigint(20) NOT NULL,
        page_number int(11) NOT NULL,
        audio_file varchar(255) NOT NULL,
        unique_filename varchar(255) NOT NULL,
        description text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY book_id (book_id),
        KEY page_number (page_number)
    ) $charset_collate;";
    dbDelta($sql_audio);

    // Reading progress table
    $sql_progress = "CREATE TABLE IF NOT EXISTS {$prefix}progress (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        book_id bigint(20) NOT NULL,
        current_page int(11) DEFAULT 1,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_book (user_id, book_id)
    ) $charset_collate;";
    dbDelta($sql_progress);

    // Saved books table
    $sql_saved = "CREATE TABLE IF NOT EXISTS {$prefix}saved_books (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        book_id bigint(20) NOT NULL,
        saved_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_book (user_id, book_id)
    ) $charset_collate;";
    dbDelta($sql_saved);
}
add_action('after_switch_theme', 'dbt_create_tables');

/**
 * Create Upload Directories
 */
function dbt_create_directories() {
    $upload_dir = wp_upload_dir();
    $books_dir = $upload_dir['basedir'] . '/dbt-books';
    $audio_dir = $upload_dir['basedir'] . '/dbt-audio';

    if (!file_exists($books_dir)) {
        wp_mkdir_p($books_dir);
    }

    if (!file_exists($audio_dir)) {
        wp_mkdir_p($audio_dir);
    }
}
add_action('after_switch_theme', 'dbt_create_directories');

/**
 * Register Custom Post Type: Book
 */
function dbt_register_book_post_type() {
    $labels = array(
        'name'               => __('الكتب', 'digital-books-theme'),
        'singular_name'      => __('كتاب', 'digital-books-theme'),
        'menu_name'          => __('الكتب الرقمية', 'digital-books-theme'),
        'add_new'            => __('إضافة كتاب', 'digital-books-theme'),
        'add_new_item'       => __('إضافة كتاب جديد', 'digital-books-theme'),
        'edit_item'          => __('تعديل الكتاب', 'digital-books-theme'),
        'new_item'           => __('كتاب جديد', 'digital-books-theme'),
        'view_item'          => __('عرض الكتاب', 'digital-books-theme'),
        'search_items'       => __('بحث عن كتاب', 'digital-books-theme'),
        'not_found'          => __('لم يتم العثور على كتب', 'digital-books-theme'),
        'not_found_in_trash' => __('لم يتم العثور على كتب في سلة المهملات', 'digital-books-theme'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'books'),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 20,
        'menu_icon'           => 'dashicons-book',
        'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'show_in_rest'        => true,
    );

    register_post_type('book', $args);

    // Register taxonomy for book categories
    $tax_labels = array(
        'name'              => __('التصنيفات', 'digital-books-theme'),
        'singular_name'     => __('تصنيف', 'digital-books-theme'),
        'search_items'      => __('بحث عن تصنيف', 'digital-books-theme'),
        'all_items'         => __('كل التصنيفات', 'digital-books-theme'),
        'edit_item'         => __('تعديل التصنيف', 'digital-books-theme'),
        'update_item'       => __('تحديث التصنيف', 'digital-books-theme'),
        'add_new_item'      => __('إضافة تصنيف جديد', 'digital-books-theme'),
        'new_item_name'     => __('اسم التصنيف الجديد', 'digital-books-theme'),
        'menu_name'         => __('التصنيفات', 'digital-books-theme'),
    );

    register_taxonomy('book_category', array('book'), array(
        'hierarchical'      => true,
        'labels'            => $tax_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'book-category'),
        'show_in_rest'      => true,
    ));
}
add_action('init', 'dbt_register_book_post_type');

/**
 * Add Book Meta Boxes
 */
function dbt_add_book_meta_boxes() {
    add_meta_box(
        'dbt_book_details',
        __('تفاصيل الكتاب', 'digital-books-theme'),
        'dbt_book_details_callback',
        'book',
        'normal',
        'high'
    );

    add_meta_box(
        'dbt_book_audio',
        __('الملفات الصوتية', 'digital-books-theme'),
        'dbt_book_audio_callback',
        'book',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'dbt_add_book_meta_boxes');

/**
 * Book Details Meta Box Callback
 */
function dbt_book_details_callback($post) {
    wp_nonce_field('dbt_save_book_details', 'dbt_book_details_nonce');

    $product_id = get_post_meta($post->ID, '_dbt_product_id', true);
    $pdf_file = get_post_meta($post->ID, '_dbt_pdf_file', true);
    $unique_filename = get_post_meta($post->ID, '_dbt_unique_filename', true);

    ?>
    <table class="form-table">
        <tr>
            <th><label for="dbt_product_id"><?php _e('WooCommerce Product ID:', 'digital-books-theme'); ?></label></th>
            <td>
                <input type="number" id="dbt_product_id" name="dbt_product_id" value="<?php echo esc_attr($product_id); ?>" class="regular-text">
                <p class="description"><?php _e('ID المنتج في WooCommerce (اتركه فارغاً لإنشاء منتج جديد)', 'digital-books-theme'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="dbt_pdf_file"><?php _e('ملف PDF:', 'digital-books-theme'); ?></label></th>
            <td>
                <input type="file" id="dbt_pdf_file" name="dbt_pdf_file" accept=".pdf">
                <?php if ($pdf_file): ?>
                    <p class="description">
                        <?php _e('الملف الحالي:', 'digital-books-theme'); ?>
                        <a href="<?php echo wp_upload_dir()['baseurl'] . '/dbt-books/' . $unique_filename; ?>" target="_blank"><?php echo esc_html($pdf_file); ?></a>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Book Audio Meta Box Callback
 */
function dbt_book_audio_callback($post) {
    global $wpdb;
    $table = $wpdb->prefix . 'dbt_audio';

    $audio_files = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE book_id = %d ORDER BY page_number ASC",
        $post->ID
    ));

    ?>
    <div id="dbt-audio-manager">
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('رقم الصفحة', 'digital-books-theme'); ?></th>
                    <th><?php _e('الملف الصوتي', 'digital-books-theme'); ?></th>
                    <th><?php _e('الوصف', 'digital-books-theme'); ?></th>
                    <th><?php _e('الإجراءات', 'digital-books-theme'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($audio_files): ?>
                    <?php foreach ($audio_files as $audio): ?>
                        <tr>
                            <td><?php echo esc_html($audio->page_number); ?></td>
                            <td>
                                <a href="<?php echo wp_upload_dir()['baseurl'] . '/dbt-audio/' . $audio->unique_filename; ?>" target="_blank">
                                    <?php echo esc_html($audio->audio_file); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($audio->description); ?></td>
                            <td>
                                <button type="button" class="button delete-audio" data-id="<?php echo $audio->id; ?>">
                                    <?php _e('حذف', 'digital-books-theme'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4"><?php _e('لا توجد ملفات صوتية', 'digital-books-theme'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h4><?php _e('إضافة ملف صوتي جديد', 'digital-books-theme'); ?></h4>
        <table class="form-table">
            <tr>
                <th><label for="audio_page_number"><?php _e('رقم الصفحة:', 'digital-books-theme'); ?></label></th>
                <td><input type="number" id="audio_page_number" name="audio_page_number" min="1" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="audio_file"><?php _e('الملف الصوتي:', 'digital-books-theme'); ?></label></th>
                <td><input type="file" id="audio_file" name="audio_file" accept="audio/*"></td>
            </tr>
            <tr>
                <th><label for="audio_description"><?php _e('الوصف:', 'digital-books-theme'); ?></label></th>
                <td><input type="text" id="audio_description" name="audio_description" class="regular-text"></td>
            </tr>
        </table>
    </div>
    <?php
}

/**
 * Save Book Meta Data
 */
function dbt_save_book_meta($post_id) {
    // Check nonce
    if (!isset($_POST['dbt_book_details_nonce']) || !wp_verify_nonce($_POST['dbt_book_details_nonce'], 'dbt_save_book_details')) {
        return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save Product ID
    if (isset($_POST['dbt_product_id'])) {
        update_post_meta($post_id, '_dbt_product_id', sanitize_text_field($_POST['dbt_product_id']));
    }

    // Handle PDF file upload
    if (!empty($_FILES['dbt_pdf_file']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $upload_dir = wp_upload_dir();
        $books_dir = $upload_dir['basedir'] . '/dbt-books';

        $file = $_FILES['dbt_pdf_file'];
        $unique_filename = time() . '_' . sanitize_file_name($file['name']);
        $destination = $books_dir . '/' . $unique_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            update_post_meta($post_id, '_dbt_pdf_file', $file['name']);
            update_post_meta($post_id, '_dbt_unique_filename', $unique_filename);

            // Update database
            global $wpdb;
            $table = $wpdb->prefix . 'dbt_books';

            $wpdb->replace($table, array(
                'product_id' => get_post_meta($post_id, '_dbt_product_id', true),
                'pdf_file' => $file['name'],
                'unique_filename' => $unique_filename,
            ));
        }
    }

    // Handle audio file upload
    if (!empty($_FILES['audio_file']['name']) && !empty($_POST['audio_page_number'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/dbt-audio';

        $file = $_FILES['audio_file'];
        $unique_filename = time() . '_' . sanitize_file_name($file['name']);
        $destination = $audio_dir . '/' . $unique_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            global $wpdb;
            $table = $wpdb->prefix . 'dbt_audio';

            $wpdb->insert($table, array(
                'book_id' => $post_id,
                'page_number' => intval($_POST['audio_page_number']),
                'audio_file' => $file['name'],
                'unique_filename' => $unique_filename,
                'description' => sanitize_text_field($_POST['audio_description']),
            ));
        }
    }
}
add_action('save_post_book', 'dbt_save_book_meta');

/**
 * AJAX: Delete Audio File
 */
function dbt_ajax_delete_audio() {
    check_ajax_referer('dbt_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('غير مسموح', 'digital-books-theme')));
    }

    $audio_id = intval($_POST['audio_id']);

    global $wpdb;
    $table = $wpdb->prefix . 'dbt_audio';

    // Get audio file info
    $audio = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $audio_id));

    if ($audio) {
        // Delete file
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/dbt-audio/' . $audio->unique_filename;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Delete from database
        $wpdb->delete($table, array('id' => $audio_id));

        wp_send_json_success();
    }

    wp_send_json_error();
}
add_action('wp_ajax_dbt_delete_audio', 'dbt_ajax_delete_audio');

/**
 * AJAX: Save Reading Progress
 */
function dbt_ajax_save_progress() {
    check_ajax_referer('dbt_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('يجب تسجيل الدخول', 'digital-books-theme')));
    }

    $user_id = get_current_user_id();
    $book_id = intval($_POST['book_id']);
    $current_page = intval($_POST['current_page']);

    global $wpdb;
    $table = $wpdb->prefix . 'dbt_progress';

    $wpdb->replace($table, array(
        'user_id' => $user_id,
        'book_id' => $book_id,
        'current_page' => $current_page,
    ), array('%d', '%d', '%d'));

    wp_send_json_success();
}
add_action('wp_ajax_dbt_save_progress', 'dbt_ajax_save_progress');

/**
 * AJAX: Save Book
 */
function dbt_ajax_save_book() {
    check_ajax_referer('dbt_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('يجب تسجيل الدخول', 'digital-books-theme')));
    }

    $user_id = get_current_user_id();
    $book_id = intval($_POST['book_id']);

    global $wpdb;
    $table = $wpdb->prefix . 'dbt_saved_books';

    $wpdb->replace($table, array(
        'user_id' => $user_id,
        'book_id' => $book_id,
    ), array('%d', '%d'));

    wp_send_json_success();
}
add_action('wp_ajax_dbt_save_book', 'dbt_ajax_save_book');

/**
 * AJAX: Remove Saved Book
 */
function dbt_ajax_remove_book() {
    check_ajax_referer('dbt_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('يجب تسجيل الدخول', 'digital-books-theme')));
    }

    $user_id = get_current_user_id();
    $book_id = intval($_POST['book_id']);

    global $wpdb;
    $table = $wpdb->prefix . 'dbt_saved_books';

    $wpdb->delete($table, array(
        'user_id' => $user_id,
        'book_id' => $book_id,
    ), array('%d', '%d'));

    wp_send_json_success();
}
add_action('wp_ajax_dbt_remove_book', 'dbt_ajax_remove_book');

/**
 * Check if user has purchased book (via WooCommerce)
 */
function dbt_user_has_purchased_book($user_id, $product_id) {
    if (!function_exists('wc_customer_bought_product')) {
        return false;
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return false;
    }

    return wc_customer_bought_product($user->user_email, $user_id, $product_id);
}

/**
 * Get book audio files by page
 */
function dbt_get_audio_by_page($book_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'dbt_audio';

    $audio_files = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE book_id = %d ORDER BY page_number ASC",
        $book_id
    ));

    $audio_by_page = array();
    foreach ($audio_files as $audio) {
        $audio_by_page[$audio->page_number] = $audio;
    }

    return $audio_by_page;
}

/**
 * Get user reading progress
 */
function dbt_get_reading_progress($user_id, $book_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'dbt_progress';

    $progress = $wpdb->get_row($wpdb->prepare(
        "SELECT current_page FROM $table WHERE user_id = %d AND book_id = %d",
        $user_id,
        $book_id
    ));

    return $progress ? $progress->current_page : 1;
}

// Include additional files
require_once DBT_THEME_DIR . '/inc/woocommerce-integration.php';
require_once DBT_THEME_DIR . '/inc/template-functions.php';
