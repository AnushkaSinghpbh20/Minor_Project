<?php
include('connection.php'); // DB connection

// Step 1: Get company ID from URL
if(isset($_GET['compId']) && is_numeric($_GET['compId'])){
    $company_id = intval($_GET['compId']);

    // Step 2: Fetch company info
    $sql = "SELECT company_name, website, logo, location FROM company_info WHERE company_id = ?";
    $stmt = $conn->prepare($sql);
    if(!$stmt){ die("Prepare failed: ".$conn->error); }
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $company = $result->fetch_assoc();
    } else {
        die("<h2 style='text-align:center;color:red;margin-top:40px;'>Company not found!</h2>");
    }
    $stmt->close();    

    // Step 3: Total students
    $sql_students = "SELECT COUNT(DISTINCT survey_id) AS total_students FROM training_dts WHERE company_id=?";
    $stmt_students = $conn->prepare($sql_students);
    if(!$stmt_students){ die("Prepare failed: ".$conn->error); }
    $stmt_students->bind_param("i", $company_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();
    $total_students = ($result_students->fetch_assoc())['total_students'];
    $stmt_students->close();

    // Step 4: Technology-wise student count + rating    
    $sql_tech = "
    SELECT tt.tech_id, tt.technology_name, stm.survey_id, 
        (COALESCE(tf.teaching,0) + COALESCE(tf.knowledge,0) + COALESCE(tf.overall_fdb,0) + COALESCE(cf.proj_status,0)) AS trainer_points
    FROM training_tech tt
    JOIN student_tech_map stm ON tt.tech_id = stm.tech_id
    JOIN training_dts td ON td.survey_id = stm.survey_id
    LEFT JOIN trainer_feedback tf ON td.survey_id = tf.survey_id
    LEFT JOIN comp_feedback cf ON td.survey_id = cf.survey_id
    WHERE td.company_id = ?
    ";
    $stmt_tech = $conn->prepare($sql_tech);
    if(!$stmt_tech){ die("Prepare failed: ".$conn->error); }
    $stmt_tech->bind_param("i", $company_id);
    $stmt_tech->execute();
    $result_tech = $stmt_tech->get_result();

    $tech_data = [];
    while($row = $result_tech->fetch_assoc()){
        $tech_id = $row['tech_id'];
        $trainer_points = $row['trainer_points'];
        $rating = ($trainer_points / 20) * 5; // 20 points = 5 rating scale
        if(!isset($tech_data[$tech_id])){
            $tech_data[$tech_id] = [
                'technology_name' => $row['technology_name'],
                'ratings' => [],
                'student_count' => 0
            ];
        }
        $tech_data[$tech_id]['ratings'][] = $rating;
        $tech_data[$tech_id]['student_count']++;
    }
    foreach($tech_data as &$tech){
        $tech['avg_rating'] = round(array_sum($tech['ratings']) / count($tech['ratings']), 1);
    }
    unset($tech);
    $stmt_tech->close();

    // Step 5: Overall Rating
    $sql_overall = "
    SELECT td.survey_id,
        (COALESCE(cf.activity,0) 
        + COALESCE(cf.communication,0)
        + COALESCE(cf.infra,0)
        + COALESCE(cf.overall_fdb,0)
        + COALESCE(tf.teaching,0) 
        + COALESCE(tf.knowledge,0) 
        + COALESCE(tf.overall_fdb,0) 
        + COALESCE(cf.proj_status,0)
        + COALESCE(ofd.location,0) 
        + COALESCE(td.overall_exp,0)
        ) AS total_points
    FROM training_dts td
    LEFT JOIN comp_feedback cf ON td.survey_id = cf.survey_id
    LEFT JOIN trainer_feedback tf ON td.survey_id = tf.survey_id
    LEFT JOIN other_feedback ofd ON td.survey_id = ofd.survey_id
    WHERE td.company_id = ?
    ";
    $stmt_overall = $conn->prepare($sql_overall);
    if(!$stmt_overall){ die("Prepare failed: ".$conn->error); }
    $stmt_overall->bind_param("i", $company_id);
    $stmt_overall->execute();
    $result_overall = $stmt_overall->get_result();

    $total_percent = 0;
    $count_students = 0;
    while($row = $result_overall->fetch_assoc()){
        $percent = ($row['total_points'] / 45) * 100; // out of 45
        $total_percent += $percent;
        $count_students++;
    }
    $stmt_overall->close();

    $overall_rating = 0;
    if($count_students > 0){
        $avg_percent = $total_percent / $count_students;
        $overall_rating = round(($avg_percent / 100) * 5, 1);
    }

    // Step 6: Trainer Rating
    $sql_trainer = "
    SELECT td.survey_id,
        (COALESCE(tf.teaching,0) 
        + COALESCE(tf.knowledge,0) 
        + COALESCE(tf.overall_fdb,0) 
        + COALESCE(cf.proj_status,0)) AS trainer_points
    FROM training_dts td
    LEFT JOIN trainer_feedback tf ON td.survey_id = tf.survey_id
    LEFT JOIN comp_feedback cf ON td.survey_id = cf.survey_id
    WHERE td.company_id = ?
    ";
    $stmt_trainer = $conn->prepare($sql_trainer);
    if(!$stmt_trainer){ die("Prepare failed: ".$conn->error); }
    $stmt_trainer->bind_param("i", $company_id);
    $stmt_trainer->execute();
    $result_trainer = $stmt_trainer->get_result();

    $total_trainer_percent = 0;
    $count_students_trainer = 0;
    while($row = $result_trainer->fetch_assoc()){
        $percent_trainer = ($row['trainer_points'] / 20) * 100; // out of 20
        $total_trainer_percent += $percent_trainer;
        $count_students_trainer++;
    }  
    $stmt_trainer->close();

    $trainer_rating = 0;
    if($count_students_trainer > 0){
        $avg_trainer_percent = $total_trainer_percent / $count_students_trainer;
        $trainer_rating = round(($avg_trainer_percent / 100) * 5, 1);
    }

    // Step 7: Infrastructure Rating
    $sql_infra = "
    SELECT td.survey_id,
        (COALESCE(cf.communication,0)
        + COALESCE(cf.infra,0)
        + COALESCE(cf.activity,0) 
        + COALESCE(cf.overall_fdb,0)) AS infra_points
    FROM training_dts td
    LEFT JOIN comp_feedback cf ON td.survey_id = cf.survey_id
    WHERE td.company_id = ?
    ";
    $stmt_infra = $conn->prepare($sql_infra);
    if(!$stmt_infra){ die("Prepare failed: ".$conn->error); }
    $stmt_infra->bind_param("i", $company_id);
    $stmt_infra->execute();
    $result_infra = $stmt_infra->get_result();

    $total_infra_percent = 0;
    $count_students_infra = 0;
    while($row = $result_infra->fetch_assoc()){
        $percent_infra = ($row['infra_points'] / 16) * 100; // out of 16
        $total_infra_percent += $percent_infra;
        $count_students_infra++;
    }
    $stmt_infra->close();

    $infra_rating = 0;
    if($count_students_infra > 0){
        $avg_infra_percent = $total_infra_percent / $count_students_infra;
        $infra_rating = round(($avg_infra_percent / 100) * 5, 1);
    }

    // ======== Step 8: Fetch Reviews (Merged) ========

    // Company Feedback
    $companyReviews = [];
    $compSql = "
    SELECT cf.remark, cf.overall_fdb
    FROM comp_feedback cf
    WHERE cf.company_id = $company_id
    AND cf.remark IS NOT NULL      
    AND TRIM(cf.remark) <> ''
    AND LOWER(TRIM(cf.remark)) <> 'no'
    ";
    $compRes = mysqli_query($conn, $compSql);
    while ($row = mysqli_fetch_assoc($compRes)) {
        $companyReviews[] = [
            'remark' => $row['remark'],
            'rating' => (int)$row['overall_fdb'] 
        ];
    }

    // Trainer Feedback
    $trainerReviews = [];
    $trainerSql = "
    SELECT tf.remark, tf.overall_fdb, tr.name AS trainer_name
    FROM trainer_feedback tf
    JOIN trainer_dts tr ON tf.trainer_id = tr.trainer_id
    WHERE tf.company_id = $company_id
    AND tf.remark IS NOT NULL
    AND TRIM(tf.remark) <> ''
    AND LOWER(TRIM(tf.remark)) <> 'no'
    ";
    $trainerRes = mysqli_query($conn, $trainerSql);
    while ($row = mysqli_fetch_assoc($trainerRes)) {
        $trainerReviews[] = [
            'trainer' => $row['trainer_name'],
            'remark' => $row['remark'],
            'rating' => (int)$row['overall_fdb']
        ];
    }

    // Training Remarks
    $trainingReviews = [];
    $trainSql = "
    SELECT remark, overall_exp 
    FROM training_dts 
    WHERE company_id = $company_id
    AND remark IS NOT NULL
    AND TRIM(remark) <> ''
    AND LOWER(TRIM(remark)) <> 'no'
    ";
    $trainRes = mysqli_query($conn, $trainSql);
    while ($row = mysqli_fetch_assoc($trainRes)) {
        $trainingReviews[] = [
            'remark' => $row['remark'],
            'rating' => (int)$row['overall_exp']
        ];
    }

} else {
    die("<h2 style='text-align:center;color:red;margin-top:40px;'>Invalid Company ID</h2>");
}

// Helper function to show stars dynamically
function renderStars($rating){
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    return str_repeat('★', $fullStars) . str_repeat('✩', $halfStar + $emptyStars);
} 

// Star helper for tech cards (already defined)
function generateStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<span style="color:gold; font-size:18px;">★</span>';
        } else {
            $stars .= '<span style="color:#ccc; font-size:18px;">★</span>';
        }
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo $company['company_name']; ?> | Company Details</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
<style>
/* Your existing CSS here (company card, stats, tech-grid) */
:root{--bg:#f8fafc;--surface:#fff;--text:#111827;--muted:#475569;--navy:#1e293b;--accent:#0ea5e9;--border:#e2e8f0;--star:#facc15;}
body{margin:0;font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);}
.company-card{max-width:1100px;margin:28px auto;background:var(--surface);border-radius:20px;padding:30px;box-shadow:0 8px 25px rgba(14,165,233,.15);border:1px solid var(--border);}
.company-header{display:flex;align-items:center;gap:20px;flex-wrap:wrap;}
.company-logo{width:70px;height:70px;border-radius:50%;overflow:hidden;border:2px solid black;flex-shrink:0;}
.company-logo img{width:100%;height:100%;object-fit:cover;}
.company-details h1{color:crimson;font-size:25px;margin:0;}
.company-details p{margin:6px 0;color: var(--text);font-size:15px;}
.company-details a{color: var(--accent);font-weight:600;text-decoration:none;}
.company-stats{margin-top:20px;display:flex;flex-wrap:wrap;gap:16px;}
.stat-box{flex:1 1 220px;background: var(--surface);border-radius:14px;padding:14px;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(0,0,0,.08);border:1px solid var(--border);transition:.25s ease;border-top:4px solid crimson;}
.stat-box:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(14,165,233,.22);}
.stat-box i{font-size:20px;margin-bottom:6px;color: var(--muted);}
.stat-title{font-size:14px;color:var(--muted);margin-bottom:4px;}
.stat-value{font-size:18px;font-weight:600;color: var(--accent);}
.stat-rating{color:orange;font-weight:600;font-size:18px;}
.section-title{max-width:1100px;margin:24px auto 10px;padding:0 16px;display:flex;align-items:center;gap:12px;color: var(--navy);}
.section-title h2{margin:0;font-size:20px;}
.rule{height:1px;background:var(--border);flex:1;}
.tech-grid{max-width:1100px;margin:10px auto 24px;padding:0 16px;display:grid;grid-template-columns:repeat(auto-fit, minmax(210px,1fr));gap:16px;}
.tech-card{background:var(--surface);border-radius:14px;padding:16px;box-shadow:0 6px 16px rgba(0,0,0,.08);border:1px solid var(--border);transition:.25s ease;position:relative;overflow:hidden;}
.tech-card:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(14,165,233,.18);border-color:rgba(14,165,233,.35);}
.tech-name{font-weight:600;color:var(--text);margin:0 0 6px;font-size:14px;}
.tech-stars{color:var(--star);font-size:18px; font-weight:700; letter-spacing:1px; margin-bottom:4px;}
.tech-stars b{color:var(--accent); margin-left:6px; font-size:16px;}
.progress-wrap{margin-top:10px;}
.progress{width:100%;height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;}
.progress > span{display:block;height:100%;width:0;background: var(--accent);border-radius:999px;transition: width 1.2s ease;}
.tech-badge{position:absolute;top:10px;right:10px;font-size:11px;color:#0b5ea8;background:#e6f3ff;padding:4px 8px;border-radius:999px;border:1px solid #cfe6ff;font-weight:700;}
.review-card{max-width:1100px;margin:20px auto;padding:16px;background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);border:1px solid #e2e8f0;}
.review-stars{color:var(--star);font-size:18px;margin-bottom:6px;}
.review-remark{font-size:14px;color:#111;margin:0;}
.review-trainer{font-size:13px;color:#666;margin-bottom:4px;}
</style>
</head>
<body>

<?php include('header.php'); ?>

<!-- Company Card -->
<section class="company-card" data-aos="zoom-in">
  <div class="company-header">
    <div class="company-logo sy">
      <img src="logo/<?php echo $company['logo']; ?>" alt="<?php echo $company['company_name']; ?>">
    </div>
    <div class="company-details">
      <h1><?php echo $company['company_name']; ?></h1>
      <p><i class="fas fa-globe"></i> Website: <a href="<?php echo $company['website']; ?>" target="_blank"><?php echo $company['website']; ?></a></p>
      <p><i class="fas fa-map-marker-alt"></i> Location: <?php echo $company['location']; ?></p>
    </div>
  </div>

  <div class="company-stats">
    <div class="stat-box">
      <i class="fas fa-user-graduate"></i>
      <div class="stat-title">Students Trained</div>
      <div class="stat-value counter" data-target="<?php echo $total_students; ?>">0</div>
    </div>
    <div class="stat-box">
      <i class="fas fa-star"></i>
      <div class="stat-title">Overall Rating</div>
      <div class="stat-rating"><?php echo number_format($overall_rating, 1); ?>/5</div>
    </div>
    <div class="stat-box">
      <i class="fas fa-star"></i>
      <div class="stat-title">Trainer Rating</div>
      <div class="stat-rating"><?php echo number_format($trainer_rating, 1); ?>/5</div>
    </div>
    <div class="stat-box">
      <i class="fas fa-star"></i>
      <div class="stat-title">Infrastructure Rating</div>
      <div class="stat-rating"><?php echo number_format($infra_rating, 1); ?>/5</div>
    </div>
  </div>
</section>

<!-- Technology Cards -->
<div class="section-title" data-aos="fade-right">
  <h2>Technology-wise Ratings (Cards)</h2><div class="rule"></div>
</div>
<section class="tech-grid">
<?php foreach($tech_data as $tech): ?>
  <div class="tech-card" data-aos="flip-left">
    <span class="tech-badge"><?php echo $tech['avg_rating']; ?></span>
    <p class="tech-name"><?php echo $tech['technology_name']; ?></p>
    <p class="tech-stars"><?php echo generateStars($tech['avg_rating']); ?> <b><?php echo $tech['student_count']; ?></b></p>
    <div class="progress-wrap">
      <div class="progress"><span style="width:<?php echo $tech['student_count']*10; ?>%"></span></div>
    </div>
  </div>
<?php endforeach; ?>
</section>

<!-- Reviews Section -->
<div class="section-title" data-aos="fade-right">
  <h2>Student Reviews</h2><div class="rule"></div>
</div>
<div style="max-width:1100px;margin:auto;padding:0 16px;">
    <!-- Company Feedback -->
    <?php if(!empty($companyReviews)): ?>
        <h3>🏢 Company Feedback</h3>
        <?php foreach($companyReviews as $rev): ?>
            <div class="review-card" data-aos="fade-up">
                <div class="review-stars"><?php echo generateStars($rev['rating']); ?></div>
                <p class="review-remark"><?php echo htmlspecialchars($rev['remark']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Trainer Feedback -->
    <?php if(!empty($trainerReviews)): ?>
        <h3>👨‍🏫 Trainer Feedback</h3>
        <?php foreach($trainerReviews as $rev): ?>
            <div class="review-card" data-aos="fade-up">
               <!-- <p class="review-trainer">Trainer: <?php //echo htmlspecialchars($rev['trainer']); ?></p>-->
                <div class="review-stars"><?php echo generateStars($rev['rating']); ?></div>
                <p class="review-remark"><?php echo htmlspecialchars($rev['remark']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Training Feedback -->
    <?php if(!empty($trainingReviews)): ?>
        <h3>🎓 Training Feedback</h3>
        <?php foreach($trainingReviews as $rev): ?>
            <div class="review-card" data-aos="fade-up">
                <div class="review-stars"><?php echo generateStars($rev['rating']); ?></div>
                <p class="review-remark"><?php echo htmlspecialchars($rev['remark']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if(empty($companyReviews) && empty($trainerReviews) && empty($trainingReviews)): ?>
        <p style="text-align:center;color:#555;">No reviews available for this company.</p>
    <?php endif; ?>
</div>
  <?php
  include_once('footer.php');

?>
<script>
const counters = document.querySelectorAll('.counter');
counters.forEach(counter => {
  const update = () => {
    const target = +counter.getAttribute('data-target');
    const current = +counter.innerText;
    const inc = Math.max(1, Math.ceil(target / 60));
    if(current < target){
      counter.innerText = current + inc;
      setTimeout(update, 18);
    } else {
      counter.innerText = target;
    }
  };
  update(); 
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
AOS.init({ duration: 900, once: true });
gsap.from(".logo", { opacity: 0, y: -30, duration: 0.9 });
gsap.from(".nav-links li", { opacity: 0, y: -18, duration: 0.7, stagger: 0.15 });
</script>
</body>
</html>
