<?php
session_start();

include('connection.php');

// move.php

$logger = require __DIR__ . '/logger.php';

$logger->info("Data move initiated");

try {

    // Example: Move data from staging to main table
    // yaha apna DB logic aayega

    // ---- dummy example ----
    $movedRows = 120; // maan lo itni rows move hui
    // -----------------------

    $logger->info("Training data moved successfully", [
        'rows_moved' => $movedRows
    ]);

} catch (Exception $e) {

    $logger->critical("Data move failed", [
        'error' => $e->getMessage()
    ]);

    echo "Data move failed";
}


 
// ---------- HELPER FUNCTIONS ----------

function ratingTextToNumber($text) {
    if (!$text) return null;
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
    $text = str_replace("\xA0", ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = strtolower(trim($text));

    switch ($text) {
        case 'excellent': return 5;
        case 'very good': return 4;
        case 'good': return 3;
        case 'average': return 2;
        case 'poor': return 1;
        default: return 5;
    }
}

function projectStatusToNumber($percentText){
    $num = (int) filter_var($percentText, FILTER_SANITIZE_NUMBER_INT);
    if($num == 100) return 5;
    elseif($num >= 76) return 4;
    elseif($num >= 51) return 3;
    elseif($num >= 26) return 2;
    elseif($num >= 1) return 1;
    return 0;
}

function locationToNumber($text){
    if(!$text) return null;
    $text = strtolower(trim($text));
    switch($text){
        case 'easy to find': return 4;
        case 'average': return 3;
        case 'difficult to find': return 2;
        case 'too far': return 1;
        default: return null;
    }
}

// ---------- Move / Retry ----------

if(isset($_GET['move']) || isset($_GET['retry_move'])){
    $file_id = isset($_GET['move']) ? intval($_GET['move']) : intval($_GET['retry_move']);
    $fileRow = $conn->query("SELECT * FROM file_info WHERE file_id=$file_id")->fetch_assoc();

    if(!$fileRow){
        echo "<script>alert('File not found!'); window.location='upload.php';</script>";
        exit;
    }

    $isRetry = isset($_GET['retry_move']);
    $result = $conn->query("SELECT * FROM staging_table WHERE file_id=$file_id");
    if($result->num_rows == 0){
        echo "<script>alert('No staging data found to move!'); window.location='upload.php';</script>";
        exit;
    }

    $errorFlag = false;

    while($row = $result->fetch_assoc()){
        $survey_id = $row['survey_id'];

        // 🧼 Company Name CLEAN FIX
        $rawCompanyName = $row['company_name'] ?? '';
        $rawCompanyName = preg_replace('/^\xEF\xBB\xBF/', '', $rawCompanyName);
        $companyName = preg_replace('/[\p{C}\x{00A0}]/u', '', $rawCompanyName);
        $companyName = trim($companyName);

        $company_id = 0;
        if(strlen($companyName) > 0){
            $companyNameEsc = $conn->real_escape_string($companyName);
            $cRes = $conn->query("SELECT company_id FROM company_info WHERE company_name='$companyNameEsc' LIMIT 1");
            if($cRes->num_rows > 0){
                $company_id = $cRes->fetch_assoc()['company_id'];
            } else {
                $conn->query("INSERT INTO company_info (company_name) VALUES ('$companyNameEsc')");
                $company_id = $conn->insert_id;
            }
        }

        // 📝 Training Details
        $timestamp = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $row['timestamp_col'])));
        $start_dt  = date('Y-m-d', strtotime(str_replace('/', '-', $row['start_date'])));
        $end_dt    = date('Y-m-d', strtotime(str_replace('/', '-', $row['end_date'])));

        $fees = 0;
        if(isset($row['fees_submitted'])){
            $fees_arr = explode('-', $row['fees_submitted']);
            $fees = preg_replace('/[^0-9.]/', '', trim($fees_arr[0]));
        }

        $overall_exp_rating = ratingTextToNumber($row['overall_training_exp']);

        $sql = "INSERT INTO training_dts (
            survey_id, file_id, timestamp, email_id, enroll_no, name, branch, session,
            company_id, project_name, start_dt, end_dt, location, fees,
            training_mode, overall_exp, remark
        ) VALUES (
            '$survey_id',
            '".$row['file_id']."',
            '$timestamp',
            '".$row['email_address']."',
            '".$row['enrollment_no']."',
            '".$conn->real_escape_string($row['name'])."',
            '".$row['branch']."',
            '".$row['current_year']."',
            '$company_id',
            '".$conn->real_escape_string($row['project_name'])."',
            '$start_dt',
            '$end_dt',
            '".$conn->real_escape_string($row['location'])."',
            '$fees',
            '".$row['training_mode']."',
            ".($overall_exp_rating ?? 'NULL').",
            '".$conn->real_escape_string($row['remark_overall'])."'
        )";

        if(!$conn->query($sql)){
            echo "<b>training_dts insert error:</b> ".$conn->error."<br>";
            $errorFlag = true;
            continue;
        }

        // 🧠 Technologies
        $techs = explode(",", $row['technology']);
        foreach($techs as $tech){
            $tech = trim($tech);
            if($tech != ""){
                $tRes = $conn->query("SELECT tech_id FROM training_tech WHERE technology_name='$tech'");
                if($tRes->num_rows > 0){
                    $tech_id = $tRes->fetch_assoc()['tech_id'];
                } else {
                    $conn->query("INSERT INTO training_tech (technology_name) VALUES ('$tech')");
                    $tech_id = $conn->insert_id;
                }
                $conn->query("INSERT INTO student_tech_map (survey_id, tech_id) VALUES ($survey_id, $tech_id)");
            }
        }

        // 👨‍🏫 Trainer
        unset($trainer_id);
        $trainer_name = trim($conn->real_escape_string($row['trainer_name']));
        if($trainer_name != ""){
            $tRes = $conn->query("SELECT trainer_id FROM trainer_dts WHERE name='$trainer_name'");
            if($tRes->num_rows > 0){
                $trainer_id = $tRes->fetch_assoc()['trainer_id'];
            } else {
                $conn->query("INSERT INTO trainer_dts (name) VALUES ('$trainer_name')");
                $trainer_id = $conn->insert_id;
            }
            $conn->query("INSERT INTO student_trainer_map (survey_id, trainer_id) VALUES ($survey_id, $trainer_id)");
        }

        // 🏢 Company Feedback
        $communication_rating = ratingTextToNumber($row['communication']);
        $infra_rating = ratingTextToNumber($row['infrastructure']);
        $activity_rating = (strtolower(trim($row['extra_activities'])) == 'yes') ? 1 : 0;
        $overall_company_rating = ratingTextToNumber($row['overall_company_exp']);
        $project_status_rating = projectStatusToNumber($row['project_status']);

        $sql = "INSERT INTO comp_feedback (
            survey_id, company_id, communication, infra, activity, overall_fdb, remark, proj_status
        ) VALUES (
            $survey_id, $company_id,
            ".($communication_rating ?? 'NULL').",
            ".($infra_rating ?? 'NULL').",
            ".($activity_rating ?? 'NULL').",
            ".($overall_company_rating ?? 'NULL').",
            '".$conn->real_escape_string($row['remark_company'])."',
            ".($project_status_rating ?? 'NULL')."
        )";

        if(!$conn->query($sql)){
            echo "<b>comp_feedback insert error:</b> ".$conn->error."<br>";
            $errorFlag = true;
        }

        // 👨‍🏫 Trainer Feedback
        $teaching_rating = ratingTextToNumber($row['trainer_teaching_style']);
        $knowledge_rating = ratingTextToNumber($row['trainer_knowledge']);
        $overall_trainer_rating = ratingTextToNumber($row['trainer_classroom_exp']);

        if (isset($trainer_id) && $trainer_id > 0) {
            $sql = "INSERT INTO trainer_feedback (
                survey_id, trainer_id, company_id, teaching, knowledge, overall_fdb, remark
            ) VALUES (
                $survey_id,
                $trainer_id,
                $company_id,
                ".($teaching_rating ?? 'NULL').",
                ".($knowledge_rating ?? 'NULL').", 
                ".($overall_trainer_rating ?? 'NULL').",
                '".$conn->real_escape_string($row['remark_trainer'])."'
            )";

            if (!$conn->query($sql)) {
                echo "<b>trainer_feedback insert error:</b> ".$conn->error."<br>";
                $errorFlag = true;
            }
        }

        // 🌍 Other Feedback
        $location_rating = locationToNumber($row['location_status']);
        $accommodation = $conn->real_escape_string($row['accomodation']);
        $fooding = $conn->real_escape_string($row['food_facility']);

        $sql = "INSERT INTO other_feedback (
            survey_id, location, accommodation, fooding
        ) VALUES (
            $survey_id,
            ".($location_rating ?? 'NULL').",
            '$accommodation',
            '$fooding'
        )";
        if(!$conn->query($sql)){
            echo "<b>other_feedback insert error:</b> ".$conn->error."<br>";
            $errorFlag = true;
        }

        // ✅ Mark row moved
        if(!$errorFlag){
            $conn->query("UPDATE staging_table SET move_status='MOVED' WHERE id=$survey_id");
        }
    }

    // ✅ File Status Update
    $status = $errorFlag ? 'error' : 'moved';
    $remark = $errorFlag ? ($isRetry ? 'Move retry failed' : 'Move failed') : ($isRetry ? 'Move retried successfully' : 'Moved successfully');

    $conn->query("UPDATE file_info SET status='$status' WHERE file_id=$file_id");
    $conn->query("INSERT INTO file_log (file_id, session_year, remarks) VALUES ($file_id, '{$fileRow['session_year']}', '$remark')");

    echo "<script>alert('$remark'); window.location='upload.php';</script>";
    exit;
}
?>