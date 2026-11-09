<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <title>Home Page</title>
  <meta name="description" content="Y-Global System Solution - Professional web development, graphics, and video editing services">
  <meta name="keywords" content="web development, graphics design, video editing, logo design, Y-Global System Solution">
  <!-- Favicons -->
  <link href="assets/img/favicon1.png" rel="icon">
  <link href="assets/img/apple-touch-icon1.png" rel="apple-touch-icon">
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    /* Prevent horizontal scrolling globally */
    html, body {
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      width: 100%;
    }
    /* Header Styling */
    .header {
      background: linear-gradient(90deg, #033f5a 0%, #045c7f 100%);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
    }
    /* Remove padding-top from main to eliminate white space */
    .main {
      margin-top: 0;
      padding-top: 0;
    }
    /* Ensure content after carousel has proper spacing */
    .parallax {
      margin-top: 20px; /* Add small margin to prevent content from touching carousel */
    }
    /* General Navmenu Links */
    .navmenu ul li a {
      color: white;
      font-weight: 500;
      padding: 10px 15px;
      transition: color 0.3s ease, transform 0.3s ease;
    }
    .navmenu ul li a:hover {
      color: #00c4ff;
      transform: translateY(-2px);
    }
    /* Styling for SignUp and Login Links */
    .navmenu ul li a[href="signup.php"],
    .navmenu ul li a[href="login.php"] {
      background: linear-gradient(90deg, #0288d1 0%, #03a9f4 100%);
      border-radius: 25px;
      padding: 8px 20px;
      margin-left: 10px;
      color: white;
      font-weight: 600;
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(2, 136, 209, 0.3);
    }
    /* Hover Effect for SignUp and Login */
    .navmenu ul li a[href="signup.php"]:hover,
    .navmenu ul li a[href="login.php"]:hover {
      background: linear-gradient(90deg, #0277bd 0%, #0288d1 100%);
      transform: translateY(-3px);
      box-shadow: 0 4px 15px rgba(2, 136, 209, 0.5);
      color: #ffffff;
    }
    /* Shine Effect on Hover */
    .navmenu ul li a[href="signup.php"]::before,
    .navmenu ul li a[href="login.php"]::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: 0.5s;
    }
    .navmenu ul li a[href="signup.php"]:hover::before,
    .navmenu ul li a[href="login.php"]:hover::before {
      left: 100%;
    }
    /* Carousel Styling (Original) */
    .carousel-inner img {
      height: 590px;
    }
    /* Parallax Section */
    .parallax1,
    .parallax2 {
      background-attachment: fixed;
      background-position: center;
      background-repeat: no-repeat;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      overflow: hidden;
      position: relative;
    }
    .parallax1 {
      background-image: url('assets/img/web.jpg');
    }
    .parallax2 {
      background-image: url('assets/img/webdevelopment.jpg');
    }
    .page-title {
      background: rgba(0, 0, 0, 0.5);
      text-align: center;
      color: white;
      padding: 20px 30px;
      font-variant: small-caps;
      font-weight: 700;
      font-size: 60px;
      font-family: 'Raleway', fantasy;
      letter-spacing: 3px;
      max-width: 90%;
      box-sizing: border-box;
      border-radius: 10px;
    }
                                 
    /* Responsive Adjustments */
    @media (max-width: 991px) {
      .navmenu ul li a {
        color: black;
      }
      .navmenu ul li a[href="signup.php"],
      .navmenu ul li a[href="login.php"] {
        background: linear-gradient(90deg, #0288d1 0%, #03a9f4 100%);
        color: white;
        width: 20%;
        display: inline-block;
        margin: 5px auto;
      }
      .carousel-inner img {
        height: 400px;
      }
      .parallax1,
      .parallax2 {
        background-attachment: scroll; /* Fallback for mobile */
        min-height: 80vh;
      }
      .page-title {
        font-size: 40px;
        padding: 15px 20px;
        max-width: 95%;
      }
    }
    @media (max-width: 576px) {
      .navmenu ul li a {
        padding: 8px 10px;
        font-size: 0.9rem;
      }
      .navmenu ul li a[href="signup.php"],
      .navmenu ul li a[href="login.php"] {
        width: 90%; /* Even wider for very small screens */
        max-width: 80px;
        padding: 8px 12px;
        font-size: 0.9rem;
      }
      .carousel-inner img {
        height: 300px;
      }
      .parallax1,
      .parallax2 {
        min-height: 350px !important; /* Forced 300px height on small and minimized devices */
      }
      .page-title {
        font-size: 28px;
        padding: 10px 15px;
        max-width: 100%;
        letter-spacing: 2px;
      }
    }
    @media (max-width: 576px) {
      .navmenu ul li a {
        padding: 5px 10px;
        font-size: 0.9rem;
      }
    }
    /* Service Details Image (Original) */
    @media (max-width: 768px) {
      .services-img {
        max-height: 400px;
        width: 100%;
      }
    }
    @media (max-width: 576px) {
      .services-img {
        max-height: 300px;
        width: 100%;
      }
    }
  </style>
</head>
<body class="services-page">
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main class="main">
    <!-- Parallax Section 1 -->
    <div class="parallax1">
      <div class="page-title">Welcome To Y-Global System Solution</div>
    </div>
     <br>
    <!-- Carousel (Original Implementation) -->
    <div id="demo" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-indicators">
        <!-- Add indicators if needed -->
      </div>
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="assets/img/s8.jpg" alt="Slide 1" class="d-block w-100">
        </div>
        <div class="carousel-item">
          <img src="assets/img/s33.jpg" alt="Slide 2" class="d-block w-100">
        </div>
        <div class="carousel-item">
          <img src="assets/img/s4.jpg" alt="Slide 3" class="d-block w-100">
        </div>
        <div class="carousel-item">
          <img src="assets/img/s55.jpg" alt="Slide 4" class="d-block w-100">
        </div>
        <div class="carousel-item">
          <img src="assets/img/s7.jpg" alt="Slide 5" class="d-block w-100">
        </div>
        <div class="carousel-item">
          <img src="assets/img/s1.png" alt="Slide 5" class="d-block w-100">
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#demo" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#demo" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
    <!-- Responsive Styles for Carousel (Original) -->
    <style>
      .carousel-inner img {
        height: 590px;
      }
      @media (max-width: 991px) {
        .carousel-inner img {
          height: 400px;
        }
      }
      @media (max-width: 576px) {
        .carousel-inner img {
          height: 300px;
        }
      }
    </style>
    <!-- Service Details Section -->
    <section id="service-details" class="service-details section">
      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
            <div class="services-list">
              <p><strong>Website Development:</strong> is the process of creating and maintaining websites. It includes both front end and back end developmet.</p>
              <p><strong>Front-End Development:</strong> This focuses on the user-facing aspects of a website.</p>
              <p><strong>Back-End Development:</strong> This involves the server-side technologies and logic that power the functionality and data management of a website.</p>
              <p><strong>Full-Stack Development:</strong> includes both front-end and back-end development.</p>
              <p><strong>Web Design:</strong> This encompasses the visual design and user experience of a website, including layout, color schemes, typography, and graphics.</p>
              <p><strong>Web Hosting and Deployment:</strong> The process of making a website accessible on the internet.</p>
              <p><strong>Uses Of Websites:</strong> Websites are the powerful tools for promotion and advertisement for needs of both individuals and large organizations.</p>
            </div>
          </div>
          <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
            <img src="assets/img/web.jpg" alt="" class="img-fluid services-img" style="width: 100%; height: auto; max-height: 700px;">
          </div>
        </div>
      </div>
    </section>
    <!-- Parallax Section 2 -->
    <div class="parallax2">
      <div class="page-title">Website and System Development</div>
    </div>
                                   
    <!-- Portfolio Details Section -->
    <section id="portfolio-details" class="portfolio-details section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
          <div class="col-lg-8">
            <div class="portfolio-details-slider swiper init-swiper">
              <script type="application/json" class="swiper-config">
                {
                  "loop": true,
                  "speed": 600,
                  "autoplay": {
                    "delay": 5000
                  },
                  "slidesPerView": "auto",
                  "pagination": {
                    "el": ".swiper-pagination",
                    "type": "bullets",
                    "clickable": true
                  }
                }
              </script>
              <div class="swiper-wrapper align-items-center">
                <div class="swiper-slide">
                  <img src="assets/img/portfolio/web.png" alt="">
                </div>
                <div class="swiper-slide">
                  <img src="assets/img/portfolio/graphics.png" alt="">
                </div>
                <div class="swiper-slide">
                  <img src="assets/img/portfolio/video.png" alt="">
                </div>
                <div class="swiper-slide">
                  <img src="assets/img/portfolio/logo.png" alt="">
                </div>
              </div>
              <div class="swiper-pagination"></div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="portfolio-info" data-aos="fade-up" data-aos-delay="200">
              <h3>Our Service Highlights</h3>
              <ul>
                <li><strong>WEBSITE DESIGNIND</strong>: FULLSTUCK WEBSITE</li>
                <li><strong>GRAPHICS DESIGNING</strong>: BEST DESIGN</li>
                <li><strong>VIDEO EDITING</strong>: BEST EDITION</li>
                <li><strong>LOGO DESIGNING</strong>: BEST DESIGNING</li>
                <li><p>These are the key service highlights we are proud to offer our clients. We are always ready to work with you and welcome the opportunity to discuss your specific needs.</p></li>
                <li><p>We are happy to make your dream real and to be part of your success. We are committed to helping you achieve your goals, and we encourage you to reach out to us to explore how we can collaborate to make your dreams a reality.</p></li>
                <li><h3>Why Choose Us?</h3></li>
                <li><p>We’re dedicated to turning your ideas into reality with a strong focus on usability and performance, so your website works effortlessly for you and your customers.</p></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
  <footer id="footer" class="footer dark-background" style="background-color: #033f5a;">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="index.php" class="logo d-flex align-items-center">
            <span class="sitename">Y-Global System Solution</span>
          </a>
          <div class="footer-contact pt-3">
            <p>Ethiopia, AddisAbaba</p>
            <p class="mt-3"><strong>Phone:</strong> <span>+251 926-862-255</span></p>
            <p><strong>Email:</strong> <span>yared1357@gmail.com</span></p>
          </div>
          <div class="social-links d-flex mt-4">
            <a href="https://www.facebook.com/profile.php?id=100057756135221"><i class="bi bi-facebook"></i></a>
            <a href="mailto:yared1357@gmail.com"><i class="bi bi-envelope"></i></a>
            <a href="https://www.linkedin.com/in/yared-alebachewu-84b20b252/"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="portfolio.php">Portfolio</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Our Services</h4>
          <ul>
            <li>Website Designing</li>
            <li>Graphics Designing</li>
            <li>Video Editing</li>
            <li>Logo Designing</li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-12 footer-newsletter">
          <h4>From Developer</h4>
          <p>I am always ready to work with you and to satisfy your needs. We are very dedicated for our customers orders and we work hard to create everything as our customers need.</p>
          <p>Working with us always makes happier and very satisfied and makes your organization and profession very competitive internationally.</p>
        </div>
      </div>
    </div>
    <div class="container copyright text-center mt-4">
      <p><span>Copyright© <?php echo date("Y"); ?></span> <strong class="px-1 sitename">|| Designed by Yared Alebachewu</strong> <span>All Rights Reserved</span></p>
    </div>
  </footer>
  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <!-- Preloader -->
  <div id="preloader"></div>
  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
</body>
</html>