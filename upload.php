<?php
session_start();

include('connection.php');

// upload.php

$logger = require __DIR__ . '/logger.php';

// Page accessed
$logger->info("Upload page accessed");

if (isset($_POST['upload'])) {

    if (!isset($_FILES['csv'])) {
        $logger->error("No file selected");
        die("No file selected");
    }

    $fileName = $_FILES['csv']['name'];
    $tmpName  = $_FILES['csv']['tmp_name'];

    $logger->info("File upload attempt", ['file' => $fileName]);

    $targetPath = __DIR__ . '/uploads/pending/' . $fileName;

    if (move_uploaded_file($tmpName, $targetPath)) {
        $logger->info("File moved to pending folder", [
            'file' => $fileName,
            'path' => $targetPath
        ]);
    } else {
        $logger->error("File move failed", [
            'file' => $fileName
        ]);
    }
}



// Folders
$pendingDir = "uploads/pending/";
$doneDir    = "uploads/done/";
$errorDir   = "uploads/error/";
$deletedDir = "uploads/deleted/";

foreach([$pendingDir,$doneDir,$errorDir,$deletedDir] as $dir){
    if(!is_dir($dir)) mkdir($dir,0777,true);
}

// ---------- Upload CSV ----------
if(isset($_POST['form_submit'])){
    $sessionYear = $_POST['session_year'];
    $fileName = basename($_FILES["csvfile"]["name"]);
    $pendingPath = $pendingDir.$fileName;

    if(move_uploaded_file($_FILES["csvfile"]["tmp_name"], $pendingPath)){
        $check = $conn->query("SELECT * FROM file_info WHERE file_name='$fileName' AND session_year='$sessionYear'");
        if($check->num_rows==0){
            $insert = $conn->query("INSERT INTO file_info (file_name, session_year, status) VALUES ('$fileName','$sessionYear','pending')");
            if(!$insert) die("Error: ".$conn->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">  
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - Upload</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
body{font-family:'Times New Roman',serif;background:#1e1e1e;color:#fff;margin:0;display:flex;}
.sidebar{width:250px;background:#111;padding:20px;flex-direction:column;position:fixed;height:100%;}
.sidebar h2{color:#E75B18;text-align:center;margin-bottom:30px;}
.sidebar a{color:#ddd;text-decoration:none;display:block;padding:10px;border-radius:5px;margin-bottom:10px;}
.sidebar a:hover,.sidebar a.active{background:#ff6666;color:#fff;}
.logout-btn{background:#ff4d4d;color:white;border:none;padding:10px;border-radius:5px;width:100%;font-weight:bold;margin-top:auto;}
.logout-btn:hover{background:#ff6666;}
.main-content{margin-left:250px;padding:40px;flex:1;}
.card{background:#2c2c2c;border:none;padding:20px;border-radius:12px;color:white;max-width:500px;margin:0 auto;}
.form-control{background-color:#1e1e1e;color:white;border:1px solid #555;}
.form-control::file-selector-button{background-color:#ff6666;color:white;border:none;padding:.5rem 1rem;cursor:pointer;border-radius:5px;}
.btn-warning{background-color:#ff6666;border:none;width:30%;font-weight:bold;}
.btn-warning:hover{background-color:#ff4d4d;}
table{margin-top:20px;background:#2c2c2c;}
th,td{color:#fff;text-align:center;}
</style>
</head>
<body>
<div class="sidebar">
<h2>Admin Panel</h2>
<a href="upload.php" class="active"><i class="fa fa-home"></i> Dashboard</a>
<a href="upload.php"><i class="fa fa-upload"></i> Upload Survey Data</a>
<a href="adminlogout.php" class="logout-btn">Logout</a>
</div>
<div class="main-content">
<h1>Insert Data</h1>
<div class="card shadow-lg">
<form action="" method="post" enctype="multipart/form-data">
<div class="mb-3">
<label class="form-label">Select Session</label>
<select class="form-control" name="session_year" required>
<option value="2025">2025</option>
<option value="2024">2024</option>
<option value="2023">2023</option>
</select>
</div>
<div class="mb-3">
<label for="csv" class="form-label">Upload CSV File</label>
<input class="form-control" type="file" name="csvfile" id="csv" required>
</div>
<button type="submit" class="btn btn-warning" name="form_submit">Import File</button>
</form>
</div>

<h2 class="mt-5">Uploaded Files</h2>
<table class="table table-bordered">
<thead>
<tr>
<th>File ID</th>
<th>File Name</th>
<th>Session</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php
$result = $conn->query("SELECT * FROM file_info ORDER BY file_id DESC");
while($row = $result->fetch_assoc()){
    $status = $row['status'];
    echo "<tr>
        <td>{$row['file_id']}</td>
        <td>{$row['file_name']}</td>
        <td>{$row['session_year']}</td>
        <td>$status</td>
        <td>";

    if($status == 'pending'){
        echo "<a href='process.php?process={$row['file_id']}' class='btn btn-success btn-sm'>Process</a> "; 
           echo "<a href='move.php?delete={$row['file_id']}' class='btn btn-danger btn-sm'>Delete</a>";
    } elseif($status == 'done'){
        echo "<a href='move.php?move={$row['file_id']}' class='btn btn-danger btn-sm'>Move</a> ";
           echo "<a href='move.php?delete={$row['file_id']}' class='btn btn-danger btn-sm'>Delete</a>";
    } elseif($status == 'error'){
        echo "<a href='move.php?retry_move={$row['file_id']}' class='btn btn-warning btn-sm'>Retry Move</a> ";
           echo "<a href='move.php?delete={$row['file_id']}' class='btn btn-danger btn-sm'>Delete</a>";
    } elseif($status == 'moved'){ 
        echo "<span class='text-success'>Moved</span> ";
        echo "<a href='delete.php?delete={$row['file_id']}' class='btn btn-danger btn-sm'>Delete</a>";
    }

    echo "</td></tr>";
}
?>
</tbody>
</table>
</div>
</body>
</html>