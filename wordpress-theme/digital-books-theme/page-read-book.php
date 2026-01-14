<?php
/**
 * Template Name: قراءة الكتاب
 * Template for reading books with PDF viewer
 *
 * @package Digital_Books_Theme
 */

get_header();

$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
?>

<div class="container">
    <div class="section read-book-section">
        <?php echo dbt_get_pdf_viewer($book_id); ?>
    </div>
</div>

<?php
get_footer();
