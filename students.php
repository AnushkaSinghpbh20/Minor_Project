<?php
include('connection.php'); 
include('header.php');

$result = $conn->query("SELECT COUNT(*) AS total FROM staging_table");

if($result){
    $students = $result->fetch_assoc()['total'];
} else {
    die("Query failed: " . $conn->error);
}

// ✅ Technology-wise Students
$techResult = $conn->query("
    SELECT tt.technology_name, COUNT(DISTINCT stm.survey_id) AS student_count
    FROM student_tech_map stm
    JOIN training_tech tt ON stm.tech_id = tt.tech_id
    GROUP BY stm.tech_id
");

// ✅ Location-wise Students
$locResult = $conn->query("
    SELECT location, COUNT(DISTINCT survey_id) AS student_count
    FROM training_dts
    GROUP BY location
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Students - Training Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
  <style>
    body { margin:0; padding:0; font-family: 'Poppins', sans-serif; color:#111827; background:#f8fafc; }
    h1,h2{text-align:center;margin:20px 0 10px 0;font-weight:600;color:#1e293b;}
    p.description{text-align:center;max-width:850px;margin:0 auto 25px auto;color:#475569;font-size:1rem;line-height:1.6;}
    .container{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px;padding:30px;max-width:1200px;margin:auto;}
    .card{background:#ffffff;border-radius:15px;padding:25px 20px;text-align:center;box-shadow:0 6px 18px rgba(0,0,0,0.1);transition:all 0.3s ease;text-decoration:none;color:inherit;display:block;}
    .card:hover{transform:translateY(-6px);box-shadow:0 10px 28px rgba(14,165,233,0.3);}
    .icon-circle{width:70px;height:70px;margin:0 auto 15px auto;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;background:#0ea5e9;color:#fff;}
    .card h2{font-size:1.3rem;margin:8px 0;font-weight:600;}
    .card p{font-size:1.1rem;margin:5px 0;font-weight:bold;color:#334155;}
    .grid-section{max-width:1200px;margin:30px auto;padding:20px;}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;}
    .info-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 4px 14px rgba(0,0,0,0.1);text-align:center;transition:0.3s;border-top:4px solid #0ea5e9;}
    .info-card:hover{transform:translateY(-5px);box-shadow:0 10px 20px rgba(14,165,233,0.25);}
    .info-card h3{font-size:1rem;margin:5px 0;font-weight:600;color:#1e293b;}
    .info-card p{font-size:0.9rem;color:#475569;font-weight:bold;}
  </style>
</head>   
<body>

<h1 data-aos="zoom-in">👩‍🎓 Students <span>Insights</span></h1>
<p class="description" data-aos="fade-up">
    This page shows overall <strong>student insights</strong> for the Summer Training program.
    Here you can check total enrolled students, their distribution by technology, location, and training companies.
</p>

<!-- Total Students -->
<div class="container">
  <div class="card" data-aos="flip-left">
    <div class="icon-circle"><i class="fas fa-user-graduate"></i></div>
    <h2>Total Students</h2>
    <p><?php echo $students; ?></p>
  </div>
</div>

<!-- Technology Wise Students -->
<div class="grid-section">
  <h2 data-aos="fade-up">💻 <span>Technology</span> Wise Students</h2>
  <div class="grid">
    <?php while($tech = $techResult->fetch_assoc()): ?>
      <div class="info-card" data-aos="zoom-in">
        <h3><?php echo $tech['technology_name']; ?></h3>
        <p><?php echo $tech['student_count']; ?> Students</p>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- Location Wise Students -->
<div class="grid-section">
  <h2 data-aos="fade-up">📍 Location <span>Wise Students</span></h2>
  <div class="grid">
    <?php while($loc = $locResult->fetch_assoc()): ?>
      <div class="info-card" data-aos="zoom-in">
        <h3><?php echo $loc['location']; ?></h3>
        <p><?php echo $loc['student_count']; ?> Students</p>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php include('footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>AOS.init({ duration: 1000, once: true });</script>
</body>
</html>
