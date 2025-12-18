<?php
include('connection.php'); 

$totalTech = $conn->query("SELECT COUNT(*) AS total FROM training_tech")->fetch_assoc()['total'];

$sql = "
SELECT 
    tt.tech_id,
    tt.technology_name,
    COUNT(DISTINCT stm.survey_id) AS student_count,
    GROUP_CONCAT(DISTINCT ci.company_id, ':', ci.company_name SEPARATOR ',') AS companies
FROM training_tech tt
LEFT JOIN student_tech_map stm ON tt.tech_id = stm.tech_id
LEFT JOIN training_dts td ON stm.survey_id = td.survey_id
LEFT JOIN company_info ci ON td.company_id = ci.company_id
GROUP BY tt.tech_id, tt.technology_name
ORDER BY student_count DESC
";
$techData = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Technologies Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
/* --- Your original styles --- */
body {margin:0;font-family:'Poppins',sans-serif;background:#f8fafc;color:#111827;}
h1,h2{text-align:center;margin:20px 0 10px;font-weight:700;color:#1e293b;}
p.description{text-align:center;max-width:850px;margin:0 auto 25px;color:#475569;font-size:1rem;line-height:1.6;}
.total-tech{display:flex;justify-content:center;margin-bottom:30px;font-size:1.5rem;font-weight:700;color:#0ea5e9;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:18px;max-width:1200px;margin:0 auto 50px;padding:0 15px;}
.tech-box{background:#ffffff;border-radius:15px;padding:18px 15px;display:flex;flex-direction:column;justify-content:flex-start;cursor:pointer;transition: transform 0.3s, box-shadow 0.3s;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-top: 4px solid #0ea5e9;}
.tech-box:hover{transform:translateY(-6px);box-shadow:0 10px 20px rgba(14,165,233,0.25);}
.tech-box h3{font-size:1.3rem;font-weight:700;margin-bottom:8px;}
.tech-box p{font-size:1rem;margin:4px 0;font-weight:500;color:#334155;}
.company-container{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;transition: all 0.3s ease;}
.company-badge{padding:4px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;background:#e2e8f0;color:#1e293b;transition: transform 0.2s, background 0.3s;text-decoration:none;display:inline-block;}
.company-badge:hover{transform:scale(1.05);background:#cbd5e1;color:#111;}
.tech-box.active .company-badge{transform: scale(1.1); background: linear-gradient(135deg, #0ea5e9, #3b82f6); color:#fff;}
</style>
</head>
<body>

<?php include('header.php'); ?>

<h1>💻 Technologies Dashboard</h1>
<p class="description">Explore technologies, see student enrollment, and associated companies in one glance.</p>

<div class="total-tech">Total Technologies: <?php echo $totalTech; ?></div>

<div class="grid">
<?php
if($techData && $techData->num_rows>0):
    while($tech=$techData->fetch_assoc()):
?>
    <div class="tech-box">
        <h3><?php echo $tech['technology_name']; ?></h3>
        <p>👨‍🎓 <?php echo $tech['student_count']; ?> Students</p>
        <div class="company-container">      
        <?php    
            if($tech['companies']){
                $companies = explode(',', $tech['companies']);
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
<?php endwhile; else: ?>
<p style="text-align:center;">No technology data found!</p>
<?php endif; ?>
</div>

<?php include('footer.php'); ?>

<script>
const boxes = document.querySelectorAll('.tech-box');
boxes.forEach(box => {
    box.addEventListener('click', () => {
        box.classList.toggle('active');
    });
});
</script>

</body>
</html>
