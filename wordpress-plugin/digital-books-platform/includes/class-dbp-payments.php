<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Payments {
    public static function create_purchase_request($user_id, $book_id, $data) {
        global $wpdb;
        $table = DBP_Database::get_table_name('purchase_requests');
        
        $data['user_id'] = $user_id;
        $data['book_id'] = $book_id;
        $data['status'] = 'pending';
        
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    public static function approve_purchase($request_id, $admin_id) {
        global $wpdb;
        $requests_table = DBP_Database::get_table_name('purchase_requests');
        $purchases_table = DBP_Database::get_table_name('purchases');
        
        // Get request
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $requests_table WHERE id = %d", $request_id));
        
        if (!$request) return false;
        
        // Update request status
        $wpdb->update(
            $requests_table,
            array(
                'status' => 'approved',
                'admin_id' => $admin_id,
                'admin_response_date' => current_time('mysql')
            ),
            array('id' => $request_id)
        );
        
        // Create purchase
        $wpdb->insert($purchases_table, array(
            'user_id' => $request->user_id,
            'book_id' => $request->book_id,
            'amount_paid' => $request->amount,
            'payment_method' => $request->payment_method,
            'purchase_request_id' => $request_id
        ));
        
        return true;
    }

    public static function has_purchased($user_id, $book_id) {
        global $wpdb;
        $table = DBP_Database::get_table_name('purchases');
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND book_id = %d",
            $user_id, $book_id
        ));
        
        return $count > 0;
    }
}
