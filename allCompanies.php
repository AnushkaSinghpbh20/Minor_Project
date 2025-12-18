<?php
include 'connection.php';

// ✅ Companies Data
$companyData = $conn->query("SELECT * FROM company_info");

// ✅ Calculate overall rating dynamically for each company
$companies_ratings = [];
$allCompanies = $conn->query("SELECT company_id FROM company_info");
while($row = $allCompanies->fetch_assoc()){
    $compId = $row['company_id'];

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

    $stmt = $conn->prepare($sql_overall);
    $stmt->bind_param("i", $compId);
    $stmt->execute();
    $result = $stmt->get_result();

    $total_percent = 0;
    $count_students = 0;
    while($r = $result->fetch_assoc()){
        $percent = ($r['total_points'] / 45) * 100; // Max points = 45
        $total_percent += $percent;
        $count_students++;
    }
    $stmt->close();

    $overall_rating = 0;
    if($count_students > 0){
        $avg_percent = $total_percent / $count_students;
        $overall_rating = round(($avg_percent / 100) * 5, 1); // Scale to 5
    }

    $companies_ratings[$compId] = $overall_rating;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Our Training Partners</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- AOS Animation CSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
          
  <style>
    body { margin: 0; padding: 0; font-family: 'Playfair Display', serif; background: #f8fafc; color: #111827; }

    h2 { text-align: center; margin: 20px 0 10px 0; font-weight: 600; color: #1e293b; }
    p.description { text-align: center; max-width: 850px; margin: 0 auto 25px auto; color: #475569; font-size: 1rem; line-height: 1.6; }

    .companies-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
    }
    .company-card {
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
      background: #fff;
      transition: all 0.3s ease;
      text-align: center;
      padding: 20px;
      border-top: 4px solid #0ea5e9;
    }
    .company-card:hover { transform: translateY(-6px); box-shadow: 0 10px 25px rgba(14, 165, 233, 0.25); }
    .logo-circle {
      width: 90px; height: 90px; margin: 0 auto 12px;
      border-radius: 50%; overflow: hidden;
      display: flex; align-items: center; justify-content: center;
      border: 2px solid black; background: #fff;
    }
    .logo-circle img { width: 80%; height: 80%; object-fit: contain; }
    .company-card h3 { font-size: 1.1rem; margin: 6px 0; font-weight: 600; color: #1e293b; }
    .company-card p { font-size: 0.9rem; margin: 5px 0; color: #475569; }
    .rating { color: #facc15; margin: 8px 0; font-size: 0.95rem; }
    .company-card .btn {
      background: #0ea5e9; color: #fff; padding: 7px 15px;
      border-radius: 20px; text-decoration: none;
      font-weight: 500; font-size: 0.85rem;
      display: inline-block; margin-top: 8px; transition: 0.3s;
    }
    .company-card .btn:hover { background: #0284c7; }
    span { color: crimson; }
  </style>
</head>
<body>
<?php include('header.php'); ?>

  <!-- ✅ Dynamic Companies -->
  <h2 data-aos="fade-up">🏢 <span>Our Training</span> Partners</h2>
  <p class="description" data-aos="fade-up" data-aos-delay="100">
    Here are our <strong>Training Partner Companies</strong> providing students
    opportunities to grow with the latest technologies and real-world projects.
  </p>

  <div class="companies-grid">
    <?php while($row = $companyData->fetch_assoc()): ?>   
      <div class="company-card" data-aos="zoom-in"> 
        <div class="logo-circle">   
          <img src="logo/<?php echo $row['logo']; ?>" alt="<?php echo $row['company_name']; ?>">     
        </div>
        <h3><?php echo $row['company_name']; ?></h3>
        <p><i class="fas fa-map-marker-alt"></i> <?php echo $row['location']; ?></p>
        <div class="rating">
          <?php 
            $compRating = isset($companies_ratings[$row['company_id']]) ? $companies_ratings[$row['company_id']] : 0;
            echo str_repeat("⭐", floor($compRating));
            echo ($compRating - floor($compRating) >= 0.5 ? "✩" : "");
            echo " <b>($compRating)</b>";
          ?>
        </div>
        <a href="company.php?compId=<?=$row['company_id']?>" class="btn">View More</a>
      </div>     
    <?php endwhile; ?> 
  </div> 
  <?php
  include_once('footer.php');

?>

  <!-- AOS JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script>AOS.init({ duration: 1000, once: true });</script>

</body> 
</html>
