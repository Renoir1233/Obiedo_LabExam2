<?php
// ============================================
// SECURE DASHBOARD
// Fixes: Access control, session timeout,
// XSS output escaping, prepared statements
// ============================================

// Secure session cookie settings (MUST be before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

session_start();

include("db.php");
include("csrf.php");

// --- FIX: Access Control - Redirect if not logged in ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); // FIX: exit() after redirect
}

// --- FIX: Session Timeout (30 minutes) ---
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// --- Handle Delete (POST method with CSRF, admin only) ---
$msg = "";

// Check for success message from add_student.php
if (isset($_SESSION['success_message'])) {
    $msg = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_POST['delete_student'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = "Invalid request.";
    } elseif ($_SESSION['role'] !== 'admin') {
        $msg = "Access denied. Admin only.";
    } else {
        $delete_id = intval($_POST['student_id'] ?? 0);
        if ($delete_id > 0) {
            $del_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $del_stmt->bind_param("i", $delete_id);
            $del_stmt->execute();
            $del_stmt->close();
            $msg = "Student deleted successfully.";
        }
    }
    unset($_SESSION['csrf_token']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=2.0">
</head>
<body>

<div class="container-wide">

    <div class="top-bar">
        <div>
            <h2><i class="fas fa-chart-line"></i> Dashboard - Welcome, <?php echo htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?>
                <span class="role-badge"><?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></span>
            </h2>
        </div>
        <div class="nav-links">
            <a href="add_student.php"><i class="fas fa-user-plus"></i> Add Student</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="backup.php"><i class="fas fa-database"></i> Backup</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <p class="success"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div class="card">
    <h3><i class="fas fa-graduation-cap"></i> Student List</h3>

    <div class="table-wrapper">
    <table>
    <tr>
        <th>ID</th>
        <th>Student ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Course</th>
        <th>Course Description</th>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <th>Action</th>
        <?php endif; ?>
    </tr>

    <?php
    // --- FIX: Use JOIN for normalized data (courses table) ---
    $query = "SELECT s.id, s.student_id, s.fullname, s.email, 
                     c.course_code, c.course_description
              FROM students s
              LEFT JOIN courses c ON s.course_id = c.id
              ORDER BY s.id ASC";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
    ?>
    <tr>
        <!-- FIX: All output escaped with htmlspecialchars -->
        <td><?php echo intval($row['id']); ?></td>
        <td><?php echo htmlspecialchars($row['student_id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($row['course_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($row['course_description'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <td>
                <!-- FIX: Delete via POST with CSRF (prevents direct object reference) -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="student_id" value="<?php echo intval($row['id']); ?>">
                    <button type="submit" name="delete_student" class="btn-delete">Delete</button>
                </form>
            </td>
        <?php endif; ?>
    </tr>
    <?php
        endwhile;
    else:
    ?>
    <tr><td colspan="7" class="no-data">No students found.</td></tr>
    <?php endif; ?>

    </table>
    </div>
    </div>
</div>

</body>
</html>
