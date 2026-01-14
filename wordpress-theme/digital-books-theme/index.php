<?php
/**
 * The main template file
 *
 * @package Digital_Books_Theme
 */

get_header();
?>

<div class="container">
    <div class="section">
        <?php if (have_posts()): ?>
            <div class="books-grid">
                <?php while (have_posts()): the_post(); ?>
                    <?php dbt_display_book_card(get_the_ID()); ?>
                <?php endwhile; ?>
            </div>

            <?php dbt_pagination(); ?>
        <?php else: ?>
            <div class="no-results">
                <h2><?php _e('لم يتم العثور على نتائج', 'digital-books-theme'); ?></h2>
                <p><?php _e('لم يتم العثور على محتوى هنا', 'digital-books-theme'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
