<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Admin_Books {
    
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('جميع الكتب', 'digital-books-platform'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=dbp-add-book'); ?>" class="page-title-action">إضافة كتاب جديد</a>
            
            <?php
            $books = DBP_Books::get_books(array('limit' => 50));
            ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>الفئة</th>
                        <th>السعر</th>
                        <th>تاريخ الرفع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?php echo esc_html($book->title); ?></td>
                        <td><?php echo esc_html($book->category); ?></td>
                        <td><?php echo $book->is_paid ? $book->price . ' جنيه' : 'مجاني'; ?></td>
                        <td><?php echo date('Y/m/d', strtotime($book->upload_date)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=dbp-edit-book&id=' . $book->id); ?>">تعديل</a> |
                            <a href="#" class="delete-book" data-id="<?php echo $book->id; ?>">حذف</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_add_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('إضافة كتاب جديد', 'digital-books-platform'); ?></h1>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('dbp_add_book', 'dbp_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="title">عنوان الكتاب</label></th>
                        <td><input type="text" name="title" id="title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="description">الوصف</label></th>
                        <td><textarea name="description" id="description" rows="5" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="pdf_file">ملف PDF</label></th>
                        <td><input type="file" name="pdf_file" id="pdf_file" accept=".pdf" required></td>
                    </tr>
                    <tr>
                        <th><label for="cover_image">صورة الغلاف</label></th>
                        <td><input type="file" name="cover_image" id="cover_image" accept="image/*"></td>
                    </tr>
                    <tr>
                        <th><label for="category">الفئة</label></th>
                        <td><input type="text" name="category" id="category" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="is_paid">نوع الكتاب</label></th>
                        <td>
                            <select name="is_paid" id="is_paid">
                                <option value="0">مجاني</option>
                                <option value="1">مدفوع</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="price">السعر (جنيه)</label></th>
                        <td><input type="number" name="price" id="price" step="0.01" value="0"></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="dbp_add_book_submit" class="button button-primary">إضافة الكتاب</button>
                </p>
            </form>
        </div>
        <?php
        
        // Handle form submission
        if (isset($_POST['dbp_add_book_submit']) && check_admin_referer('dbp_add_book', 'dbp_nonce')) {
            self::handle_add_book();
        }
    }

    private static function handle_add_book() {
        // Handle file upload and save book
        // This is simplified - full implementation would include proper file handling
        
        if (!empty($_FILES['pdf_file']['name'])) {
            $upload = wp_handle_upload($_FILES['pdf_file'], array('test_form' => false));
            
            if ($upload && !isset($upload['error'])) {
                $unique_filename = uniqid('book_') . '.pdf';
                
                $book_data = array(
                    'title' => sanitize_text_field($_POST['title']),
                    'description' => sanitize_textarea_field($_POST['description']),
                    'author_id' => get_current_user_id(),
                    'original_filename' => $_FILES['pdf_file']['name'],
                    'unique_filename' => $unique_filename,
                    'file_path' => $upload['file'],
                    'file_size' => $_FILES['pdf_file']['size'],
                    'category' => sanitize_text_field($_POST['category']),
                    'is_paid' => intval($_POST['is_paid']),
                    'price' => floatval($_POST['price']),
                );
                
                $book_id = DBP_Books::add_book($book_data);
                
                if ($book_id) {
                    echo '<div class="notice notice-success"><p>تم إضافة الكتاب بنجاح!</p></div>';
                }
            }
        }
    }
}
