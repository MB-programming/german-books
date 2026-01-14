<?php
/**
 * Books Management Class
 */

if (!defined('ABSPATH')) { exit; }

class DBP_Books {

    /**
     * Get Book
     */
    public static function get_book($book_id) {
        global $wpdb;
        $table = DBP_Database::get_table_name('books');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $book_id));
    }

    /**
     * Get All Books
     */
    public static function get_books($args = array()) {
        global $wpdb;
        $table = DBP_Database::get_table_name('books');

        $defaults = array(
            'limit' => 10,
            'offset' => 0,
            'is_paid' => null,
            'category' => null,
            'orderby' => 'upload_date',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        
        if ($args['is_paid'] !== null) {
            $where[] = $wpdb->prepare('is_paid = %d', $args['is_paid']);
        }
        
        if ($args['category']) {
            $where[] = $wpdb->prepare('category = %s', $args['category']);
        }

        $where_sql = implode(' AND ', $where);
        
        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $args['limit'], $args['offset']));
    }

    /**
     * Add Book
     */
    public static function add_book($data) {
        global $wpdb;
        $table = DBP_Database::get_table_name('books');
        
        $wpdb->insert($table, $data);
        
        return $wpdb->insert_id;
    }

    /**
     * Update Book
     */
    public static function update_book($book_id, $data) {
        global $wpdb;
        $table = DBP_Database::get_table_name('books');
        
        return $wpdb->update($table, $data, array('id' => $book_id));
    }

    /**
     * Delete Book
     */
    public static function delete_book($book_id) {
        global $wpdb;
        $table = DBP_Database::get_table_name('books');
        
        return $wpdb->delete($table, array('id' => $book_id));
    }
}
