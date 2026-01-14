<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container">
        <div class="header-wrapper">
            <div class="site-logo">
                <?php
                if (has_custom_logo()) {
                    the_custom_logo();
                } else {
                    echo '<a href="' . esc_url(home_url('/')) . '">' . get_bloginfo('name') . '</a>';
                }
                ?>
            </div>

            <nav class="main-navigation">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'primary-menu',
                    'fallback_cb' => false,
                ));
                ?>
            </nav>

            <div class="header-actions">
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('my-books')); ?>" class="btn-header">
                        <?php _e('كتبي', 'digital-books-theme'); ?>
                    </a>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn-header">
                        <?php _e('تسجيل الخروج', 'digital-books-theme'); ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn-header">
                        <?php _e('تسجيل الدخول', 'digital-books-theme'); ?>
                    </a>
                <?php endif; ?>

                <?php if (function_exists('WC')): ?>
                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="btn-header cart-link">
                        🛒 <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main class="site-main">
