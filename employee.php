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

// --- GET EMPLOYEE LIST FOR MODAL ---
if(isset($_GET['get_employee_list'])) {
    $res = mysqli_query($conn, "SELECT e.*, p.department, p.position FROM employees e LEFT JOIN employee_positions p ON e.employee_number = p.employee_number ORDER BY last_name ASC");
    $employees = mysqli_fetch_all($res, MYSQLI_ASSOC);
    echo json_encode(['employees' => $employees]);
    exit;
}

// --- DELETE EMPLOYEE HANDLER ---
if(isset($_GET['delete_employee'])) {
    $emp_no = mysqli_real_escape_string($conn, $_GET['delete_employee']);
    
    mysqli_query($conn, "DELETE FROM dependents WHERE employee_number = '$emp_no'");
    mysqli_query($conn, "DELETE FROM education WHERE employee_number = '$emp_no'");
    mysqli_query($conn, "DELETE FROM character_references WHERE employee_number = '$emp_no'");
    mysqli_query($conn, "DELETE FROM emergency_contacts WHERE employee_number = '$emp_no'");
    mysqli_query($conn, "DELETE FROM employee_positions WHERE employee_number = '$emp_no'");
    mysqli_query($conn, "DELETE FROM employees WHERE employee_number = '$emp_no'");
    
    echo json_encode(['success' => true]);
    exit;
}

// --- AJAX HANDLER PARA SA DATA FETCHING (EXACT DATABASE CONTENTS) ---
if(isset($_GET['get_details'])) {
    $id = mysqli_real_escape_string($conn, $_GET['get_details']);
    $emp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM employees WHERE employee_number = '$id'"));
    $emg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM emergency_contacts WHERE employee_number = '$id'"));
    $deps = mysqli_query($conn, "SELECT * FROM dependents WHERE employee_number = '$id'");
    $edus = mysqli_query($conn, "SELECT * FROM education WHERE employee_number = '$id'");
    $refs = mysqli_query($conn, "SELECT * FROM character_references WHERE employee_number = '$id'");
    $pos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM employee_positions WHERE employee_number = '$id'"));

    $deps_arr = $deps ? mysqli_fetch_all($deps, MYSQLI_ASSOC) : [];
    $edus_arr = $edus ? mysqli_fetch_all($edus, MYSQLI_ASSOC) : [];
    $refs_arr = $refs ? mysqli_fetch_all($refs, MYSQLI_ASSOC) : [];

    // I-map ang address ng reference kahit ano pa ang eksaktong column name sa database
    foreach($refs_arr as &$r_row) {
        if(!isset($r_row['address']) || empty($r_row['address'])) {
            foreach($r_row as $col_key => $col_val) {
                if(stripos($col_key, 'addr') !== false && !empty($col_val)) {
                    $r_row['address'] = $col_val;
                    break;
                }
            }
        }
    }

    echo json_encode([
        'emp' => $emp, 
        'emg' => $emg, 
        'pos' => $pos,
        'deps' => $deps_arr,
        'edus' => $edus_arr,
        'refs' => $refs_arr
    ]);
    exit;
}

// --- SAVE LOGIC WITH FILE UPLOAD ---
if(isset($_POST['save_employee'])) {
    $emp_no = mysqli_real_escape_string($conn, $_POST['employee_number']);
    
    // Kunin ang lumang photo path para hindi mabura kung walang bagong inupload
    $old_photo_query = mysqli_query($conn, "SELECT photo FROM employees WHERE employee_number = '$emp_no'");
    $old_photo_data = mysqli_fetch_assoc($old_photo_query);
    $profile_pic = ($old_photo_data) ? $old_photo_data['photo'] : '';

    // Handle file upload
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
        $upload_dir = 'profiles/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if(in_array($file_ext, $allowed)) {
            $file_name = $emp_no . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $file_path)) {
                $profile_pic = $file_path;
            }
        }
    }
    
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name'] ?? '');
    $civil_status = mysqli_real_escape_string($conn, $_POST['civil_status'] ?? '');
    $nationality = mysqli_real_escape_string($conn, $_POST['nationality'] ?? '');
    $religion = mysqli_real_escape_string($conn, $_POST['religion'] ?? '');
    $atm_no = mysqli_real_escape_string($conn, $_POST['atm_no'] ?? '');
    
    // Ginamit ang REPLACE INTO para ma-update ang record kung existing na ang ID
    $sql1 = "REPLACE INTO employees (employee_number, portal_password, last_name, first_name, middle_name, gender, email, civil_status, nationality, religion, birth_date, perm_address, pres_address, contact_no, sss_no, philhealth_no, pagibig_no, atm_no, tin_no, photo) 
             VALUES ('$emp_no', '{$_POST['portal_password']}', '{$_POST['last_name']}', '{$_POST['first_name']}', '$middle_name', '{$_POST['gender']}', '{$_POST['email']}', '$civil_status', '$nationality', '$religion', '{$_POST['birth_date']}', '{$_POST['perm_address']}', '{$_POST['pres_address']}', '{$_POST['contact_no']}', '{$_POST['sss_no']}', '{$_POST['philhealth_no']}', '{$_POST['pagibig_no']}', '$atm_no', '{$_POST['tin_no']}', '$profile_pic')";
    
    if(mysqli_query($conn, $sql1)) {
        // Clear secondary tables before re-inserting to avoid duplicates
        mysqli_query($conn, "DELETE FROM emergency_contacts WHERE employee_number = '$emp_no'");
        mysqli_query($conn, "DELETE FROM dependents WHERE employee_number = '$emp_no'");
        mysqli_query($conn, "DELETE FROM education WHERE employee_number = '$emp_no'");
        mysqli_query($conn, "DELETE FROM character_references WHERE employee_number = '$emp_no'");
        mysqli_query($conn, "DELETE FROM employee_positions WHERE employee_number = '$emp_no'");

        $emg_name = mysqli_real_escape_string($conn, $_POST['emg_name'] ?? '');
        $emg_rel = mysqli_real_escape_string($conn, $_POST['emg_rel'] ?? '');
        $emg_addr = mysqli_real_escape_string($conn, $_POST['emg_addr'] ?? '');
        $emg_con = mysqli_real_escape_string($conn, $_POST['emg_con'] ?? '');
        mysqli_query($conn, "INSERT INTO emergency_contacts (employee_number, name, relationship, address, contact) VALUES ('$emp_no', '$emg_name', '$emg_rel', '$emg_addr', '$emg_con')");
        
        if(isset($_POST['dep_name'])) {
            foreach($_POST['dep_name'] as $key => $val) { if(!empty($val)) {
                $d_name = mysqli_real_escape_string($conn, $val); $d_bdate = mysqli_real_escape_string($conn, $_POST['dep_bdate'][$key]); $d_rel = mysqli_real_escape_string($conn, $_POST['dep_rel'][$key]);
                mysqli_query($conn, "INSERT INTO dependents (employee_number, name, birth_date, relationship) VALUES ('$emp_no', '$d_name', '$d_bdate', '$d_rel')");
            }}
        }
        if(isset($_POST['edu_school'])) {
            foreach($_POST['edu_school'] as $key => $val) { if(!empty($val)) {
                $e_sch = mysqli_real_escape_string($conn, $val); $e_adr = mysqli_real_escape_string($conn, $_POST['edu_addr'][$key]); $e_sy = mysqli_real_escape_string($conn, $_POST['edu_sy'][$key]); $e_lvl = mysqli_real_escape_string($conn, $_POST['edu_level'][$key]);
                mysqli_query($conn, "INSERT INTO education (employee_number, school, address, sy, level) VALUES ('$emp_no', '$e_sch', '$e_adr', '$e_sy', '$e_lvl')");
            }}
        }
        
        // Dynamic insert para sa character_references (sumusuporta sa 'address' o 'ref_address')
        if(isset($_POST['ref_name'])) {
            $col_check = mysqli_query($conn, "SHOW COLUMNS FROM character_references LIKE 'address'");
            $addr_field = (mysqli_num_rows($col_check) > 0) ? 'address' : 'ref_address';

            foreach($_POST['ref_name'] as $key => $val) { if(!empty($val)) {
                $r_nm = mysqli_real_escape_string($conn, $val); 
                $r_oc = mysqli_real_escape_string($conn, $_POST['ref_occ'][$key]); 
                $r_ad = mysqli_real_escape_string($conn, $_POST['ref_addr'][$key] ?? ''); 
                $r_cn = mysqli_real_escape_string($conn, $_POST['ref_con'][$key]);
                mysqli_query($conn, "INSERT INTO character_references (employee_number, name, occupation, $addr_field, contact) VALUES ('$emp_no', '$r_nm', '$r_oc', '$r_ad', '$r_cn')");
            }}
        }
        
        mysqli_query($conn, "INSERT INTO employee_positions (employee_number, department, position, start_date, end_date, monthly, daily, hourly) VALUES ('$emp_no', '{$_POST['dept']}', '{$_POST['pos']}', '{$_POST['start_date']}', '{$_POST['end_date']}', '{$_POST['monthly']}', '{$_POST['daily']}', '{$_POST['hourly']}')");
        
        echo "<script>alert('Employee Saved Successfully!'); window.location='employee.php';</script>";
        exit;
    } else { 
        echo "Error: " . mysqli_error($conn); 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management System | LCC Admin</title>
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
        .navbar-custom .nav-link:hover, .navbar-custom .nav-link.text-primary { 
            color: var(--primary) !important;
            background-color: var(--primary-light);
        }

        .header-card {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            border-radius: var(--radius-lg);
            padding: 22px 28px;
            color: white;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-card h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-card p {
            margin: 4px 0 0 0;
            font-size: 0.82rem;
            opacity: 0.85;
        }

        .toolbar { 
            background: #ffffff; 
            padding: 12px 20px; 
            border-radius: var(--radius-lg); 
            margin-bottom: 22px; 
            display: flex; 
            flex-wrap: wrap;
            justify-content: flex-start; 
            gap: 10px; 
            box-shadow: var(--shadow-sm); 
            border: 1px solid var(--border-color); 
        }
        .btn-tool { 
            background: #ffffff; 
            color: #334155; 
            border: 1px solid var(--border-color); 
            font-size: 0.82rem; 
            padding: 8px 18px; 
            font-weight: 600; 
            border-radius: 8px; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            cursor: pointer;
        }
        .btn-tool:hover { 
            background: #f8fafc; 
            color: var(--primary); 
            border-color: #cbd5e1; 
            transform: translateY(-1px); 
            box-shadow: var(--shadow-sm);
        }

        .nav-tabs { 
            border-bottom: none; 
            gap: 6px; 
            padding: 0 4px;
        }
        .nav-tabs .nav-link { 
            color: #64748b; 
            background: #e2e8f0; 
            border: 1px solid transparent; 
            border-radius: 10px 10px 0 0; 
            padding: 10px 22px; 
            font-weight: 600; 
            font-size: 0.82rem;
            transition: all 0.2s ease; 
        }
        .nav-tabs .nav-link:hover {
            color: var(--primary);
            background: #edf2f7;
        }
        .nav-tabs .nav-link.active { 
            background: #ffffff !important; 
            color: var(--primary) !important; 
            border: 1px solid var(--border-color);
            border-bottom: 2px solid #ffffff !important; 
            position: relative; 
            top: 1px; 
            box-shadow: 0 -2px 5px rgba(0,0,0,0.02);
        }

        .tab-content { 
            background: #ffffff; 
            padding: 32px 30px; 
            border-radius: 0 var(--radius-lg) var(--radius-lg) var(--radius-lg); 
            min-height: 480px; 
            border: 1px solid var(--border-color); 
            box-shadow: var(--shadow-lg); 
        }

        .form-label { 
            font-weight: 600; 
            color: #475569; 
            margin-bottom: 6px; 
            font-size: 0.76rem; 
            text-transform: uppercase; 
            letter-spacing: 0.4px;
        }
        .form-control, .form-select {
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 0.88rem;
            color: #1e293b;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3.5px rgba(37, 99, 235, 0.12);
            outline: none;
            background-color: #ffffff;
        }

        .profile-photo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 12px;
        }
        #preview_img {
            width: 130px; 
            height: 130px; 
            object-fit: cover; 
            border-radius: 16px; 
            border: 3px solid #f1f5f9; 
            box-shadow: var(--shadow-md);
            transition: all 0.2s ease;
        }
        #preview_img:hover {
            border-color: #93c5fd;
            transform: scale(1.02);
        }

        .data-table { 
            width: 100%; 
            margin-top: 16px; 
            border-radius: 10px; 
            overflow: hidden; 
            border: 1px solid var(--border-color); 
            border-collapse: separate;
            border-spacing: 0;
        }
        .data-table th { 
            background: #f1f5f9; 
            color: #334155; 
            padding: 12px 16px; 
            font-size: 0.76rem; 
            font-weight: 700;
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color); 
        }
        .data-table td { 
            background: #ffffff; 
            color: #475569; 
            padding: 12px 16px; 
            font-size: 0.85rem;
            border-bottom: 1px solid #f1f5f9; 
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }
        .data-table tr:hover td {
            background-color: #f8fafc;
        }

        .btn-add { 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 9px 18px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 0.82rem;
            transition: all 0.2s ease; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 42px;
        }
        .btn-add:hover { 
            background: var(--primary-dark); 
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
            transform: translateY(-1px);
        }

        .modal-content {
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 16px 24px;
        }
        .table-hover tbody tr:hover {
            background-color: #f8fafc;
        }

        @media print {
            body * { visibility: hidden; }
            #printArea, #printArea * { visibility: visible; }
            #printArea { position: absolute; left: 0; top: 0; width: 100%; background: white; padding: 25px; }
            .modal-header, .modal-footer, .btn-close { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="profiles/logo.png" alt="LCC Logo" style="height: 38px; width: auto; border-radius: 6px;">
            <span>LCC ADMIN</span>
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto ms-4 gap-1">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> DASHBOARD</a></li>
                <li class="nav-item"><a class="nav-link" href="employee.php"><i class="fas fa-users me-1"></i> EMPLOYEES</a></li>
                <li class="nav-item"><a class="nav-link" href="dept.php"><i class="fas fa-sitemap me-1"></i> DEP_POS</a></li>
                <li class="nav-item"><a class="nav-link" href="loans.php"><i class="fas fa-hand-holding-usd me-1"></i> LOANS</a></li>
                <li class="nav-item"><a class="nav-link" href="payroll.php"><i class="fas fa-file-invoice-dollar me-1"></i> PAYROLL</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-line me-1"></i> REPORTS</a></li>
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

<div class="container-fluid mt-4 px-4 pb-5">
    <!-- Header Banner -->
    <div class="header-card">
        <div>
            <h4><i class="fas fa-user-gear"></i> Employee Information Management</h4>
            <p>Maintain, update, and review masterlist employee credentials and assignments.</p>
        </div>
        <div class="d-none d-md-block text-end">
            <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-semibold shadow-sm">
                <i class="far fa-calendar-alt me-1"></i> Active Record View
            </span>
        </div>
    </div>
    
    <form method="POST" action="employee.php" id="employeeForm" enctype="multipart/form-data">
        <!-- Floating Toolbar -->
        <div class="toolbar">
            <a href="employee.php" class="btn-tool"><i class="fas fa-plus-circle text-primary"></i> NEW</a>
            <button type="submit" name="save_employee" class="btn-tool"><i class="fas fa-check-circle text-success"></i> SAVE</button>
            <button type="reset" class="btn-tool"><i class="fas fa-undo-alt text-warning"></i> CANCEL</button>
            <button type="button" class="btn-tool" data-bs-toggle="modal" data-bs-target="#viewModal"><i class="fas fa-print text-info"></i> PRINT</button>
            <button type="button" class="btn-tool" data-bs-toggle="modal" data-bs-target="#viewModal"><i class="fas fa-table-list text-secondary"></i> VIEW</button>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal"><i class="fas fa-id-card me-1"></i> PERSONAL INFO</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#emergency"><i class="fas fa-phone-volume me-1"></i> EMERGENCY</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#dependent"><i class="fas fa-users-rectangle me-1"></i> DEPENDENTS</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#education"><i class="fas fa-graduation-cap me-1"></i> EDUCATION</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#reference"><i class="fas fa-user-check me-1"></i> REFERENCES</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#position"><i class="fas fa-briefcase me-1"></i> POSITION</button></li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content shadow-sm">
            <!-- PERSONAL INFORMATION -->
            <div class="tab-pane fade show active" id="personal">
                <div class="row g-3">
                    <div class="col-md-2 text-center border-end pe-3">
                        <div id="preview_container" class="profile-photo-wrapper">
                            <img id="preview_img" src="profiles/default.png" alt="Profile Picture">
                        </div>
                        <input type="file" id="profile_picture" name="profile_picture" class="form-control form-control-sm" accept="image/*">
                        <small class="text-muted d-block mt-2">Recommended: Square format</small>
                    </div>
                    
                    <div class="col-md-10">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Employee Number</label><input type="text" name="employee_number" id="inp_emp_no" class="form-control" placeholder="e.g. 24-001" required></div>
                            <div class="col-md-4"><label class="form-label">Portal Password</label><input type="password" name="portal_password" id="inp_password" class="form-control" placeholder="••••••••"></div>
                            <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" id="inp_gender" class="form-select"><option>Male</option><option>Female</option></select></div>
                            
                            <div class="col-md-4"><label class="form-label">Last Name</label><input type="text" name="last_name" id="inp_last_name" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">First Name</label><input type="text" name="first_name" id="inp_first_name" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Middle Name</label><input type="text" name="middle_name" id="inp_middle_name" class="form-control"></div>
                            
                            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" id="inp_email" class="form-control" placeholder="example@lcc.edu.ph"></div>
                            <div class="col-md-4"><label class="form-label">Contact No</label><input type="text" name="contact_no" id="inp_contact_no" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Birth Date</label><input type="date" name="birth_date" id="inp_birth_date" class="form-control"></div>
                            
                            <div class="col-md-4"><label class="form-label">Civil Status</label><input type="text" name="civil_status" id="inp_civil_status" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Nationality</label><input type="text" name="nationality" id="inp_nationality" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Religion</label><input type="text" name="religion" id="inp_religion" class="form-control"></div>
                            
                            <div class="col-md-6"><label class="form-label">Permanent Address</label><input type="text" name="perm_address" id="inp_perm_address" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Present Address</label><input type="text" name="pres_address" id="inp_pres_address" class="form-control"></div>
                            
                            <div class="col-md-3"><label class="form-label">SSS No</label><input type="text" name="sss_no" id="inp_sss_no" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">PhilHealth</label><input type="text" name="philhealth_no" id="inp_philhealth_no" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Pag-Ibig</label><input type="text" name="pagibig_no" id="inp_pagibig_no" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">TIN No</label><input type="text" name="tin_no" id="inp_tin_no" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">ATM No</label><input type="text" name="atm_no" id="inp_atm_no" class="form-control"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMERGENCY CONTACT -->
            <div class="tab-pane fade" id="emergency">
                <div class="p-2 mb-3 bg-light rounded-3 border ps-3">
                    <span class="text-secondary fw-semibold small"><i class="fas fa-info-circle text-primary me-1"></i> Designated primary contact in case of emergency</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Contact Person Name</label><input type="text" name="emg_name" id="inp_emg_name" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Relationship</label><input type="text" name="emg_rel" id="inp_emg_rel" class="form-control"></div>
                    <div class="col-md-5"><label class="form-label">Address</label><input type="text" name="emg_addr" id="inp_emg_addr" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Contact No</label><input type="text" name="emg_con" id="inp_emg_con" class="form-control"></div>
                </div>
            </div>

            <!-- DEPENDENTS -->
            <div class="tab-pane fade" id="dependent">
                <div class="bg-light p-3 rounded-3 border mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4"><label class="form-label">Dependent Name</label><input type="text" id="d_name" class="form-control" placeholder="Full name"></div>
                        <div class="col-md-3"><label class="form-label">Birth Date</label><input type="date" id="d_bdate" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Relationship</label><input type="text" id="d_rel" class="form-control" placeholder="e.g. Child / Spouse"></div>
                        <div class="col-md-2"><button type="button" class="btn-add w-100" onclick="addRow('dependent')"><i class="fas fa-plus"></i> ADD ROW</button></div>
                    </div>
                </div>
                <table class="data-table" id="tbl_dependent">
                    <thead><tr><th width="40%">Name</th><th width="30%">Birth Date</th><th width="30%">Relationship</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- EDUCATION -->
            <div class="tab-pane fade" id="education">
                <div class="bg-light p-3 rounded-3 border mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4"><label class="form-label">School Name</label><input type="text" id="e_school" class="form-control" placeholder="School Name"></div>
                        <div class="col-md-4"><label class="form-label">School Year</label><input type="text" id="e_sy" class="form-control" placeholder="e.g. 2018-2022"></div>
                        <div class="col-md-4"><label class="form-label">Education Level</label><select id="e_level" class="form-select"><option>Primary</option><option>Secondary</option><option>Tertiary</option><option>Post-Grad</option></select></div>
                        <div class="col-md-10"><label class="form-label">School Address</label><input type="text" id="e_addr" class="form-control" placeholder="Complete School Address"></div>
                        <div class="col-md-2"><button type="button" class="btn-add w-100" onclick="addRow('education')"><i class="fas fa-plus"></i> ADD ROW</button></div>
                    </div>
                </div>
                <table class="data-table" id="tbl_education">
                    <thead><tr><th width="30%">School</th><th width="40%">Address</th><th width="15%">S.Y.</th><th width="15%">Level</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- REFERENCES -->
            <div class="tab-pane fade" id="reference">
                <div class="bg-light p-3 rounded-3 border mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4"><label class="form-label">Full Name</label><input type="text" id="r_name" class="form-control" placeholder="Reference Person Name"></div>
                        <div class="col-md-4"><label class="form-label">Occupation</label><input type="text" id="r_occ" class="form-control" placeholder="Occupation / Position"></div>
                        <div class="col-md-4"><label class="form-label">Contact No</label><input type="text" id="r_con" class="form-control" placeholder="Mobile / Tel No."></div>
                        <div class="col-md-10"><label class="form-label">Address</label><input type="text" id="r_addr" class="form-control" placeholder="Complete Address of Reference"></div>
                        <div class="col-md-2"><button type="button" class="btn-add w-100" onclick="addRow('reference')"><i class="fas fa-plus"></i> ADD ROW</button></div>
                    </div>
                </div>
                <table class="data-table" id="tbl_reference">
                    <thead><tr><th width="25%">Name</th><th width="20%">Occupation</th><th width="35%">Address</th><th width="20%">Contact</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- POSITION -->
            <div class="tab-pane fade" id="position">
                <div class="bg-light p-3 rounded-3 border mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3"><label class="form-label">Department</label><select id="p_dept" class="form-select">
                            <option value="">-- Select Department --</option>
                            <?php 
                            $dept_res = mysqli_query($conn, "SELECT id, dept_name FROM departments ORDER BY dept_name ASC");
                            while($dept = mysqli_fetch_assoc($dept_res)) { echo "<option value='{$dept['dept_name']}'>{$dept['dept_name']}</option>"; }
                            ?>
                        </select></div>
                        <div class="col-md-3"><label class="form-label">Position</label><select id="p_pos" class="form-select">
                            <option value="">-- Select Position --</option>
                            <?php 
                            $pos_res = mysqli_query($conn, "SELECT id, pos_name FROM positions ORDER BY pos_name ASC");
                            while($pos = mysqli_fetch_assoc($pos_res)) { echo "<option value='{$pos['pos_name']}'>{$pos['pos_name']}</option>"; }
                            ?>
                        </select></div>
                        <div class="col-md-2"><label class="form-label">Start Date</label><input type="date" id="p_start_date" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">End Date</label><input type="date" id="p_end_date" class="form-control"></div>
                        <div class="col-md-2"><button type="button" class="btn-add w-100" onclick="addRow('position')"><i class="fas fa-check"></i> SET POSITION</button></div>
                        
                        <div class="col-md-4 mt-2"><label class="form-label">Monthly Rate (₱)</label><input type="number" step="0.01" id="p_monthly" class="form-control" oninput="calculateRates('monthly')"></div>
                        <div class="col-md-4 mt-2"><label class="form-label">Daily Rate (₱)</label><input type="number" step="0.01" id="p_daily" class="form-control" oninput="calculateRates('daily')"></div>
                        <div class="col-md-4 mt-2"><label class="form-label">Hourly Rate (₱)</label><input type="number" step="0.01" id="p_hourly" class="form-control" oninput="calculateRates('hourly')"></div>
                    </div>
                </div>
                <table class="data-table" id="tbl_position">
                    <thead><tr><th width="25%">Dept</th><th width="25%">Position</th><th width="25%">Duration</th><th width="25%">Rates Computation</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-database me-2"></i>Master List Records</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light text-primary">
                            <tr><th>Emp ID</th><th>Full Name</th><th>Department</th><th>Position</th><th class="text-center" width="230">Action</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $res = mysqli_query($conn, "SELECT e.*, p.department, p.position FROM employees e LEFT JOIN employee_positions p ON e.employee_number = p.employee_number ORDER BY last_name ASC");
                            while($r = mysqli_fetch_assoc($res)) {
                                echo "<tr>
                                    <td class='fw-bold text-dark'>{$r['employee_number']}</td>
                                    <td class='fw-semibold'>{$r['last_name']}, {$r['first_name']}</td>
                                    <td><span class='badge bg-light text-primary border'>{$r['department']}</span></td>
                                    <td>{$r['position']}</td>
                                    <td class='text-center'>
                                        <button class='btn btn-sm btn-outline-primary me-1' onclick='fetchForPrint(\"{$r['employee_number']}\")' title='View & Print Record'><i class='fas fa-eye me-1'></i> View</button>
                                        <button class='btn btn-sm btn-outline-success me-1' onclick='loadEmployeeToForm(\"{$r['employee_number']}\")' title='Load into Form'><i class='fas fa-pen-to-square me-1'></i> Edit</button>
                                        <button class='btn btn-sm btn-outline-danger' onclick='deleteEmployeeFromTable(\"{$r['employee_number']}\")' title='Delete Record'><i class='fas fa-trash me-1'></i></button>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PRINT MODAL -->
<div class="modal fade" id="printModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-semibold"><i class="fas fa-file-invoice me-2"></i>Employee Document Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5" id="printArea">
                <div id="printBody"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary px-4" onclick="window.print()"><i class="fas fa-print me-2"></i>PRINT DOCUMENT</button>
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>

<script>
// Profile Picture Preview logic
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if(file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('preview_img').src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Auto-compute Rates logic
function calculateRates(source) {
    let m = document.getElementById('p_monthly');
    let d = document.getElementById('p_daily');
    let h = document.getElementById('p_hourly');

    if (source === 'monthly') {
        let val = parseFloat(m.value) || 0;
        d.value = (val / 26).toFixed(2);
        h.value = ((val / 26) / 8).toFixed(2);
    } else if (source === 'daily') {
        let val = parseFloat(d.value) || 0;
        m.value = (val * 26).toFixed(2);
        h.value = (val / 8).toFixed(2);
    } else if (source === 'hourly') {
        let val = parseFloat(h.value) || 0;
        d.value = (val * 8).toFixed(2);
        m.value = ((val * 8) * 26).toFixed(2);
    }
}

function addRow(type) {
    let html = "";
    if(type === 'dependent') {
        let n = document.getElementById('d_name').value, b = document.getElementById('d_bdate').value, r = document.getElementById('d_rel').value;
        if(!n) return;
        html = `<tr><td>${n}<input type="hidden" name="dep_name[]" value="${n}"></td><td>${b}<input type="hidden" name="dep_bdate[]" value="${b}"></td><td>${r}<input type="hidden" name="dep_rel[]" value="${r}"></td></tr>`;
        document.getElementById('tbl_dependent').querySelector('tbody').insertAdjacentHTML('beforeend', html);
        document.getElementById('d_name').value = '';
        document.getElementById('d_bdate').value = '';
        document.getElementById('d_rel').value = '';
    } else if(type === 'education') {
        let s = document.getElementById('e_school').value, a = document.getElementById('e_addr').value, y = document.getElementById('e_sy').value, l = document.getElementById('e_level').value;
        if(!s) return;
        html = `<tr><td>${s}<input type="hidden" name="edu_school[]" value="${s}"></td><td>${a}<input type="hidden" name="edu_addr[]" value="${a}"></td><td>${y}<input type="hidden" name="edu_sy[]" value="${y}"></td><td>${l}<input type="hidden" name="edu_level[]" value="${l}"></td></tr>`;
        document.getElementById('tbl_education').querySelector('tbody').insertAdjacentHTML('beforeend', html);
        document.getElementById('e_school').value = '';
        document.getElementById('e_addr').value = '';
        document.getElementById('e_sy').value = '';
    } else if(type === 'reference') {
        let n = document.getElementById('r_name').value.trim(), 
            o = document.getElementById('r_occ').value.trim(), 
            a = document.getElementById('r_addr').value.trim(), 
            c = document.getElementById('r_con').value.trim();
        if(!n) {
            alert('Please enter reference name.');
            return;
        }
        html = `<tr>
            <td>${n}<input type="hidden" name="ref_name[]" value="${n}"></td>
            <td>${o}<input type="hidden" name="ref_occ[]" value="${o}"></td>
            <td>${a}<input type="hidden" name="ref_addr[]" value="${a}"></td>
            <td>${c}<input type="hidden" name="ref_con[]" value="${c}"></td>
        </tr>`;
        document.getElementById('tbl_reference').querySelector('tbody').insertAdjacentHTML('beforeend', html);
        document.getElementById('r_name').value = '';
        document.getElementById('r_occ').value = '';
        document.getElementById('r_addr').value = '';
        document.getElementById('r_con').value = '';
    } else if(type === 'position') {
        let d = document.getElementById('p_dept').value, p = document.getElementById('p_pos').value, sd = document.getElementById('p_start_date').value, ed = document.getElementById('p_end_date').value, m = document.getElementById('p_monthly').value, dy = document.getElementById('p_daily').value, h = document.getElementById('p_hourly').value;
        if(!d || !p) return;
        html = `<tr><td>${d}<input type="hidden" name="dept" value="${d}"></td><td>${p}<input type="hidden" name="pos" value="${p}"></td><td>${sd} - ${ed}<input type="hidden" name="start_date" value="${sd}"><input type="hidden" name="end_date" value="${ed}"></td><td>M: ₱${m} | D: ₱${dy} | H: ₱${h}<input type="hidden" name="monthly" value="${m}"><input type="hidden" name="daily" value="${dy}"><input type="hidden" name="hourly" value="${h}"></td></tr>`;
        document.getElementById('tbl_position').querySelector('tbody').innerHTML = html;
    }
}

// Function para kunin ang address ng reference kahit ano pa ang column name sa database
function getRefAddressValue(r) {
    if(!r) return '';
    if(r.address && r.address !== '') return r.address;
    if(r.ref_address && r.ref_address !== '') return r.ref_address;
    if(r.addr && r.addr !== '') return r.addr;
    if(r.ref_addr && r.ref_addr !== '') return r.ref_addr;
    for(let k in r) {
        if(k.toLowerCase().includes('addr') && r[k]) {
            return r[k];
        }
    }
    return '';
}

// VIEW & PRINT PREVIEW (DIRECT DATABASE DATA)
function fetchForPrint(empId) {
    fetch('employee.php?get_details=' + empId)
    .then(r => r.json())
    .then(data => {
        let e = data.emp || {};
        let p = data.pos || {};
        let emg = data.emg || {};
        
        let photoUrl = (e.photo && e.photo !== '') ? e.photo + '?t=' + new Date().getTime() : 'profiles/default.png';
        let profileImg = `<img src="${photoUrl}" style="width: 130px; height: 130px; border-radius: 12px; object-fit: cover; border: 3px solid #2563eb; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">`;

        let h = `
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="font-weight: 800; color: #1e3a8a; margin-bottom: 2px;">OFFICIAL EMPLOYEE RECORD</h3>
                <p style="font-size: 0.8rem; color: #64748b; letter-spacing: 1px; text-transform: uppercase;">CONFIDENTIAL • HR SYSTEM MASTER FILE</p>
                <div style="height: 2px; background: #e2e8f0; width: 100%; margin: 15px 0 20px 0;"></div>
                ${profileImg}
            </div>
            
            <table class="table table-bordered align-middle" style="font-size: 0.82rem; border-color: #cbd5e1;">
                <tr class="table-light"><th colspan="4" style="color: #1e3a8a; background: #f1f5f9;"><b>I. PERSONAL INFORMATION</b></th></tr>
                <tr>
                    <td width="20%"><b>Employee No:</b></td><td width="30%" class="fw-bold text-primary">${e.employee_number || ''}</td>
                    <td width="20%"><b>Portal Pass:</b></td><td width="30%">••••••••</td>
                </tr>
                <tr>
                    <td><b>Full Name:</b></td><td colspan="3" class="fw-semibold">${e.last_name || ''}, ${e.first_name || ''} ${e.middle_name || ''}</td>
                </tr>
                <tr>
                    <td><b>Gender:</b></td><td>${e.gender || ''}</td>
                    <td><b>Birth Date:</b></td><td>${e.birth_date || ''}</td>
                </tr>
                <tr>
                    <td><b>Civil Status:</b></td><td>${e.civil_status || ''}</td>
                    <td><b>Nationality:</b></td><td>${e.nationality || ''}</td>
                </tr>
                <tr>
                    <td><b>Religion:</b></td><td>${e.religion || ''}</td>
                    <td><b>Email:</b></td><td>${e.email || ''}</td>
                </tr>
                <tr>
                    <td><b>Contact No:</b></td><td colspan="3">${e.contact_no || ''}</td>
                </tr>
                <tr>
                    <td><b>Permanent Address:</b></td><td colspan="3">${e.perm_address || ''}</td>
                </tr>
                <tr>
                    <td><b>Present Address:</b></td><td colspan="3">${e.pres_address || ''}</td>
                </tr>
                
                <tr class="table-light"><th colspan="4" style="color: #1e3a8a; background: #f1f5f9;"><b>II. GOVERNMENT IDENTIFICATION</b></th></tr>
                <tr>
                    <td><b>SSS No:</b></td><td>${e.sss_no || ''}</td>
                    <td><b>PhilHealth:</b></td><td>${e.philhealth_no || ''}</td>
                </tr>
                <tr>
                    <td><b>Pag-Ibig:</b></td><td>${e.pagibig_no || ''}</td>
                    <td><b>TIN No:</b></td><td>${e.tin_no || ''}</td>
                </tr>
                <tr>
                    <td><b>ATM No:</b></td><td colspan="3">${e.atm_no || ''}</td>
                </tr>

                <tr class="table-light"><th colspan="4" style="color: #1e3a8a; background: #f1f5f9;"><b>III. EMERGENCY CONTACT</b></th></tr>
                <tr>
                    <td><b>Contact Person:</b></td><td>${emg.name || ''}</td>
                    <td><b>Relationship:</b></td><td>${emg.relationship || ''}</td>
                </tr>
                <tr>
                    <td><b>Address:</b></td><td>${emg.address || ''}</td>
                    <td><b>Contact No:</b></td><td>${emg.contact || ''}</td>
                </tr>

                <tr class="table-light"><th colspan="4" style="color: #1e3a8a; background: #f1f5f9;"><b>IV. POSITION & ASSIGNMENT</b></th></tr>
                <tr>
                    <td><b>Department:</b></td><td>${p.department || ''}</td>
                    <td><b>Position:</b></td><td>${p.position || ''}</td>
                </tr>
                <tr>
                    <td><b>Start Date:</b></td><td>${p.start_date || ''}</td>
                    <td><b>End Date:</b></td><td>${p.end_date || ''}</td>
                </tr>
                <tr>
                    <td><b>Monthly Rate:</b></td><td>₱${p.monthly ? parseFloat(p.monthly).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'}</td>
                    <td><b>Daily Rate:</b></td><td>₱${p.daily ? parseFloat(p.daily).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'}</td>
                </tr>
                <tr>
                    <td><b>Hourly Rate:</b></td><td colspan="3">₱${p.hourly ? parseFloat(p.hourly).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'}</td>
                </tr>
            </table>

            <h6 class="mt-4 fw-bold text-dark" style="font-size: 0.85rem;"><i class="fas fa-users-rectangle text-primary me-2"></i>V. DEPENDENTS</h6>
            <table class="table table-sm table-bordered" style="font-size: 0.8rem; border-color: #cbd5e1;">
                <thead><tr class="table-light"><th>Name</th><th>Birth Date</th><th>Relationship</th></tr></thead>
                <tbody>${data.deps && data.deps.length > 0 ? data.deps.map(d => `<tr><td>${d.name || ''}</td><td>${d.birth_date || ''}</td><td>${d.relationship || ''}</td></tr>`).join('') : '<tr><td colspan="3" class="text-center text-muted">No records registered</td></tr>'}</tbody>
            </table>

            <h6 class="mt-4 fw-bold text-dark" style="font-size: 0.85rem;"><i class="fas fa-graduation-cap text-primary me-2"></i>VI. EDUCATIONAL ATTAINMENT</h6>
            <table class="table table-sm table-bordered" style="font-size: 0.8rem; border-color: #cbd5e1;">
                <thead><tr class="table-light"><th>School</th><th>Address</th><th>S.Y.</th><th>Level</th></tr></thead>
                <tbody>${data.edus && data.edus.length > 0 ? data.edus.map(ed => `<tr><td>${ed.school || ''}</td><td>${ed.address || ''}</td><td>${ed.sy || ''}</td><td>${ed.level || ''}</td></tr>`).join('') : '<tr><td colspan="4" class="text-center text-muted">No records registered</td></tr>'}</tbody>
            </table>

            <h6 class="mt-4 fw-bold text-dark" style="font-size: 0.85rem;"><i class="fas fa-user-check text-primary me-2"></i>VII. CHARACTER REFERENCES</h6>
            <table class="table table-sm table-bordered" style="font-size: 0.8rem; border-color: #cbd5e1;">
                <thead><tr class="table-light"><th>Name</th><th>Occupation</th><th>Address</th><th>Contact</th></tr></thead>
                <tbody>${data.refs && data.refs.length > 0 ? data.refs.map(r => {
                    let actualAddr = getRefAddressValue(r);
                    return `<tr>
                        <td>${r.name || ''}</td>
                        <td>${r.occupation || ''}</td>
                        <td>${actualAddr}</td>
                        <td>${r.contact || ''}</td>
                    </tr>`;
                }).join('') : '<tr><td colspan="4" class="text-center text-muted">No records registered</td></tr>'}</tbody>
            </table>
        `;
        document.getElementById('printBody').innerHTML = h;
        new bootstrap.Modal(document.getElementById('printModal')).show();
    });
}

// I-LOAD ANG RECORD DIRETSO SA FORM MULA SA DATABASE
function loadEmployeeToForm(empId) {
    fetch('employee.php?get_details=' + empId)
    .then(r => r.json())
    .then(data => {
        let e = data.emp || {};
        let emg = data.emg || {};
        let p = data.pos || {};

        document.getElementById('inp_emp_no').value = e.employee_number || '';
        document.getElementById('inp_password').value = e.portal_password || '';
        document.getElementById('inp_gender').value = e.gender || 'Male';
        document.getElementById('inp_last_name').value = e.last_name || '';
        document.getElementById('inp_first_name').value = e.first_name || '';
        document.getElementById('inp_middle_name').value = e.middle_name || '';
        document.getElementById('inp_email').value = e.email || '';
        document.getElementById('inp_contact_no').value = e.contact_no || '';
        document.getElementById('inp_birth_date').value = e.birth_date || '';
        document.getElementById('inp_civil_status').value = e.civil_status || '';
        document.getElementById('inp_nationality').value = e.nationality || '';
        document.getElementById('inp_religion').value = e.religion || '';
        document.getElementById('inp_perm_address').value = e.perm_address || '';
        document.getElementById('inp_pres_address').value = e.pres_address || '';
        document.getElementById('inp_sss_no').value = e.sss_no || '';
        document.getElementById('inp_philhealth_no').value = e.philhealth_no || '';
        document.getElementById('inp_pagibig_no').value = e.pagibig_no || '';
        document.getElementById('inp_tin_no').value = e.tin_no || '';
        document.getElementById('inp_atm_no').value = e.atm_no || '';

        if(e.photo && e.photo !== '') {
            document.getElementById('preview_img').src = e.photo + '?t=' + new Date().getTime();
        } else {
            document.getElementById('preview_img').src = 'profiles/default.png';
        }

        // Emergency
        document.getElementById('inp_emg_name').value = emg.name || '';
        document.getElementById('inp_emg_rel').value = emg.relationship || '';
        document.getElementById('inp_emg_addr').value = emg.address || '';
        document.getElementById('inp_emg_con').value = emg.contact || '';

        // Dependents
        let depTbody = document.getElementById('tbl_dependent').querySelector('tbody');
        depTbody.innerHTML = '';
        if(data.deps && data.deps.length > 0) {
            data.deps.forEach(d => {
                depTbody.insertAdjacentHTML('beforeend', `<tr><td>${d.name}<input type="hidden" name="dep_name[]" value="${d.name}"></td><td>${d.birth_date}<input type="hidden" name="dep_bdate[]" value="${d.birth_date}"></td><td>${d.relationship}<input type="hidden" name="dep_rel[]" value="${d.relationship}"></td></tr>`);
            });
        }

        // Education
        let eduTbody = document.getElementById('tbl_education').querySelector('tbody');
        eduTbody.innerHTML = '';
        if(data.edus && data.edus.length > 0) {
            data.edus.forEach(ed => {
                eduTbody.insertAdjacentHTML('beforeend', `<tr><td>${ed.school}<input type="hidden" name="edu_school[]" value="${ed.school}"></td><td>${ed.address}<input type="hidden" name="edu_addr[]" value="${ed.address}"></td><td>${ed.sy}<input type="hidden" name="edu_sy[]" value="${ed.sy}"></td><td>${ed.level}<input type="hidden" name="edu_level[]" value="${ed.level}"></td></tr>`);
            });
        }

        // Character References (Kasama ang tunay na Address mula DB)
        let refTbody = document.getElementById('tbl_reference').querySelector('tbody');
        refTbody.innerHTML = '';
        if(data.refs && data.refs.length > 0) {
            data.refs.forEach(r => {
                let actualAddr = getRefAddressValue(r);
                refTbody.insertAdjacentHTML('beforeend', `<tr>
                    <td>${r.name}<input type="hidden" name="ref_name[]" value="${r.name}"></td>
                    <td>${r.occupation}<input type="hidden" name="ref_occ[]" value="${r.occupation}"></td>
                    <td>${actualAddr}<input type="hidden" name="ref_addr[]" value="${actualAddr}"></td>
                    <td>${r.contact}<input type="hidden" name="ref_con[]" value="${r.contact}"></td>
                </tr>`);
            });
        }

        // Position
        let posTbody = document.getElementById('tbl_position').querySelector('tbody');
        posTbody.innerHTML = '';
        if(p.department && p.position) {
            document.getElementById('p_dept').value = p.department;
            document.getElementById('p_pos').value = p.position;
            document.getElementById('p_start_date').value = p.start_date || '';
            document.getElementById('p_end_date').value = p.end_date || '';
            document.getElementById('p_monthly').value = p.monthly || '';
            document.getElementById('p_daily').value = p.daily || '';
            document.getElementById('p_hourly').value = p.hourly || '';

            posTbody.innerHTML = `<tr><td>${p.department}<input type="hidden" name="dept" value="${p.department}"></td><td>${p.position}<input type="hidden" name="pos" value="${p.position}"></td><td>${p.start_date || ''} - ${p.end_date || ''}<input type="hidden" name="start_date" value="${p.start_date || ''}"><input type="hidden" name="end_date" value="${p.end_date || ''}"></td><td>M: ₱${p.monthly || 0} | D: ₱${p.daily || 0} | H: ₱${p.hourly || 0}<input type="hidden" name="monthly" value="${p.monthly || 0}"><input type="hidden" name="daily" value="${p.daily || 0}"><input type="hidden" name="hourly" value="${p.hourly || 0}"></td></tr>`;
        }

        // Isara ang modal at tumungo sa Personal Tab
        let modalEl = document.getElementById('viewModal');
        let modalInstance = bootstrap.Modal.getInstance(modalEl);
        if(modalInstance) modalInstance.hide();

        let firstTab = new bootstrap.Tab(document.querySelector('#myTab button[data-bs-target="#personal"]'));
        firstTab.show();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function deleteEmployeeFromTable(empId) {
    if(confirm('Are you sure you want to permanently delete this employee record?')) {
        fetch('employee.php?delete_employee=' + empId)
        .then(r => r.json())
        .then(data => { if(data.success) location.reload(); });
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>