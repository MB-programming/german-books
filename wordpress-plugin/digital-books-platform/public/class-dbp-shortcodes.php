<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Shortcodes {
    
    public static function init() {
        add_shortcode('dbp_books_list', array(__CLASS__, 'books_list'));
        add_shortcode('dbp_my_books', array(__CLASS__, 'my_books'));
        add_shortcode('dbp_pdf_viewer', array(__CLASS__, 'pdf_viewer'));
    }

    /**
     * Books List Shortcode
     * [dbp_books_list category="programming" paid="0"]
     */
    public static function books_list($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'paid' => null,
            'limit' => 12,
        ), $atts);

        $args = array(
            'limit' => intval($atts['limit']),
            'offset' => 0,
        );

        if (!empty($atts['category'])) {
            $args['category'] = $atts['category'];
        }

        if ($atts['paid'] !== null) {
            $args['is_paid'] = intval($atts['paid']);
        }

        $books = DBP_Books::get_books($args);

        ob_start();
        ?>
        <div class="dbp-books-grid">
            <?php foreach ($books as $book): ?>
            <div class="dbp-book-card">
                <?php if ($book->cover_image): ?>
                    <img src="<?php echo esc_url($book->cover_image); ?>" alt="<?php echo esc_attr($book->title); ?>">
                <?php endif; ?>
                
                <h3><?php echo esc_html($book->title); ?></h3>
                
                <?php if ($book->description): ?>
                    <p><?php echo esc_html(wp_trim_words($book->description, 20)); ?></p>
                <?php endif; ?>
                
                <div class="dbp-book-meta">
                    <?php if ($book->is_paid): ?>
                        <span class="price"><?php echo $book->price; ?> جنيه</span>
                    <?php else: ?>
                        <span class="free">مجاني</span>
                    <?php endif; ?>
                </div>
                
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo add_query_arg('book_id', $book->id, get_permalink()); ?>" class="button">
                        <?php echo $book->is_paid ? 'شراء الكتاب' : 'قراءة الكتاب'; ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button">سجل دخول للقراءة</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * My Books Shortcode
     * [dbp_my_books]
     */
    public static function my_books($atts) {
        if (!is_user_logged_in()) {
            return '<p>الرجاء <a href="' . wp_login_url(get_permalink()) . '">تسجيل الدخول</a> لعرض كتبك.</p>';
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $saved_table = DBP_Database::get_table_name('saved_books');
        $books_table = DBP_Database::get_table_name('books');
        
        $books = $wpdb->get_results($wpdb->prepare(
            "SELECT b.* FROM $books_table b 
            INNER JOIN $saved_table s ON b.id = s.book_id 
            WHERE s.user_id = %d 
            ORDER BY s.saved_at DESC",
            $user_id
        ));

        ob_start();
        ?>
        <div class="dbp-my-books">
            <h2>كتبي المحفوظة</h2>
            
            <?php if (empty($books)): ?>
                <p>لا توجد كتب محفوظة بعد.</p>
            <?php else: ?>
                <div class="dbp-books-grid">
                    <?php foreach ($books as $book): ?>
                    <div class="dbp-book-card">
                        <?php if ($book->cover_image): ?>
                            <img src="<?php echo esc_url($book->cover_image); ?>" alt="<?php echo esc_attr($book->title); ?>">
                        <?php endif; ?>
                        
                        <h3><?php echo esc_html($book->title); ?></h3>
                        
                        <a href="<?php echo add_query_arg('book_id', $book->id, get_permalink()); ?>" class="button">متابعة القراءة</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * PDF Viewer Shortcode
     * [dbp_pdf_viewer]
     */
    public static function pdf_viewer($atts) {
        if (!is_user_logged_in()) {
            return '<p>الرجاء <a href="' . wp_login_url(get_permalink()) . '">تسجيل الدخول</a> لقراءة الكتاب.</p>';
        }

        $book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
        
        if (!$book_id) {
            return '<p>الكتاب غير موجود.</p>';
        }

        $book = DBP_Books::get_book($book_id);
        
        if (!$book) {
            return '<p>الكتاب غير موجود.</p>';
        }

        // Check if book is paid and user has purchased
        $user_id = get_current_user_id();
        if ($book->is_paid && !DBP_Payments::has_purchased($user_id, $book_id)) {
            return '<p>يجب شراء هذا الكتاب أولاً. <a href="' . add_query_arg('purchase_book', $book_id, get_permalink()) . '">شراء الآن</a></p>';
        }

        // Get audio files
        $audio_files = DBP_Audio::get_audio_files($book_id);
        $audio_pages = array();
        foreach ($audio_files as $audio) {
            $audio_pages[$audio->page_number] = $audio;
        }

        // Get progress
        $progress = DBP_Progress::get_progress($user_id, $book_id);
        $current_page = $progress ? $progress->current_page : 1;

        // Include PDF Viewer
        return DBP_PDF_Viewer::render($book, $audio_pages, $current_page);
    }
}
