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

// Filters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'payroll_summary';
$filter_pay_date = isset($_GET['pay_date']) ? mysqli_real_escape_string($conn, $_GET['pay_date']) : '';
$filter_dept = isset($_GET['dept']) ? mysqli_real_escape_string($conn, $_GET['dept']) : '';

// Kunin ang pinakabagong pay date kung walang napili
if(empty($filter_pay_date)) {
    $latest_pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pay_date FROM payroll_periods ORDER BY pay_date DESC LIMIT 1"));
    if($latest_pay) {
        $filter_pay_date = $latest_pay['pay_date'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Reports | LCC Admin</title>
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

        .header-card {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
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
            opacity: 0.85;
        }

        .report-nav-tabs {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }
        .report-nav-btn {
            border: none;
            background: transparent;
            color: #475569;
            font-weight: 600;
            font-size: 0.82rem;
            padding: 9px 18px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .report-nav-btn:hover {
            background: #f1f5f9;
            color: var(--primary);
        }
        .report-nav-btn.active {
            background: var(--primary);
            color: white !important;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }

        .filter-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .mgmt-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .table-custom-main {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .table-custom-main thead th {
            background-color: #f8fafc;
            color: #334155;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 14px;
            border-bottom: 1.5px solid var(--border-color);
            vertical-align: middle;
        }
        .table-custom-main tbody td {
            padding: 12px 14px;
            font-size: 0.84rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .table-custom-main tbody tr:hover td {
            background-color: #f8fafc;
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
            color: var(--primary); 
            border-color: #cbd5e1; 
        }

        /* Printable Sheet Styling */
        @media print {
            body { background: #ffffff !important; color: #000000 !important; font-size: 10pt; }
            .navbar-custom, .report-nav-tabs, .filter-card, .btn-print-hide { display: none !important; }
            .header-card { background: none !important; color: #000 !important; box-shadow: none !important; border-bottom: 2px solid #000; padding: 0 0 10px 0; border-radius: 0; margin-bottom: 15px; }
            .header-card p { color: #555 !important; }
            .mgmt-card { border: none !important; box-shadow: none !important; }
            .table-custom-main thead th { background: #eee !important; color: #000 !important; border: 1px solid #333 !important; }
            .table-custom-main tbody td { border: 1px solid #333 !important; color: #000 !important; }
            .badge { border: 1px solid #666 !important; background: transparent !important; color: #000 !important; }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top mb-4">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="profiles/logo.png" alt="LCC Logo" style="height: 38px; width: auto; border-radius: 6px;">
            <span>LCC ADMIN</span>
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-4 gap-1">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> DASHBOARD</a></li>
                <li class="nav-item"><a class="nav-link" href="employee.php"><i class="fas fa-users me-1"></i> Employees</a></li>
                <li class="nav-item"><a class="nav-link" href="dept.php"><i class="fas fa-sitemap me-1"></i> Dept & Position</a></li>
                <li class="nav-item"><a class="nav-link" href="loans.php"><i class="fas fa-hand-holding-usd me-1"></i> Loans</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-user-clock me-1"></i> Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="payroll.php"><i class="fas fa-file-invoice-dollar me-1"></i> Payroll</a></li>
                <li class="nav-item"><a class="nav-link active text-primary" href="reports.php"><i class="fas fa-chart-line me-1"></i> Reports</a></li>
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
    <!-- Header Card -->
    <div class="header-card">
        <div>
            <h4><i class="fas fa-chart-pie"></i> LCC PAYROLL & HR REPORTS CENTER</h4>
            <p>Generate, review, and print official payroll computations, attendance sheets, and employee records.</p>
        </div>
        <div class="d-none d-md-block text-end btn-print-hide">
            <button onclick="window.print()" class="btn btn-light text-primary fw-bold rounded-pill px-4 shadow-sm">
                <i class="fas fa-print me-1"></i> PRINT REPORT
            </button>
        </div>
    </div>

    <!-- Report Selector Tabs -->
    <div class="report-nav-tabs btn-print-hide">
        <a href="reports.php?type=payroll_summary&pay_date=<?php echo urlencode($filter_pay_date); ?>" class="report-nav-btn <?php echo $report_type == 'payroll_summary' ? 'active' : ''; ?>">
            <i class="fas fa-calculator"></i> Payroll & Net Pay Sheet
        </a>
        <a href="reports.php?type=attendance&pay_date=<?php echo urlencode($filter_pay_date); ?>" class="report-nav-btn <?php echo $report_type == 'attendance' ? 'active' : ''; ?>">
            <i class="fas fa-user-clock"></i> Attendance & DTR Summary
        </a>
        <a href="reports.php?type=employee_masterlist" class="report-nav-btn <?php echo $report_type == 'employee_masterlist' ? 'active' : ''; ?>">
            <i class="fas fa-id-card"></i> Employee Masterlist
        </a>
        <a href="reports.php?type=income_deductions&pay_date=<?php echo urlencode($filter_pay_date); ?>" class="report-nav-btn <?php echo $report_type == 'income_deductions' ? 'active' : ''; ?>">
            <i class="fas fa-coins"></i> Other Income & Deductions
        </a>
        <a href="reports.php?type=loans" class="report-nav-btn <?php echo $report_type == 'loans' ? 'active' : ''; ?>">
            <i class="fas fa-hand-holding-dollar"></i> Employee Loans Ledger
        </a>
        <a href="reports.php?type=dept_summary" class="report-nav-btn <?php echo $report_type == 'dept_summary' ? 'active' : ''; ?>">
            <i class="fas fa-sitemap"></i> Department Headcounts
        </a>
    </div>

    <!-- Filter Card -->
    <div class="filter-card btn-print-hide">
        <form method="GET" class="d-flex flex-wrap align-items-center gap-3">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($report_type); ?>">
            
            <?php if(in_array($report_type, ['payroll_summary', 'attendance', 'income_deductions'])): ?>
            <div class="d-flex align-items-center gap-2">
                <label class="small fw-bold text-muted text-nowrap"><i class="far fa-calendar-alt text-primary me-1"></i> PAY DATE:</label>
                <select name="pay_date" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 170px;">
                    <option value="">-- All Cutoffs --</option>
                    <?php 
                    $pd_res = mysqli_query($conn, "SELECT DISTINCT pay_date FROM payroll_periods ORDER BY pay_date DESC");
                    while($pd = mysqli_fetch_assoc($pd_res)) {
                        $selected = ($filter_pay_date == $pd['pay_date']) ? 'selected' : '';
                        echo "<option value='{$pd['pay_date']}' $selected>{$pd['pay_date']}</option>";
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if($report_type == 'employee_masterlist'): ?>
            <div class="d-flex align-items-center gap-2">
                <label class="small fw-bold text-muted text-nowrap"><i class="fas fa-building text-primary me-1"></i> DEPARTMENT:</label>
                <select name="dept" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 170px;">
                    <option value="">-- All Departments --</option>
                    <?php 
                    $dept_q = mysqli_query($conn, "SELECT dept_name FROM departments ORDER BY dept_name ASC");
                    while($d = mysqli_fetch_assoc($dept_q)) {
                        $sel = ($filter_dept == $d['dept_name']) ? 'selected' : '';
                        echo "<option value='{$d['dept_name']}' $sel>{$d['dept_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
        </form>

        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn-tool"><i class="fas fa-print text-primary"></i> Print View</button>
            <button onclick="exportTableToCSV('report_data.csv')" class="btn-tool"><i class="fas fa-file-excel text-success"></i> Export CSV</button>
        </div>
    </div>

    <!-- Official Report Content -->
    <div class="mgmt-card">
        <div class="p-4">
            
            <!-- REPORT 1: PAYROLL & NET PAY SHEET -->
            <?php if($report_type == 'payroll_summary'): ?>
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1 text-dark">OFFICIAL PAYROLL COMPUTATION SHEET</h5>
                    <span class="badge bg-light text-primary border px-3 py-1.5 fw-semibold">
                        PAY DATE: <?php echo !empty($filter_pay_date) ? htmlspecialchars($filter_pay_date) : 'All Cutoff Dates'; ?>
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table-custom-main align-middle" id="reportTable">
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th class="text-end">Daily Rate</th>
                                <th class="text-end">Basic Pay</th>
                                <th class="text-end">Overtime</th>
                                <th class="text-end">Other Income</th>
                                <th class="text-end text-danger">Tardiness/Abs</th>
                                <th class="text-end text-danger">Other Ded</th>
                                <th class="text-end text-primary fw-bold">Net Pay</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $date_cond = !empty($filter_pay_date) ? "WHERE a.pay_date = '$filter_pay_date'" : "";
                            $sql = "SELECT a.*, e.first_name, e.last_name, p.position, p.daily as base_daily 
                                    FROM attendance a 
                                    LEFT JOIN employees e ON a.employee_number = e.employee_number 
                                    LEFT JOIN employee_positions p ON a.employee_number = p.employee_number 
                                    $date_cond 
                                    ORDER BY e.last_name ASC";
                            $res = mysqli_query($conn, $sql);
                            
                            $grand_net = 0;
                            if(mysqli_num_rows($res) == 0): ?>
                                <tr><td colspan="10" class="text-center py-4 text-muted">No payroll/attendance records found for this period.</td></tr>
                            <?php endif;

                            while($row = mysqli_fetch_assoc($res)): 
                                $emp_no = $row['employee_number'];
                                $p_date = $row['pay_date'];

                                // Kunin ang other income para sa cutoff na ito
                                $inc_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM other_income WHERE employee_number='$emp_no' AND pay_date='$p_date'");
                                $other_inc = floatval(mysqli_fetch_assoc($inc_q)['total'] ?? 0);

                                // Kunin ang other deduction para sa cutoff na ito
                                $ded_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM other_deductions WHERE employee_number='$emp_no' AND pay_date='$p_date'");
                                $other_ded = floatval(mysqli_fetch_assoc($ded_q)['total'] ?? 0);

                                $daily_rate = floatval($row['daily'] > 0 ? $row['daily'] : $row['base_daily']);
                                $hourly_rate = $daily_rate / 8;

                                // Basic (Halimbawa: 13 days standard cutoff - absences)
                                $days_worked = max(0, 13 - floatval($row['absences']));
                                $basic_pay = $days_worked * $daily_rate;

                                // Overtime Pay (125% rate)
                                $ot_pay = floatval($row['overtime']) * ($hourly_rate * 1.25);

                                // Tardiness & Undertime Deductions
                                $tardy_ded = (floatval($row['tardiness']) + floatval($row['undertime'])) * $hourly_rate;

                                // Total Net Pay
                                $net_pay = ($basic_pay + $ot_pay + $other_inc) - ($tardy_ded + $other_ded);
                                $grand_net += $net_pay;
                            ?>
                            <tr>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($row['employee_number']); ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars("{$row['last_name']}, {$row['first_name']}"); ?></td>
                                <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                                <td class="text-end">₱<?php echo number_format($daily_rate, 2); ?></td>
                                <td class="text-end">₱<?php echo number_format($basic_pay, 2); ?></td>
                                <td class="text-end text-success">+₱<?php echo number_format($ot_pay, 2); ?></td>
                                <td class="text-end text-success">+₱<?php echo number_format($other_inc, 2); ?></td>
                                <td class="text-end text-danger">-₱<?php echo number_format($tardy_ded, 2); ?></td>
                                <td class="text-end text-danger">-₱<?php echo number_format($other_ded, 2); ?></td>
                                <td class="text-end fw-bold text-primary" style="font-size: 0.95rem;">₱<?php echo number_format($net_pay, 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <?php if($grand_net > 0): ?>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="9" class="text-end">TOTAL PAYROLL DISBURSEMENT:</td>
                                <td class="text-end text-primary" style="font-size: 1.05rem;">₱<?php echo number_format($grand_net, 2); ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>

            <!-- REPORT 2: ATTENDANCE & DTR -->
            <?php elseif($report_type == 'attendance'): ?>
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1 text-dark">OFFICIAL ATTENDANCE & TIME RECORD SUMMARY</h5>
                    <span class="badge bg-light text-teal border px-3 py-1.5 fw-semibold">
                        PAY DATE: <?php echo !empty($filter_pay_date) ? htmlspecialchars($filter_pay_date) : 'All Dates'; ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table-custom-main text-center" id="reportTable">
                        <thead>
                            <tr>
                                <th style="text-align: left;">Employee Name</th>
                                <th>Emp ID</th>
                                <th>Pay Date</th>
                                <th>Daily Rate</th>
                                <th>Absences</th>
                                <th>Tardiness</th>
                                <th>Under Time</th>
                                <th>Over Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $date_cond = !empty($filter_pay_date) ? "WHERE a.pay_date = '$filter_pay_date'" : "";
                            $sql = "SELECT a.*, e.first_name, e.last_name 
                                    FROM attendance a 
                                    LEFT JOIN employees e ON a.employee_number = e.employee_number 
                                    $date_cond ORDER BY e.last_name ASC";
                            $res = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($res) == 0): ?>
                                <tr><td colspan="8" class="text-muted py-4">No attendance records found.</td></tr>
                            <?php endif;
                            while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td style="text-align: left;" class="fw-semibold"><?php echo htmlspecialchars("{$r['last_name']}, {$r['first_name']}"); ?></td>
                                <td class="text-primary fw-bold"><?php echo htmlspecialchars($r['employee_number']); ?></td>
                                <td><?php echo htmlspecialchars($r['pay_date']); ?></td>
                                <td>₱<?php echo number_format($r['daily'], 2); ?></td>
                                <td><span class="<?php echo $r['absences'] > 0 ? 'text-danger fw-bold' : ''; ?>"><?php echo htmlspecialchars($r['absences']); ?></span></td>
                                <td><?php echo htmlspecialchars($r['tardiness']); ?></td>
                                <td><?php echo htmlspecialchars($r['undertime']); ?></td>
                                <td><span class="<?php echo $r['overtime'] > 0 ? 'text-primary fw-bold' : ''; ?>"><?php echo htmlspecialchars($r['overtime']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <!-- REPORT 3: EMPLOYEE MASTERLIST -->
            <?php elseif($report_type == 'employee_masterlist'): ?>
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1 text-dark">EMPLOYEE MASTERLIST & CREDENTIALS DIRECTORY</h5>
                    <span class="badge bg-light text-primary border px-3 py-1.5 fw-semibold">
                        <?php echo !empty($filter_dept) ? "Department: ".htmlspecialchars($filter_dept) : "All Active Departments"; ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table-custom-main align-middle" id="reportTable">
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Contact No</th>
                                <th>Email</th>
                                <th>SSS No</th>
                                <th>PhilHealth</th>
                                <th>TIN No</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $dept_cond = !empty($filter_dept) ? "WHERE p.department = '$filter_dept'" : "";
                            $sql = "SELECT e.*, p.department, p.position 
                                    FROM employees e 
                                    LEFT JOIN employee_positions p ON e.employee_number = p.employee_number 
                                    $dept_cond ORDER BY e.last_name ASC";
                            $res = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($res) == 0): ?>
                                <tr><td colspan="9" class="text-center py-4 text-muted">No employees registered under this department.</td></tr>
                            <?php endif;
                            while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($r['employee_number']); ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars("{$r['last_name']}, {$r['first_name']} {$r['middle_name']}"); ?></td>
                                <td><span class="badge bg-light text-primary border"><?php echo htmlspecialchars($r['department'] ?? 'Unassigned'); ?></span></td>
                                <td><?php echo htmlspecialchars($r['position'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($r['contact_no']); ?></td>
                                <td><?php echo htmlspecialchars($r['email']); ?></td>
                                <td><?php echo htmlspecialchars($r['sss_no']); ?></td>
                                <td><?php echo htmlspecialchars($r['philhealth_no']); ?></td>
                                <td><?php echo htmlspecialchars($r['tin_no']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <!-- REPORT 4: OTHER INCOME & DEDUCTIONS -->
            <?php elseif($report_type == 'income_deductions'): ?>
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1 text-dark">OTHER INCOME & DEDUCTIONS LEDGER</h5>
                    <span class="badge bg-light text-primary border px-3 py-1.5 fw-semibold">
                        Pay Date: <?php echo !empty($filter_pay_date) ? htmlspecialchars($filter_pay_date) : 'All Cutoff Schedules'; ?>
                    </span>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 bg-light">
                            <h6 class="fw-bold text-success mb-3"><i class="fas fa-hand-holding-dollar me-2"></i>Recorded Other Income</h6>
                            <table class="table-custom-main">
                                <thead><tr><th>Emp ID</th><th>Pay Date</th><th>Income Type</th><th class="text-end">Amount</th></tr></thead>
                                <tbody>
                                    <?php 
                                    $inc_cond = !empty($filter_pay_date) ? "WHERE pay_date = '$filter_pay_date'" : "";
                                    $inc_res = mysqli_query($conn, "SELECT * FROM other_income $inc_cond ORDER BY id DESC");
                                    if(mysqli_num_rows($inc_res) == 0): ?><tr><td colspan="4" class="text-center text-muted py-3">No records</td></tr><?php endif;
                                    while($ri = mysqli_fetch_assoc($inc_res)): ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?php echo htmlspecialchars($ri['employee_number']); ?></td>
                                        <td><?php echo htmlspecialchars($ri['pay_date']); ?></td>
                                        <td><?php echo htmlspecialchars($ri['income_type']); ?></td>
                                        <td class="text-end fw-bold text-success">₱<?php echo number_format($ri['amount'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 bg-light">
                            <h6 class="fw-bold text-danger mb-3"><i class="fas fa-file-invoice-dollar me-2"></i>Recorded Other Deductions</h6>
                            <table class="table-custom-main">
                                <thead><tr><th>Emp ID</th><th>Pay Date</th><th>Deduction Type</th><th class="text-end">Amount</th></tr></thead>
                                <tbody>
                                    <?php 
                                    $ded_cond = !empty($filter_pay_date) ? "WHERE pay_date = '$filter_pay_date'" : "";
                                    $ded_res = mysqli_query($conn, "SELECT * FROM other_deductions $ded_cond ORDER BY id DESC");
                                    if(mysqli_num_rows($ded_res) == 0): ?><tr><td colspan="4" class="text-center text-muted py-3">No records</td></tr><?php endif;
                                    while($rd = mysqli_fetch_assoc($ded_res)): ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?php echo htmlspecialchars($rd['employee_number']); ?></td>
                                        <td><?php echo htmlspecialchars($rd['pay_date']); ?></td>
                                        <td><?php echo htmlspecialchars($rd['deduction_type']); ?></td>
                                        <td class="text-end fw-bold text-danger">-₱<?php echo number_format($rd['amount'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- REPORT 5: LOANS LEDGER -->
            <?php elseif($report_type == 'loans'): ?>
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1 text-dark">EMPLOYEE ACTIVE LOANS & FINANCING SUMMARY</h5>
                    <span class="badge bg-light text-primary border px-3 py-1.5 fw-semibold">Master Loan Records</span>
                </div>
                <div class="table-responsive">
                    <table class="table-custom-main align-middle text-center" id="reportTable">
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th style="text-align: left;">Employee Name</th>
                                <th>Loan Application Date</th>
                                <th>Type of Loan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT l.*, e.first_name, e.last_name 
                                    FROM employee_loans l 
                                    LEFT JOIN employees e ON l.employee_number = e.employee_number 
                                    ORDER BY l.loan_date DESC";
                            $res = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($res) == 0): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No employee loan records found.</td></tr>
                            <?php endif;
                            while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($r['employee_number']); ?></td>
                                <td style="text-align: left;" class="fw-semibold"><?php echo htmlspecialchars("{$r['last_name']}, {$r['first_name']}"); ?></td>
                                <td><?php echo htmlspecialchars($r['loan_date']); ?></td>
                                <td><span class="badge bg-light text-dark border px-3 py-1.5"><?php echo htmlspecialchars($r['loan_type']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <!-- REPORT 6: DEPARTMENT SUMMARY -->
            <?php elseif($report_type == 'dept_summary'): ?>
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1 text-dark">DEPARTMENT & POSITIONS HEADCOUNT BREAKDOWN</h5>
                    <span class="badge bg-light text-primary border px-3 py-1.5 fw-semibold">Institutional Directory</span>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3">
                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-building me-2"></i>Employees per Department</h6>
                            <table class="table-custom-main">
                                <thead><tr><th>Department</th><th class="text-end">Headcount</th></tr></thead>
                                <tbody>
                                    <?php 
                                    $dept_q = mysqli_query($conn, "SELECT d.dept_name, COUNT(p.employee_number) as total 
                                                                   FROM departments d 
                                                                   LEFT JOIN employee_positions p ON d.dept_name = p.department 
                                                                   GROUP BY d.dept_name ORDER BY total DESC");
                                    while($d = mysqli_fetch_assoc($dept_q)): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($d['dept_name']); ?></td>
                                        <td class="text-end fw-bold text-primary"><?php echo $d['total']; ?> employee(s)</td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3">
                            <h6 class="fw-bold text-teal mb-3"><i class="fas fa-id-badge me-2"></i>Available Positions List</h6>
                            <table class="table-custom-main">
                                <thead><tr><th>Position Title</th><th class="text-end">Occupied By</th></tr></thead>
                                <tbody>
                                    <?php 
                                    $pos_q = mysqli_query($conn, "SELECT pos.pos_name, COUNT(p.employee_number) as total 
                                                                 FROM positions pos 
                                                                 LEFT JOIN employee_positions p ON pos.pos_name = p.position 
                                                                 GROUP BY pos.pos_name ORDER BY pos.pos_name ASC");
                                    while($pos = mysqli_fetch_assoc($pos_q)): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($pos['pos_name']); ?></td>
                                        <td class="text-end fw-bold text-success"><?php echo $pos['total']; ?> person(s)</td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Function para sa Excel/CSV Export
function exportTableToCSV(filename) {
    let csv = [];
    let rows = document.querySelectorAll("#reportTable tr");
    if(rows.length === 0) {
        alert("No table data available to export.");
        return;
    }
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/(\s\s+)/g, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(","));
    }

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>