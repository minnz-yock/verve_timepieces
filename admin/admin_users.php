<?php
session_start();

/* ---- SIMPLE ADMIN GUARD ---- */
if (!isset($_SESSION['first_name']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../signinform.php");
    exit();
}
require_once "../dbconnect.php";


/* ---- FLASH HELPERS ---- */
function set_flash($type, $msg)
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function get_flash()
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---- FORM HANDLERS ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $adminCount = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();

    if ($action === 'update_role' && isset($_POST['id'], $_POST['role'])) {
        $id = (int)$_POST['id'];
        $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';

        $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
        $stmt->execute([$id]);
        $currentRole = $stmt->fetchColumn();

        if ($currentRole === false) {
            set_flash('danger', 'User not found.');
        } else {
            if ($currentRole === 'admin' && $adminCount === 1 && $role !== 'admin') {
                set_flash('warning', 'Cannot demote the last remaining admin.');
            } else {
                $upd = $conn->prepare("UPDATE users SET role=? WHERE id=?");
                $upd->execute([$role, $id]);
                set_flash('success', 'Role updated.');
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'delete_user' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];

        $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
        $stmt->execute([$id]);
        $role = $stmt->fetchColumn();

        if ($role === false) {
            set_flash('danger', 'User not found.');
        } else {
            if ($role === 'admin' && $adminCount === 1) {
                set_flash('warning', 'Cannot delete the last remaining admin.');
            } else {
                $del = $conn->prepare("DELETE FROM users WHERE id=?");
                $del->execute([$id]);
                set_flash('success', 'User deleted.');
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'add_user' && isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['password'], $_POST['role'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';
        $password = $_POST['password'];

        if ($first_name === '' || $last_name === '' || $email === '' || $password === '') {
            set_flash('danger', 'All fields are required.');
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$first_name, $last_name, $email, $hash, $role]);
                set_flash('success', 'New user added.');
            } catch (PDOException $e) {
                set_flash('danger', 'Could not add user: ' . $e->getMessage());
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

/* ---- FETCH USERS ---- */
$adminCount = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$stmt = $conn->query("SELECT id, first_name, last_name, email, role FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Management — Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background-color: #352826;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
            color: #DED2C8;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: 100%;
        }

        .main-content h1 {
            color: #785A49;
            font-weight: 800;
            margin-bottom: 30px;
            font-size: 2.2rem;
            letter-spacing: 0.5px;
        }

        .table {
            border-radius: 0 0 10px 10px;
            overflow: hidden;
            border: 1px solid #A57A5B;
        }

        .table thead th {
            background-color: #352826;
            color: #DED2C8;
            font-weight: 400;
            border-right: 2px solid #A57A5B;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.4px;
        }

        .table tbody tr {
            background-color: #DED2C8;
            color: #352826;
        }

        .table tbody td {
            vertical-align: middle;
            color: #352826;
            font-size: 0.95rem;
            border-top: 1px solid #785A5B;
            border-right: 1px solid #785A5B;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-hover tbody tr:hover {
            background-color: #A57A5B;
            color: #DED2C8;
        }

        .table-hover tbody tr:hover a,
        .table-hover tbody tr:hover i {
            color: #fff;
        }

        .table-hover tbody tr:hover td {
            border-right-color: #DED2C8;
            border-top-color: #DED2C8;
        }

        .action-buttons button,
        .action-buttons a {
            font-size: 1rem;
            padding: 0.6rem 1rem;
            border-radius: 5px;
            margin-right: 5px;
        }

        .action-buttons .btn-delete {
            background-color: #e74c3c;
            border-color: #e74c3c;
            color: white;
        }

        .action-buttons .btn-delete:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }

        /* Added style for the delete icon color */
        .btn-delete i {
            color: #e74c3c;
        }

        .table-hover tbody tr:hover .btn-delete i {
            color: #785A49;;
        }

        /* The .btn-primary class styles were conflicting with Bootstrap's defaults.
           I've made the styles !important to ensure they are applied. */
        .btn-primary {
            background-color: #785A49 !important;
            border-color: #785A49 !important;
            color: #DED2C8 !important;
        }

        .btn-primary:hover {
            background-color: #A57A5B !important;
            border-color: #A57A5B !important;
            color: #DED2C8 !important;
        }

        .form-control,
        .form-select {
            background-color: #fff;
            color: #352826;
            border: 1px solid #A57A5B;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #785A49;
            box-shadow: 0 0 0 0.25rem rgba(120, 90, 73, 0.25);
        }

        .modal-header,
        .modal-footer {
            background-color: #352826;
            color: #DED2C8;
            border-color: #785A49;
        }

        .modal-body {
            background-color: #DED2C8;
            color: #352826;
        }

        .table-warning {
            --bs-table-bg: #fff3cd;
            --bs-table-color: #352826;
        }

        /* Custom style for the entries count */
        .table-info-bar {
            background-color: #352826;
            border-radius: 6px 6px 0 0;
            border: 1px solid #A57A5B;
            border-bottom: none;
            padding: 0.6rem 1.5rem;
            color: #DED2C8;
            text-align: right;
            font-size: 0.85rem;
        }

        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="m-0"><i class="fa fa-users me-2"></i>User Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa fa-plus me-1"></i> Add New User
            </button>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <!-- Moved this section above the table -->
            <div class="table-info-bar">
                Showing <?= count($users) ?> <?= count($users) === 1 ? 'entry' : 'entries' ?><?= $adminCount === 1 ? " — Highlighted row is the only Admin (Protected)" : "" ?>.
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead>
                            <tr>
                                <th style="width:80px;">ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th style="width:220px;">Role</th>
                                <th style="width:140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u):
                                    $isProtected = ($u['role'] === 'admin' && $adminCount === 1);
                                ?>
                                    <tr class="<?= $isProtected ? 'table-warning' : '' ?>">
                                        <td><?= htmlspecialchars($u['id']) ?></td>
                                        <td><?= htmlspecialchars($u['first_name']) ?></td>
                                        <td><?= htmlspecialchars($u['last_name']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <?php if ($isProtected): ?>
                                                <span class="badge bg-warning text-dark">Protected</span>
                                            <?php else: ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                    <select name="role" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                        <option value="customer" <?= $u['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                                    </select>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isProtected): ?>
                                                <span class="text-muted">Protected</span>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-delete" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-user-id="<?= (int)$u['id'] ?>" data-username="<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLbl" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserLbl"><i class="fa fa-user-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <p class="text-muted small m-0">Passwords are hashed with <code>password_hash()</code>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the user "<strong id="userToDeleteName"></strong>"? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteUserForm" method="post" class="d-inline">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="id" id="userToDeleteId">
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (required for modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- Modal handling for delete confirmation ---
        document.getElementById('deleteUserModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-username');
            const modal = this;
            modal.querySelector('#userToDeleteName').textContent = userName;
            modal.querySelector('#userToDeleteId').value = userId;
        });

        // --- Script to highlight active menu item (from see_all_products.php) ---
        document.addEventListener('DOMContentLoaded', (event) => {
            const currentFile = window.location.pathname.split('/').pop();
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                if (link.getAttribute('href').includes(currentFile)) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>

</html>