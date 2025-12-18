<?php
include 'connection.php';

// ✅ Counts
$companies = $conn->query("SELECT COUNT(*) AS total FROM company_info")->fetch_assoc()['total'];
$students = $conn->query("SELECT COUNT(*) AS total FROM staging_table")->fetch_assoc()['total'];
$technologies = $conn->query("SELECT COUNT(*) AS total FROM training_tech")->fetch_assoc()['total'];
$locations = $conn->query("SELECT COUNT(DISTINCT location) AS total FROM training_dts")->fetch_assoc()['total'];

// ✅ Prepare company data with rating and student count
$companyData = [];
$allCompanies = $conn->query("SELECT company_id, company_name FROM company_info");

while($row = $allCompanies->fetch_assoc()){
    $compId = $row['company_id'];   
    $compName = $row['company_name'];

    // Total points for rating
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

    $totalPoints = 0;
    $studentCount = 0;
    while($r = $result->fetch_assoc()){
        $totalPoints += $r['total_points'];
        $studentCount++;
    }
    $stmt->close();

    $avgRating = 0;
    if($studentCount > 0){
        $avgRating = round(($totalPoints / ($studentCount * 45)) * 5, 1);
    }

    $companyData[] = [
        'name' => $compName,
        'rating' => $avgRating,
        'students' => $studentCount
    ];
}

// Prepare data for Chart.js
$labels = [];
$ratings = [];
$studentsCount = [];

foreach($companyData as $c){
    $labels[] = $c['name'];
    $ratings[] = $c['rating'];
    $studentsCount[] = $c['students'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Training Review Dashboard</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<!-- AOS Animation CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { margin: 0; font-family: 'Poppins', sans-serif; color: #111827; background: #f8fafc; }
h1, h2 { text-align: center; margin: 20px 0 10px 0; font-weight: 600; color: #1e293b; }
p.description { text-align: center; max-width: 850px; margin: 0 auto 25px auto; color: #475569; font-size: 1rem; line-height: 1.6; }
.container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px,1fr)); gap: 25px; padding: 30px; max-width: 1200px; margin: auto; }
.card { background: #fff; border-radius: 15px; padding: 25px 20px; text-align: center; box-shadow:0 6px 18px rgba(0,0,0,0.1); transition:0.3s; cursor: pointer; text-decoration: none; color: inherit; display: block; }
.card:hover { transform: translateY(-6px); box-shadow:0 10px 28px rgba(14,165,233,0.3); }
.icon-circle { width:70px; height:70px; margin:0 auto 15px auto; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px; background:#0ea5e9; color:#fff; }
.card h2 { font-size:1.3rem; margin:8px 0; font-weight:600; }
.card p { font-size:1.1rem; margin:5px 0; font-weight:bold; color:#334155; }
.chart-container { max-width:1000px; margin:40px auto; padding:20px; background:#fff; border-radius:15px; box-shadow:0 6px 18px rgba(0,0,0,0.1); }
</style>
</head> 
<body>

<?php include('header.php'); ?>

<h1 data-aos="zoom-in">📊 Summer <span>Training Dashboard</span></h1>
<p class="description" data-aos="fade-up">
  Welcome to the <strong>Summer Training Review Dashboard</strong>!
  Quick insights into partner companies, enrolled students, and ratings.
</p>

<!-- ✅ Counters -->
<div class="container">
  <a href="allCompanies.php" class="card" data-aos="flip-left"><div class="icon-circle"><i class="fas fa-building"></i></div><h2>Companies</h2><p class="counter" data-target="<?php echo $companies; ?>">0</p></a>
  <a href="students.php" class="card" data-aos="flip-left" data-aos-delay="100"><div class="icon-circle"><i class="fas fa-user-graduate"></i></div><h2>Students</h2><p class="counter" data-target="<?php echo $students; ?>">0</p></a>
  <a href="technology.php" class="card" data-aos="flip-left" data-aos-delay="200"><div class="icon-circle"><i class="fas fa-laptop-code"></i></div><h2>Technology</h2><p class="counter" data-target="<?php echo $technologies; ?>">0</p></a>
  <a href="locations.php" class="card" data-aos="flip-left" data-aos-delay="300"><div class="icon-circle"><i class="fas fa-location-dot"></i></div><h2>Locations</h2><p class="counter" data-target="<?php echo $locations; ?>">0</p></a>
</div>

<!-- ✅ Company Ratings Chart -->
<div class="chart-container" data-aos="fade-up">
  <h2>Company Ratings & Student Count</h2>
  <canvas id="ratingsChart"></canvas>
</div>

<?php include('footer.php'); ?>

<!-- ✅ Counter Script -->
<script>
const counters = document.querySelectorAll('.counter');
const speed = 100;
counters.forEach(counter => {
  const updateCount = () => {
    const target = +counter.getAttribute('data-target');
    const count = +counter.innerText;
    const increment = Math.ceil(target / speed);
    if(count < target){
      counter.innerText = count + increment;
      setTimeout(updateCount, 20);
    } else {
      counter.innerText = target;
    }
  };
  updateCount();
});
</script>

<!-- ✅ Chart.js Script -->
<script>  
const labels = <?php echo json_encode($labels); ?>; 
const ratings = <?php echo json_encode($ratings); ?>;
const studentsCount = <?php echo json_encode($studentsCount); ?>;

const ctx = document.getElementById('ratingsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
datasets: [ 
  { 
    label: 'Student Count',
    data: studentsCount,
    backgroundColor: 'rgba(14, 165, 233, 0.6)',
    borderColor: 'rgba(14, 165, 233, 1)',
    borderWidth: 1,
    borderRadius: 8,
    yAxisID: 'y1',
    type: 'bar'   // 🔹 Student count bar chart bhi rahega
  },
  {
    label: 'Average Rating (out of 5)',
    data: ratings,
    type: 'line',  // 🔸 Rating ab line chart hoga
    borderColor: 'rgba(251, 191, 36, 1)',
    backgroundColor: 'rgba(251, 191, 36, 0.2)',
    fill: true,
    tension: 0.3,
    yAxisID: 'y'
  }
]

    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                mode: 'index',
                intersect: false
            },
            legend: { display: true }
        },
        interaction: {
            mode: 'nearest',
            intersect: true
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 5,
                title: { display: true, text: 'Rating' },
                ticks: { stepSize: 0.5 }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                title: { display: true, text: 'Student Count' },
                grid: { drawOnChartArea: false }
            }
        }
    }
});
</script>

<!-- GSAP + AOS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
AOS.init({ duration: 1000, once: true });
gsap.from(".icon-circle", { opacity: 0, y: -30, duration: 1, stagger: 0.2 });
</script>

</body>
</html>   