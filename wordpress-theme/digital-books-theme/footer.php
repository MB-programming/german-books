</main><!-- .site-main -->

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo">
                <h3><?php bloginfo('name'); ?></h3>
                <p><?php bloginfo('description'); ?></p>
            </div>

            <div class="footer-nav">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'container' => false,
                    'menu_class' => 'footer-menu',
                    'fallback_cb' => false,
                ));
                ?>
            </div>

            <div class="footer-social">
                <h4><?php _e('تابعنا', 'digital-books-theme'); ?></h4>
                <!-- Add social media links here -->
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php _e('جميع الحقوق محفوظة', 'digital-books-theme'); ?>.</p>
                <p><?php _e('صنع بـ', 'digital-books-theme'); ?> ❤️ <?php _e('من أجل مجتمع القراءة العربي', 'digital-books-theme'); ?></p>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
