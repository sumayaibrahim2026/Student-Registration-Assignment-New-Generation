<?php 
include 'db.php';
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
$is_admin = ($_SESSION['user'] == 'admin');

// Handle Delete
if (isset($_GET['del']) && $is_admin) {
    $conn->query("DELETE FROM students WHERE id=" . intval($_GET['del']));
    $_SESSION['msg'] = "Student deleted successfully!";
    header("Location: index.php"); exit;
}

// Handle Add / Update (NO DUPLICATE CHECKS - Creates everything freely)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? ''); 
    $email = trim($_POST['email'] ?? ''); 
    $grade = trim($_POST['grade'] ?? ''); 
    $id = intval($_POST['id'] ?? 0);
    
    if ($id > 0) {
        $conn->query("UPDATE students SET name='$name', email='$email', grade='$grade' WHERE id=$id");
        $_SESSION['msg'] = "Student updated successfully!";
    } else {
        $conn->query("INSERT INTO students (name, email, grade) VALUES ('$name', '$email', '$grade')");
        $_SESSION['msg'] = "Student added successfully!";
    }
    header("Location: index.php"); exit;
}

// Get data for Edit form safely
$edit_data = ['id'=>0, 'name'=>'', 'email'=>'', 'grade'=>''];
if (isset($_GET['edit']) && $is_admin) {
    $res = $conn->query("SELECT * FROM students WHERE id=" . intval($_GET['edit']));
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $edit_data['id'] = $row['id'] ?? 0;
        $edit_data['name'] = $row['name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $edit_data['email'] = $row['email'] ?? '';
        $edit_data['grade'] = $row['grade'] ?? '';
    }
}

// QUICK VIEW: Search Logic + Newest students at the TOP
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM students";
if ($search !== '') {
    $safe_search = $conn->real_escape_string($search);
    $sql .= " WHERE name LIKE '%$safe_search%' OR email LIKE '%$safe_search%' OR grade LIKE '%$safe_search%'";
}
$sql .= " ORDER BY id DESC"; 

$students = $conn->query($sql);
$db_error = $students ? '' : $conn->error;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        :root {
            --bg-color: #0a0a0f;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(255, 255, 255, 0.05);
            --table-head-bg: rgba(255, 255, 255, 0.05);
            --hover-bg: rgba(255, 255, 255, 0.02);
        }
        [data-theme="light"] {
            --bg-color: #f1f5f9;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(0, 0, 0, 0.08);
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.2);
            --danger: #dc2626;
            --warning: #d97706;
            --success: #059669;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --input-bg: rgba(0, 0, 0, 0.03);
            --table-head-bg: rgba(0, 0, 0, 0.03);
            --hover-bg: rgba(0, 0, 0, 0.02);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
        body { background: var(--bg-color); color: var(--text-main); min-height: 100vh; padding: 40px 20px; position: relative; overflow-x: hidden; }
        .blob { position: fixed; border-radius: 50%; filter: blur(100px); z-index: 0; opacity: 0.5; animation: float 15s infinite alternate; transition: opacity 0.5s ease; }
        [data-theme="light"] .blob { opacity: 0.15; }
        .blob-1 { width: 500px; height: 500px; background: #4f46e5; top: -150px; left: -150px; }
        .blob-2 { width: 400px; height: 400px; background: #ec4899; bottom: -100px; right: -100px; animation-delay: -5s; }
        @keyframes float { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(40px, 60px) scale(1.1); } }
        .dashboard-card { position: relative; z-index: 1; max-width: 1000px; margin: 0 auto; background: var(--glass-bg); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid var(--glass-border); border-radius: 24px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--glass-border); flex-wrap: wrap; gap: 15px; }
        .user-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(99, 102, 241, 0.15); color: var(--primary); padding: 8px 16px; border-radius: 50px; font-size: 14px; font-weight: 600; border: 1px solid rgba(99, 102, 241, 0.3); }
        .top-actions { display: flex; align-items: center; gap: 12px; }
        .theme-btn { background: var(--input-bg); border: 1px solid var(--glass-border); color: var(--text-main); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; }
        .theme-btn:hover { border-color: var(--primary); background: var(--primary-glow); }
        .theme-btn svg { width: 20px; height: 20px; fill: currentColor; }
        .logout-btn { color: var(--danger); text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s ease; padding: 8px 16px; border-radius: 8px; border: 1px solid transparent; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1); }
        .form-section { background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px; margin-bottom: 30px; }
        .form-section h3 { margin-bottom: 20px; font-size: 18px; color: var(--text-main); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px; }
        input { width: 100%; padding: 12px 16px; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 10px; color: var(--text-main); font-size: 14px; transition: all 0.3s ease; outline: none; }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        input::placeholder { color: var(--text-muted); }
        .btn { padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), #8b5cf6); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px var(--primary-glow); }
        .btn-cancel { background: transparent; color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); margin-left: 10px; }
        .btn-cancel:hover { background: rgba(239, 68, 68, 0.1); }
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { flex: 1; }
        .search-bar .btn { width: auto; padding: 12px 20px; }
        .search-bar .btn-clear { background: var(--input-bg); color: var(--text-muted); border: 1px solid var(--glass-border); }
        .search-bar .btn-clear:hover { background: var(--hover-bg); color: var(--text-main); }
        .table-container { overflow-x: auto; border-radius: 16px; border: 1px solid var(--glass-border); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: var(--table-head-bg); color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; padding: 16px; text-align: left; border-bottom: 1px solid var(--glass-border); }
        td { padding: 16px; border-bottom: 1px solid var(--glass-border); color: var(--text-main); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--hover-bg); }
        .action-btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: all 0.2s ease; display: inline-block; margin-right: 6px; }
        .btn-edit { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.3); }
        .btn-edit:hover { background: rgba(245, 158, 11, 0.25); }
        .btn-del { background: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-del:hover { background: rgba(239, 68, 68, 0.25); }
        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
        .msg-box { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; animation: slideDown 0.3s ease; }
        .msg-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger); }
        .msg-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="dashboard-card">
        <div class="top-bar">
            <div class="user-badge">
                <span>👤</span>
                <span><?= htmlspecialchars($_SESSION['user']) ?> <small style="opacity:0.7">(<?= $is_admin ? 'Admin' : 'User' ?>)</small></span>
            </div>
            <div class="top-actions">
                <button id="theme-toggle" class="theme-btn" title="Toggle Dark/Light Mode">
                    <svg id="icon-sun" viewBox="0 0 24 24" style="display: none;"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58a.996.996 0 00-1.41 0 .996.996 0 000 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37a.996.996 0 00-1.41 0 .996.996 0 000 1.41l1.06 1.06c.39.39 1.03.39 1.41 0a.996.996 0 000-1.41l-1.06-1.06zm1.06-10.96a.996.996 0 000-1.41.996.996 0 00-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36a.996.996 0 000 1.41.996.996 0 001.41 0l1.06-1.06c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06z"/></svg>
                    <svg id="icon-moon" viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-3.03 0-5.5-2.47-5.5-5.5 0-1.82.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg>
                </button>
                <a href="login.php?logout=1" class="logout-btn">Logout →</a>
            </div>
        </div>

        <!-- Success / Error Messages -->
        <?php if (isset($_SESSION['err'])): ?>
            <div class="msg-box msg-error"><strong>⚠️ Error:</strong> <?= htmlspecialchars($_SESSION['err']) ?></div>
            <?php unset($_SESSION['err']); ?>
        <?php elseif (isset($_SESSION['msg'])): ?>
            <div class="msg-box msg-success"><strong>✅ Success:</strong> <?= htmlspecialchars($_SESSION['msg']) ?></div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>

        <?php if ($db_error): ?>
            <div class="msg-box msg-error">
                <strong>⚠️ Database Error:</strong> <?= htmlspecialchars($db_error) ?>
            </div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <div class="form-section">
            <h3><?= $edit_data['id'] > 0 ? '✏️ Update Student' : '➕ Add New Student' ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <div class="form-grid">
                    <input type="text" name="name" placeholder="Full Name" value="<?= htmlspecialchars($edit_data['name']) ?>">
                    <input type="email" name="email" placeholder="Email Address" value="<?= htmlspecialchars($edit_data['email']) ?>">
                    <input type="text" name="grade" placeholder="Grade (e.g., A, 95%)" value="<?= htmlspecialchars($edit_data['grade']) ?>">
                </div>
                <div style="display:flex; align-items:center; flex-wrap: wrap; gap: 10px;">
                    <button type="submit" class="btn btn-primary"><?= $edit_data['id'] > 0 ? 'Update Student' : 'Add Student' ?></button>
                    <?php if ($edit_data['id'] > 0): ?><a href="index.php" class="btn btn-cancel">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <h3 style="margin-bottom: 20px; font-size: 18px;">📚 Student List</h3>
        <form method="GET" class="search-bar">
            <input type="text" name="q" placeholder="🔍 Search by Name, Email, or Grade..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search !== ''): ?><a href="index.php" class="btn btn-clear">Clear</a><?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Grade</th>
                        <?php if ($is_admin) echo '<th style="text-align:right;">Actions</th>'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students && $students->num_rows > 0): ?>
                        <?php while($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td style="color: var(--text-muted);">#<?= $row['id'] ?? '' ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?: '—' ?></td>
                            <td><?= htmlspecialchars($row['email'] ?? '') ?: '—' ?></td>
                            <td>
                                <span style="background: rgba(99, 102, 241, 0.15); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                    <?= htmlspecialchars($row['grade'] ?? 'N/A') ?: 'N/A' ?>
                                </span>
                            </td>
                            <?php if ($is_admin): ?>
                            <td style="text-align:right;">
                                <a href="?edit=<?= $row['id'] ?>" class="action-btn btn-edit">Edit</a>
                                <a href="?del=<?= $row['id'] ?>" class="action-btn btn-del" onclick="return confirm('Delete this student?')">Delete</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $is_admin ? 5 : 4 ?>" class="empty-state">
                                <?= $db_error ? 'Fix the database error above.' : 'No students found. Try adding one!' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const iconSun = document.getElementById('icon-sun');
        const iconMoon = document.getElementById('icon-moon');
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'dark';
        
        html.setAttribute('data-theme', currentTheme);
        function updateIcons(theme) {
            iconSun.style.display = theme === 'light' ? 'block' : 'none';
            iconMoon.style.display = theme === 'light' ? 'none' : 'block';
        }
        updateIcons(currentTheme);

        themeToggle.addEventListener('click', () => {
            const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcons(newTheme);
        });
    </script>
</body>
</html>