<?php
/**
 * Database Class
 * إدارة قاعدة البيانات
 */

if (!defined('ABSPATH')) {
    exit;
}

class DBP_Database {

    /**
     * Create Tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'dbp_';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // جدول الكتب
        $sql_books = "CREATE TABLE {$prefix}books (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            author_id bigint(20) NOT NULL,
            original_filename varchar(255) NOT NULL,
            unique_filename varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) NOT NULL,
            cover_image varchar(500),
            total_pages int(11) DEFAULT 0,
            category varchar(100),
            language varchar(50),
            is_paid tinyint(1) DEFAULT 0,
            price decimal(10,2) DEFAULT 0.00,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY author_id (author_id),
            KEY unique_filename (unique_filename),
            KEY is_paid (is_paid),
            KEY category (category)
        ) $charset_collate;";

        // جدول الملفات الصوتية
        $sql_audio = "CREATE TABLE {$prefix}audio_files (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            book_id bigint(20) NOT NULL,
            page_number int(11) NOT NULL,
            qr_code varchar(255) NOT NULL,
            audio_filename varchar(255) NOT NULL,
            audio_path varchar(500) NOT NULL,
            duration int(11) COMMENT 'Duration in seconds',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY book_id (book_id),
            KEY qr_code (qr_code),
            UNIQUE KEY unique_book_page (book_id, page_number)
        ) $charset_collate;";

        // جدول الكتب المحفوظة
        $sql_saved = "CREATE TABLE {$prefix}saved_books (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            book_id bigint(20) NOT NULL,
            saved_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY book_id (book_id),
            UNIQUE KEY unique_user_book (user_id, book_id)
        ) $charset_collate;";

        // جدول تقدم القراءة
        $sql_progress = "CREATE TABLE {$prefix}reading_progress (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            book_id bigint(20) NOT NULL,
            current_page int(11) DEFAULT 1,
            total_pages int(11) DEFAULT 0,
            last_read datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            progress_percentage decimal(5,2) DEFAULT 0.00,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY book_id (book_id),
            KEY last_read (last_read),
            UNIQUE KEY unique_user_book_progress (user_id, book_id)
        ) $charset_collate;";

        // جدول طلبات الشراء
        $sql_purchase_requests = "CREATE TABLE {$prefix}purchase_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            book_id bigint(20) NOT NULL,
            payment_method enum('instapay','vodafone_cash') NOT NULL,
            amount decimal(10,2) NOT NULL,
            phone_number varchar(20),
            transaction_id varchar(100),
            status enum('pending','approved','rejected') DEFAULT 'pending',
            request_date datetime DEFAULT CURRENT_TIMESTAMP,
            admin_response_date datetime NULL,
            admin_id bigint(20),
            admin_notes text,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY book_id (book_id),
            KEY status (status),
            KEY request_date (request_date)
        ) $charset_collate;";

        // جدول المشتريات المكتملة
        $sql_purchases = "CREATE TABLE {$prefix}purchases (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            book_id bigint(20) NOT NULL,
            purchase_date datetime DEFAULT CURRENT_TIMESTAMP,
            amount_paid decimal(10,2) NOT NULL,
            payment_method enum('instapay','vodafone_cash') NOT NULL,
            purchase_request_id bigint(20),
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY book_id (book_id),
            KEY purchase_date (purchase_date),
            UNIQUE KEY unique_user_book_purchase (user_id, book_id)
        ) $charset_collate;";

        // تنفيذ الاستعلامات
        dbDelta($sql_books);
        dbDelta($sql_audio);
        dbDelta($sql_saved);
        dbDelta($sql_progress);
        dbDelta($sql_purchase_requests);
        dbDelta($sql_purchases);

        // حفظ رقم إصدار قاعدة البيانات
        add_option('dbp_db_version', DBP_VERSION);
    }

    /**
     * Drop Tables
     */
    public static function drop_tables() {
        global $wpdb;

        $prefix = $wpdb->prefix . 'dbp_';

        $tables = array(
            $prefix . 'purchases',
            $prefix . 'purchase_requests',
            $prefix . 'reading_progress',
            $prefix . 'saved_books',
            $prefix . 'audio_files',
            $prefix . 'books',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('dbp_db_version');
    }

    /**
     * Get Table Name
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'dbp_' . $table;
    }
}
