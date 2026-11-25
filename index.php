<?php 
session_start();
include 'db.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] != true) {
    header('location: login.php');
    exit;
}

// Handle logout
if(isset($_GET['logout'])) {
    session_destroy();
    header('location: login.php');
    exit;
}

// Admin Display Name at ID
$admin_name = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'Admin Joyce Ann';
$admin_id   = $_SESSION['admin_id'] ?? '340576';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | LCC Payroll</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { 
            --primary: #2563eb; 
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --teal-custom: #0f766e;
            --secondary: #64748b;
            --bg-body: #f8fafc;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
        }

        body { 
            background-color: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; 
            color: var(--text-main); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Synchronized Navbar */
        .navbar-custom { 
            background-color: #ffffff; 
            border-bottom: 1px solid var(--border-color);
            padding: 12px 24px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
        }
        .navbar-custom .navbar-brand {
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--primary) !important;
            letter-spacing: -0.5px;
        }
        .navbar-custom .nav-link { 
            color: var(--secondary) !important; 
            font-weight: 600; 
            font-size: 0.85rem;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .navbar-custom .nav-link:hover, .navbar-custom .nav-link.active { 
            color: var(--primary) !important;
            background-color: var(--primary-light);
        }

        /* Center Content Area */
        .center-screen-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding-bottom: 60px;
        }

        /* Centered Welcome Title */
        .admin-display-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: #0f766e; /* Elegant Teal tone */
            letter-spacing: 1px;
            margin: 0;
            transition: all 0.2s ease;
        }
        .admin-display-title:hover {
            color: var(--primary);
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

<script>
     const SUPABASE_URL = 
  "https://nvrdtgnzfyicuwezodob.supabase.co";

const SUPABASE_KEY = 
  "sb_publishable_FKAPrYS_O2u_WSDXjpXiTw_WrLP0eBK";

const db = supabase.createClient(
  SUPABASE_URL,
  SUPABASE_KEY
);

</script>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <img src="profiles/logo.png" alt="LCC Logo" style="height: 38px; width: auto; border-radius: 6px;">
            <span>LCC ADMIN</span>
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-4 gap-1">
                <li class="nav-item"><a class="nav-link active text-primary" href="index.php"><i class="fas fa-home me-1"></i> DASHBOARD</a></li>
                <li class="nav-item"><a class="nav-link" href="employee.php"><i class="fas fa-users me-1"></i> EMPLOYEES</a></li>
                <li class="nav-item"><a class="nav-link" href="dept.php"><i class="fas fa-sitemap me-1"></i> DEP_POS</a></li>
                <li class="nav-item"><a class="nav-link" href="loans.php"><i class="fas fa-hand-holding-usd me-1"></i> LOANS</a></li>
                <li class="nav-item"><a class="nav-link" href="payroll.php"><i class="fas fa-file-invoice-dollar me-1"></i> PAYROLL</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-line me-1"></i> REPORTS</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center text-muted small bg-light py-1 px-3 rounded-pill border">
                    <span class="rounded-circle bg-success me-2" style="width: 8px; height: 8px;"></span>
                    <span>Admin Active</span>
                </div>
                <a href="?logout=1" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- Upper Right ID Badge Row -->
<div class="container-fluid px-4 pt-3">
    <div class="text-end">
    </div>
</div>

<!-- Gitnang Bahagi: WELCOME BACK ADMIN! -->
<div class="center-screen-wrapper">
    <h1 class="admin-display-title">
        WELCOME BACK ADMIN!
    </h1>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>