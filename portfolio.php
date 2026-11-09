<?php
session_start();
include 'connect.php';

// Check if connection is valid
if (!$conn) {
  die("Database connection failed: " . mysqli_connect_error());
}

// Fetch portfolios and their images
$sql = "SELECT p.id, p.title, p.link, p.description, GROUP_CONCAT(pi.image_url) as images
        FROM portfolio p
        LEFT JOIN portfolio_image_mappings pim ON p.id = pim.portfolio_id
        LEFT JOIN portfolio_images pi ON pim.image_id = pi.id
        GROUP BY p.id
        ORDER BY p.id DESC";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="theme-color" content="#033f5a">
  <link rel="manifest" href="manifest.php?v=6">
  <link rel="apple-touch-icon" href="assets/img/pwa-192.png?v=4">
  <meta http-equiv="Content-Security-Policy" content="
    default-src 'self';
    script-src 'self' 'unsafe-inline' 'unsafe-eval';
    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
    font-src 'self' https://fonts.gstatic.com data:;
    img-src 'self' data: images/*;
    connect-src 'self'
  ">
  <title>Portfolio</title>

  <!-- Favicons -->
  <link href="assets/img/favicon1.png" rel="icon">
  <link href="assets/img/pwa-192.png?v=4" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css?v=public-header-36" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
      color: #2c3e50;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
      overflow-x: hidden;
    }

    #header .navmenu ul li a[href="signup.php"],
    #header .navmenu ul li a[href="login.php"] {
      position: relative;
      overflow: hidden;
      margin-left: 10px;
      padding: 8px 20px;
      border-radius: 25px;
      background: linear-gradient(90deg, #0288d1 0%, #03a9f4 100%);
      box-shadow: 0 2px 8px rgba(2, 136, 209, 0.3);
      color: #fff;
      font-weight: 600;
      transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
    }

    #header .navmenu ul li a[href="signup.php"]:hover,
    #header .navmenu ul li a[href="login.php"]:hover {
      transform: translateY(-3px);
      background: linear-gradient(90deg, #0277bd 0%, #0288d1 100%);
      box-shadow: 0 4px 15px rgba(2, 136, 209, 0.5);
      color: #fff;
    }

    #header .navmenu ul li a[href="signup.php"]::before,
    #header .navmenu ul li a[href="login.php"]::before {
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      content: '';
      transition: left 0.5s ease;
    }

    #header .navmenu ul li a[href="signup.php"]:hover::before,
    #header .navmenu ul li a[href="login.php"]:hover::before {
      left: 100%;
    }

    .main {
      flex: 1;
      padding: 28px 20px 40px;
      margin-top: 0;
    }

    .portfolio-section {
      width: 98%;
      max-width: 98%;
      margin: 0 auto;
      padding: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }

    .portfolio-section h2 {
      text-align: center;
      color: #ffffff;
      font-size: 2.2rem;
      margin-bottom: 30px;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .search-container {
      position: relative;
      max-width: 600px;
      margin: 0 auto 40px;
    }

    .search-container input {
      width: 100%;
      padding: 15px 50px 15px 20px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50px;
      background: rgba(255, 255, 255, 0.95);
      font-size: 1rem;
      color: #2c3e50;
      outline: none;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .search-container input:focus {
      border-color: #4facfe;
      box-shadow: 0 6px 20px rgba(79, 172, 254, 0.3);
      transform: translateY(-2px);
    }

    .search-container .search-icon {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.3rem;
      color: #667eea;
      pointer-events: none;
    }

    .portfolio-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 25px;
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

    .no-portfolios,
    .no-results {
      text-align: center;
      font-size: 1.2rem;
      color: #ffffff;
      margin-top: 40px;
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .footer {
      background-color: #033f5a;
      margin-top: auto;
    }

    .footer .copyright p {
      margin: 0;
      font-size: 0.9rem;
    }

    @media (max-width: 1200px) {
      .portfolio-section {
        padding: 15px;
        width: 98%;
      }

      .portfolio-grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
      }

      .portfolio-item h3 {
        font-size: 1.4rem;
      }

      .portfolio-item .swiper {
        height: 250px;
      }
    }

    @media (max-width: 991px) {
      #header .navmenu ul li a[href="signup.php"],
      #header .navmenu ul li a[href="login.php"] {
        display: inline-block;
        margin: 5px auto;
      }

      .portfolio-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
      }

      .portfolio-item h3 {
        font-size: 1.3rem;
      }

      .portfolio-item .swiper {
        height: 220px;
      }
    }

    @media (max-width: 768px) {
      .main {
        padding: 20px 15px 30px;
      }

      .portfolio-section {
        font-size: 2rem;
        padding: 15px;
      }

      .portfolio-section h2 {
        font-size: 1.8rem;
        margin-bottom: 25px;
      }

      .search-container {
        margin-bottom: 25px;
      }

      .search-container input {
        padding: 12px 45px 12px 15px;
        font-size: 0.9rem;
      }

      .portfolio-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .portfolio-item {
        padding: 12px;
      }

      .portfolio-item h3 {
        font-size: 1.3rem;
      }

      .portfolio-item p {
        font-size: 0.9rem;
      }

      .portfolio-item .swiper {
        height: 200px;
      }
    }

    @media (max-width: 576px) {
      #header .navmenu ul li a[href="signup.php"],
      #header .navmenu ul li a[href="login.php"] {
        max-width: 110px;
        margin-left: 0;
        padding: 8px 12px;
        font-size: 0.9rem;
      }

      .portfolio-section {
        padding: 10px;
        position: relative;
      }

      .portfolio-section h2 {
        font-size: 1.5rem;
        margin-bottom: 20px;
      }

      .search-container input {
        padding: 10px 40px 10px 12px;
        font-size: 0.85rem;
      }

      .portfolio-grid {
        display: flex;
        overflow-x: auto;
        overflow-y: hidden;
        gap: 16px;
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
        flex: 0 0 82%;
        max-width: 82%;
        min-width: 82%;
        padding: 10px;
        scroll-snap-align: start;
      }

      .portfolio-item h3 {
        font-size: 1.2rem;
      }

      .portfolio-item p {
        font-size: 0.85rem;
      }

      .portfolio-item .swiper {
        height: 180px;
      }

      .portfolio-item a {
        padding: 8px 16px;
        font-size: 0.9rem;
      }

      .portfolio-mobile-nav {
        position: absolute;
        left: 10px;
        right: 10px;
        top: 55%;
        transform: translateY(-50%);
        display: flex;
        justify-content: space-between;
        align-items: center;
        pointer-events: none;
        z-index: 3;
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
        font-size: 1.1rem;
        cursor: pointer;
        pointer-events: auto;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .portfolio-mobile-nav button:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 24px rgba(15, 113, 183, 0.3);
      }
    }
  </style>
</head>

<body class="services-page mobile-app-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">
    <section class="portfolio-section">
      <h2>OUR PORTFOLIO</h2>

      <!-- Search Box -->
      <div class="search-container">
        <input type="text" id="portfolioSearch" placeholder="Search portfolios by title or description..."
          autocomplete="off">
        <i class="bi bi-search search-icon"></i>
      </div>

      <?php if ($result->num_rows === 0): ?>
        <p class="no-portfolios">No portfolios available at the moment.</p>
      <?php else: ?>
        <div class="portfolio-grid" id="portfolioScrollGrid">
          <?php while ($row = $result->fetch_assoc()): ?>
            <div class="portfolio-item" data-aos="fade-up" data-aos-delay="100"
              data-title="<?php echo htmlspecialchars($row['title']); ?>"
              data-description="<?php echo htmlspecialchars($row['description'] ?: ''); ?>">
              <h3><?php echo htmlspecialchars($row['title']); ?></h3>
              <?php
              $images = $row['images'] ? explode(',', $row['images']) : [];
              if (!empty($images)):
                ?>
                <div class="swiper">
                  <div class="swiper-wrapper">
                    <?php foreach ($images as $image): ?>
                      <div class="swiper-slide">
                        <img src="admin/<?php echo htmlspecialchars($image); ?>" alt="Portfolio Image" loading="lazy">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="swiper-button-prev"></div>
                  <div class="swiper-button-next"></div>
                  <div class="swiper-pagination"></div>
                </div>
              <?php else: ?>
                <p>No images available for this project.</p>
              <?php endif; ?>
              <p><?php echo htmlspecialchars($row['description'] ?: 'No description provided.'); ?></p>
              <a href="<?php echo htmlspecialchars($row['link']); ?>" target="_blank">View Project</a>
            </div>
          <?php endwhile; ?>
        </div>
        <div class="portfolio-mobile-nav d-md-none" aria-label="Portfolio navigation">
          <button type="button" aria-label="Previous portfolio" id="portfolioPrevBtn"><i class="bi bi-chevron-left"></i></button>
          <button type="button" aria-label="Next portfolio" id="portfolioNextBtn"><i class="bi bi-chevron-right"></i></button>
        </div>
        <p class="no-results" style="display: none;">No portfolios match your search.</p>
      <?php endif; ?>
    </section>
  </main>

  <?php include __DIR__ . '/includes/mobile-nav.php'; ?>
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <a href="#" class="scroll-top d-flex align-items-center justify-content-center"><i
      class="bi bi-arrow-up-short"></i></a>
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    // Initialize AOS
    AOS.init({ duration: 1000 });

    // Initialize Swiper for each portfolio item
    document.querySelectorAll('.portfolio-item .swiper').forEach(swiperContainer => {
      new Swiper(swiperContainer, {
        loop: true,
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        autoplay: {
          delay: 3000,
          disableOnInteraction: false,
        },
        slidesPerView: 1,
        spaceBetween: 10,
      });
    });

    // Portfolio Search Functionality
    const searchInput = document.getElementById('portfolioSearch');
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    const noResultsMessage = document.querySelector('.no-results');
    const portfolioGrid = document.querySelector('.portfolio-grid');
    const portfolioPrevBtn = document.getElementById('portfolioPrevBtn');
    const portfolioNextBtn = document.getElementById('portfolioNextBtn');

    if (searchInput && portfolioItems.length > 0) {
      searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;

        portfolioItems.forEach(item => {
          const title = item.getAttribute('data-title').toLowerCase();
          const description = item.getAttribute('data-description').toLowerCase();

          if (title.includes(searchTerm) || description.includes(searchTerm)) {
            item.style.display = 'block';
            visibleCount++;
          } else {
            item.style.display = 'none';
          }
        });

        // Show/hide no results message
        if (visibleCount === 0 && searchTerm !== '') {
          if (noResultsMessage) noResultsMessage.style.display = 'block';
          if (portfolioGrid) portfolioGrid.style.display = 'none';
        } else {
          if (noResultsMessage) noResultsMessage.style.display = 'none';
          if (portfolioGrid) portfolioGrid.style.display = 'grid';
        }
      });
    }

    if (portfolioGrid && portfolioPrevBtn && portfolioNextBtn) {
      const scrollAmount = () => {
        const itemWidth = portfolioGrid.querySelector('.portfolio-item')?.getBoundingClientRect().width || 320;
        return itemWidth + 16;
      };

      portfolioPrevBtn.addEventListener('click', function () {
        portfolioGrid.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
      });

      portfolioNextBtn.addEventListener('click', function () {
        portfolioGrid.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
      });
    }

    // Mobile Nav Toggle
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const navMenu = document.querySelector('.navmenu ul');
    mobileNavToggle.addEventListener('click', function () {
      navMenu.classList.toggle('show');
    });

    // Close mobile nav when clicking a link
    document.querySelectorAll('.navmenu ul li a').forEach(link => {
      link.addEventListener('click', () => {
        if (navMenu.classList.contains('show')) {
          navMenu.classList.remove('show');
        }
      });
    });
  </script>
</body>

</html>