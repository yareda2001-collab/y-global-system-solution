<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="theme-color" content="#033f5a">
  <link rel="manifest" href="manifest.php?v=4">
  <link rel="apple-touch-icon" href="assets/img/pwa-192.png?v=4">
  <title>About Me</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon1.png" rel="icon">
  <link href="assets/img/pwa-192.png?v=4" rel="apple-touch-icon">

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
  <link href="assets/css/main.css?v=public-header-35" rel="stylesheet">
  <style>
    body.about-page {
      background: #f5f9ff;
    }

    .about-main {
      padding-top: 40px;
    }

    .about-hero {
      position: relative;
      overflow: hidden;
      padding: 48px 0 56px;
    }

    .about-hero::before {
      position: absolute;
      inset: 0 0 auto;
      height: 380px;
      background: linear-gradient(135deg, #033f5a, #0879a3);
      content: "";
    }

    .about-hero-card {
      position: relative;
      display: grid;
      grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr);
      align-items: center;
      gap: 48px;
      padding: clamp(24px, 5vw, 56px);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 28px;
      background: rgba(255,255,255,0.97);
      box-shadow: 0 28px 70px rgba(3, 43, 68, 0.18);
    }

    .about-copy {
      padding: 8px 0;
    }

    .about-kicker {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 14px;
      color: #0879a3;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.13em;
      text-transform: uppercase;
    }

    .about-kicker::before {
      width: 24px;
      height: 2px;
      background: #18a9d2;
      content: "";
    }

    .about-copy h1 {
      margin: 0 0 20px;
      color: #102f46;
      font-size: clamp(1.9rem, 3.2vw, 3rem);
      font-weight: 800;
      line-height: 1.18;
      text-align: center;
    }

    .about-copy h1 span {
      color: #0782aa;
    }

    .about-copy p {
      margin-bottom: 13px;
      color: #536a7c;
      font-size: 1rem;
      line-height: 1.8;
    }

    .about-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 24px;
    }

    .about-actions a {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      padding: 12px 19px;
      border: 1px solid #0879a3;
      border-radius: 999px;
      background: #0879a3;
      color: #fff;
      font-weight: 700;
      text-decoration: none;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .about-actions a:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(8, 121, 163, 0.22);
    }

    .about-actions a.secondary {
      background: #fff;
      color: #0879a3;
    }

    .about-photo {
      position: relative;
      max-width: 390px;
      margin: 0 auto;
      padding: 12px;
      border-radius: 28px;
      background: linear-gradient(145deg, #d6f5ff, #0d6f93);
      box-shadow: 0 20px 46px rgba(3, 63, 90, 0.2);
    }

    .about-photo img {
      display: block;
      width: 100%;
      aspect-ratio: 1 / 1.05;
      border-radius: 20px;
      object-fit: cover;
      object-position: center;
    }

    .about-photo-caption {
      position: absolute;
      right: -14px;
      bottom: 28px;
      padding: 13px 18px;
      border: 1px solid rgba(255,255,255,0.8);
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 12px 30px rgba(11, 45, 68, 0.16);
      color: #102f46;
      font-weight: 700;
    }

    .about-values {
      padding: 24px 0 52px;
    }

    .about-section-heading {
      max-width: 680px;
      margin: 0 auto 30px;
      text-align: center;
    }

    .about-section-heading span {
      color: #0879a3;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.13em;
      text-transform: uppercase;
    }

    .about-section-heading h2 {
      margin: 9px 0 0;
      color: #102f46;
      font-size: clamp(1.8rem, 3vw, 2.6rem);
      font-weight: 800;
      line-height: 1.2;
    }

    .about-value-card {
      height: 100%;
      padding: 30px;
      border: 1px solid #e1edf4;
      border-radius: 22px;
      background: #fff;
      box-shadow: 0 14px 36px rgba(20, 54, 77, 0.07);
    }

    .about-value-icon {
      display: grid;
      width: 52px;
      height: 52px;
      margin-bottom: 20px;
      place-items: center;
      border-radius: 16px;
      background: #e7f7fc;
      color: #0879a3;
      font-size: 1.45rem;
    }

    .about-value-card h3 {
      margin-bottom: 12px;
      color: #102f46;
      font-size: 1.4rem;
      font-weight: 750;
    }

    .about-value-card p {
      margin: 0;
      color: #536a7c;
      font-size: 0.96rem;
      line-height: 1.8;
    }

    .about-skills {
      padding: 48px 0 64px;
      background: #edf5fa;
    }

    .about-skills-panel {
      padding: clamp(24px, 4vw, 42px);
      border: 1px solid #deebf3;
      border-radius: 24px;
      background: #fff;
      box-shadow: 0 16px 40px rgba(20, 54, 77, 0.07);
    }

    .about-skills-panel .about-section-heading {
      margin-bottom: 32px;
    }

    .about-skills-panel .progress {
      margin-bottom: 22px;
    }

    .about-skills-panel .skill {
      color: #183c54;
      font-weight: 650;
    }

    .about-skills-panel .progress-bar-wrap {
      height: 9px;
      border-radius: 99px;
      background: #e7eff4;
    }

    .about-skills-panel .progress-bar {
      width: 100%;
      border-radius: 99px;
      background: linear-gradient(90deg, #0879a3, #19b1d5);
    }

    /* Header Styling */
    .header {
      background: linear-gradient(90deg, #033f5a 0%, #045c7f 100%);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

    /* Responsive Adjustments */
    @media (max-width: 991px) {
      .about-main {
        padding-top: 20px;
      }

      .about-hero-card {
        grid-template-columns: 1fr;
        gap: 30px;
      }

      .about-photo {
        width: min(100%, 360px);
      }

      .navmenu ul li a {
        color: black; /* Set text color to black for all nav items */
      }
      .navmenu ul li a[href="signup.php"],
      .navmenu ul li a[href="login.php"] {
        background: linear-gradient(90deg, #0288d1 0%, #03a9f4 100%); /* Keep original background */
        color: white; /* Keep text color white for these buttons */
        width: 20%; /* Set width to 40% on smaller devices */
        display: inline-block; /* Ensure it behaves like a block element */
        margin: 5px auto; /* Center the buttons */
      }
    }
    @media (max-width: 576px) {
  .about-hero {
    padding: 28px 0 42px;
  }
  .about-hero::before {
    height: 300px;
  }
  .about-hero-card {
    gap: 26px;
    padding: 24px 20px;
    border-radius: 22px;
  }
  .about-copy h1 {
    font-size: clamp(1.75rem, 7.5vw, 2.3rem);
  }
  .about-copy p {
    font-size: 0.94rem;
  }
  .about-actions {
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 8px;
  }
  .about-actions a {
    flex: 1 1 0;
    min-width: 0;
    justify-content: center;
    padding: 11px 8px;
    font-size: 0.82rem;
    line-height: 1.25;
    text-align: center;
  }
  .about-actions a i {
    flex: 0 0 auto;
  }
  @media (max-width: 360px) {
    .about-actions a {
      gap: 5px;
      padding: 10px 5px;
      font-size: 0.74rem;
    }
  }
  .about-photo-caption {
    right: -6px;
    bottom: 20px;
    padding: 10px 13px;
    font-size: 0.9rem;
  }
  .about-values {
    padding-bottom: 36px;
  }
  .about-value-card {
    padding: 24px;
  }
  .about-skills {
    padding: 36px 0 44px;
  }
  .about-skills-panel .col-lg-6 {
    padding-right: calc(var(--bs-gutter-x) * .5);
    padding-left: calc(var(--bs-gutter-x) * .5);
  }
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
}
</style>
  
</head>

<body class="about-page mobile-app-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="about-main">
  <section class="about-hero">
    <div class="container">
      <div class="about-hero-card" data-aos="fade-up">
        <div class="about-copy">
          <span class="about-kicker">About the developer</span>
          <h1>Thoughtful digital work, built with <span>purpose.</span></h1>
          <p>I'm Yared Alebachewu, a website developer with a strong background in front-end and back-end development. I create responsive, user-friendly websites that bring together engaging design and dependable technology.</p>
          <p>I take time to understand each client's goals and turn them into practical digital solutions, from clear interfaces and reliable systems to ongoing support after launch.</p>
          <div class="about-actions">
            <a href="contact.php"><i class="bi bi-chat-dots"></i> Let's work together</a>
            <a href="portfolio.php" class="secondary"><i class="bi bi-grid"></i> Explore my work</a>
          </div>
        </div>
        <div class="about-photo">
          <img src="assets/img/yada.jpg" alt="Yared Alebachewu, website developer" fetchpriority="high">
          <div class="about-photo-caption">Yared Alebachewu</div>
        </div>
      </div>
    </div>
  </section>

  <section class="about-values">
    <div class="container">
      <div class="about-section-heading" data-aos="fade-up">
        <span>What guides the work</span>
        <h2>Built around people, driven by possibility</h2>
      </div>
      <div class="row gy-4">
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
          <article class="about-value-card">
            <div class="about-value-icon"><i class="bi bi-compass"></i></div>
            <h3>My Mission</h3>
            <p>To empower businesses and individuals with innovative, functional, and visually engaging websites. I use modern technologies and proven practices to deliver secure, responsive, and scalable solutions that meet each client's needs and support growth.</p>
          </article>
        </div>
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
          <article class="about-value-card">
            <div class="about-value-icon"><i class="bi bi-eye"></i></div>
            <h3>My Vision</h3>
            <p>To contribute to a more accessible and inspiring digital world by creating user-centered websites with excellent design, functionality, and performance—helping every business build a meaningful connection with its audience.</p>
          </article>
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
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>