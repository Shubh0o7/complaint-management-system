<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/role_check.php';
require_once __DIR__ . '/includes/workflow_helper.php';
require_once __DIR__ . '/includes/notification_helper.php';
require_role(['admin']);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (isset($_POST['assign_department'])) {
        $complaintId = (int)($_POST['complaint_id'] ?? 0);
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $actorId = (int)$_SESSION['user_id'];

        $complaintStmt = $conn->prepare('SELECT id, user_id, subject, reference_no FROM complaints WHERE id = ? LIMIT 1');
        $complaintStmt->bind_param('i', $complaintId);
        $complaintStmt->execute();
        $complaint = $complaintStmt->get_result()->fetch_assoc();
        $complaintStmt->close();

        $departmentStmt = $conn->prepare('SELECT id, name FROM departments WHERE id = ? AND is_active = 1 LIMIT 1');
        $departmentStmt->bind_param('i', $departmentId);
        $departmentStmt->execute();
        $department = $departmentStmt->get_result()->fetch_assoc();
        $departmentStmt->close();

        // Prefer the seeded institutional officer for every admin-routed case.
        // The fallback keeps the workflow usable if the demo account is replaced.
        $officerStmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE role = 'officer' AND is_active = 1 ORDER BY CASE WHEN email = 'officer@campus.edu' THEN 0 ELSE 1 END, id ASC LIMIT 1");
        $officerStmt->execute();
        $officer = $officerStmt->get_result()->fetch_assoc();
        $officerStmt->close();

        if (!$complaint || !$department) {
            $error = 'Choose a valid case and active department.';
        } elseif (!$officer) {
            $error = 'No active case officer is configured for this department yet.';
        } else {
            $update = $conn->prepare('UPDATE complaints SET department_id = ?, officer_id = ? WHERE id = ?');
            $officerId = (int)$officer['id'];
            $update->bind_param('iii', $departmentId, $officerId, $complaintId);
            $updated = $update->execute();
            $update->close();

            if ($updated) {
                $reference = $complaint['reference_no'] ?: ('#' . $complaintId);
                add_timeline_entry($conn, $complaintId, $actorId, 'Department Assigned', null, $department['name'], 'Administrator routed this case to ' . $department['name'] . '.');
                add_timeline_entry($conn, $complaintId, $actorId, 'Officer Assigned', null, $officer['full_name'], 'The case was automatically assigned to the default department officer.');
                create_notification($conn, (int)$complaint['user_id'], $complaintId, 'Case routed', 'Your case ' . $reference . ' was routed to ' . $department['name'] . ' for review.', 'assignment');
                create_notification($conn, $officerId, $complaintId, 'New case assigned', 'Case ' . $reference . ' has been assigned to you by an administrator.', 'assignment');
                audit_log($conn, 'assign_case', 'complaint', $complaintId, 'Administrator routed case to ' . $department['name'] . ' and default officer ' . $officer['full_name']);
                $message = 'Case routed to ' . $department['name'] . ' and assigned to ' . $officer['full_name'] . '.';
            } else {
                $error = 'The case could not be routed. Please try again.';
            }
        }
    }

    if (isset($_POST['create_account'])) {
        $name = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? '';
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && in_array($role, ['department', 'officer'], true) && strlen($password) >= 8 && $departmentId > 0) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $accountStmt = $conn->prepare('INSERT INTO users (full_name, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)');
            $accountStmt->bind_param('ssssi', $name, $email, $hash, $role, $departmentId);
            if ($accountStmt->execute()) {
                audit_log($conn, 'create_account', 'user', null, 'Administrator created ' . $role . ' account for ' . $email);
                $message = 'Account created successfully.';
            } else {
                $error = 'Email may already be in use.';
            }
            $accountStmt->close();
        } else {
            $error = 'Provide valid details and a password of at least 8 characters.';
        }
    }
}

$departments = [];
$departmentResult = $conn->query('SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name');
if ($departmentResult) $departments = $departmentResult->fetch_all(MYSQLI_ASSOC);

$complaints = [];
$complaintResult = $conn->query("SELECT c.id, c.reference_no, c.subject, c.status, c.priority, c.department_id, c.created_at,
    u.full_name AS user_name, d.name AS department_name, o.full_name AS officer_name
    FROM complaints c
    JOIN users u ON u.id = c.user_id
    LEFT JOIN departments d ON d.id = c.department_id
    LEFT JOIN users o ON o.id = c.officer_id
    ORDER BY (c.department_id IS NULL) DESC, c.created_at DESC
    LIMIT 100");
if ($complaintResult) $complaints = $complaintResult->fetch_all(MYSQLI_ASSOC);

$pendingCount = 0;
foreach ($complaints as $case) if (empty($case['department_id'])) $pendingCount++;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workflow & Accounts | CampusResolve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=sidebar-collapse-20260922-1">
</head>
<body class="role-layout role-admin">
<div class="d-flex min-vh-100">
    <?php include 'includes/sidebar.php'; ?>
    <div class="flex-grow-1">
        <?php include 'includes/topbar.php'; ?>
        <main class="p-4">
            <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>

            <div class="row g-4 align-items-start">
                <div class="col-12 col-xxl-8">
                    <section class="card h-100">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div><h2 class="h5 mb-1">Case routing queue</h2><p class="small text-muted mb-0">Every routed case is automatically sent to the default department officer.</p></div>
                            <span class="badge rounded-pill text-bg-<?= $pendingCount ? 'warning' : 'success' ?>"><?= $pendingCount ?> awaiting routing</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead><tr><th>Case</th><th>Priority</th><th>Department / officer</th><th>Route</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($complaints as $case): ?>
                                        <tr>
                                            <td><a class="fw-semibold text-decoration-none" href="view_complaint.php?id=<?= (int)$case['id'] ?>"><?= htmlspecialchars($case['subject']) ?></a><div class="small text-muted"><?= htmlspecialchars($case['reference_no'] ?: ('#' . $case['id'])) ?> · <?= htmlspecialchars($case['user_name']) ?></div></td>
                                            <td><span class="badge text-bg-<?= $case['priority'] === 'High' ? 'danger' : ($case['priority'] === 'Medium' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($case['priority']) ?></span><div class="small text-muted mt-1"><?= htmlspecialchars($case['status']) ?></div></td>
                                            <td><div><?= htmlspecialchars($case['department_name'] ?: 'Unassigned') ?></div><div class="small text-muted"><i class="bi bi-person-check me-1"></i><?= htmlspecialchars($case['officer_name'] ?: 'Awaiting default officer') ?></div></td>
                                            <td><form class="d-flex gap-2" method="post"><?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= (int)$case['id'] ?>"><select class="form-select form-select-sm" name="department_id" aria-label="Department for case" required><option value="">Select</option><?php foreach ($departments as $department): ?><option value="<?= (int)$department['id'] ?>" <?= (int)$case['department_id'] === (int)$department['id'] ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary" name="assign_department" title="Route to department and default officer"><i class="bi bi-send me-1"></i>Route</button></form></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$complaints): ?><tr><td colspan="4" class="text-center text-muted py-5">No cases are waiting for routing.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if (count($complaints) === 100): ?><div class="card-footer small text-muted">Showing the latest 100 cases to keep the workflow console responsive.</div><?php endif; ?>
                    </section>
                </div>

                <div class="col-12 col-xxl-4">
                    <section class="card mb-4">
                        <div class="card-header"><h2 class="h5 mb-1">Default routing</h2><p class="small text-muted mb-0">The seeded officer receives all routed cases.</p></div>
                        <div class="card-body"><div class="d-flex align-items-center gap-3"><span class="brand-mark"><i class="bi bi-person-badge"></i></span><div><strong>Complaint Officer</strong><div class="small text-muted">officer@campus.edu</div><span class="badge text-bg-success mt-2">Active default</span></div></div></div>
                    </section>
                    <section class="card">
                        <div class="card-header"><h2 class="h5 mb-1">Create role account</h2><p class="small text-muted mb-0">Provision another department or officer account when needed.</p></div>
                        <div class="card-body"><form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="full_name">Full name</label><input class="form-control" id="full_name" name="full_name" required></div><div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" required></div><div class="mb-3"><label class="form-label" for="role">Role</label><select class="form-select" id="role" name="role" required><option value="department">Department manager</option><option value="officer">Case officer</option></select></div><div class="mb-3"><label class="form-label" for="account_department">Department</label><select class="form-select" id="account_department" name="department_id" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int)$department['id'] ?>"><?= htmlspecialchars($department['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label" for="password">Temporary password</label><input class="form-control" id="password" name="password" type="password" minlength="8" required></div><button class="btn btn-dark w-100" name="create_account"><i class="bi bi-person-plus me-1"></i>Create account</button></form></div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
