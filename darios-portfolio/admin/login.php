
<?php

require_once __DIR__ . '/auth.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if (empty($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['admin_login_csrf'], $csrfToken)
    ) {
        $error = 'Invalid request. Please refresh the page and try again.';

    } elseif ($identity === '' || $password === '') {
        $error = 'Please enter your username/email and password.';

    } else {
        $sql = "SELECT id, username, email, password_hash
                FROM admin_users
                WHERE username = :username
                   OR email = :email
                LIMIT 1";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'username' => $identity,
            'email' => $identity
        ]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            unset($_SESSION['admin_login_csrf']);

            header('Location: index.php');
            exit;

        } else {
            $error = 'Incorrect username/email or password.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | DARIOS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Inter', sans-serif;
            background: #080b16;
            color: #fff;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
            padding: 42px 35px;
            background: #111729;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 18px;
            box-shadow: 0 25px 70px rgba(0,0,0,.4);
        }

        .brand {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand h1 {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .brand h1 span {
            color: #4385ff;
        }

        .brand p {
            color: #929bb1;
            font-size: 13px;
            margin-top: 9px;
        }

        .login-heading {
            margin-bottom: 25px;
        }

        .login-heading h2 {
            font-size: 23px;
            margin-bottom: 8px;
        }

        .login-heading p {
            color: #929bb1;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            margin-bottom: 9px;
            color: #d7dced;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #78849d;
        }

        input {
            width: 100%;
            padding: 14px 15px 14px 43px;
            background: #080e1e;
            border: 1px solid #28334b;
            border-radius: 9px;
            outline: none;
            color: white;
            font-size: 14px;
        }

        input:focus {
            border-color: #4385ff;
            box-shadow: 0 0 0 3px rgba(67,133,255,.12);
        }

        .login-button {
            width: 100%;
            padding: 15px;
            margin-top: 8px;
            border: none;
            border-radius: 9px;
            background: linear-gradient(120deg, #347cff, #7655ed);
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .3s;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67,133,255,.25);
        }

        .error-message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: rgba(255,70,90,.1);
            border: 1px solid rgba(255,70,90,.25);
            color: #ff8290;
            font-size: 13px;
        }

        .back-home {
            display: block;
            margin-top: 25px;
            text-align: center;
            color: #929bb1;
            text-decoration: none;
            font-size: 13px;
        }

        .back-home:hover {
            color: #4385ff;
        }

        @media(max-width: 480px) {
            .login-container {
                padding: 35px 22px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="brand">
        <h1>DARIOS<span>.</span></h1>
        <p>PORTFOLIO ADMINISTRATION</p>
    </div>

    <div class="login-heading">
        <h2>Welcome Back</h2>
        <p>Sign in to manage your portfolio.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error-message">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['admin_login_csrf'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <div class="form-group">
            <label for="identity">Username or Email</label>

            <div class="input-wrapper">
                <i class="fa-solid fa-user"></i>
                <input
                    type="text"
                    id="identity"
                    name="identity"
                    placeholder="Enter your username or email"
                    value="<?= htmlspecialchars($_POST['identity'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="username"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <div class="input-wrapper">
                <i class="fa-solid fa-lock"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>
        </div>

        <button type="submit" class="login-button">
            Sign In <i class="fa-solid fa-arrow-right"></i>
        </button>

    </form>

    <a href="../index.php" class="back-home">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Website
    </a>

</div>

</body>
</html>
