<?php
session_start();

include('connection.php');

// process.php

$logger = require __DIR__ . '/logger.php';

$logger->info("CSV processing started");

try {

    // Example CSV processing
    $filePath = __DIR__ . '/uploads/pending/data.csv';

    if (!file_exists($filePath)) {
        throw new Exception("CSV file not found");
    }

    $rowCount = 0;

    if (($handle = fopen($filePath, "r")) !== false) {
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            // DB insert logic here
            $rowCount++;
        }
        fclose($handle);
    }

    $logger->info("CSV processed successfully", [
        'rows_inserted' => $rowCount
    ]);

} catch (Exception $e) {

    $logger->error("Error during CSV processing", [
        'error' => $e->getMessage()
    ]);

    echo "Processing failed";
}







if(isset($_GET['process'])){
    $file_id = intval($_GET['process']);
    $fileRow = $conn->query("SELECT * FROM file_info WHERE file_id=$file_id")->fetch_assoc();
    $fileName = $fileRow['file_name'];
    $pendingPath = "uploads/pending/".$fileName;

    if(file_exists($pendingPath)){
        $handle = fopen($pendingPath,"r");
        $headers = fgetcsv($handle); // skip header
        $errorFlag = false;

        while(($data = fgetcsv($handle,0,","))!==FALSE){
            $sql = "INSERT INTO staging_table (file_id, timestamp_col, email_address, enrollment_no, name,
             branch, current_year, company_name, technology, project_name, start_date, end_date,
              trainer_name, location, fees_submitted, training_mode, communication, infrastructure,
               extra_activities, overall_company_exp, remark_company, trainer_teaching_style, trainer_knowledge, 
               project_status, trainer_classroom_exp, remark_trainer, location_status, accomodation, food_facility,
                overall_training_exp, remark_overall)
            VALUES (
                $file_id,
                '".$conn->real_escape_string($data[0])."',
                '".$conn->real_escape_string($data[1])."',
                '".$conn->real_escape_string($data[2])."',
                '".$conn->real_escape_string($data[3])."',
                '".$conn->real_escape_string($data[4])."',
                '".$conn->real_escape_string($data[5])."',
                '".$conn->real_escape_string($data[6])."',
                '".$conn->real_escape_string($data[7])."',
                '".$conn->real_escape_string($data[8])."',
                '".$conn->real_escape_string($data[9])."',
                '".$conn->real_escape_string($data[10])."',
                '".$conn->real_escape_string($data[11])."',
                '".$conn->real_escape_string($data[12])."',
                '".$conn->real_escape_string($data[13])."',
                '".$conn->real_escape_string($data[14])."',
                '".$conn->real_escape_string($data[15])."',
                '".$conn->real_escape_string($data[16])."',
                '".$conn->real_escape_string($data[17])."',
                '".$conn->real_escape_string($data[18])."',
                '".$conn->real_escape_string($data[19])."',
                '".$conn->real_escape_string($data[20])."',
                '".$conn->real_escape_string($data[21])."',
                '".$conn->real_escape_string($data[22])."',
                '".$conn->real_escape_string($data[23])."',
                '".$conn->real_escape_string($data[24])."',
                '".$conn->real_escape_string($data[25])."',
                '".$conn->real_escape_string($data[26])."',
                '".$conn->real_escape_string($data[27])."',
                '".$conn->real_escape_string($data[28])."',
                '".$conn->real_escape_string($data[29])."'
            )";
            if(!$conn->query($sql)) $errorFlag = true;
        }
        fclose($handle);

        if(!$errorFlag){
            rename($pendingPath,"uploads/done/".$fileName);
            $conn->query("UPDATE file_info SET status='done' WHERE file_id=$file_id");
        } else {
            rename($pendingPath,"uploads/error/".$fileName);
            $conn->query("UPDATE file_info SET status='error' WHERE file_id=$file_id");
        }
    }       
    header("Location: upload.php");
}
?>

