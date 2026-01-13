<?php
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth($pdo);

if (!$auth->checkSession() || !isAdmin()) {
    redirect('../login.php');
}

$user = $auth->getCurrentUser();
$success = '';
$error = '';

// معالجة قبول/رفض الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];
    $notes = cleanInput($_POST['admin_notes'] ?? '');

    if ($action === 'approve' || $action === 'reject') {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        try {
            $pdo->beginTransaction();

            // تحديث حالة الطلب
            $stmt = $pdo->prepare("UPDATE purchase_requests
                                  SET status = ?, admin_response_date = NOW(), admin_id = ?, admin_notes = ?
                                  WHERE id = ?");
            $stmt->execute([$newStatus, $user['id'], $notes, $requestId]);

            // إذا تم القبول، إضافة السجل في جدول المشتريات
            if ($action === 'approve') {
                // الحصول على بيانات الطلب
                $reqStmt = $pdo->prepare("SELECT user_id, book_id, amount, payment_method FROM purchase_requests WHERE id = ?");
                $reqStmt->execute([$requestId]);
                $request = $reqStmt->fetch();

                if ($request) {
                    // إضافة للمشتريات
                    $purchaseStmt = $pdo->prepare("INSERT INTO purchases (user_id, book_id, amount_paid, payment_method, purchase_request_id)
                                                   VALUES (?, ?, ?, ?, ?)
                                                   ON DUPLICATE KEY UPDATE purchase_date = NOW()");
                    $purchaseStmt->execute([
                        $request['user_id'],
                        $request['book_id'],
                        $request['amount'],
                        $request['payment_method'],
                        $requestId
                    ]);
                }
            }

            $pdo->commit();
            $success = $action === 'approve' ? 'تم قبول الطلب بنجاح!' : 'تم رفض الطلب!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'حدث خطأ: ' . $e->getMessage();
        }
    }
}

// الحصول على جميع الطلبات
$stmt = $pdo->prepare("SELECT pr.*, u.username, u.email, b.title as book_title, b.price,
                       a.username as admin_name
                       FROM purchase_requests pr
                       JOIN users u ON pr.user_id = u.id
                       JOIN books b ON pr.book_id = b.id
                       LEFT JOIN users a ON pr.admin_id = a.id
                       ORDER BY
                       CASE
                           WHEN pr.status = 'pending' THEN 1
                           WHEN pr.status = 'approved' THEN 2
                           WHEN pr.status = 'rejected' THEN 3
                       END,
                       pr.request_date DESC");
$stmt->execute();
$requests = $stmt->fetchAll();

// إحصائيات
$pendingCount = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$approvedCount = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
$rejectedCount = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة طلبات الشراء - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar h2 { margin-bottom: 30px; font-size: 24px; text-align: center; }
        .sidebar nav a { display: block; padding: 12px 15px; color: white; text-decoration: none; margin-bottom: 10px; border-radius: 8px; transition: background 0.3s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.2); }
        .main-content { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; }
        .stat-pending .number { color: #ffc107; }
        .stat-approved .number { color: #2ecc71; }
        .stat-rejected .number { color: #e74c3c; }
        .section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f8f9fa; padding: 12px; text-align: right; color: #666; font-weight: 600; }
        table td { padding: 12px; border-bottom: 1px solid #eee; }
        table tr:hover { background: #f8f9fa; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.3s; text-decoration: none; display: inline-block; margin-left: 5px; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-info { background: #3498db; color: white; }
        .btn-info:hover { background: #2980b9; }
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 12px; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .modal h2 { margin-bottom: 20px; color: #333; }
        .modal textarea { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; margin: 15px 0; min-height: 100px; font-family: inherit; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .close-modal { float: left; font-size: 28px; font-weight: bold; cursor: pointer; color: #999; }
        .close-modal:hover { color: #333; }
        .payment-method { display: inline-flex; align-items: center; gap: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>📚 منصة الكتب</h2>
            <nav>
                <a href="dashboard.php">الرئيسية</a>
                <a href="books.php">إدارة الكتب</a>
                <a href="upload-book.php">رفع كتاب جديد</a>
                <a href="audio-manager.php">إدارة الصوتيات</a>
                <a href="purchase-requests.php" class="active">طلبات الشراء</a>
                <a href="users.php">إدارة المستخدمين</a>
                <a href="settings.php">الإعدادات</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>إدارة طلبات الشراء</h1>
                <p style="color: #666; margin-top: 5px;">مراجعة وقبول/رفض طلبات شراء الكتب</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card stat-pending">
                    <h3>طلبات معلقة</h3>
                    <div class="number"><?php echo $pendingCount; ?></div>
                </div>
                <div class="stat-card stat-approved">
                    <h3>طلبات مقبولة</h3>
                    <div class="number"><?php echo $approvedCount; ?></div>
                </div>
                <div class="stat-card stat-rejected">
                    <h3>طلبات مرفوضة</h3>
                    <div class="number"><?php echo $rejectedCount; ?></div>
                </div>
            </div>

            <div class="section">
                <h2 style="margin-bottom: 20px; color: #333;">جميع الطلبات (<?php echo count($requests); ?>)</h2>

                <?php if (count($requests) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المستخدم</th>
                                <th>الكتاب</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>رقم الهاتف</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['username']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($request['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['book_title']); ?></td>
                                    <td><strong><?php echo number_format($request['amount'], 2); ?> ج.م</strong></td>
                                    <td>
                                        <span class="payment-method">
                                            <?php if ($request['payment_method'] === 'instapay'): ?>
                                                💳 إنستاباي
                                            <?php else: ?>
                                                📱 فودافون كاش
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['phone_number'] ?: '-'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                            <?php
                                            $statusNames = [
                                                'pending' => 'في الانتظار',
                                                'approved' => 'مقبول',
                                                'rejected' => 'مرفوض'
                                            ];
                                            echo $statusNames[$request['status']];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button class="btn btn-success" onclick="showModal(<?php echo $request['id']; ?>, 'approve')">قبول</button>
                                            <button class="btn btn-danger" onclick="showModal(<?php echo $request['id']; ?>, 'reject')">رفض</button>
                                        <?php else: ?>
                                            <button class="btn btn-info" onclick="showDetails(<?php echo htmlspecialchars(json_encode($request)); ?>)">عرض التفاصيل</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">لا توجد طلبات شراء بعد</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal للقبول/الرفض -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">تأكيد الإجراء</h2>
            <form method="POST">
                <input type="hidden" name="request_id" id="requestId">
                <input type="hidden" name="action" id="action">
                <p id="modalMessage"></p>
                <label>ملاحظات (اختياري):</label>
                <textarea name="admin_notes" placeholder="أضف ملاحظات إذا لزم الأمر..."></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">إلغاء</button>
                    <button type="submit" class="btn" id="confirmBtn">تأكيد</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal للتفاصيل -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeDetailsModal()">&times;</span>
            <h2>تفاصيل الطلب</h2>
            <div id="detailsContent"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()">إغلاق</button>
            </div>
        </div>
    </div>

    <script>
        function showModal(requestId, action) {
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('confirmBtn');

            document.getElementById('requestId').value = requestId;
            document.getElementById('action').value = action;

            if (action === 'approve') {
                title.textContent = 'قبول طلب الشراء';
                message.textContent = 'هل أنت متأكد من قبول هذا الطلب؟ سيتمكن المستخدم من قراءة الكتاب فوراً.';
                confirmBtn.className = 'btn btn-success';
                confirmBtn.textContent = 'قبول الطلب';
            } else {
                title.textContent = 'رفض طلب الشراء';
                message.textContent = 'هل أنت متأكد من رفض هذا الطلب؟';
                confirmBtn.className = 'btn btn-danger';
                confirmBtn.textContent = 'رفض الطلب';
            }

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
        }

        function showDetails(request) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');

            const statusNames = {
                'pending': 'في الانتظار',
                'approved': 'مقبول',
                'rejected': 'مرفوض'
            };

            const paymentMethods = {
                'instapay': '💳 إنستاباي',
                'vodafone_cash': '📱 فودافون كاش'
            };

            let html = `
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p><strong>المستخدم:</strong> ${request.username} (${request.email})</p>
                    <p><strong>الكتاب:</strong> ${request.book_title}</p>
                    <p><strong>المبلغ:</strong> ${parseFloat(request.amount).toFixed(2)} ج.م</p>
                    <p><strong>طريقة الدفع:</strong> ${paymentMethods[request.payment_method]}</p>
                    <p><strong>رقم الهاتف:</strong> ${request.phone_number || 'غير محدد'}</p>
                    <p><strong>الحالة:</strong> <span class="status-badge status-${request.status}">${statusNames[request.status]}</span></p>
                    <p><strong>تاريخ الطلب:</strong> ${request.request_date}</p>
                    ${request.admin_response_date ? `<p><strong>تاريخ الرد:</strong> ${request.admin_response_date}</p>` : ''}
                    ${request.admin_name ? `<p><strong>تم الرد بواسطة:</strong> ${request.admin_name}</p>` : ''}
                    ${request.admin_notes ? `<p><strong>ملاحظات الأدمن:</strong><br>${request.admin_notes}</p>` : ''}
                </div>
            `;

            content.innerHTML = html;
            modal.style.display = 'block';
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // إغلاق Modal عند الضغط خارجها
        window.onclick = function(event) {
            const actionModal = document.getElementById('actionModal');
            const detailsModal = document.getElementById('detailsModal');
            if (event.target === actionModal) {
                closeModal();
            }
            if (event.target === detailsModal) {
                closeDetailsModal();
            }
        }
    </script>
</body>
</html>
