<?php
session_start();
include 'db.php'; // Siguraduhin na tama ang details sa db.php mo

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] != true) {
    header('location: login.php');
    exit;
}

if (isset($_POST['save_employee'])) {
    $emp_no = mysqli_real_escape_string($conn, $_POST['employee_number']);
    
    // 1. Personal Information
    $sql1 = "INSERT INTO employees (employee_number, portal_password, last_name, first_name, middle_name, gender, email, civil_status, birth_date, contact_no, sss_no, tin_no) 
             VALUES ('$emp_no', '{$_POST['portal_password']}', '{$_POST['last_name']}', '{$_POST['first_name']}', '{$_POST['middle_name']}', '{$_POST['gender']}', '{$_POST['email']}', '{$_POST['civil_status']}', '{$_POST['birth_date']}', '{$_POST['contact_no']}', '{$_POST['sss_no']}', '{$_POST['tin_no']}')";
    
    if (mysqli_query($conn, $sql1)) {
        
        // 2. Emergency Contact
        mysqli_query($conn, "INSERT INTO emergency_contacts (employee_number, name, relationship, address, contact) 
            VALUES ('$emp_no', '{$_POST['emg_name']}', '{$_POST['emg_rel']}', '{$_POST['emg_addr']}', '{$_POST['emg_con']}')");

        // 3. Dependents (Base sa screenshot 1)
        mysqli_query($conn, "INSERT INTO dependents (employee_number, name, birth_date, relationship) 
            VALUES ('$emp_no', '{$_POST['dep_name']}', '{$_POST['dep_birth_date']}', '{$_POST['dep_rel']}')");

        // 4. Education (Base sa screenshot 2)
        mysqli_query($conn, "INSERT INTO education (employee_number, school, address, sy, level) 
            VALUES ('$emp_no', '{$_POST['edu_school']}', '{$_POST['edu_addr']}', '{$_POST['edu_sy']}', '{$_POST['edu_level']}')");

        // 5. Character Reference (Base sa screenshot 3)
        mysqli_query($conn, "INSERT INTO character_references (employee_number, name, occupation, address, contact) 
            VALUES ('$emp_no', '{$_POST['ref_name']}', '{$_POST['ref_occ']}', '{$_POST['ref_addr']}', '{$_POST['ref_con']}')");

        // 6. Employee Position
        mysqli_query($conn, "INSERT INTO employee_positions (employee_number, department, position, monthly, start_date) 
            VALUES ('$emp_no', '{$_POST['dept']}', '{$_POST['pos']}', '{$_POST['monthly']}', '{$_POST['start_date']}')");

        echo "<script>alert('Employee data saved successfully!'); window.location='employees.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>