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

// Siguraduhing may table para sa attendance kung sakaling bago pa ang database
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(50) NOT NULL,
    pay_date DATE NOT NULL,
    daily DECIMAL(10,2) DEFAULT 0.00,
    absences DECIMAL(10,2) DEFAULT 0,
    tardiness DECIMAL(10,2) DEFAULT 0,
    undertime DECIMAL(10,2) DEFAULT 0,
    overtime DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// --- DELETE ATTENDANCE ---
if(isset($_GET['del_att'])) {
    $id = mysqli_real_escape_string($conn, $_GET['del_att']);
    mysqli_query($conn, "DELETE FROM attendance WHERE id = '$id'");
    header('location: attendance.php');
    exit;
}

// --- SAVE ATTENDANCE (ADD & EDIT) ---
if(isset($_POST['save_attendance'])) {
    $att_id    = isset($_POST['att_id']) ? mysqli_real_escape_string($conn, $_POST['att_id']) : '';
    $emp_no    = mysqli_real_escape_string($conn, $_POST['employee_number']);
    $pay_date  = mysqli_real_escape_string($conn, $_POST['pay_date']);
    $daily     = !empty($_POST['daily']) ? floatval($_POST['daily']) : 0.00;
    $absences  = !empty($_POST['absences']) ? floatval($_POST['absences']) : 0;
    $tardiness = !empty($_POST['tardiness']) ? floatval($_POST['tardiness']) : 0;
    $undertime = !empty($_POST['undertime']) ? floatval($_POST['undertime']) : 0;
    $overtime  = !empty($_POST['overtime']) ? floatval($_POST['overtime']) : 0;

    if(!empty($att_id)) {
        // Edit existing
        $sql = "UPDATE attendance SET employee_number='$emp_no', pay_date='$pay_date', daily='$daily', absences='$absences', tardiness='$tardiness', undertime='$undertime', overtime='$overtime' WHERE id='$att_id'";
    } else {
        // Add new
        $sql = "INSERT INTO attendance (employee_number, pay_date, daily, absences, tardiness, undertime, overtime) VALUES ('$emp_no', '$pay_date', '$daily', '$absences', '$tardiness', '$undertime', '$overtime')";
    }

    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Attendance Record Saved Successfully!'); window.location='attendance.php';</script>";
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// Search & Filter Query
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "";
if(!empty($search)) {
    $where = "WHERE e.last_name LIKE '%$search%' OR e.first_name LIKE '%$search%' OR a.employee_number LIKE '%$search%' OR a.pay_date LIKE '%$search%'";
}

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance");
$total_attendance = mysqli_fetch_assoc($count_res)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance | LCC Admin</title>
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

        /* Synchronized Modern Navbar */
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

        /* Header Card Banner */
        .header-card {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            border-radius: var(--radius-lg);
            padding: 22px 28px;
            color: white;
            box-shadow: var(--shadow-md);
            margin-bottom: 22px;
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
            opacity: 0.9;
        }

        /* Toolbar Actions */
        .toolbar { 
            background: #ffffff; 
            padding: 12px 20px; 
            border-radius: var(--radius-lg); 
            margin-bottom: 20px; 
            display: flex; 
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px; 
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
            transition: all 0.2s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            cursor: pointer;
            text-decoration: none;
        }
        .btn-tool:hover { 
            background: #f8fafc; 
            color: var(--teal-custom); 
            border-color: #cbd5e1; 
            transform: translateY(-1px); 
        }

        /* Management Card */
        .mgmt-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        /* Table Styling */
        .table-custom-main {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .table-custom-main thead th {
            background-color: #0f766e;
            color: #ffffff;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 13px 16px;
            border: none;
            text-align: center;
        }
        .table-custom-main tbody td {
            padding: 13px 16px;
            font-size: 0.86rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            text-align: center;
        }
        .table-custom-main tbody tr:hover td {
            background-color: #f8fafc;
        }

        .btn-action-edit {
            color: #0f766e;
            background: #ccfbf1;
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
            background: #99f6e4;
            color: #115e59;
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

        /* Modal Styling */
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
        .form-label {
            font-size: 0.76rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 5px;
        }
        .form-control, .form-select {
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
            outline: none;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top mb-4">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="profiles/logo.png" alt="LCC Logo" style="height: 38px; width: auto; border-radius: 6px;">
            <span>LCC ADMIN</span>
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-4 gap-1">
                <li class="nav-item"><a class="nav-link" href="employee.php"><i class="fas fa-users me-1"></i> Employees</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-sitemap me-1"></i> Dept & Position</a></li>
                <li class="nav-item"><a class="nav-link" href="loans.php"><i class="fas fa-hand-holding-usd me-1"></i> Loans</a></li>
                <li class="nav-item"><a class="nav-link active text-primary" href="attendance.php"><i class="fas fa-user-clock me-1"></i> Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="payroll.php"><i class="fas fa-file-invoice-dollar me-1"></i> Payroll</a></li>
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
    <!-- Header Banner -->
    <div class="header-card">
        <div>
            <h4><i class="fas fa-clipboard-user"></i> EMPLOYEE ATTENDANCE</h4>
            <p>Encode, manage, and verify daily employee attendance, absences, undertime, and overtime.</p>
        </div>
        <div class="d-none d-md-block text-end">
            <span class="badge bg-white text-dark px-3 py-2 rounded-pill fw-bold shadow-sm" style="font-size: 0.95rem;">
                <i class="fas fa-clock-rotate-left text-teal me-1"></i> Total Encoded: <?php echo $total_attendance; ?>
            </span>
        </div>
    </div>

    <!-- Toolbar Actions -->
    <div class="toolbar">
        <div class="d-flex gap-2">
            <button class="btn-tool text-teal fw-bold" onclick="openAttendanceModal()">
                <i class="fas fa-plus-circle text-success"></i> Add New Attendance
            </button>
            <a href="payroll.php" class="btn-tool">
                <i class="fas fa-calendar-check text-primary"></i> Cutoff Schedules
            </a>
        </div>
        
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search employee / date..." value="<?php echo htmlspecialchars($search); ?>" style="width: 220px;">
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i></button>
            <?php if(!empty($search)): ?>
                <a href="attendance.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Attendance Table Card -->
    <div class="mgmt-card">
        <div class="p-4">
            <div class="border rounded-3 overflow-hidden shadow-sm">
                <table class="table-custom-main align-middle">
                    <thead>
                        <tr>
                            <th style="text-align: left; width: 22%;">EMPLOYEE NAME</th>
                            <th width="12%">PAY DATE</th>
                            <th width="10%">DAILY</th>
                            <th width="10%">ABSENCES</th>
                            <th width="11%">TARDINESS</th>
                            <th width="11%">UNDER TIME</th>
                            <th width="11%">OVER TIME</th>
                            <th width="13%">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $query = "SELECT a.*, e.first_name, e.last_name 
                                  FROM attendance a 
                                  LEFT JOIN employees e ON a.employee_number = e.employee_number 
                                  $where 
                                  ORDER BY a.pay_date DESC, a.id DESC";
                        $res = mysqli_query($conn, $query);

                        if(mysqli_num_rows($res) == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-clipboard-list fa-2x mb-3 text-secondary d-block"></i>
                                    No attendance data available in table
                                </td>
                            </tr>
                        <?php endif;
                        
                        while($row = mysqli_fetch_assoc($res)): 
                            $emp_full_name = !empty($row['last_name']) ? "{$row['last_name']}, {$row['first_name']}" : "Emp #{$row['employee_number']}";
                        ?>
                        <tr>
                            <td style="text-align: left;">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($emp_full_name); ?></div>
                                <span class="badge bg-light text-primary border" style="font-size: 0.72rem;"><?php echo htmlspecialchars($row['employee_number']); ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark border px-2.5 py-1 fw-semibold"><?php echo htmlspecialchars($row['pay_date']); ?></span></td>
                            <td class="fw-bold text-success">₱<?php echo number_format($row['daily'], 2); ?></td>
                            <td><span class="<?php echo $row['absences'] > 0 ? 'text-danger fw-bold' : 'text-muted'; ?>"><?php echo htmlspecialchars($row['absences']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['tardiness']); ?></td>
                            <td><?php echo htmlspecialchars($row['undertime']); ?></td>
                            <td><span class="<?php echo $row['overtime'] > 0 ? 'text-primary fw-bold' : 'text-muted'; ?>"><?php echo htmlspecialchars($row['overtime']); ?></span></td>
                            <td>
                                <button type="button" class="btn-action-edit me-1" title="Edit" 
                                        onclick='editAttendance(<?php echo json_encode($row); ?>)'>
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                                <a href="attendance.php?del_att=<?php echo $row['id']; ?>" class="btn-action-del" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')">
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

<!-- MODAL: ADD / EDIT ATTENDANCE -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="attendance.php">
                <input type="hidden" name="att_id" id="att_id" value="">
                
                <div class="modal-header">
                    <h5 class="fw-bold text-teal" id="modalTitle"><i class="fas fa-clock text-teal me-2"></i>EMPLOYEE ATTENDANCE</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <!-- Pay Date -->
                    <div class="mb-3">
                        <label class="form-label">Pay Date</label>
                        <select name="pay_date" id="inp_pay_date" class="form-select" required>
                            <option value="">-- Select Pay Date --</option>
                            <?php 
                            $pay_query = mysqli_query($conn, "SELECT DISTINCT pay_date FROM payroll_periods ORDER BY pay_date DESC");
                            while($p_row = mysqli_fetch_assoc($pay_query)) {
                                echo "<option value='{$p_row['pay_date']}'>{$p_row['pay_date']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Employee Name Dropdown with Auto Daily Rate -->
                    <div class="mb-3">
                        <label class="form-label">Employee Name</label>
                        <select name="employee_number" id="inp_employee" class="form-select" onchange="autoFetchDailyRate()" required>
                            <option value="" data-daily="0.00">-- Select Employee --</option>
                            <?php 
                            $emp_query = mysqli_query($conn, "SELECT e.employee_number, e.last_name, e.first_name, p.daily 
                                                              FROM employees e 
                                                              LEFT JOIN employee_positions p ON e.employee_number = p.employee_number 
                                                              ORDER BY e.last_name ASC");
                            while($e_row = mysqli_fetch_assoc($emp_query)) {
                                $d_rate = !empty($e_row['daily']) ? $e_row['daily'] : '0.00';
                                echo "<option value='{$e_row['employee_number']}' data-daily='{$d_rate}'>{$e_row['last_name']}, {$e_row['first_name']} ({$e_row['employee_number']})</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Daily Rate -->
                    <div class="mb-3">
                        <label class="form-label">Daily (₱)</label>
                        <input type="number" step="0.01" name="daily" id="inp_daily" class="form-control" placeholder="0.00" required>
                    </div>

                    <!-- Absences -->
                    <div class="mb-3">
                        <label class="form-label">Absences</label>
                        <input type="number" step="0.5" name="absences" id="inp_absences" class="form-control" placeholder="0" value="0">
                    </div>

                    <!-- Tardiness -->
                    <div class="mb-3">
                        <label class="form-label">Tardiness (Mins/Hrs)</label>
                        <input type="number" step="0.01" name="tardiness" id="inp_tardiness" class="form-control" placeholder="0" value="0">
                    </div>

                    <!-- Under Time -->
                    <div class="mb-3">
                        <label class="form-label">Under Time (Mins/Hrs)</label>
                        <input type="number" step="0.01" name="undertime" id="inp_undertime" class="form-control" placeholder="0" value="0">
                    </div>

                    <!-- Over Time -->
                    <div class="mb-3">
                        <label class="form-label">Over Time (Hours)</label>
                        <input type="number" step="0.01" name="overtime" id="inp_overtime" class="form-control" placeholder="0" value="0">
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="submit" name="save_attendance" class="btn btn-success text-white px-4 fw-semibold">SAVE</button>
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Buksan ang modal para sa New Attendance
function openAttendanceModal() {
    document.getElementById('att_id').value = '';
    document.getElementById('inp_pay_date').value = '';
    document.getElementById('inp_employee').value = '';
    document.getElementById('inp_daily').value = '';
    document.getElementById('inp_absences').value = '0';
    document.getElementById('inp_tardiness').value = '0';
    document.getElementById('inp_undertime').value = '0';
    document.getElementById('inp_overtime').value = '0';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-clock text-teal me-2"></i>ADD EMPLOYEE ATTENDANCE';
    new bootstrap.Modal(document.getElementById('attendanceModal')).show();
}

// Awtomatikong kunin ang Daily Rate kapag pumili ng empleyado
function autoFetchDailyRate() {
    let empSelect = document.getElementById('inp_employee');
    let selectedOption = empSelect.options[empSelect.selectedIndex];
    let dailyRate = selectedOption.getAttribute('data-daily');
    if(dailyRate) {
        document.getElementById('inp_daily').value = dailyRate;
    }
}

// Buksan ang modal kapag mag-eedit ng attendance
function editAttendance(data) {
    document.getElementById('att_id').value = data.id || '';
    document.getElementById('inp_pay_date').value = data.pay_date || '';
    document.getElementById('inp_employee').value = data.employee_number || '';
    document.getElementById('inp_daily').value = data.daily || '0.00';
    document.getElementById('inp_absences').value = data.absences || '0';
    document.getElementById('inp_tardiness').value = data.tardiness || '0';
    document.getElementById('inp_undertime').value = data.undertime || '0';
    document.getElementById('inp_overtime').value = data.overtime || '0';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen-to-square text-teal me-2"></i>EDIT EMPLOYEE ATTENDANCE';
    new bootstrap.Modal(document.getElementById('attendanceModal')).show();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>