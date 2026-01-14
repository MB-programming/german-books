/**
 * Digital Books Theme - Main JavaScript
 *
 * @package Digital_Books_Theme
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initMobileMenu();
        initSaveBook();
        initRemoveBook();
        initBookPreview();
        initCartUpdate();
    });

    /**
     * Mobile Menu Toggle
     */
    function initMobileMenu() {
        $('.mobile-menu-toggle').on('click', function() {
            $('body').toggleClass('mobile-menu-open');
            $('.main-navigation').slideToggle();
        });
    }

    /**
     * Save Book
     */
    function initSaveBook() {
        $(document).on('click', '.save-book-btn', function(e) {
            e.preventDefault();

            const bookId = $(this).data('book-id');
            const $button = $(this);

            $.ajax({
                url: dbtData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dbt_save_book',
                    nonce: dbtData.nonce,
                    book_id: bookId
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('جاري الحفظ...');
                },
                success: function(response) {
                    if (response.success) {
                        $button.text('تم الحفظ ✓').css('background', '#38ef7d');

                        Swal.fire({
                            icon: 'success',
                            title: 'تم الحفظ!',
                            text: 'تم إضافة الكتاب إلى قائمة كتبي',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    } else {
                        $button.prop('disabled', false).text('حفظ الكتاب');

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ!',
                            text: response.data.message || 'حدث خطأ أثناء حفظ الكتاب',
                            confirmButtonText: 'حسناً'
                        });
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('حفظ الكتاب');

                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: 'حدث خطأ في الاتصال بالخادم',
                        confirmButtonText: 'حسناً'
                    });
                }
            });
        });
    }

    /**
     * Remove Book from Saved
     */
    function initRemoveBook() {
        $(document).on('click', '.remove-book-btn', function(e) {
            e.preventDefault();

            const bookId = $(this).data('book-id');
            const $card = $(this).closest('.book-card');

            Swal.fire({
                title: 'إزالة الكتاب',
                text: 'هل تريد إزالة هذا الكتاب من قائمة كتبي؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، أزل',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    removeBook(bookId, $card);
                }
            });
        });
    }

    function removeBook(bookId, $card) {
        $.ajax({
            url: dbtData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dbt_remove_book',
                nonce: dbtData.nonce,
                book_id: bookId
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'جاري الإزالة...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    $card.fadeOut(400, function() {
                        $(this).remove();

                        // Check if no books left
                        if ($('.book-card').length === 0) {
                            $('.books-grid').html('<div class="no-results"><h3>لا توجد كتب محفوظة</h3><p>ابدأ بإضافة كتب إلى مكتبتك!</p></div>');
                        }
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'تمت الإزالة!',
                        text: 'تم إزالة الكتاب من قائمتك',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ أثناء الإزالة',
                        confirmButtonText: 'حسناً'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: 'حدث خطأ في الاتصال بالخادم',
                    confirmButtonText: 'حسناً'
                });
            }
        });
    }

    /**
     * Book Preview
     */
    function initBookPreview() {
        $(document).on('click', '.preview-book', function() {
            const bookId = $(this).data('book-id');

            Swal.fire({
                title: 'معاينة الكتاب',
                html: '<p>سيتم إضافة معاينة الكتاب قريباً...</p>',
                icon: 'info',
                confirmButtonText: 'حسناً'
            });
        });
    }

    /**
     * Update Cart Count on Add to Cart
     */
    function initCartUpdate() {
        $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
            // Update cart count
            if (fragments && fragments['.cart-count']) {
                $('.cart-count').html(fragments['.cart-count']);
            }

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'تم الإضافة!',
                text: 'تم إضافة الكتاب إلى السلة',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    }

    /**
     * Smooth Scroll
     */
    $('a[href*="#"]').on('click', function(e) {
        const target = $(this.hash);
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });

    /**
     * Delete Audio (Admin)
     */
    $(document).on('click', '.delete-audio', function(e) {
        e.preventDefault();

        const audioId = $(this).data('id');
        const $row = $(this).closest('tr');

        Swal.fire({
            title: 'حذف الملف الصوتي',
            text: 'هل أنت متأكد؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteAudio(audioId, $row);
            }
        });
    });

    function deleteAudio(audioId, $row) {
        $.ajax({
            url: dbtData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dbt_delete_audio',
                nonce: dbtData.nonce,
                audio_id: audioId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(400, function() {
                        $(this).remove();
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحذف!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }
        });
    }

    /**
     * Lazy Load Images
     */
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    }

    /**
     * Share Book
     */
    window.shareBook = function(bookId, bookTitle) {
        const url = window.location.origin + '/books/' + bookId;

        if (navigator.share) {
            navigator.share({
                title: bookTitle,
                url: url
            }).catch(console.error);
        } else {
            // Fallback to clipboard
            navigator.clipboard.writeText(url).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'تم النسخ!',
                    text: 'تم نسخ رابط الكتاب',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        }
    };

})(jQuery);
