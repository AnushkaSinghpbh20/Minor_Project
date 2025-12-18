<?php
include('connection.php'); // Database connection

// ✅ Total Unique Locations Count
$totalLocationsQuery = $conn->query("SELECT COUNT(DISTINCT location) AS total FROM training_dts WHERE location IS NOT NULL AND location <> ''");
$totalLocations = $totalLocationsQuery ? $totalLocationsQuery->fetch_assoc()['total'] : 0;

// ✅ Fetch locations with student count and company list (including company_id)
$sql = "
SELECT 
    td.location,
    COUNT(DISTINCT td.survey_id) AS student_count,
    GROUP_CONCAT(DISTINCT ci.company_id, ':', ci.company_name SEPARATOR ',') AS companies
FROM training_dts td
LEFT JOIN company_info ci ON td.company_id = ci.company_id
WHERE td.location IS NOT NULL AND td.location <> ''
GROUP BY td.location
ORDER BY td.location ASC
";
$locationData = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Locations Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
body {margin:0;font-family:'Poppins',sans-serif;background:#f8fafc;color:#111827;}
h1,h2{text-align:center;margin:20px 0 10px;font-weight:700;color:#1e293b;}
p.description{text-align:center;max-width:850px;margin:0 auto 25px;color:#475569;font-size:1rem;line-height:1.6;}
.total-location{display:flex;justify-content:center;margin-bottom:30px;font-size:1.5rem;font-weight:700;color:#0ea5e9;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:18px; max-width:1200px;margin:0 auto 50px;padding:0 15px;}
.info-card{background:#ffffff;border-radius:15px;padding:20px;display:flex;flex-direction:column;justify-content:flex-start;cursor:pointer;transition: transform 0.3s, box-shadow 0.3s;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-top: 4px solid #0ea5e9;}
.info-card:hover{transform:translateY(-6px);box-shadow:0 10px 20px rgba(14,165,233,0.25);}
.info-card h3{font-size:1.3rem;font-weight:700;margin-bottom:8px;}
.info-card p{font-size:1rem;margin:4px 0;font-weight:500;color:#334155;}
.company-container{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;transition: all 0.3s ease;max-height:0;opacity:0;overflow:hidden;}
.info-card.active .company-container{max-height:200px;opacity:1;}
.company-badge{display:inline-block;padding:5px 12px;border-radius:15px;font-size:0.85rem;font-weight:600;background:#e2e8f0;color:#1e293b;box-shadow:0 3px 6px rgba(0,0,0,0.2);transition: transform 0.2s, box-shadow 0.2s;text-decoration:none;}
.company-badge:hover{transform: translateY(-2px) scale(1.05);box-shadow: 0 5px 12px rgba(0,0,0,0.25);background: #e2e8f0;color:#1e293b;}
</style>
</head>
<body>

<?php include('header.php'); ?>

<h1>🌍 Locations Dashboard</h1>
<p class="description">Explore all <strong>training locations</strong> along with the number of students trained and associated companies.</p>

<div class="total-location">Total Locations: <?php echo $totalLocations; ?></div>

<div class="grid">
<?php if($locationData && $locationData->num_rows>0): ?>
    <?php while($loc = $locationData->fetch_assoc()): ?>
        <div class="info-card">
            <h3><?php echo $loc['location']; ?></h3>
            <p>👨‍🎓 <?php echo $loc['student_count']; ?> Students Trained</p>
            <div class="company-container">
                <?php
                if($loc['companies']){
                    $companies = explode(',', $loc['companies']);
                    foreach($companies as $c){
                        list($compId, $compName) = explode(':', $c);
                        echo '<a href="company.php?compId='.trim($compId).'" class="company-badge">'.trim($compName).'</a>';
                    }
                } else {
                    echo '<span class="company-badge">N/A</span>';
                }
                ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center;">No location data found!</p>
<?php endif; ?>
</div>

<?php include('footer.php'); ?>

<script>
// Toggle company list on card click
const cards = document.querySelectorAll('.info-card');
cards.forEach(card => {
    card.addEventListener('click', () => {
        card.classList.toggle('active');
    });
});
</script>

</body>
</html>
