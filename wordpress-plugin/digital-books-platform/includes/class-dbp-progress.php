<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Progress {
    public static function save_progress($user_id, $book_id, $current_page, $total_pages) {
        global $wpdb;
        $table = DBP_Database::get_table_name('reading_progress');
        
        $progress_percentage = ($current_page / $total_pages) * 100;
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND book_id = %d",
            $user_id, $book_id
        ));
        
        $data = array(
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'progress_percentage' => $progress_percentage
        );
        
        if ($existing) {
            $wpdb->update($table, $data, array(
                'user_id' => $user_id,
                'book_id' => $book_id
            ));
        } else {
            $data['user_id'] = $user_id;
            $data['book_id'] = $book_id;
            $wpdb->insert($table, $data);
        }
        
        return true;
    }

    public static function get_progress($user_id, $book_id) {
        global $wpdb;
        $table = DBP_Database::get_table_name('reading_progress');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND book_id = %d",
            $user_id, $book_id
        ));
    }
}
