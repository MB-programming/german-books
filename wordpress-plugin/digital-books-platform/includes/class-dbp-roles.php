<?php
/**
 * Roles & Capabilities Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class DBP_Roles {

    /**
     * Add Capabilities
     */
    public static function add_capabilities() {
        // Get Administrator Role
        $admin = get_role('administrator');

        if ($admin) {
            // إضافة صلاحيات إدارة الكتب
            $admin->add_cap('manage_digital_books');
            $admin->add_cap('edit_digital_books');
            $admin->add_cap('delete_digital_books');
            $admin->add_cap('publish_digital_books');
            $admin->add_cap('manage_book_payments');
        }

        // Get Editor Role (يمكن إضافة كمحرر للكتب)
        $editor = get_role('editor');

        if ($editor) {
            $editor->add_cap('edit_digital_books');
        }
    }

    /**
     * Remove Capabilities
     */
    public static function remove_capabilities() {
        $roles = array('administrator', 'editor');

        foreach ($roles as $role_name) {
            $role = get_role($role_name);

            if ($role) {
                $role->remove_cap('manage_digital_books');
                $role->remove_cap('edit_digital_books');
                $role->remove_cap('delete_digital_books');
                $role->remove_cap('publish_digital_books');
                $role->remove_cap('manage_book_payments');
            }
        }
    }
}
