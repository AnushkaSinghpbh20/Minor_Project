<?php
include('connection.php');

if(isset($_GET['file_id'])){
    $file_id = intval($_GET['file_id']);
    $row = $conn->query("SELECT * FROM file_info WHERE file_id=$file_id")->fetch_assoc();
    $status = $row['status'];
    $fileName = $row['file_name'];
    $deletedDir = "uploads/deleted/";

    $filePath = "uploads/$status/".$fileName;
    if(file_exists($filePath)) rename($filePath,$deletedDir.$fileName);

    $conn->query("DELETE FROM staging_table WHERE file_id=$file_id");
    $conn->query("DELETE FROM file_info WHERE file_id=$file_id");
    echo "File deleted successfully.";
}