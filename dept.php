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

// --- LOGIC PARA SA DEPT & POS (HINDI GINALAW) ---
if(isset($_POST['add_dept'])) {
    $name = mysqli_real_escape_string($conn, $_POST['dept_name']);
    mysqli_query($conn, "INSERT INTO departments (dept_name) VALUES ('$name')");
    header('location: index.php');
}
if(isset($_POST['edit_dept'])) {
    $id = $_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['dept_name']);
    mysqli_query($conn, "UPDATE departments SET dept_name='$name' WHERE id=$id");
    header('location: index.php');
}
if(isset($_GET['del_dept'])) {
    $id = $_GET['del_dept'];
    mysqli_query($conn, "DELETE FROM departments WHERE id=$id");
    header('location: index.php');
}
if(isset($_POST['add_pos'])) {
    $name = mysqli_real_escape_string($conn, $_POST['pos_name']);
    mysqli_query($conn, "INSERT INTO positions (pos_name) VALUES ('$name')");
    header('location: index.php');
}
if(isset($_POST['edit_pos'])) {
    $id = $_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['pos_name']);
    mysqli_query($conn, "UPDATE positions SET pos_name='$name' WHERE id=$id");
    header('location: index.php');
}
if(isset($_GET['del_pos'])) {
    $id = $_GET['del_pos'];
    mysqli_query($conn, "DELETE FROM positions WHERE id=$id");
    header('location: index.php');
}

// --- LOGIC PARA SA PAGE 2 (SAVE OTHER INCOME & DEDUCTION) ---
if(isset($_POST['save_other_income'])) {
    $emp_no = mysqli_real_escape_string($conn, $_POST['emp_no']);
    $pay_date = mysqli_real_escape_string($conn, $_POST['pay_date']);
    $income_type = mysqli_real_escape_string($conn, $_POST['income_type']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    mysqli_query($conn, "INSERT INTO other_income (employee_number, pay_date, income_type, amount) VALUES ('$emp_no', '$pay_date', '$income_type', '$amount')");
    header('location: index.php?p=2');
}
if(isset($_POST['save_other_deduction'])) {
    $emp_no = mysqli_real_escape_string($conn, $_POST['emp_no']);
    $pay_date = mysqli_real_escape_string($conn, $_POST['pay_date']);
    $deduction_type = mysqli_real_escape_string($conn, $_POST['deduction_type']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    mysqli_query($conn, "INSERT INTO other_deductions (employee_number, pay_date, deduction_type, amount) VALUES ('$emp_no', '$pay_date', '$deduction_type', '$amount')");
    header('location: index.php?p=2');
}

// --- ITO ANG DINAGDAG PARA GUMANA ANG DELETE SA PAGE 2 ---
if(isset($_GET['del_inc'])) {
    $id = mysqli_real_escape_string($conn, $_GET['del_inc']);
    mysqli_query($conn, "DELETE FROM other_income WHERE id = '$id'");
    header('location: index.php?p=2');
}
if(isset($_GET['del_ded'])) {
    $id = mysqli_real_escape_string($conn, $_GET['del_ded']);
    mysqli_query($conn, "DELETE FROM other_deductions WHERE id = '$id'");
    header('location: index.php?p=2');
}

// SEARCH LOGIC PARA SA DEPT/POS
$search_dept = isset($_GET['s_dept']) ? $_GET['s_dept'] : '';
$search_pos = isset($_GET['s_pos']) ? $_GET['s_pos'] : '';
$search_dept_sql = mysqli_real_escape_string($conn, $search_dept);
$search_pos_sql = mysqli_real_escape_string($conn, $search_pos);
$items_per_page = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$dept_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM departments WHERE dept_name LIKE '%$search_dept_sql%'");
$dept_count = mysqli_fetch_assoc($dept_count_result);
$dept_total_pages = (int)ceil((int)$dept_count['total'] / $items_per_page);

$pos_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM positions WHERE pos_name LIKE '%$search_pos_sql%'");
$pos_count = mysqli_fetch_assoc($pos_count_result);
$pos_total_pages = (int)ceil((int)$pos_count['total'] / $items_per_page);

$total_pages = max(1, $dept_total_pages, $pos_total_pages);
$page = min($page, $total_pages);
$offset = ($page - 1) * $items_per_page;

// Navigation Logic
$view_page = isset($_GET['p']) ? $_GET['p'] : 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dept & Position Management | LCC Admin</title>
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
            --secondary: #64748b;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --radius-md: 10px;
            --radius-lg: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04);
        }

        body { 
            background-color: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; 
            color: var(--text-main); 
            font-size: 0.88rem;
            letter-spacing: -0.01em;
        }

        /* Modern Navbar (Synchronized with employee.php) */
        .navbar-custom { 
            background-color: #ffffff; 
            border-bottom: 1px solid var(--border-color);
            padding: 12px 24px; 
            box-shadow: var(--shadow-sm); 
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

        /* Cards & Section Containers */
        .mgmt-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .mgmt-card:hover {
            box-shadow: var(--shadow-md);
        }
        
        .card-header-styled {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-page2 {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: white;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Form Controls */
        .form-control, .form-select {
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        /* Tables Styling */
        .table-custom-main {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .table-custom-main thead th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1.5px solid var(--border-color);
        }
        .table-custom-main tbody td {
            padding: 12px 16px;
            font-size: 0.86rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .table-custom-main tbody tr:last-child td {
            border-bottom: none;
        }
        .table-custom-main tbody tr:hover td {
            background-color: #f8fafc;
        }

        /* Buttons */
        .btn-action-edit {
            color: var(--primary);
            background: var(--primary-light);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .btn-action-edit:hover {
            background: #dbeafe;
            color: var(--primary-dark);
            transform: scale(1.05);
        }

        .btn-action-del {
            color: #ef4444;
            background: #fee2e2;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.2s;
        }
        .btn-action-del:hover {
            background: #fecaca;
            color: #dc2626;
            transform: scale(1.05);
        }

        .btn-nav-pagination {
            border-radius: 30px;
            font-weight: 600;
            padding: 10px 28px;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        /* Modals */
        .modal-content {
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        .modal-header {
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
        }
        .modal-header h5 {
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
            color: #1e293b;
        }
    </style>
</head>
<body>

<!-- Modern Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top mb-4">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="profiles/logo.png" alt="LCC Logo" style="height: 38px; width: auto; border-radius: 6px;">
            <span>LCC ADMIN</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-4 gap-1">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> DASHBOARD</a></li>
                <li class="nav-item"><a class="nav-link" href="employee.php"><i class="fas fa-users me-1"></i> Employees</a></li>
                <li class="nav-item"><a class="nav-link active text-primary" href="dept.php"><i class="fas fa-sitemap me-1"></i> Dept & Position</a></li>
                <li class="nav-item"><a class="nav-link" href="loans.php"><i class="fas fa-hand-holding-usd me-1"></i> LOANS</a></li>
                <li class="nav-item"><a class="nav-link" href="payroll.php"><i class="fas fa-file-invoice-dollar me-1"></i> PAYROLL</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-line me-1"></i> Reports</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center text-muted small bg-light py-1 px-3 rounded-pill border">
                    <span class="rounded-circle bg-success me-2" style="width: 8px; height: 8px;"></span>
                    <span>Admin Session</span>
                </div>
                <a href="?logout=1" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 pb-5">

    <?php if($view_page == 1): ?>
    <!-- PAGE 1: DEPARTMENT AT POSITION -->
    <div class="row g-4">
        <!-- DEPARTMENT MANAGEMENT -->
        <div class="col-lg-6">
            <div class="mgmt-card">
                <div class="card-header-styled">
                    <span><i class="fas fa-building me-2"></i> DEPARTMENT MANAGEMENT</span>
                    <span class="badge bg-white text-primary rounded-pill px-2.5 py-1 small"><?php echo $dept_count['total']; ?> Total</span>
                </div>
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                            <i class="fas fa-plus-circle me-1"></i> New Dept
                        </button>
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="s_dept" class="form-control form-control-sm" placeholder="Search department..." value="<?php echo htmlspecialchars($search_dept); ?>">
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-search"></i></button>
                            <?php if(!empty($search_dept)): ?>
                                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="border rounded-3 overflow-hidden shadow-sm">
                        <table class="table-custom-main">
                            <thead><tr><th>Department Name</th><th class="text-center" width="120">Action</th></tr></thead>
                            <tbody>
                                <?php 
                                $res = mysqli_query($conn, "SELECT * FROM departments WHERE dept_name LIKE '%$search_dept_sql%' ORDER BY dept_name ASC LIMIT $items_per_page OFFSET $offset");
                                if(mysqli_num_rows($res) == 0): ?>
                                    <tr><td colspan="2" class="text-center text-muted py-4">No department records found.</td></tr>
                                <?php endif;
                                while($row = mysqli_fetch_assoc($res)): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                    <td class="text-center">
                                        <button class="btn-action-edit me-1" title="Edit" data-bs-toggle="modal" data-bs-target="#editDept<?php echo $row['id']; ?>"><i class="fas fa-pen-to-square"></i></button>
                                        <a href="index.php?del_dept=<?php echo $row['id']; ?>" class="btn-action-del" title="Delete" onclick="return confirm('Sigurado ka ba?')"><i class="fas fa-trash-can"></i></a>
                                    </td>
                                </tr>

                                <!-- EDIT DEPT MODAL -->
                                <div class="modal fade" id="editDept<?php echo $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <div class="modal-header">
                                                    <h5><i class="fas fa-pen text-primary me-2"></i>Edit Department</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <label class="form-label small fw-bold text-muted uppercase">Department Name</label>
                                                    <input type="text" name="dept_name" class="form-control" value="<?php echo htmlspecialchars($row['dept_name']); ?>" required>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="submit" name="edit_dept" class="btn btn-primary px-4">Update Department</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- POSITION MANAGEMENT -->
        <div class="col-lg-6">
            <div class="mgmt-card">
                <div class="card-header-styled">
                    <span><i class="fas fa-id-badge me-2"></i> POSITION MANAGEMENT</span>
                    <span class="badge bg-white text-primary rounded-pill px-2.5 py-1 small"><?php echo $pos_count['total']; ?> Total</span>
                </div>
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#addPosModal">
                            <i class="fas fa-plus-circle me-1"></i> New Position
                        </button>
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="s_pos" class="form-control form-control-sm" placeholder="Search position..." value="<?php echo htmlspecialchars($search_pos); ?>">
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-search"></i></button>
                            <?php if(!empty($search_pos)): ?>
                                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="border rounded-3 overflow-hidden shadow-sm">
                        <table class="table-custom-main">
                            <thead><tr><th>Position Name</th><th class="text-center" width="120">Action</th></tr></thead>
                            <tbody>
                                <?php 
                                $res = mysqli_query($conn, "SELECT * FROM positions WHERE pos_name LIKE '%$search_pos_sql%' ORDER BY pos_name ASC LIMIT $items_per_page OFFSET $offset");
                                if(mysqli_num_rows($res) == 0): ?>
                                    <tr><td colspan="2" class="text-center text-muted py-4">No position records found.</td></tr>
                                <?php endif;
                                while($row = mysqli_fetch_assoc($res)): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['pos_name']); ?></td>
                                    <td class="text-center">
                                        <button class="btn-action-edit me-1" title="Edit" data-bs-toggle="modal" data-bs-target="#editPos<?php echo $row['id']; ?>"><i class="fas fa-pen-to-square"></i></button>
                                        <a href="index.php?del_pos=<?php echo $row['id']; ?>" class="btn-action-del" title="Delete" onclick="return confirm('Sigurado ka ba?')"><i class="fas fa-trash-can"></i></a>
                                    </td>
                                </tr>

                                <!-- EDIT POSITION MODAL -->
                                <div class="modal fade" id="editPos<?php echo $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <div class="modal-header">
                                                    <h5><i class="fas fa-pen text-primary me-2"></i>Edit Position</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <label class="form-label small fw-bold text-muted uppercase">Position Name</label>
                                                    <input type="text" name="pos_name" class="form-control" value="<?php echo htmlspecialchars($row['pos_name']); ?>" required>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="submit" name="edit_pos" class="btn btn-primary px-4">Update Position</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <a href="index.php?p=2" class="btn btn-primary btn-nav-pagination shadow"><i class="fas fa-arrow-right-arrow-left me-2"></i> PROCEED TO OTHER INCOME & DEDUCTIONS <i class="fas fa-chevron-right ms-2"></i></a>
    </div>

    <?php else: ?>
    <!-- PAGE 2: OTHER INCOME & DEDUCTION -->
    <div class="row g-4">
        <!-- OTHER INCOME SECTION -->
        <div class="col-lg-6">
            <div class="mgmt-card">
                <div class="card-header-page2">
                    <span><i class="fas fa-hand-holding-dollar me-2"></i> OTHER INCOME ENTRIES</span>
                    <button class="btn btn-light btn-sm text-teal fw-semibold rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addOtherIncModal">
                        <i class="fas fa-plus-circle me-1"></i> Add Income
                    </button>
                </div>
                <div class="p-4">
                    <div class="border rounded-3 overflow-hidden shadow-sm">
                        <table class="table-custom-main text-center">
                            <thead>
                                <tr>
                                    <th>Emp No.</th>
                                    <th>Pay Date</th>
                                    <th>Income Type</th>
                                    <th>Amount</th>
                                    <th width="80">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $res_inc = mysqli_query($conn, "SELECT * FROM other_income ORDER BY id DESC");
                                if(mysqli_num_rows($res_inc) == 0): ?>
                                    <tr><td colspan="5" class="py-4 text-muted">No other income records registered.</td></tr>
                                <?php endif;
                                while($r_inc = mysqli_fetch_assoc($res_inc)): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($r_inc['employee_number']); ?></td>
                                    <td><?php echo htmlspecialchars($r_inc['pay_date']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r_inc['income_type']); ?></span></td>
                                    <td class="fw-semibold text-success">₱<?php echo number_format($r_inc['amount'], 2); ?></td>
                                    <td>
                                        <a href="index.php?del_inc=<?php echo $r_inc['id']; ?>&p=2" class="btn-action-del" title="Delete" onclick="return confirm('Sigurado ka ba?')">
                                            <i class="fas fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- OTHER DEDUCTION SECTION -->
        <div class="col-lg-6">
            <div class="mgmt-card">
                <div class="card-header-styled">
                    <span><i class="fas fa-file-invoice-dollar me-2"></i> OTHER DEDUCTION ENTRIES</span>
                    <button class="btn btn-light btn-sm text-primary fw-semibold rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addOtherDedModal">
                        <i class="fas fa-plus-circle me-1"></i> Add Deduction
                    </button>
                </div>
                <div class="p-4">
                    <div class="border rounded-3 overflow-hidden shadow-sm">
                        <table class="table-custom-main text-center">
                            <thead>
                                <tr>
                                    <th>Emp No.</th>
                                    <th>Pay Date</th>
                                    <th>Deduction Type</th>
                                    <th>Amount</th>
                                    <th width="80">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $res_ded = mysqli_query($conn, "SELECT * FROM other_deductions ORDER BY id DESC");
                                if(mysqli_num_rows($res_ded) == 0): ?>
                                    <tr><td colspan="5" class="py-4 text-muted">No other deduction records registered.</td></tr>
                                <?php endif;
                                while($r_ded = mysqli_fetch_assoc($res_ded)): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($r_ded['employee_number']); ?></td>
                                    <td><?php echo htmlspecialchars($r_ded['pay_date']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r_ded['deduction_type']); ?></span></td>
                                    <td class="fw-semibold text-danger">-₱<?php echo number_format($r_ded['amount'], 2); ?></td>
                                    <td>
                                        <a href="index.php?del_ded=<?php echo $r_ded['id']; ?>&p=2" class="btn-action-del" title="Delete" onclick="return confirm('Sigurado ka ba?')">
                                            <i class="fas fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5 mb-4">
        <a href="index.php?p=1" class="btn btn-secondary btn-nav-pagination shadow"><i class="fas fa-chevron-left me-2"></i> BACK TO DEPT & POSITIONS</a>
    </div>
    <?php endif; ?>

</div>

<!-- QUERY PARA SA LISTAHAN NG EMPLOYEES PARA SA SELECT DROPDOWN -->
<?php
$emp_dropdown_options = "<option value=''>-- Select Employee --</option>";
$emp_fetch = mysqli_query($conn, "SELECT employee_number, last_name, first_name FROM employees ORDER BY last_name ASC");
while($e_opt = mysqli_fetch_assoc($emp_fetch)) {
    $emp_dropdown_options .= "<option value='{$e_opt['employee_number']}'>{$e_opt['employee_number']} - {$e_opt['last_name']}, {$e_opt['first_name']}</option>";
}
?>

<!-- CREATE MODALS -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5><i class="fas fa-building text-primary me-2"></i>Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label small fw-bold text-muted">DEPARTMENT NAME</label>
                    <input type="text" name="dept_name" class="form-control" placeholder="e.g. Accounting, Human Resources" required>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" name="add_dept" class="btn btn-primary px-4 fw-semibold">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addPosModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5><i class="fas fa-id-badge text-primary me-2"></i>Add Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label small fw-bold text-muted">POSITION TITLE</label>
                    <input type="text" name="pos_name" class="form-control" placeholder="e.g. Senior Accountant, Instructor" required>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" name="add_pos" class="btn btn-primary px-4 fw-semibold">Save Position</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ADD OTHER INCOME MODAL (SELECT EMPLOYEE) -->
<div class="modal fade" id="addOtherIncModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5><i class="fas fa-hand-holding-dollar text-success me-2"></i>Add Other Income</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">SELECT EMPLOYEE</label>
                        <select name="emp_no" class="form-select" required>
                            <?php echo $emp_dropdown_options; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">PAY DATE</label><input type="date" name="pay_date" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">INCOME TYPE</label><input type="text" name="income_type" class="form-control" placeholder="e.g. Overtime, Incentive" required></div>
                    <div class="mb-2"><label class="form-label small fw-bold text-muted">AMOUNT (₱)</label><input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" name="save_other_income" class="btn btn-success text-white px-4 fw-semibold">Save Income</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ADD OTHER DEDUCTION MODAL (SELECT EMPLOYEE) -->
<div class="modal fade" id="addOtherDedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5><i class="fas fa-file-invoice-dollar text-danger me-2"></i>Add Other Deduction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">SELECT EMPLOYEE</label>
                        <select name="emp_no" class="form-select" required>
                            <?php echo $emp_dropdown_options; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">PAY DATE</label><input type="date" name="pay_date" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">DEDUCTION TYPE</label><input type="text" name="deduction_type" class="form-control" placeholder="e.g. Uniform, Cash Advance" required></div>
                    <div class="mb-2"><label class="form-label small fw-bold text-muted">AMOUNT (₱)</label><input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" name="save_other_deduction" class="btn btn-danger px-4 fw-semibold">Save Deduction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>