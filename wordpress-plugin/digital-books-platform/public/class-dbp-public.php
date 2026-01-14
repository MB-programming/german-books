<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Public {
    
    public static function init() {
        // AJAX Actions
        add_action('wp_ajax_dbp_save_progress', array(__CLASS__, 'ajax_save_progress'));
        add_action('wp_ajax_dbp_save_book', array(__CLASS__, 'ajax_save_book'));
        add_action('wp_ajax_dbp_create_purchase_request', array(__CLASS__, 'ajax_create_purchase_request'));
    }

    public static function ajax_save_progress() {
        check_ajax_referer('dbp_public_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $book_id = intval($_POST['book_id']);
        $current_page = intval($_POST['current_page']);
        $total_pages = intval($_POST['total_pages']);
        
        if ($user_id && $book_id) {
            DBP_Progress::save_progress($user_id, $book_id, $current_page, $total_pages);
            wp_send_json_success(array('message' => 'تم حفظ التقدم'));
        } else {
            wp_send_json_error(array('message' => 'خطأ في البيانات'));
        }
    }

    public static function ajax_save_book() {
        check_ajax_referer('dbp_public_nonce', 'nonce');
        
        global $wpdb;
        $table = DBP_Database::get_table_name('saved_books');
        
        $user_id = get_current_user_id();
        $book_id = intval($_POST['book_id']);
        
        if ($user_id && $book_id) {
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'book_id' => $book_id
            ));
            
            wp_send_json_success(array('message' => 'تم حفظ الكتاب'));
        } else {
            wp_send_json_error(array('message' => 'خطأ في البيانات'));
        }
    }

    public static function ajax_create_purchase_request() {
        check_ajax_referer('dbp_public_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $book_id = intval($_POST['book_id']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $amount = floatval($_POST['amount']);
        
        if ($user_id && $book_id) {
            DBP_Payments::create_purchase_request($user_id, $book_id, array(
                'payment_method' => $payment_method,
                'amount' => $amount
            ));
            
            wp_send_json_success(array('message' => 'تم إرسال طلب الشراء'));
        } else {
            wp_send_json_error(array('message' => 'خطأ في البيانات'));
        }
    }
}
