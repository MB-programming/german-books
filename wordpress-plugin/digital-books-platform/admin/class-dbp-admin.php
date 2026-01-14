<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Admin {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu_pages'));
    }

    public static function add_menu_pages() {
        // Main Menu
        add_menu_page(
            __('الكتب الرقمية', 'digital-books-platform'),
            __('الكتب الرقمية', 'digital-books-platform'),
            'manage_digital_books',
            'dbp-books',
            array('DBP_Admin_Books', 'render_page'),
            'dashicons-book',
            30
        );

        // Books Submenu
        add_submenu_page(
            'dbp-books',
            __('جميع الكتب', 'digital-books-platform'),
            __('جميع الكتب', 'digital-books-platform'),
            'manage_digital_books',
            'dbp-books',
            array('DBP_Admin_Books', 'render_page')
        );

        // Add New Book
        add_submenu_page(
            'dbp-books',
            __('إضافة كتاب', 'digital-books-platform'),
            __('إضافة كتاب', 'digital-books-platform'),
            'edit_digital_books',
            'dbp-add-book',
            array('DBP_Admin_Books', 'render_add_page')
        );

        // Purchase Requests
        add_submenu_page(
            'dbp-books',
            __('طلبات الشراء', 'digital-books-platform'),
            __('طلبات الشراء', 'digital-books-platform'),
            'manage_book_payments',
            'dbp-purchase-requests',
            array('DBP_Admin_Payments', 'render_page')
        );

        // Settings
        add_submenu_page(
            'dbp-books',
            __('الإعدادات', 'digital-books-platform'),
            __('الإعدادات', 'digital-books-platform'),
            'manage_options',
            'dbp-settings',
            array('DBP_Admin_Settings', 'render_page')
        );
    }
}
