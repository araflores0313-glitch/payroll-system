<?php
$conn = mysqli_connect("localhost", "root", "", "pd");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>