<?php
/**
 * Ojasva Events - Admin Login Page (PHP + SQLite)
 * Authenticates against the 'login' table in database.sqlite
 */

session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_user'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/db.php';

$error = '';
$usernameVal = 'admin@123';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $usernameVal = $username;

    if (empty($username) || empty($password)) {
        $error = 'Please enter both Login ID and Password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM login WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && (password_verify($password, $user['password']) || $password === $user['password'] || ($username === 'admin@123' && $password === 'admin'))) {
                // Successful login
                $_SESSION['admin_user'] = $user['username'];
                $_SESSION['admin_id']   = $user['id'];
                $_SESSION['admin_role'] = $user['role'] ?? 'admin';
                $_SESSION['logged_in_time'] = time();

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid Login ID or Password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database verification error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#29172a">
<title>Admin Login | Ojasva Events</title>
<link rel="icon" href="assets/ojasva-monogram.png" type="image/png" sizes="256x256">
<link rel="stylesheet" href="assets/site.css">
<style>
.login-body {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 30px 16px;
  background-color: var(--plum);
  color: #fff8f4;
  background-image: radial-gradient(circle at 50% 20%, #3e1f40 0%, var(--plum) 70%);
}
.login-card {
  width: min(440px, 100%);
  background: #fffaf6;
  color: var(--ink);
  border: 1px solid var(--border);
  padding: 38px 32px;
  box-shadow: 0 20px 45px rgba(18, 9, 20, 0.45);
}
.login-brand {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 24px;
}
.login-brand img {
  width: 58px;
  height: 58px;
  margin-bottom: 12px;
}
.login-title {
  font-family: var(--serif);
  font-size: 1.85rem;
  line-height: 1.1;
  color: var(--plum);
  margin: 0;
}
.login-sub {
  font-size: 0.85rem;
  color: var(--muted);
  margin: 6px 0 0;
}
.cred-badge {
  background: #f3e9e3;
  padding: 10px 14px;
  font-size: 0.78rem;
  color: #6a424e;
  border-left: 3px solid #8e4f62;
  margin-bottom: 18px;
  line-height: 1.5;
}
.cred-badge code {
  font-weight: 700;
  background: #fff;
  padding: 2px 6px;
  border: 1px solid #decac4;
}
.alert-error {
  padding: 10px 12px;
  background: #fde8e8;
  color: #9b1c1c;
  border: 1px solid #f8b4b4;
  font-size: 0.85rem;
  margin-bottom: 16px;
}
.login-fields {
  display: grid;
  gap: 16px;
  margin-bottom: 22px;
}
.login-fields label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--ink);
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
.login-fields input {
  display: block;
  width: 100%;
  margin-top: 6px;
  padding: 10px 12px;
  border: 1px solid #c8b7c0;
  background: white;
  font-size: 0.95rem;
  color: var(--ink);
}
.login-fields input:focus {
  outline: 2px solid #8e4f62;
  border-color: #8e4f62;
}
.back-link {
  display: block;
  text-align: center;
  margin-top: 22px;
  font-size: 0.84rem;
  color: #eed6c9;
}
.back-link:hover {
  text-decoration: underline;
  color: var(--gold);
}
</style>
</head>
<body class="login-body">

<div class="login-card">
  <div class="login-brand">
    <img src="assets/ojasva-monogram.png" alt="Ojasva Events">
    <h1 class="login-title">Admin Login</h1>
    <p class="login-sub">Powered by PHP &amp; SQLite Database</p>
  </div>

  <div class="cred-badge">
    <strong>SQLite Login Credentials:</strong><br>
    Login ID: <code>admin@123</code> &nbsp;|&nbsp; Password: <code>admin</code>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert-error" role="alert">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="login-fields">
      <label>
        Login ID
        <input type="text" name="username" required value="<?= htmlspecialchars($usernameVal) ?>" placeholder="e.g. admin@123" autocomplete="username">
      </label>
      <label>
        Password
        <input type="password" name="password" required placeholder="Enter password" autocomplete="current-password">
      </label>
    </div>

    <button type="submit" class="button primary" style="width:100%">
      Sign In to Dashboard →
    </button>
  </form>
</div>

<a href="index.html" class="back-link">← Return to Ojasva Events Home</a>

</body>
</html>

