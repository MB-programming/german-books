<?php
if (!defined('ABSPATH')) { exit; }

class DBP_Admin_Settings {
    
    public static function render_page() {
        // Handle form submission
        if (isset($_POST['dbp_save_settings']) && check_admin_referer('dbp_settings', 'dbp_settings_nonce')) {
            update_option('dbp_instapay_number', sanitize_text_field($_POST['instapay_number']));
            update_option('dbp_vodafone_cash_number', sanitize_text_field($_POST['vodafone_cash_number']));
            update_option('dbp_whatsapp_number', sanitize_text_field($_POST['whatsapp_number']));
            update_option('dbp_enable_payments', isset($_POST['enable_payments']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>تم حفظ الإعدادات بنجاح!</p></div>';
        }
        
        $instapay = get_option('dbp_instapay_number', '');
        $vodafone = get_option('dbp_vodafone_cash_number', '');
        $whatsapp = get_option('dbp_whatsapp_number', '');
        $enable_payments = get_option('dbp_enable_payments', 1);
        
        ?>
        <div class="wrap">
            <h1><?php _e('إعدادات منصة الكتب الرقمية', 'digital-books-platform'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('dbp_settings', 'dbp_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="instapay_number">رقم إنستاباي</label></th>
                        <td><input type="text" name="instapay_number" id="instapay_number" value="<?php echo esc_attr($instapay); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="vodafone_cash_number">رقم فودافون كاش</label></th>
                        <td><input type="text" name="vodafone_cash_number" id="vodafone_cash_number" value="<?php echo esc_attr($vodafone); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="whatsapp_number">رقم واتساب</label></th>
                        <td><input type="text" name="whatsapp_number" id="whatsapp_number" value="<?php echo esc_attr($whatsapp); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="enable_payments">تفعيل نظام الدفع</label></th>
                        <td><input type="checkbox" name="enable_payments" id="enable_payments" value="1" <?php checked($enable_payments, 1); ?>></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="dbp_save_settings" class="button button-primary">حفظ الإعدادات</button>
                </p>
            </form>
        </div>
        <?php
    }
}
