<?php
/**
 * Plugin Name:       منصة الكتب الرقمية - Digital Books Platform
 * Plugin URI:        https://netlabacademy.com/
 * Description:       منصة متكاملة لإدارة وقراءة الكتب الرقمية مع دعم الملفات الصوتية التفاعلية عبر QR Codes ونظام دفع محلي
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            NetLab Academy
 * Author URI:        https://netlabacademy.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       digital-books-platform
 * Domain Path:       /languages
 */

// إذا تم الوصول مباشرة للملف، أوقف التنفيذ
if (!defined('ABSPATH')) {
    exit;
}

// تعريف الثوابت
define('DBP_VERSION', '1.0.0');
define('DBP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DBP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DBP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin Main Class
 */
class Digital_Books_Platform {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Get Instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    /**
     * Load Dependencies
     */
    private function load_dependencies() {
        // Core Classes
        require_once DBP_PLUGIN_DIR . 'includes/class-dbp-database.php';
        require_once DBP_PLUGIN_DIR . 'includes/class-dbp-roles.php';
        require_once DBP_PLUGIN_DIR . 'includes/class-dbp-books.php';
        require_once DBP_PLUGIN_DIR . 'includes/class-dbp-audio.php';
        require_once DBP_PLUGIN_DIR . 'includes/class-dbp-payments.php';
        require_once DBP_PLUGIN_DIR . 'includes/class-dbp-progress.php';

        // Admin Classes
        if (is_admin()) {
            require_once DBP_PLUGIN_DIR . 'admin/class-dbp-admin.php';
            require_once DBP_PLUGIN_DIR . 'admin/class-dbp-admin-books.php';
            require_once DBP_PLUGIN_DIR . 'admin/class-dbp-admin-payments.php';
            require_once DBP_PLUGIN_DIR . 'admin/class-dbp-admin-settings.php';
        }

        // Public Classes
        require_once DBP_PLUGIN_DIR . 'public/class-dbp-public.php';
        require_once DBP_PLUGIN_DIR . 'public/class-dbp-shortcodes.php';
        require_once DBP_PLUGIN_DIR . 'public/class-dbp-pdf-viewer.php';
    }

    /**
     * Define Hooks
     */
    private function define_hooks() {
        // Activation & Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Init
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Enqueue Scripts & Styles
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'public_enqueue_scripts'));
    }

    /**
     * Plugin Activation
     */
    public function activate() {
        // Create Database Tables
        DBP_Database::create_tables();

        // Create Upload Directories
        $this->create_upload_directories();

        // Add Capabilities
        DBP_Roles::add_capabilities();

        // Flush Rewrite Rules
        flush_rewrite_rules();

        // Set Default Options
        $this->set_default_options();
    }

    /**
     * Plugin Deactivation
     */
    public function deactivate() {
        // Flush Rewrite Rules
        flush_rewrite_rules();
    }

    /**
     * Init
     */
    public function init() {
        // Initialize Admin
        if (is_admin()) {
            DBP_Admin::init();
        }

        // Initialize Public
        DBP_Public::init();

        // Initialize Shortcodes
        DBP_Shortcodes::init();
    }

    /**
     * Load Textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'digital-books-platform',
            false,
            dirname(DBP_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Admin Enqueue Scripts
     */
    public function admin_enqueue_scripts($hook) {
        // SweetAlert2
        wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true);

        // Plugin Admin Styles
        wp_enqueue_style('dbp-admin', DBP_PLUGIN_URL . 'assets/css/admin.css', array(), DBP_VERSION);

        // Plugin Admin Scripts
        wp_enqueue_script('dbp-admin', DBP_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'sweetalert2'), DBP_VERSION, true);

        // Localize Script
        wp_localize_script('dbp-admin', 'dbpAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dbp_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('هل أنت متأكد من الحذف؟', 'digital-books-platform'),
                'saved' => __('تم الحفظ بنجاح', 'digital-books-platform'),
                'error' => __('حدث خطأ', 'digital-books-platform'),
            )
        ));
    }

    /**
     * Public Enqueue Scripts
     */
    public function public_enqueue_scripts() {
        // SweetAlert2
        wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true);

        // PDF.js
        wp_enqueue_script('pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js', array(), '3.11.174', true);

        // Plugin Public Styles
        wp_enqueue_style('dbp-public', DBP_PLUGIN_URL . 'assets/css/public.css', array(), DBP_VERSION);

        // Plugin Public Scripts
        wp_enqueue_script('dbp-public', DBP_PLUGIN_URL . 'assets/js/public.js', array('jquery', 'sweetalert2', 'pdfjs'), DBP_VERSION, true);

        // Localize Script
        wp_localize_script('dbp-public', 'dbpPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dbp_public_nonce'),
            'pluginUrl' => DBP_PLUGIN_URL,
            'strings' => array(
                'loading' => __('جاري التحميل...', 'digital-books-platform'),
                'error' => __('حدث خطأ', 'digital-books-platform'),
            )
        ));
    }

    /**
     * Create Upload Directories
     */
    private function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $dbp_upload_dir = $upload_dir['basedir'] . '/digital-books';

        $directories = array(
            $dbp_upload_dir,
            $dbp_upload_dir . '/books',
            $dbp_upload_dir . '/audio',
            $dbp_upload_dir . '/covers',
            $dbp_upload_dir . '/qr',
        );

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);

                // Create index.html for security
                file_put_contents($dir . '/index.html', '<!-- Protected -->');
            }
        }
    }

    /**
     * Set Default Options
     */
    private function set_default_options() {
        $defaults = array(
            'dbp_instapay_number' => '01222112819',
            'dbp_vodafone_cash_number' => '01014959132',
            'dbp_whatsapp_number' => '01222112819',
            'dbp_enable_payments' => '1',
            'dbp_books_per_page' => '12',
        );

        foreach ($defaults as $key => $value) {
            if (!get_option($key)) {
                add_option($key, $value);
            }
        }
    }
}

/**
 * Initialize Plugin
 */
function digital_books_platform() {
    return Digital_Books_Platform::get_instance();
}

// Start the plugin
digital_books_platform();
