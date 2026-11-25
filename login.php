<?php 
session_start();
include 'db.php'; 

if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
    header('location: index.php');
    exit;
}

$error = '';

if(isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    if(!empty($username) && !empty($password)) {
        $query = "SELECT * FROM admin WHERE username = '$username' AND password = '$password' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if(mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user_data['id'];
            $_SESSION['admin_name'] = $user_data['name'];
            header('location: index.php');
            exit;
        } else {
            $error = 'Maling username o password!';
        }
    } else {
        $error = 'Pakisagutan ang lahat ng blanks.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - LCC Payroll</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --dark-slate: #2c3e50;
            --soft-gray: #f4f7f6;
            --border-color: #dcdde1;
            --text-muted: #7f8c8d;
        }

        body {
            background-color: var(--soft-gray);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #333;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 15px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .login-header {
            background-color: #ffffff;
            padding: 40px 30px 20px 30px;
            text-align: center;
        }

        .login-header img {
            height: 70px;
            margin-bottom: 15px;
            filter: grayscale(20%); /* Binabawasan ang tingkad ng logo */
        }

        .login-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark-slate);
            margin: 0;
            letter-spacing: 1px;
        }

        .login-header p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 5px;
        }

        .login-body {
            padding: 20px 35px 40px 35px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-slate);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid var(--border-color);
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fafafa;
        }

        .form-control:focus {
            border-color: var(--dark-slate);
            box-shadow: none;
            background-color: #fff;
        }

        .btn-login {
            background-color: var(--dark-slate);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 6px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            background-color: #1a252f;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .alert-error {
            background-color: #fff5f5;
            color: #c0392b;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            border: 1px solid #f8d7da;
            text-align: center;
        }

        .login-footer {
            text-align: center;
            padding: 20px;
            font-size: 11px;
            color: var(--text-muted);
            border-top: 1px solid #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="profiles/logo.png" alt="LCC Logo">
                <h1>LCC ADMIN</h1>
                <p>Access the Payroll Management System</p>
            </div>

            <div class="login-body">
                <?php if(!empty($error)): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="I-type ang username" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="I-type ang password" required>
                    </div>

                    <button type="submit" name="login" class="btn-login">
                        LOGIN
                    </button>
                </form>
            </div>

            <div class="login-footer">
                SISTEMA NG PAYROLL &copy; 2024 | SECURE LOGIN
            </div>
        </div>
    </div>
</body>
</html>