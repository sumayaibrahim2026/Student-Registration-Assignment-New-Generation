<?php 
include 'db.php';
$err = "";
$msg = "";
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- LOGIN LOGIC ---
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
        $stmt->bind_param("s", $_POST['user']);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (password_verify($_POST['pass'], $row['password'])) {
                $_SESSION['user'] = $row['username'];
                $_SESSION['role'] = $row['role']; 
                header("Location: index.php"); exit;
            }
        }
        $err = "Invalid username or password!";
    }
    
    // --- REGISTRATION LOGIC ---
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        $user = trim($_POST['user']);
        $email = trim($_POST['email']);
        $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT); 
        $role = 'user'; 
        
        $check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $check->bind_param("ss", $user, $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $err = "Username or Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user, $pass, $role, $email);
            
            if ($stmt->execute()) {
                $msg = "Account created! Please login.";
                $mode = 'login'; 
            } else {
                $err = "Registration failed: " . $conn->error; 
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Portal</title>
    <style>
        :root {
            --bg-color: #0a0a0f;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.5);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error: #ef4444;
            --success: #10b981;
            --input-bg: rgba(0, 0, 0, 0.2);
            --input-focus-bg: rgba(0, 0, 0, 0.4);
            --hint-bg: rgba(99, 102, 241, 0.08);
            --hint-border: rgba(99, 102, 241, 0.2);
            --hint-text: #a5b4fc;
        }

        [data-theme="light"] {
            --bg-color: #f1f5f9;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(0, 0, 0, 0.08);
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.3);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --error: #dc2626;
            --success: #059669;
            --input-bg: rgba(255, 255, 255, 0.8);
            --input-focus-bg: rgba(255, 255, 255, 1);
            --hint-bg: rgba(79, 70, 229, 0.08);
            --hint-border: rgba(79, 70, 229, 0.2);
            --hint-text: #4338ca;
        }

        * { 
            margin: 0; padding: 0; box-sizing: border-box; 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease; 
        }

        body { 
            background: var(--bg-color); 
            color: var(--text-main); 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            overflow: hidden; 
            position: relative; 
        }

        .blob { 
            position: absolute; 
            border-radius: 50%; 
            filter: blur(80px); 
            z-index: 0; 
            opacity: 0.6; 
            animation: float 10s infinite alternate; 
            transition: opacity 0.5s ease;
        }
        [data-theme="light"] .blob { opacity: 0.15; }
        .blob-1 { width: 400px; height: 400px; background: #4f46e5; top: -100px; left: -100px; }
        .blob-2 { width: 300px; height: 300px; background: #ec4899; bottom: -50px; right: -50px; animation-delay: -5s; }

        @keyframes float { 
            0% { transform: translate(0, 0) scale(1); } 
            100% { transform: translate(30px, 50px) scale(1.1); } 
        }

        .portal-card { 
            position: relative; 
            z-index: 1; 
            width: 100%; 
            max-width: 420px; 
            background: var(--glass-bg); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px); 
            border: 1px solid var(--glass-border); 
            border-radius: 24px; 
            padding: 40px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
        }

        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--input-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .theme-toggle:hover {
            border-color: var(--primary);
            background: var(--primary-glow);
        }
        .theme-toggle svg { width: 20px; height: 20px; fill: currentColor; }

        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 8px; }
        .header p { color: var(--text-muted); font-size: 14px; }

        .form-group { margin-bottom: 15px; position: relative; }

        input { 
            width: 100%; 
            padding: 14px 16px; 
            background: var(--input-bg); 
            border: 1px solid var(--glass-border); 
            border-radius: 12px; 
            color: var(--text-main); 
            font-size: 15px; 
            transition: all 0.3s ease; 
            outline: none; 
        }
        input:focus { 
            border-color: var(--primary); 
            box-shadow: 0 0 0 3px var(--primary-glow); 
            background: var(--input-focus-bg); 
        }
        input::placeholder { color: var(--text-muted); }

        button { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, var(--primary), #8b5cf6); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            margin-top: 10px; 
        }
        button:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 20px -5px var(--primary-glow); 
        }

        .alert { 
            padding: 12px; 
            border-radius: 10px; 
            font-size: 14px; 
            text-align: center; 
            margin-bottom: 20px; 
            animation: slideDown 0.3s ease; 
        }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: var(--error); border: 1px solid rgba(239, 68, 68, 0.2); }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
        
        [data-theme="light"] .alert-error { background: rgba(220, 38, 38, 0.1); border-color: rgba(220, 38, 38, 0.2); }
        [data-theme="light"] .alert-success { background: rgba(5, 150, 105, 0.1); border-color: rgba(5, 150, 105, 0.2); }

        @keyframes slideDown { 
            from { opacity: 0; transform: translateY(-10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* Admin Credentials Hint */
        .admin-hint {
            margin-top: 20px;
            padding: 12px 14px;
            background: var(--hint-bg);
            border: 1px dashed var(--hint-border);
            border-radius: 10px;
            font-size: 12px;
            color: var(--hint-text);
            text-align: center;
            animation: slideDown 0.4s ease;
        }
        .admin-hint strong {
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .admin-hint .cred {
            display: inline-block;
            background: var(--input-bg);
            padding: 2px 8px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            margin: 0 3px;
            border: 1px solid var(--glass-border);
        }

        .toggle-text { text-align: center; margin-top: 24px; font-size: 14px; color: var(--text-muted); }
        .toggle-text a { color: var(--primary); text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .toggle-text a:hover { color: #8b5cf6; text-decoration: underline; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="portal-card">
        <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode">
            <svg id="icon-sun" viewBox="0 0 24 24" style="display: none;">
                <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58a.996.996 0 00-1.41 0 .996.996 0 000 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37a.996.996 0 00-1.41 0 .996.996 0 000 1.41l1.06 1.06c.39.39 1.03.39 1.41 0a.996.996 0 000-1.41l-1.06-1.06zm1.06-10.96a.996.996 0 000-1.41.996.996 0 00-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36a.996.996 0 000 1.41.996.996 0 001.41 0l1.06-1.06c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06z"/>
            </svg>
            <svg id="icon-moon" viewBox="0 0 24 24">
                <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-3.03 0-5.5-2.47-5.5-5.5 0-1.82.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/>
            </svg>
        </button>

        <div class="header">
            <h2 id="title">Welcome Back</h2>
            <p id="subtitle">Enter your credentials to access the system</p>
        </div>

        <?php if($err): ?> <div class="alert alert-error"><?= $err ?></div> <?php endif; ?>
        <?php if($msg): ?> <div class="alert alert-success"><?= $msg ?></div> <?php endif; ?>

        <!-- LOGIN FORM -->
        <form id="loginForm" method="POST" class="<?= $mode == 'register' ? 'hidden' : '' ?>">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <input type="text" name="user" placeholder="Username" required autocomplete="username">
            </div>
            <div class="form-group">
                <input type="password" name="pass" placeholder="Password" required autocomplete="current-password">
            </div>
            <button type="submit">Sign In</button>

            <!-- Admin Credentials Hint (Only shows on Login) -->
            <div class="admin-hint">
                🔐 <strong>Default Admin:</strong> 
                User: <span class="cred">admin</span> / 
                Pass: <span class="cred">admin123</span>
            </div>

            <div class="toggle-text">
                Don't have an account? <a href="?mode=register">Create one</a>
            </div>
        </form>

        <!-- REGISTER FORM -->
        <form id="registerForm" method="POST" class="<?= $mode == 'login' ? 'hidden' : '' ?>">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <input type="text" name="user" placeholder="Choose a Username" required autocomplete="username">
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your Email" required autocomplete="email">
            </div>
            <div class="form-group">
                <input type="password" name="pass" placeholder="Choose a Password" required minlength="6" autocomplete="new-password">
            </div>
            <button type="submit">Create Account</button>
            <div class="toggle-text">
                Already have an account? <a href="?mode=login">Sign in</a>
            </div>
        </form>
    </div>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const iconSun = document.getElementById('icon-sun');
        const iconMoon = document.getElementById('icon-moon');
        const html = document.documentElement;

        const currentTheme = localStorage.getItem('theme') || 'dark';
        html.setAttribute('data-theme', currentTheme);
        updateIcons(currentTheme);

        themeToggle.addEventListener('click', () => {
            const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcons(newTheme);
        });

        function updateIcons(theme) {
            if (theme === 'light') {
                iconSun.style.display = 'block';
                iconMoon.style.display = 'none';
            } else {
                iconSun.style.display = 'none';
                iconMoon.style.display = 'block';
            }
        }
    </script>
</body>
</html>