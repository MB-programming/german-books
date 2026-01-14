/**
 * Digital Books Platform - Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        initDeleteBook();
        initApproveRequest();
        initRejectRequest();
        initFileUploadPreview();
        initFormValidation();
        initAudioManagement();
    });

    /**
     * Delete Book
     */
    function initDeleteBook() {
        $(document).on('click', '.delete-book', function(e) {
            e.preventDefault();

            const bookId = $(this).data('id');
            const $row = $(this).closest('tr');

            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم حذف الكتاب وجميع البيانات المرتبطة به!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذف!',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteBook(bookId, $row);
                }
            });
        });
    }

    function deleteBook(bookId, $row) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dbp_delete_book',
                nonce: dbpAdmin.nonce,
                book_id: bookId
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'جاري الحذف...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(400, function() {
                        $(this).remove();
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحذف!',
                        text: 'تم حذف الكتاب بنجاح',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ أثناء الحذف',
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
     * Approve Purchase Request
     */
    function initApproveRequest() {
        $(document).on('click', '.approve-request', function(e) {
            e.preventDefault();

            const requestId = $(this).data('id');
            const $row = $(this).closest('tr');

            Swal.fire({
                title: 'الموافقة على الطلب',
                text: 'هل تريد الموافقة على طلب الشراء؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#38ef7d',
                cancelButtonColor: '#d33',
                confirmButtonText: 'موافقة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    approveRequest(requestId, $row);
                }
            });
        });
    }

    function approveRequest(requestId, $row) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dbp_approve_purchase',
                nonce: dbpAdmin.nonce,
                request_id: requestId
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'جاري المعالجة...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    // Update status cell
                    $row.find('td:nth-child(5)').html('<span style="color: green;">مقبول</span>');
                    $row.find('.approve-request, .reject-request').remove();

                    Swal.fire({
                        icon: 'success',
                        title: 'تمت الموافقة!',
                        text: 'تمت الموافقة على طلب الشراء بنجاح',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ أثناء المعالجة',
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
     * Reject Purchase Request
     */
    function initRejectRequest() {
        $(document).on('click', '.reject-request', function(e) {
            e.preventDefault();

            const requestId = $(this).data('id');
            const $row = $(this).closest('tr');

            Swal.fire({
                title: 'رفض الطلب',
                text: 'هل تريد رفض طلب الشراء؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'رفض',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    rejectRequest(requestId, $row);
                }
            });
        });
    }

    function rejectRequest(requestId, $row) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dbp_reject_purchase',
                nonce: dbpAdmin.nonce,
                request_id: requestId
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'جاري المعالجة...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    // Update status cell
                    $row.find('td:nth-child(5)').html('<span style="color: red;">مرفوض</span>');
                    $row.find('.approve-request, .reject-request').remove();

                    Swal.fire({
                        icon: 'success',
                        title: 'تم الرفض!',
                        text: 'تم رفض طلب الشراء',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ أثناء المعالجة',
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
     * File Upload Preview
     */
    function initFileUploadPreview() {
        // PDF File Preview
        $('#pdf_file').on('change', function() {
            const fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $(this).parent().find('.file-name').remove();
                $(this).after('<span class="file-name" style="margin-right: 10px; color: #667eea; font-weight: bold;">📄 ' + fileName + '</span>');
            }
        });

        // Cover Image Preview
        $('#cover_image').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#cover-preview').remove();
                    $(this).parent().append('<img id="cover-preview" src="' + e.target.result + '" style="max-width: 200px; margin-top: 10px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">');
                }.bind(this);
                reader.readAsDataURL(file);
            }
        });

        // Audio File Preview
        $(document).on('change', '.audio-file-input', function() {
            const fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $(this).parent().find('.file-name').remove();
                $(this).after('<span class="file-name" style="margin-right: 10px; color: #667eea; font-weight: bold;">🎵 ' + fileName + '</span>');
            }
        });
    }

    /**
     * Form Validation
     */
    function initFormValidation() {
        $('form[name="dbp_add_book_form"]').on('submit', function(e) {
            const title = $('#title').val().trim();
            const pdfFile = $('#pdf_file')[0].files[0];

            if (!title) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: 'يرجى إدخال عنوان الكتاب',
                    confirmButtonText: 'حسناً'
                });
                return false;
            }

            if (!pdfFile) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: 'يرجى اختيار ملف PDF',
                    confirmButtonText: 'حسناً'
                });
                return false;
            }

            // Check file size (50MB max)
            if (pdfFile.size > 50 * 1024 * 1024) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: 'حجم الملف كبير جداً (الحد الأقصى 50 ميجابايت)',
                    confirmButtonText: 'حسناً'
                });
                return false;
            }

            // Show loading
            Swal.fire({
                title: 'جاري الرفع...',
                text: 'يرجى الانتظار حتى يتم رفع الكتاب',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
    }

    /**
     * Audio Management
     */
    function initAudioManagement() {
        // Delete Audio File
        $(document).on('click', '.delete-audio', function(e) {
            e.preventDefault();

            const audioId = $(this).data('id');
            const $item = $(this).closest('.dbp-audio-item');

            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم حذف ملف الصوت نهائياً!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذف!',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteAudio(audioId, $item);
                }
            });
        });

        // Play Audio Preview
        $(document).on('click', '.play-audio', function(e) {
            e.preventDefault();
            const audioUrl = $(this).data('url');

            Swal.fire({
                title: 'تشغيل الملف الصوتي',
                html: '<audio controls autoplay style="width: 100%;"><source src="' + audioUrl + '" type="audio/mpeg"></audio>',
                showConfirmButton: false,
                showCloseButton: true
            });
        });
    }

    function deleteAudio(audioId, $item) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dbp_delete_audio',
                nonce: dbpAdmin.nonce,
                audio_id: audioId
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'جاري الحذف...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    $item.fadeOut(400, function() {
                        $(this).remove();
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحذف!',
                        text: 'تم حذف الملف الصوتي بنجاح',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ أثناء الحذف',
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
     * Copy to Clipboard
     */
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'تم النسخ!',
                text: 'تم نسخ النص إلى الحافظة',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        }, function() {
            Swal.fire({
                icon: 'error',
                title: 'خطأ!',
                text: 'فشل النسخ إلى الحافظة',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        });
    };

})(jQuery);
