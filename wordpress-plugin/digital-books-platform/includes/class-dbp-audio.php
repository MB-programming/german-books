<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Audio {
    public static function get_audio_files($book_id) {
        global $wpdb;
        $table = DBP_Database::get_table_name('audio_files');
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE book_id = %d ORDER BY page_number", $book_id));
    }

    public static function add_audio($data) {
        global $wpdb;
        $table = DBP_Database::get_table_name('audio_files');
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }
}
