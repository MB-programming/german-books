<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession()) {
    redirect('../login.php');
}

$user = $auth->getCurrentUser();
$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// الحصول على بيانات الكتاب
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND is_paid = 1");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    redirect('dashboard.php');
}

// التحقق من أن المستخدم لم يشتري الكتاب بالفعل
$purchaseCheck = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND book_id = ?");
$purchaseCheck->execute([$user['id'], $bookId]);
if ($purchaseCheck->fetch()) {
    redirect('view-book.php?id=' . $bookId);
}

// التحقق من وجود طلب شراء معلق
$pendingCheck = $pdo->prepare("SELECT id FROM purchase_requests WHERE user_id = ? AND book_id = ? AND status = 'pending'");
$pendingCheck->execute([$user['id'], $bookId]);
$pendingRequest = $pendingCheck->fetch();

$error = '';
$success = '';
$selectedMethod = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $paymentMethod = $_POST['payment_method'];
    $phoneNumber = cleanInput($_POST['phone_number'] ?? '');
    $transactionId = cleanInput($_POST['transaction_id'] ?? '');

    if ($paymentMethod !== 'instapay' && $paymentMethod !== 'vodafone_cash') {
        $error = 'طريقة دفع غير صالحة';
    } else {
        // إنشاء طلب شراء جديد
        try {
            $stmt = $pdo->prepare("INSERT INTO purchase_requests (user_id, book_id, payment_method, amount, phone_number, transaction_id)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user['id'], $bookId, $paymentMethod, $book['price'], $phoneNumber, $transactionId]);

            $requestId = $pdo->lastInsertId();
            $success = 'تم إرسال طلبك بنجاح! في انتظار موافقة الأدمن.';

            // تحديث حالة الطلب المعلق
            $pendingCheck->execute([$user['id'], $bookId]);
            $pendingRequest = $pendingCheck->fetch();
        } catch (PDOException $e) {
            $error = 'حدث خطأ في إرسال الطلب: ' . $e->getMessage();
        }
    }
}

// بيانات الدفع
$paymentInfo = [
    'instapay' => [
        'name' => 'إنستاباي',
        'number' => '01222112819',
        'icon' => '💳'
    ],
    'vodafone_cash' => [
        'name' => 'فودافون كاش',
        'number' => '01014959132',
        'icon' => '📱'
    ]
];

$whatsappNumber = '01222112819';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شراء الكتاب - <?php echo htmlspecialchars($book['title']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; margin-bottom: 20px; }
        h1 { color: #333; margin-bottom: 10px; }
        .book-info { background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .book-info h2 { color: #667eea; margin-bottom: 10px; }
        .price { font-size: 32px; color: #2ecc71; font-weight: bold; margin: 10px 0; }
        .payment-methods { display: grid; gap: 20px; margin: 30px 0; }
        .payment-option { border: 3px solid #ddd; border-radius: 12px; padding: 25px; cursor: pointer; transition: all 0.3s; position: relative; }
        .payment-option:hover { border-color: #667eea; transform: translateY(-5px); box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2); }
        .payment-option.selected { border-color: #667eea; background: #f0f1ff; }
        .payment-option input[type="radio"] { position: absolute; opacity: 0; }
        .payment-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .payment-icon { font-size: 32px; }
        .payment-details h3 { color: #333; font-size: 20px; }
        .payment-number { color: #667eea; font-size: 24px; font-weight: bold; margin-top: 5px; direction: ltr; text-align: left; }
        .whatsapp-section { background: #dcf8c6; padding: 20px; border-radius: 12px; margin: 20px 0; border: 2px solid #25D366; }
        .whatsapp-btn { background: #25D366; color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s; width: 100%; justify-content: center; margin-top: 10px; }
        .whatsapp-btn:hover { background: #1ea952; transform: scale(1.05); }
        .instructions { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .instructions h3 { color: #856404; margin-bottom: 15px; }
        .instructions ol { margin-right: 20px; color: #856404; }
        .instructions li { margin: 10px 0; line-height: 1.6; }
        .alert { padding: 15px; border-radius: 8px; margin: 20px 0; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #dcf8c6; color: #2d5016; border: 1px solid #25D366; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .btn { padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        input[type="text"], input[type="tel"] { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px; }
        input:focus { outline: none; border-color: #667eea; }
        .pending-notice { background: #d1ecf1; border: 2px solid #17a2b8; padding: 20px; border-radius: 12px; text-align: center; }
        .pending-notice h3 { color: #0c5460; margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin-top: 10px; }
        .status-pending { background: #ffc107; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">← العودة</a>

            <h1>شراء الكتاب</h1>

            <div class="book-info">
                <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                <p style="color: #666;"><?php echo htmlspecialchars($book['description'] ?: 'لا يوجد وصف'); ?></p>
                <div class="price"><?php echo number_format($book['price'], 2); ?> جنيه</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($pendingRequest): ?>
                <div class="pending-notice">
                    <h3>⏳ لديك طلب شراء معلق</h3>
                    <p>تم إرسال طلبك للأدمن وفي انتظار الموافقة.</p>
                    <span class="status-badge status-pending">في الانتظار</span>
                    <p style="margin-top: 15px; color: #666;">سيتم إخطارك عند قبول أو رفض الطلب.</p>
                </div>
            <?php else: ?>
                <div class="instructions">
                    <h3>📋 خطوات الشراء:</h3>
                    <ol>
                        <li>اختر طريقة الدفع المناسبة (إنستاباي أو فودافون كاش)</li>
                        <li>احفظ الرقم المعروض</li>
                        <li>اضغط على زر "إرسال طلب عبر واتساب"</li>
                        <li>سيتم فتح محادثة واتساب مع رسالة جاهزة تحتوي على تفاصيل طلبك</li>
                        <li>أرسل الرسالة ثم قم بتحويل المبلغ</li>
                        <li>أرسل لقطة شاشة لعملية التحويل عبر الواتساب</li>
                        <li>انتظر موافقة الأدمن لتتمكن من قراءة الكتاب</li>
                    </ol>
                </div>

                <form method="POST" id="paymentForm">
                    <h3 style="margin: 20px 0;">اختر طريقة الدفع:</h3>

                    <div class="payment-methods">
                        <label class="payment-option" id="instapay-option">
                            <input type="radio" name="payment_method" value="instapay" required>
                            <div class="payment-header">
                                <div class="payment-icon"><?php echo $paymentInfo['instapay']['icon']; ?></div>
                                <div class="payment-details">
                                    <h3><?php echo $paymentInfo['instapay']['name']; ?></h3>
                                    <div class="payment-number"><?php echo $paymentInfo['instapay']['number']; ?></div>
                                </div>
                            </div>
                            <p style="color: #666; margin-top: 10px;">حول المبلغ عبر تطبيق إنستاباي</p>
                        </label>

                        <label class="payment-option" id="vodafone-option">
                            <input type="radio" name="payment_method" value="vodafone_cash" required>
                            <div class="payment-header">
                                <div class="payment-icon"><?php echo $paymentInfo['vodafone_cash']['icon']; ?></div>
                                <div class="payment-details">
                                    <h3><?php echo $paymentInfo['vodafone_cash']['name']; ?></h3>
                                    <div class="payment-number"><?php echo $paymentInfo['vodafone_cash']['number']; ?></div>
                                </div>
                            </div>
                            <p style="color: #666; margin-top: 10px;">حول المبلغ عبر خدمة فودافون كاش</p>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>رقم هاتفك (اختياري)</label>
                        <input type="tel" name="phone_number" placeholder="01XXXXXXXXX">
                    </div>

                    <div class="whatsapp-section">
                        <h3 style="color: #2d5016; margin-bottom: 10px;">📱 التواصل عبر واتساب</h3>
                        <p style="color: #2d5016; margin-bottom: 15px;">بعد اختيار طريقة الدفع، اضغط على الزر لإرسال طلبك عبر واتساب</p>
                        <button type="button" class="whatsapp-btn" id="whatsappBtn" disabled>
                            <span style="font-size: 24px;">💬</span>
                            <span>إرسال طلب الشراء عبر واتساب</span>
                        </button>
                        <p style="color: #2d5016; margin-top: 10px; font-size: 14px;">سيتم فتح محادثة واتساب مع رسالة جاهزة</p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const paymentOptions = document.querySelectorAll('.payment-option');
        const whatsappBtn = document.getElementById('whatsappBtn');
        const form = document.getElementById('paymentForm');
        let selectedMethod = '';

        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                paymentOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                selectedMethod = radio.value;
                whatsappBtn.disabled = false;
            });
        });

        whatsappBtn.addEventListener('click', function() {
            if (!selectedMethod) {
                alert('الرجاء اختيار طريقة الدفع');
                return;
            }

            const bookTitle = <?php echo json_encode($book['title']); ?>;
            const bookPrice = <?php echo $book['price']; ?>;
            const userName = <?php echo json_encode($user['username']); ?>;
            const userEmail = <?php echo json_encode($user['email']); ?>;
            const phoneNumber = document.querySelector('input[name="phone_number"]').value || 'غير محدد';

            const paymentMethodName = selectedMethod === 'instapay' ? 'إنستاباي' : 'فودافون كاش';
            const paymentNumber = selectedMethod === 'instapay' ? '<?php echo $paymentInfo['instapay']['number']; ?>' : '<?php echo $paymentInfo['vodafone_cash']['number']; ?>';

            const message = `السلام عليكم،

أريد شراء الكتاب التالي:

📚 *عنوان الكتاب:* ${bookTitle}
💰 *السعر:* ${bookPrice} جنيه
👤 *اسم المستخدم:* ${userName}
📧 *البريد الإلكتروني:* ${userEmail}
📱 *رقم الهاتف:* ${phoneNumber}

💳 *طريقة الدفع المختارة:* ${paymentMethodName}
🔢 *الرقم:* ${paymentNumber}

سأقوم بتحويل المبلغ الآن وإرسال لقطة شاشة لعملية التحويل.

شكراً لكم.`;

            const whatsappUrl = `https://wa.me/<?php echo $whatsappNumber; ?>?text=${encodeURIComponent(message)}`;

            // فتح واتساب في نافذة جديدة
            window.open(whatsappUrl, '_blank');

            // إرسال الطلب للقاعدة البيانات
            setTimeout(() => {
                form.submit();
            }, 1000);
        });
    </script>
</body>
</html>
