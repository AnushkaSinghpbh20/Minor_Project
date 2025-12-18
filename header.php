<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Dashboard</title>

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      color: #111827;
      background: #f8fafc;
    }

    /* Navbar */
    .navbar {
      background: #1e293b;
      padding: 12px 0;
      box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.3);
      position: relative;
      z-index: 100;
    }

    .navbar .container {
      max-width: 1200px;
      margin: auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
    }

    .logo {
      color: #ffffff;
      font-size: 22px;
      font-weight: 600;
      letter-spacing: 1px;
    }

    /* Navbar Links */
    .nav-links {
      list-style: none;
      display: flex;
      gap: 25px;
      margin: 0;
      padding: 0;
    }

    .nav-links li {
      position: relative;
    }

    .nav-links a,
    .dropbtn {
      color: #f1f5f9;
      text-decoration: none;
      font-weight: 500;
      cursor: pointer;
      transition: color 0.3s;
    }

    .nav-links a:hover,
    .dropbtn:hover {
      color: #0ea5e9;
    }

    /* Dropdown */
    .dropdown-menu {
      list-style: none;
      position: absolute;
      top: 35px;
      left: 0;
      background: #ffffff;
      min-width: 180px;
      display: none;
      flex-direction: column;
      border-radius: 6px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      padding: 10px 0;
      z-index: 1000;
    }

    .dropdown-menu li a {
      display: block;
      padding: 8px 15px;
      color: #1e293b;
      text-decoration: none;
    }

    .dropdown-menu li a:hover {
      background: #f1f5f9;
      color: #0ea5e9;
    }

    .dropdown:hover .dropdown-menu {
      display: block;
    }

    /* span {
      color: crimson;
    } */

    /* ✅ Mobile Menu Button */
    .menu-toggle {
      display: none;
      flex-direction: column;
      cursor: pointer;
    }

    .menu-toggle span {
      background: #fff;
      height: 3px;
      width: 25px;
      margin: 4px 0;
      transition: 0.4s;
    }

    /* ✅ Responsive Design */
    @media (max-width: 768px) {
      .nav-links {
        position: absolute;
        top: 60px;
        right: 0;
        background: #1e293b;
        flex-direction: column;
        width: 100%;
        display: none;
        text-align: center;
        padding: 15px 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      }

      .nav-links.active {
        display: flex;
      }

      .menu-toggle {
        display: flex;
      }

      .dropdown-menu {
        position: static;
        box-shadow: none;
        background: none;
        padding: 0;
      }

      .dropdown-menu li a {
        padding: 8px 0;
        color: #f1f5f9;
      }

      .dropdown-menu li a:hover {
        background: none;
        color: #0ea5e9;
      }
    }
  </style>
</head>

<body>
  <header class="navbar" data-aos="fade-down">
    <div class="container">
      <div class="logo">Training <span>Dashboard</span></div>

      <!-- ✅ Mobile Toggle Button -->
      <div class="menu-toggle" id="menu-toggle">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <nav>
        <ul class="nav-links" id="nav-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="allcompanies.php">Companies</a></li>
          <li><a href="students.php">Students</a></li>
          <li><a href="technology.php">Technologies</a></li>
          <li><a href="locations.php">Locations</a></li>
        </ul>
      </nav>
    </div>
  </header>
         
  <script>
    // ✅ Toggle mobile menu
    const toggle = document.getElementById("menu-toggle");
    const navLinks = document.getElementById("nav-links");

    toggle.addEventListener("click", () => {
      navLinks.classList.toggle("active");
      toggle.classList.toggle("open");
    });
  </script>
</body>
</html>
