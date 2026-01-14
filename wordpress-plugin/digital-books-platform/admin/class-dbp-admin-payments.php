<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Admin_Payments {
    
    public static function render_page() {
        global $wpdb;
        $table = DBP_Database::get_table_name('purchase_requests');
        
        $requests = $wpdb->get_results("SELECT pr.*, b.title as book_title, u.display_name as user_name 
            FROM $table pr 
            LEFT JOIN {$wpdb->prefix}dbp_books b ON pr.book_id = b.id 
            LEFT JOIN {$wpdb->users} u ON pr.user_id = u.ID 
            ORDER BY pr.request_date DESC 
            LIMIT 50");
        
        ?>
        <div class="wrap">
            <h1><?php _e('طلبات الشراء', 'digital-books-platform'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>الكتاب</th>
                        <th>المبلغ</th>
                        <th>طريقة الدفع</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo esc_html($request->user_name); ?></td>
                        <td><?php echo esc_html($request->book_title); ?></td>
                        <td><?php echo $request->amount; ?> جنيه</td>
                        <td><?php echo $request->payment_method == 'instapay' ? 'إنستاباي' : 'فودافون كاش'; ?></td>
                        <td><?php echo self::get_status_badge($request->status); ?></td>
                        <td><?php echo date('Y/m/d', strtotime($request->request_date)); ?></td>
                        <td>
                            <?php if ($request->status == 'pending'): ?>
                                <a href="#" class="button approve-request" data-id="<?php echo $request->id; ?>">موافقة</a>
                                <a href="#" class="button reject-request" data-id="<?php echo $request->id; ?>">رفض</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function get_status_badge($status) {
        $badges = array(
            'pending' => '<span style="color: orange;">قيد الانتظار</span>',
            'approved' => '<span style="color: green;">مقبول</span>',
            'rejected' => '<span style="color: red;">مرفوض</span>',
        );
        
        return isset($badges[$status]) ? $badges[$status] : $status;
    }
}
