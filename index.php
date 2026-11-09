<?php
include 'connect.php';

$homePortfolioQuery = "
    SELECT p.id, p.title, p.link, p.description, GROUP_CONCAT(pi.image_url) as images
    FROM portfolio p
    LEFT JOIN portfolio_image_mappings pim ON p.id = pim.portfolio_id
    LEFT JOIN portfolio_images pi ON pim.image_id = pi.id
    GROUP BY p.id
    ORDER BY p.id DESC
    LIMIT 6
";
$homePortfolioResult = $conn ? $conn->query($homePortfolioQuery) : false;
if ($conn) { $conn->close(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#033f5a">
  <link rel="manifest" href="manifest.php?v=6">
  <link rel="apple-touch-icon" href="assets/img/pwa-192.png?v=4">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <title>Home Page</title>
  <meta name="description" content="Y-Global System Solution - Professional web development, graphics, and video editing services">
  <meta name="keywords" content="web development, graphics design, video editing, logo design, Y-Global System Solution">
  <!-- Favicons -->
  <link href="assets/img/favicon1.png" rel="icon">
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/swiper@11.1.9/swiper-bundle.min.css" rel="stylesheet">
  <!-- Main CSS File -->
  <link href="assets/css/main.css?v=public-header-36" rel="stylesheet">
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
      background-image: url('assets/img/website.png');
    }
    .parallax2 {
      background-image: url('assets/img/webdevelopment.png');
    }
    .parallax1 .page-title {
      max-width: 90%;
      box-sizing: border-box;
      padding: 20px 30px;
      border-radius: 10px;
      background: rgba(0, 0, 0, 0.5);
      color: #fff;
      font-family: 'Raleway', fantasy;
      font-size: 60px;
      font-variant: small-caps;
      font-weight: 700;
      letter-spacing: 3px;
      text-align: center;
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
      .parallax1 .page-title {
        max-width: 95%;
        padding: 15px 20px;
        font-size: 40px;
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
      .parallax1 .page-title {
        max-width: 100%;
        padding: 10px 15px;
        font-size: 28px;
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

    .homepage-portfolio {
      padding: 40px 0 20px;
    }
    .homepage-portfolio .section-header {
      text-align: center;
      margin-bottom: 28px;
    }
    .homepage-portfolio .section-header h3 {
      font-size: clamp(2rem, 3vw, 2.8rem);
      font-weight: 700;
      color: #0b2540;
      margin-bottom: 12px;
    }
    .homepage-portfolio .section-header p {
      color: #51657c;
      max-width: 700px;
      margin: 0 auto;
      line-height: 1.7;
    }
    .portfolio-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 24px;
      margin-bottom: 20px;
    }
    .portfolio-item {
      text-align: center;
      padding: 15px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.9);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
      min-width: 0;
      overflow: hidden;
      box-shadow: 0 18px 36px rgba(11, 37, 64, 0.08);
    }
    .portfolio-item:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }
    .portfolio-item h3 {
      font-size: 1.5rem;
      margin-bottom: 15px;
      color: #1e3a8a;
      font-weight: 600;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    .portfolio-item p {
      font-size: 0.95rem;
      color: #4b5563;
      margin-bottom: 15px;
      word-wrap: break-word;
      overflow-wrap: break-word;
      flex-grow: 1;
    }
    .portfolio-item .swiper {
      width: 100%;
      height: 280px;
      margin-bottom: 20px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      flex-shrink: 0;
    }
    .portfolio-item .swiper-slide {
      display: flex;
      justify-content: center;
      align-items: center;
      background: #000;
    }
    .portfolio-item .swiper-slide img {
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      object-fit: contain;
      border-radius: 10px;
      transition: transform 0.3s ease;
    }
    .portfolio-item .swiper-slide img:hover {
      transform: scale(1.05);
    }
    .portfolio-item .swiper-button-prev,
    .portfolio-item .swiper-button-next {
      color: #ffffff;
      background: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s ease;
    }
    .portfolio-item .swiper-button-prev:hover,
    .portfolio-item .swiper-button-next:hover {
      background: rgba(0, 0, 0, 0.7);
    }
    .portfolio-item .swiper-button-prev::after,
    .portfolio-item .swiper-button-next::after {
      font-size: 20px;
    }
    .portfolio-item .swiper-pagination-bullet {
      background: #9ca3af;
    }
    .portfolio-item .swiper-pagination-bullet-active {
      background: #3b82f6;
    }
    .portfolio-item a {
      display: inline-block;
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      color: #ffffff;
      padding: 12px 24px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 600;
      transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4);
    }
    .portfolio-item a:hover {
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 18px rgba(79, 172, 254, 0.6);
    }
    .portfolio-see-more {
      text-align: center;
      margin-top: 28px;
    }
    .portfolio-see-more a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 14px 26px;
      border-radius: 999px;
      background: linear-gradient(135deg, #0d6fb8 0%, #1fa6d7 100%);
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      box-shadow: 0 12px 24px rgba(15, 113, 183, 0.2);
    }
    @media (max-width: 991px) {
      .portfolio-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }
    @media (max-width: 576px) {
      .homepage-portfolio {
        position: relative;
      }
      .portfolio-grid {
        display: flex;
        grid-template-columns: none;
        gap: 16px;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 12px 12px 16px;
        scroll-snap-type: x proximity;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: rgba(13, 111, 184, 0.7) rgba(13, 111, 184, 0.08);
      }
      .portfolio-grid::-webkit-scrollbar {
        height: 8px;
      }
      .portfolio-grid::-webkit-scrollbar-thumb {
        background: rgba(13, 111, 184, 0.7);
        border-radius: 999px;
      }
      .portfolio-grid::-webkit-scrollbar-track {
        background: rgba(13, 111, 184, 0.08);
        border-radius: 999px;
      }
      .portfolio-item {
        flex: 0 0 80%;
        max-width: 80%;
        min-width: 80%;
        padding: 12px;
        scroll-snap-align: start;
      }
      .portfolio-item .swiper {
        height: 220px;
      }
      .portfolio-mobile-nav {
        position: absolute;
        left: 8px;
        right: 8px;
        top: 52%;
        transform: translateY(-50%);
        display: flex;
        justify-content: space-between;
        align-items: center;
        pointer-events: none;
        z-index: 2;
      }
      .portfolio-mobile-nav button {
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d6fb8 0%, #1fa6d7 100%);
        color: #fff;
        box-shadow: 0 12px 20px rgba(15, 113, 183, 0.28);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        opacity: 0.96;
        pointer-events: auto;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
      }
      .portfolio-mobile-nav button:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 24px rgba(15, 113, 183, 0.30);
      }
      .portfolio-mobile-nav button:active {
        transform: scale(0.96);
      }
    }
  </style>
</head><body class="services-page mobile-app-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">
    <!-- Parallax Section 1 -->
    <div class="parallax1">
      <div class="page-title">Welcome To Y-Global System Solution</div>
    </div>
    <?php if ($homePortfolioResult && $homePortfolioResult->num_rows > 0): ?>
    <section class="homepage-portfolio" data-aos="fade-up" data-aos-delay="100">
      <div class="container">
        <div class="section-header">
          <h3>Featured Projects</h3>
          <p>Some of the recent work I have created and delivered for clients and businesses.</p>
        </div>
        <div class="portfolio-grid" id="mobilePortfolioCarousel">
          <?php while ($portfolio = $homePortfolioResult->fetch_assoc()): ?>
            <div class="portfolio-item" data-aos="fade-up" data-aos-delay="100"
              data-title="<?php echo htmlspecialchars($portfolio['title']); ?>"
              data-description="<?php echo htmlspecialchars($portfolio['description'] ?: ''); ?>">
              <h3><?php echo htmlspecialchars($portfolio['title']); ?></h3>
              <?php
                $portfolioImages = $portfolio['images'] ? array_filter(array_map('trim', explode(',', $portfolio['images']))) : [];
                if (!empty($portfolioImages)):
              ?>
                <div class="swiper">
                  <div class="swiper-wrapper">
                    <?php foreach ($portfolioImages as $image): ?>
                      <div class="swiper-slide">
                        <?php
                        $imagePath = ltrim(str_replace('\\', '/', $image), '/');
                        $imagePath = preg_replace('#^(?:\./)?admin/#i', '', $imagePath);
                        $imageUrl = 'admin/' . implode('/', array_map('rawurlencode', explode('/', $imagePath)));
                        ?>
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($portfolio['title']); ?> project image" loading="lazy">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <?php if (count($portfolioImages) > 1): ?>
                    <div class="swiper-button-prev" aria-label="Previous image"></div>
                    <div class="swiper-button-next" aria-label="Next image"></div>
                    <div class="swiper-pagination"></div>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <p>No images available for this project.</p>
              <?php endif; ?>
              <p><?php echo htmlspecialchars($portfolio['description'] ?: 'No description provided.'); ?></p>
              <a href="<?php echo htmlspecialchars($portfolio['link'] ?: 'portfolio.php'); ?>" target="_blank">View Project</a>
            </div>
          <?php endwhile; ?>
        </div>
        <div class="portfolio-mobile-nav d-sm-none" aria-label="Portfolio navigation">
          <button type="button" aria-label="Previous project" id="portfolioPrevBtn"><i class="bi bi-chevron-left"></i></button>
          <button type="button" aria-label="Next project" id="portfolioNextBtn"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div class="portfolio-see-more">
          <a href="portfolio.php">See More</a>
        </div>
      </div>
    </section>
    <?php endif; ?>
     <br>
    <!-- Carousel (Original Implementation) -->
    <div id="demo" class="carousel slide" data-bs-ride="carousel">
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
    <div class="parallax2" role="img" aria-label="Web and system development showcase"></div>
                                   
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
  <?php include __DIR__ . '/includes/mobile-nav.php'; ?>
  <?php include __DIR__ . '/includes/footer.php'; ?>
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
  <script src="https://cdn.jsdelivr.net/npm/swiper@11.1.9/swiper-bundle.min.js"></script>
  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
  <script>
    AOS.init({ duration: 1000 });

    document.querySelectorAll('.portfolio-item .swiper').forEach(function(swiperContainer) {
      const slideCount = swiperContainer.querySelectorAll('.swiper-slide').length;
      const options = { loop: slideCount > 1, watchOverflow: true };

      if (slideCount > 1) {
        options.pagination = {
          el: swiperContainer.querySelector('.swiper-pagination'),
          clickable: true,
        };
        options.navigation = {
          nextEl: swiperContainer.querySelector('.swiper-button-next'),
          prevEl: swiperContainer.querySelector('.swiper-button-prev'),
        };
      }

      new Swiper(swiperContainer, options);
    });

    const portfolioGrid = document.getElementById('mobilePortfolioCarousel');
    const portfolioPrevBtn = document.getElementById('portfolioPrevBtn');
    const portfolioNextBtn = document.getElementById('portfolioNextBtn');

    if (portfolioGrid && portfolioPrevBtn && portfolioNextBtn) {
      const scrollAmount = () => {
        const itemWidth = portfolioGrid.querySelector('.portfolio-item')?.getBoundingClientRect().width || 300;
        return itemWidth + 16;
      };

      portfolioPrevBtn.addEventListener('click', function () {
        portfolioGrid.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
      });

      portfolioNextBtn.addEventListener('click', function () {
        portfolioGrid.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
      });
    }
  </script>
</body>
</html>