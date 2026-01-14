/**
 * Digital Books Platform - Public JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        initSaveBook();
        initRemoveBook();
        initPurchaseBook();
        initBookFilters();
        initLazyLoading();
    });

    /**
     * Save Book to My Books
     */
    function initSaveBook() {
        $(document).on('click', '.save-book', function(e) {
            e.preventDefault();

            const bookId = $(this).data('book-id');
            const $button = $(this);

            saveBook(bookId, $button);
        });
    }

    function saveBook(bookId, $button) {
        $.ajax({
            url: dbpPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dbp_save_book',
                nonce: dbpPublic.nonce,
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
    }

    /**
     * Remove Book from My Books
     */
    function initRemoveBook() {
        $(document).on('click', '.remove-book', function(e) {
            e.preventDefault();

            const bookId = $(this).data('book-id');
            const $card = $(this).closest('.dbp-book-card');

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
            url: dbpPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dbp_remove_book',
                nonce: dbpPublic.nonce,
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
                        if ($('.dbp-book-card').length === 0) {
                            $('.dbp-books-grid').html('<div class="dbp-empty-state"><div class="dbp-empty-state-icon">📚</div><h3>لا توجد كتب محفوظة</h3><p>ابدأ بإضافة كتب إلى مكتبتك!</p></div>');
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
     * Purchase Book
     */
    function initPurchaseBook() {
        $(document).on('click', '.purchase-book', function(e) {
            e.preventDefault();

            const bookId = $(this).data('book-id');
            const bookTitle = $(this).data('book-title');
            const bookPrice = $(this).data('book-price');

            showPurchaseModal(bookId, bookTitle, bookPrice);
        });
    }

    function showPurchaseModal(bookId, bookTitle, bookPrice) {
        Swal.fire({
            title: 'شراء الكتاب',
            html: `
                <div class="dbp-purchase-modal">
                    <h3>${bookTitle}</h3>
                    <p style="font-size: 24px; font-weight: bold; color: #667eea; margin: 20px 0;">
                        ${bookPrice} جنيه
                    </p>

                    <div class="dbp-payment-options">
                        <div class="dbp-payment-option" onclick="selectPaymentMethod(this, 'instapay')">
                            <input type="radio" name="payment_method" value="instapay" id="payment_instapay">
                            <label for="payment_instapay">إنستاباي</label>
                        </div>
                        <div class="dbp-payment-option" onclick="selectPaymentMethod(this, 'vodafone')">
                            <input type="radio" name="payment_method" value="vodafone" id="payment_vodafone">
                            <label for="payment_vodafone">فودافون كاش</label>
                        </div>
                    </div>

                    <div id="payment-info" class="dbp-payment-info" style="display: none;">
                        <p><strong>خطوات الدفع:</strong></p>
                        <ol style="text-align: right; padding-right: 20px;">
                            <li>قم بتحويل المبلغ إلى الرقم المعروض</li>
                            <li>اضغط على زر "إرسال طلب الشراء"</li>
                            <li>سيتم تفعيل الكتاب بعد تأكيد الدفع</li>
                        </ol>
                        <p id="payment-number" style="font-size: 20px; font-weight: bold; text-align: center; margin: 20px 0;"></p>
                    </div>
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'إرسال طلب الشراء',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#667eea',
            preConfirm: () => {
                const selectedMethod = $('input[name="payment_method"]:checked').val();

                if (!selectedMethod) {
                    Swal.showValidationMessage('يرجى اختيار طريقة الدفع');
                    return false;
                }

                return { method: selectedMethod };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                submitPurchaseRequest(bookId, result.value.method, bookPrice);
            }
        });

        // Make selectPaymentMethod available globally for this modal
        window.selectPaymentMethod = function(element, method) {
            $('.dbp-payment-option').removeClass('selected');
            $(element).addClass('selected');
            $(element).find('input[type="radio"]').prop('checked', true);

            // Show payment info
            $('#payment-info').show();

            // Set payment number based on method
            if (method === 'instapay') {
                $('#payment-number').text('رقم إنستاباي: ' + (dbpPublic.instapayNumber || 'غير متوفر'));
            } else {
                $('#payment-number').text('رقم فودافون كاش: ' + (dbpPublic.vodafoneNumber || 'غير متوفر'));
            }
        };
    }

    function submitPurchaseRequest(bookId, paymentMethod, amount) {
        $.ajax({
            url: dbpPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dbp_purchase_request',
                nonce: dbpPublic.nonce,
                book_id: bookId,
                payment_method: paymentMethod,
                amount: amount
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'جاري الإرسال...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم إرسال الطلب!',
                        html: `
                            <p>تم إرسال طلب الشراء بنجاح</p>
                            <p>سيتم مراجعة طلبك وتفعيل الكتاب بعد تأكيد الدفع</p>
                            <p style="margin-top: 20px;">
                                <a href="https://wa.me/${dbpPublic.whatsappNumber}?text=تم إرسال طلب شراء للكتاب رقم ${bookId}"
                                   target="_blank"
                                   style="display: inline-block; background: #25D366; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold;">
                                    💬 تواصل عبر واتساب
                                </a>
                            </p>
                        `,
                        confirmButtonText: 'حسناً'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ أثناء إرسال الطلب',
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
     * Book Filters
     */
    function initBookFilters() {
        $('#dbp-category-filter, #dbp-paid-filter').on('change', function() {
            filterBooks();
        });

        $('#dbp-search-input').on('input', debounce(function() {
            filterBooks();
        }, 500));
    }

    function filterBooks() {
        const category = $('#dbp-category-filter').val();
        const isPaid = $('#dbp-paid-filter').val();
        const search = $('#dbp-search-input').val();

        $.ajax({
            url: dbpPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dbp_filter_books',
                nonce: dbpPublic.nonce,
                category: category,
                is_paid: isPaid,
                search: search
            },
            beforeSend: function() {
                $('.dbp-books-grid').html('<div class="dbp-loading-container"><div class="dbp-spinner"></div><p>جاري التحميل...</p></div>');
            },
            success: function(response) {
                if (response.success) {
                    $('.dbp-books-grid').html(response.data.html);
                } else {
                    $('.dbp-books-grid').html('<div class="dbp-empty-state"><div class="dbp-empty-state-icon">📚</div><h3>لم يتم العثور على نتائج</h3><p>جرب تغيير معايير البحث</p></div>');
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
     * Lazy Loading for Images
     */
    function initLazyLoading() {
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
    }

    /**
     * Debounce Helper
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Share Book
     */
    window.shareBook = function(bookId, bookTitle) {
        const url = window.location.origin + '?book_id=' + bookId;

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

    /**
     * Rate Book
     */
    window.rateBook = function(bookId) {
        Swal.fire({
            title: 'تقييم الكتاب',
            html: `
                <div style="text-align: center;">
                    <div class="star-rating" style="font-size: 40px; margin: 20px 0;">
                        <span class="star" data-rating="1">☆</span>
                        <span class="star" data-rating="2">☆</span>
                        <span class="star" data-rating="3">☆</span>
                        <span class="star" data-rating="4">☆</span>
                        <span class="star" data-rating="5">☆</span>
                    </div>
                    <p>اضغط على النجوم لتقييم الكتاب</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'إرسال التقييم',
            cancelButtonText: 'إلغاء',
            didOpen: () => {
                let selectedRating = 0;

                $('.star').on('click', function() {
                    selectedRating = $(this).data('rating');

                    $('.star').each(function(index) {
                        if (index < selectedRating) {
                            $(this).text('★');
                        } else {
                            $(this).text('☆');
                        }
                    });
                });

                // Store rating for retrieval
                Swal.getConfirmButton().onclick = function() {
                    if (selectedRating === 0) {
                        Swal.showValidationMessage('يرجى اختيار تقييم');
                        return false;
                    }
                    submitRating(bookId, selectedRating);
                };
            }
        });
    };

    function submitRating(bookId, rating) {
        $.ajax({
            url: dbpPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dbp_rate_book',
                nonce: dbpPublic.nonce,
                book_id: bookId,
                rating: rating
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'شكراً!',
                        text: 'تم إرسال تقييمك بنجاح',
                        confirmButtonText: 'حسناً'
                    });
                }
            }
        });
    }

})(jQuery);
