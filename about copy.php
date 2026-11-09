<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="theme-color" content="#033f5a">
  <link rel="manifest" href="manifest.php?v=6">
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
  <link href="assets/css/main.css?v=public-header-36" rel="stylesheet">
  <style>
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

<body class="services-page mobile-app-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">

<section>
  <div class="container" data-aos="fade-up">

    <div class="row mb-5">

      <div class="d-md-flex post-entry-2 half">
        <a href="#" class="me-4 thumbnail">
          <img src="assets/img/yada.jpg" alt="" class="img-fluid">
        </a>
        <div class="ps-md-5 mt-4 mt-md-0">
          <h2 class="mb-4 display-4">About Yared</h2>  
          <p>I'm Yared Alebachewu, website developer with a higher quality.I hand a strong background in both front-end and back-end development.</p>
           <p>I bring a comprehensive approach to every project, ensuring that each website is visually captivating and technically robust.</p>
          <p>My work is driven by a commitment to delivering high-quality, responsive, and user-friendly websites.</p>
          <p>In my role, I focus on crafting engaging interfaces that are easy to navigate and visually appealing. My goal is to create seamless user experiences that work beautifully on all devices.</p>
          <p>On the back-end, I ensure that the technical infrastructure is secure, reliable, and capable of supporting the website's needs, from data management to user authentication.</p>
        <p>I take pride in understanding the unique goals of each client, offering personalized solutions that align with their brand and vision.</p>
         <p>I believe in open communication and collaboration, ensuring that every step of the development process is clear and focused on achieving the best results.</p>
        <p>Beyond just building websites, I provide ongoing support to keep them running smoothly and up-to-date.</p>
      <p>Let's work together to bring your digital ideas to life, creating an impactful and lasting online presence.</p>  
      </div>
      </div>

      

    </div>

  </div>
</section>
    
<!-- Services Section -->
<section id="services" class="services section">

  <div class="container">

    <div class="row gy-4">

      <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
        <div class="service-item d-flex position-relative h-100">
          <div>
            <h4 class="title">Mission</h4>
            <p class="description" style="font-size: 22px;">"To empower businesses and individuals by crafting innovative, functional, and visually stunning websites that deliver exceptional user experiences. We are dedicated to leveraging the latest technologies and best practices to create secure, responsive, and scalable web solutions that meet our clients' unique needs. Our mission is to bridge creativity and technology, fostering digital transformations that drive growth, engagement, and success."</p>

          </div>
        </div>
      </div><!-- End Service Item -->

      <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
        <div class="service-item d-flex position-relative h-100">
          <div>
            <h4 class="title">Vision</h4>
            <p class="description" style="font-size: 22px;">"To be a leading force in digital innovation, transforming the online landscape by creating cutting-edge, user-centric websites that set new standards in design, functionality, and performance. We envision a future where every business and individual has access to a powerful, engaging digital presence that seamlessly connects them with their audience. Through continuous learning, adaptation, and collaboration, we strive to make the digital world more accessible, impactful, and inspiring for everyone."</p>          
          </div>
        </div>
      </div><!-- End Service Item -->

    


    </div>

  </div>

</section><!-- /Services Section -->

    <!-- Skills Section -->
    <section id="skills" class="skills section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        
        <p>Check My  Skills<br></p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row skills-content skills-animation">

          <div class="col-lg-6">

            <div class="progress">
              <span class="skill"><span>HTML</span> <i class="val">100%</i></span>
              <div class="progress-bar-wrap">
                <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div><!-- End Skills Item -->

            <div class="progress">
              <span class="skill"><span>CSS</span> <i class="val">100%</i></span>
              <div class="progress-bar-wrap">
                <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div><!-- End Skills Item -->

            <div class="progress">
              <span class="skill"><span>JavaScript</span> <i class="val">100%</i></span>
              <div class="progress-bar-wrap">
                <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div><!-- End Skills Item -->

             <div class="progress">
              <span class="skill"><span>React</span> <i class="val">100%</i></span>
              <div class="progress-bar-wrap">
                <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div><!-- End Skills Item -->

          </div>

          <div class="col-lg-6">

            <div class="progress">
              <span class="skill"><span>PHP</span> <i class="val">100%</i></span>
              <div class="progress-bar-wrap">
                <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div><!-- End Skills Item -->

            <div class="progress">
              <span class="skill"><span>Bootstrap</span> <i class="val">100%</i></span>
              <div class="progress-bar-wrap">
                <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div><!-- End Skills Item -->

            <div class="progress">
              <span class="skill"><span>SQL</span> <i class="val">100%</i></span>
              <div class="progress-bar-wrap">
                <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div><!-- End Skills Item -->


          </div>

        </div>

      </div>

    </section><!-- /Skills Section -->

    


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