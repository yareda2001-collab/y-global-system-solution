<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="theme-color" content="#033f5a">
  <link rel="manifest" href="manifest.php?v=6">
  <link rel="apple-touch-icon" href="assets/img/pwa-192.png?v=4">
  <title>Services</title>
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
  <link href="assets/css/main.css?v=public-header-36" rel="stylesheet">
  <style>
    /* Header Styling */
    .header {
      background: linear-gradient(90deg, #033f5a 0%, #045c7f 100%);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    body {
      background: linear-gradient(180deg, #f5f9ff 0%, #edf3ff 100%);
      color: #14213d;
    }

    .main {
      padding-top: 96px;
    }

    .services-hero {
      padding: 40px 0 18px;
    }

    .services-hero-inner {
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 30px;
      align-items: center;
      background:
        radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 30%),
        linear-gradient(135deg, rgba(3, 63, 90, 0.98), rgba(15, 115, 184, 0.9) 58%, rgba(13, 63, 109, 0.94));
      border-radius: 30px;
      padding: 36px;
      box-shadow: 0 30px 70px rgba(13, 68, 115, 0.22);
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.12);
    }

    .services-hero-copy {
      color: #fff;
      max-width: 600px;
      text-align: center;
    }

    .eyebrow {
      display: inline-block;
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #9fe8ff;
      margin-bottom: 14px;
    }

    .eyebrow.accent {
      color: #0d6fb8;
    }

    .services-hero-copy h1 {
      color: #fff;
      font-size: clamp(1.8rem, 2.4vw, 2.7rem);
      line-height: 1.2;
      margin-bottom: 16px;
      font-weight: 800;
      max-width: 100%;
      white-space: normal;
      text-align: center;
    }

    .services-hero-copy p {
      color: rgba(255, 255, 255, 0.85);
      font-size: 1.05rem;
      line-height: 1.8;
      margin-bottom: 22px;
    }

    .service-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
      margin: 0 0 24px;
      padding: 0;
      list-style: none;
    }

    .service-pills li {
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.16);
      color: #ebfaff;
      border-radius: 999px;
      padding: 9px 14px;
      font-size: 0.82rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .cta-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #ffffff 0%, #dff8ff 100%);
      color: #0d6fb8;
      text-decoration: none;
      font-weight: 700;
      border-radius: 999px;
      padding: 14px 28px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      box-shadow: 0 12px 24px rgba(255, 255, 255, 0.18);
      margin: 0 auto;
    }

    .cta-btn:hover {
      text-decoration: none;
      transform: translateY(-2px);
      box-shadow: 0 14px 28px rgba(255, 255, 255, 0.25);
    }

    .services-hero-image-wrap {
      position: relative;
      min-height: 360px;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 25px 48px rgba(0, 0, 0, 0.2);
    }

    .services-hero-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .section-title {
      max-width: 820px;
      margin: 0 auto 30px;
      padding-bottom: 18px;
    }

    .section-title h2 {
      display: block;
      color: #112b46;
      font-size: clamp(2rem, 3vw, 3rem);
      font-weight: 700;
      margin-top: 8px;
      line-height: 1.3;
      letter-spacing: normal;
      text-transform: none;
      white-space: normal;
      word-break: break-word;
      overflow-wrap: anywhere;
    }

    .section-title h2::after {
      display: none;
    }

    .service-grid {
      margin-top: 10px;
    }

    .service-slide {
      scroll-snap-align: start;
    }

    .service-card {
      background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(245,250,255,0.96));
      border: 1px solid rgba(17, 43, 70, 0.08);
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(15, 49, 76, 0.08);
      height: 100%;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      position: relative;
    }

    .service-card::before {
      content: "";
      position: absolute;
      inset: 0 0 auto 0;
      height: 4px;
      background: linear-gradient(90deg, #0d6fb8 0%, #20c1ff 100%);
    }

    .service-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 32px 55px rgba(15, 49, 76, 0.12);
    }

    .service-card img {
      width: 100%;
      height: 230px;
      object-fit: cover;
      display: block;
      filter: saturate(1.08) contrast(1.04);
    }

    .service-card-body {
      padding: 24px 22px 28px;
    }

    .service-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 54px;
      height: 54px;
      border-radius: 16px;
      background: linear-gradient(135deg, #0d6fb8 0%, #1fa6d7 100%);
      color: #fff;
      margin-bottom: 14px;
      font-size: 1.5rem;
      box-shadow: 0 12px 22px rgba(13, 111, 184, 0.22);
    }

    .service-card h4 {
      font-size: 1.45rem;
      font-weight: 700;
      color: #112b46;
      margin-bottom: 12px;
    }

    .service-card p {
      margin: 0;
      color: #52657a;
      line-height: 1.8;
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

    @media (max-width: 991px) {
      .services-hero-inner {
        grid-template-columns: 1fr;
      }
      .services-hero-copy h1 {
        white-space: normal;
      }
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
    }
    @media (max-width: 576px) {
      .main {
        padding-top: 88px;
      }
      .services-hero {
        padding-top: 18px;
      }
      .services-hero-inner {
        padding: 24px 18px;
      }
      .services-hero-copy h1 {
        font-size: 1.8rem;
        white-space: normal;
      }
      .service-grid {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 16px;
        padding-bottom: 12px;
        scroll-snap-type: x proximity;
        -ms-overflow-style: none;
        scrollbar-width: none;
      }
      .service-grid::-webkit-scrollbar {
        display: none;
      }
      .service-grid > .service-slide {
        flex: 0 0 85%;
        max-width: 85%;
      }
      .navmenu ul li a {
        padding: 8px 10px;
        font-size: 0.9rem;
      }
      .navmenu ul li a[href="signup.php"],
      .navmenu ul li a[href="login.php"] {
        width: 90%;
        max-width: 80px;
        padding: 8px 12px;
        font-size: 0.9rem;
      }
      .service-card img {
        height: 200px;
      }
    }
</style>
  
</head>

<body class="services-page mobile-app-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">
    <section class="services-hero" data-aos="fade-up">
      <div class="container">
        <div class="services-hero-inner">
          <div class="services-hero-copy">
            <span class="eyebrow">From strategy to launch</span>
            <h1>Digital consulting, website design, and system development.</h1>
            <p>
              I help organizations plan, design, build, and implement digital solutions that support growth,
              improve customer experience, and streamline operations. From consultation to development and
              final deployment, I guide each stage with strategy, quality, and technical excellence.
            </p>
            <ul class="service-pills">
              <li>Consulting</li>
              <li>Web Design</li>
              <li>System Development</li>
              <li>Implementation</li>
            </ul>
            <a href="portfolio.php" class="cta-btn">View Portfolio</a>
          </div>
          <div class="services-hero-image-wrap">
            <img src="assets/img/webdevelopment.jpg" alt="Website development services" class="services-hero-image">
          </div>
        </div>
      </div>
    </section>

    <section id="services" class="services section">
      <div class="container">
        <div class="section-title text-center mb-5" data-aos="fade-up">
          <span class="eyebrow accent">Core services</span>
          <h2>Everything you need to build a stronger digital presence</h2>
        </div>

        <div class="service-grid row gy-4">
          <div class="col-lg-6 service-slide" data-aos="fade-up" data-aos-delay="100">
            <article class="service-card">
              <img src="assets/img/webdevelopment.jpg" alt="Website development" loading="lazy">
              <div class="service-card-body">
                <div class="service-icon"><i class="bi bi-briefcase"></i></div>
                <h4>Website & System Consulting</h4>
                <p>I help businesses define the right digital strategy, understand their goals, and choose the most effective website or system approach for long-term success.</p>
              </div>
            </article>
          </div>

          <div class="col-lg-6 service-slide" data-aos="fade-up" data-aos-delay="200">
            <article class="service-card">
              <img src="assets/img/s2.jpg" alt="Business website design" loading="lazy">
              <div class="service-card-body">
                <div class="service-icon"><i class="bi bi-pencil"></i></div>
                <h4>Business Website Design</h4>
                <p>I design modern, user-friendly business websites that look professional, reflect your brand, and create trust with your audience.</p>
              </div>
            </article>
          </div>

          <div class="col-lg-6 service-slide" data-aos="fade-up" data-aos-delay="300">
            <article class="service-card">
              <img src="assets/img/s33.jpg" alt="Custom system development" loading="lazy">
              <div class="service-card-body">
                <div class="service-icon"><i class="bi bi-layers"></i></div>
                <h4>Custom System Development</h4>
                <p>I build custom systems and web-based solutions that automate processes, improve workflows, and support efficient digital operations.</p>
              </div>
            </article>
          </div>

          <div class="col-lg-6 service-slide" data-aos="fade-up" data-aos-delay="400">
            <article class="service-card">
              <img src="assets/img/s55.jpg" alt="Implementation and deployment" loading="lazy">
              <div class="service-card-body">
                <div class="service-icon"><i class="bi bi-laptop"></i></div>
                <h4>Implementation & Deployment</h4>
                <p>I handle launch, configuration, testing, and deployment so your website or system is fully ready for real-world use and long-term reliability.</p>
              </div>
            </article>
          </div>

          <div class="col-lg-6 service-slide" data-aos="fade-up" data-aos-delay="500">
            <article class="service-card">
              <img src="assets/img/s7.jpg" alt="Digital branding support" loading="lazy">
              <div class="service-card-body">
                <div class="service-icon"><i class="bi bi-camera-video"></i></div>
                <h4>Digital Branding Support</h4>
                <p>I support organizations with branding visuals and digital assets that create consistency, professionalism, and stronger market presence.</p>
              </div>
            </article>
          </div>

          <div class="col-lg-6 service-slide" data-aos="fade-up" data-aos-delay="600">
            <article class="service-card">
              <img src="assets/img/s8.jpg" alt="Maintenance and support" loading="lazy">
              <div class="service-card-body">
                <div class="service-icon"><i class="bi bi-image"></i></div>
                <h4>Maintenance & Ongoing Support</h4>
                <p>I provide updates, fixes, and performance support after launch so your digital platform continues to work smoothly and remains effective.</p>
              </div>
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