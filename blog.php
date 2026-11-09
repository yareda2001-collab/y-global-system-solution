<?php
session_start();
include 'connect.php';

// Fetch posts with images
$sql = "SELECT p.id, p.title, p.content, p.created_at, i.image_url
        FROM posts p
        LEFT JOIN post_images pi ON p.id = pi.post_id
        LEFT JOIN images i ON pi.image_id = i.id
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
if (!$result) {
    error_log("Failed to fetch posts: " . $conn->error);
    $posts = [];
} else {
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $post_id = $row['id'];
        if (!isset($posts[$post_id])) {
            $posts[$post_id] = [
                'title' => $row['title'],
                'content' => $row['content'],
                'created_at' => $row['created_at'],
                'images' => []
            ];
        }
        if ($row['image_url']) {
            $posts[$post_id]['images'][] = $row['image_url'];
        }
    }
}

$conn->close();

function wrapText($text, $limit) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    $breakpoint = strrpos(substr($text, 0, $limit), ' ');
    if ($breakpoint === false) {
        $breakpoint = $limit;
    }
    return substr($text, 0, $breakpoint) . '...';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="theme-color" content="#033f5a">
  <link rel="manifest" href="manifest.php?v=4">
  <link rel="apple-touch-icon" href="assets/img/pwa-192.png?v=4">
  <title>Blog</title>

  <!-- Favicons -->
  <link href="assets/img/favicon1.png" rel="icon">
  <link href="assets/img/pwa-192.png?v=4" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;400;500;700&family=Poppins:wght@100;400;500;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css?v=public-header-35" rel="stylesheet">

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

    .content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 15px;
    }
    h2.section-title {
      text-align: center;
      color: var(--dark-blue);
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.0001rem; /* Kept from previous update for larger screens */
      position: relative;
    }
    h2.section-title::after {
      content: '';
      display: block;
      width: 80px;
      height: 4px;
      background: var(--primary-blue);
      margin: 5px auto; /* Kept from previous update for larger screens */
      border-radius: 2px;
    }
    .posts-container {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 30px;
      padding: 2px 0; /* Kept from previous update for larger screens */
    }
    .post-card {
      background: var(--white);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
    }
    .post-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }
    .post-images {
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 9;
      overflow: hidden;
      background: #f0f0f0;
    }
    .post-images img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-top-left-radius: var(--border-radius);
      border-top-right-radius: var(--border-radius);
      transition: transform 0.3s ease;
      display: block;
    }
    .post-images img:hover {
      transform: scale(1.05);
    }
    .post-images .swiper {
      width: 100%;
      height: 100%;
    }
    .post-images .swiper-slide {
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .swiper-button-prev,
    .swiper-button-next {
      color: var(--white);
      background: rgba(0, 0, 0, 0.5);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      transition: background 0.3s ease;
    }
    .swiper-button-prev:hover,
    .swiper-button-next:hover {
      background: rgba(0, 0, 0, 0.7);
    }
    .swiper-button-prev::after,
    .swiper-button-next::after {
      font-size: 18px;
    }
    .post-content {
      padding: 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .post-content h3 {
      font-size: 1.6rem;
      font-weight: 600;
      color: var(--dark-blue);
      margin: 0 0 12px;
      line-height: 1.4;
    }
    .post-meta {
      font-size: 0.85rem;
      color: var(--gray-400);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .post-meta i {
      font-size: 1rem;
      color: var(--gray-400);
    }
    .post-excerpt {
      font-size: 0.95rem;
      color: var(--gray-600);
      line-height: 1.7;
      margin-bottom: 16px;
      transition: opacity 0.3s ease, max-height 0.5s ease;
      overflow: hidden;
      max-height: 100px;
    }
    .post-excerpt.short-content {
      display: block;
    }
    .post-excerpt.full-content {
      display: none;
      max-height: none;
    }
    .post-excerpt.expanded {
      display: block;
      max-height: none;
      opacity: 1;
    }
    .post-excerpt.collapsed {
      opacity: 0.7;
    }
    .read-toggle {
      color: var(--primary-blue);
      font-weight: 500;
      font-size: 0.9rem;
      cursor: pointer;
      transition: color 0.3s ease;
      display: inline-block;
      margin-top: 8px;
    }
    .read-toggle:hover {
      color: blue;
      text-decoration: none;
    }
    .read-toggle[aria-expanded="true"] .read-less-text,
    .read-toggle[aria-expanded="false"] .read-more-text {
      display: inline;
    }
    .read-toggle[aria-expanded="true"] .read-more-text,
    .read-toggle[aria-expanded="false"] .read-less-text {
      display: none;
    }
    .no-posts {
      text-align: center;
      font-size: 1.2rem;
      color: var(--gray-400);
      padding: 40px 0;
      grid-column: 1 / -1;
    }
    @media (max-width: 991px) {
      .posts-container {
        grid-template-columns: 1fr;
        gap: 24px;
      }
      .post-card {
        max-width: 500px;
        margin: 0 auto;
      }
    }
    @media (max-width: 1200px) {
      .navmenu ul li {
        margin: 0 0.3rem;
      }
      .navmenu ul li a {
        font-size: 0.9rem;
        padding: 0.4rem 0.6rem;
      }
      .btn-getstarted {
        padding: 0.4rem 1rem;
        font-size: 0.9rem;
      }
    }
    @media (max-width: 992px) {
      .header-container {
        justify-content: space-between;
        align-items: center;
      }
      .logo {
        order: 1;
      }
      .mobile-nav-toggle {
        display: block;
        order: 2;
        margin-right: 0.5rem;
      }
      .navmenu {
        order: 3;
        display: flex;
        align-items: center;
      }
      .btn-getstarted {
        margin-left: 0.5rem;
        order: 4;
      }
      .navmenu ul {
        display: none;
      }
      .navmenu ul.show {
        display: flex;
        flex-direction: column;
        position: absolute;
        top: 60px;
        left: 0;
        right: 0;
        background: #2d4b9e;
        padding: 1rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 1000;
      }
      .navmenu ul li {
        margin: 0.5rem 0;
      }
      .navmenu ul li a {
        font-size: 1rem;
        padding: 0.5rem;
      }
      .dropdown ul {
        position: static;
        background: var(--primary-blue);
        box-shadow: none;
        margin-left: 1rem;
      }
    }
    @media (max-width: 768px) {
      .main {
        padding: 12px 15px 30px;
      }
      h2.section-title {
        font-size: 2rem;
        margin-bottom: 0.001rem; /* Further reduced for smaller devices */
      }
      h2.section-title::after {
        margin: 2px auto; /* Further reduced for smaller devices */
      }
      .posts-container {
        padding: 1px 0; /* Further reduced for smaller devices */
      }
      .post-images {
        aspect-ratio: 4 / 3;
      }
      .post-content h3 {
        font-size: 1.4rem;
      }
      .post-excerpt {
        font-size: 0.9rem;
      }
    }
    @media (max-width: 576px) {
      .logo h3 {
        font-size: 1.2rem;
      }
      .mobile-nav-toggle {
        font-size: 1.3rem;
      }
      .navmenu ul.show {
        top: 50px;
      }
      .btn-getstarted {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
      }
      h2.section-title {
        font-size: 1.8rem;
        margin-bottom: 0.1rem; /* Even further reduced for very small devices */
      }
      h2.section-title::after {
        margin: 1px auto; /* Even further reduced for very small devices */
      }
      .posts-container {
        padding: 0; /* Minimal padding for very small devices */
      }
      .post-content h3 {
        font-size: 1.3rem;
      }
      .post-excerpt {
        font-size: 0.85rem;
      }
      .post-images {
        aspect-ratio: 4 / 3;
      }
      .post-content {
        padding: 16px;
      }
    }

    .public-blog-page {
      --blog-ink: #123952;
      --blog-muted: #637b8a;
      --blog-accent: #087f9d;
      background: #f3f8fb;
    }
    .public-blog-page .main {
      padding: 2rem 1.25rem 4rem;
      background:
        radial-gradient(ellipse at 8% 8%, rgba(35, 198, 218, 0.13), transparent 30rem),
        radial-gradient(ellipse at 92% 38%, rgba(87, 144, 211, 0.1), transparent 32rem),
        #f3f8fb;
    }
    .public-blog-page #blog-section {
      width: min(100%, 1240px);
      max-width: 1240px;
      margin: 0 auto;
      padding: clamp(1.25rem, 3vw, 2.25rem) 0;
    }
    .blog-list-heading {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.85rem;
      margin: 0 auto 1.6rem;
      text-align: center;
    }
    .blog-list-copy {
      display: grid;
      justify-items: center;
    }
    .blog-list-heading h2 {
      position: relative;
      margin: 0;
      color: var(--blog-ink);
      font-size: clamp(1.65rem, 3vw, 2.15rem);
      font-weight: 700;
      letter-spacing: -0.04em;
    }
    .blog-list-heading h2::after {
      display: block;
      width: 46px;
      height: 3px;
      margin: 0.65rem auto 0;
      border-radius: 99px;
      background: linear-gradient(90deg, #17b9c9, #1478b5);
      content: "";
    }
    .blog-rail-controls {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .blog-rail-button {
      display: grid;
      width: 42px;
      height: 42px;
      place-items: center;
      border: 1px solid #d5e5ec;
      border-radius: 13px;
      background: #fff;
      color: #17516b;
      box-shadow: 0 5px 15px rgba(23, 71, 95, 0.07);
      cursor: pointer;
      transition: transform 160ms ease, background 160ms ease, color 160ms ease;
    }
    .blog-rail-button:hover:not(:disabled) {
      transform: translateY(-2px);
      background: #eaf8fb;
      color: #087f9d;
    }
    .blog-rail-button:disabled {
      opacity: 0.42;
      cursor: default;
    }
    .blog-rail-button:focus-visible {
      outline: 3px solid rgba(21, 150, 173, 0.35);
      outline-offset: 3px;
    }
    .public-blog-page .posts-container {
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1.35rem;
      padding: 0.2rem 0 1rem;
    }
    .public-blog-page .post-card {
      min-width: 0;
      border: 1px solid rgba(210, 225, 233, 0.85);
      border-radius: 22px;
      background: #fff;
      box-shadow: 0 12px 32px rgba(23, 57, 78, 0.08);
    }
    .public-blog-page .post-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 42px rgba(23, 57, 78, 0.14);
    }
    .public-blog-page .post-images {
      aspect-ratio: 16 / 9;
      background: linear-gradient(135deg, #dceef4, #edf5f8);
    }
    .public-blog-page .post-images img {
      border-radius: 0;
    }
    .public-blog-page .swiper-button-prev,
    .public-blog-page .swiper-button-next {
      top: 50%;
      display: grid;
      width: 44px;
      height: 44px;
      margin-top: 0;
      place-items: center;
      transform: translateY(-50%);
      border: 1px solid rgba(255, 255, 255, 0.58);
      border-radius: 50%;
      background: rgba(7, 42, 59, 0.56);
      color: #fff;
      box-shadow: 0 5px 18px rgba(5, 28, 41, 0.28);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      transition: background 160ms ease, box-shadow 160ms ease, opacity 160ms ease;
    }
    .public-blog-page .swiper-button-prev {
      left: 14px;
    }
    .public-blog-page .swiper-button-next {
      right: 14px;
    }
    .public-blog-page .swiper-button-prev::after,
    .public-blog-page .swiper-button-next::after {
      font-size: 0.9rem;
      font-weight: 800;
    }
    .public-blog-page .swiper-button-prev:hover,
    .public-blog-page .swiper-button-next:hover {
      transform: translateY(-50%);
      background: rgba(5, 37, 53, 0.88);
      box-shadow: 0 7px 22px rgba(5, 28, 41, 0.35);
    }
    .public-blog-page .swiper-button-disabled {
      opacity: 0.45;
    }
    .public-blog-page .post-content {
      padding: clamp(1.15rem, 2.5vw, 1.65rem);
    }
    .public-blog-page .post-content h3 {
      margin-bottom: 0.7rem;
      color: var(--blog-ink);
      font-size: clamp(1.15rem, 2vw, 1.42rem);
      font-weight: 700;
      letter-spacing: -0.02em;
      line-height: 1.35;
      overflow-wrap: anywhere;
    }
    .public-blog-page .post-meta {
      gap: 0.45rem;
      margin-bottom: 0.85rem;
      color: #738a98;
      font-size: 0.78rem;
    }
    .public-blog-page .post-meta i {
      color: var(--blog-accent);
    }
    .public-blog-page .post-excerpt {
      margin-bottom: 0.6rem;
      color: #516a79;
      font-size: 0.9rem;
      line-height: 1.75;
    }
    .public-blog-page .post-excerpt.short-content {
      max-height: 7.9em;
      display: -webkit-box;
      overflow: hidden;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 4;
    }
    .public-blog-page .post-excerpt.full-content {
      white-space: pre-line;
    }
    .public-blog-page .read-toggle {
      align-self: flex-start;
      margin-top: auto;
      padding: 0;
      border: 0;
      background: transparent;
      color: var(--blog-accent);
      font: 650 0.86rem "Poppins", sans-serif;
      cursor: pointer;
    }
    .public-blog-page .read-toggle:hover {
      color: #075675;
      text-decoration: underline;
    }
    .public-blog-page .no-posts {
      padding: 3rem 1rem;
      border: 1px dashed #cbdde6;
      border-radius: 20px;
      background: rgba(255, 255, 255, 0.75);
      color: var(--blog-muted);
    }
    @media (min-width: 1200px) {
      .public-blog-page .posts-container {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }
    @media (max-width: 767.98px) {
      .public-blog-page .main {
        padding: 1rem 0 2.5rem;
      }
      .public-blog-page #blog-section {
        padding: 1.2rem 0 2rem;
      }
      .blog-list-heading {
        margin: 0 auto 1.1rem;
        gap: 0.7rem;
      }
      .blog-rail-button {
        width: 38px;
        height: 38px;
      }
      .public-blog-page .posts-container {
        display: flex;
        gap: 0.9rem;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 0.4rem 1rem 1.3rem;
        scroll-behavior: smooth;
        scroll-padding-inline: 1rem;
        scroll-snap-type: x mandatory;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #9dc9d5 #e4eef2;
      }
      .public-blog-page .posts-container::-webkit-scrollbar {
        height: 6px;
      }
      .public-blog-page .posts-container::-webkit-scrollbar-track {
        border-radius: 99px;
        background: #e4eef2;
      }
      .public-blog-page .posts-container::-webkit-scrollbar-thumb {
        border-radius: 99px;
        background: #9dc9d5;
      }
      .public-blog-page .post-card {
        flex: 0 0 min(84vw, 390px);
        width: auto;
        min-width: 0;
        max-width: none;
        min-height: 0;
        margin: 0;
        scroll-snap-align: start;
        scroll-snap-stop: always;
      }
      .public-blog-page .post-card:hover {
        transform: none;
      }
      .public-blog-page .post-images {
        aspect-ratio: 16 / 10;
      }
      .public-blog-page .post-content h3 {
        font-size: 1.2rem;
      }
      .public-blog-page .swiper-button-prev,
      .public-blog-page .swiper-button-next {
        width: 40px;
        height: 40px;
      }
      .public-blog-page .swiper-button-prev {
        left: 10px;
      }
      .public-blog-page .swiper-button-next {
        right: 10px;
      }
      .public-blog-page .post-excerpt {
        font-size: 0.87rem;
      }
      .public-blog-page .no-posts {
        min-width: calc(100vw - 2rem);
        box-sizing: border-box;
        margin: 0;
      }
    }
    @media (prefers-reduced-motion: reduce) {
      .public-blog-page .posts-container {
        scroll-behavior: auto;
      }
      .public-blog-page .post-card {
        transition: none;
      }
    }
  </style>
</head>
<body class="services-page mobile-app-page public-blog-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">
    <section id="blog-section" class="content section">
      <div class="blog-list-heading" data-aos="fade-up">
        <div class="blog-list-copy">
          <h2>Latest articles</h2>
        </div>
        <?php if (count($posts) > 1): ?>
          <div class="blog-rail-controls" aria-label="Article navigation">
            <button type="button" class="blog-rail-button" data-blog-direction="previous" aria-label="Scroll to previous articles">
              <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="blog-rail-button" data-blog-direction="next" aria-label="Scroll to next articles">
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </button>
          </div>
        <?php endif; ?>
      </div>
      <div class="posts-container" data-aos="fade-up" data-aos-delay="100" aria-label="Latest blog articles" tabindex="0">
        <?php if (!empty($posts)): ?>
          <?php foreach ($posts as $post_id => $post): ?>
            <article class="post-card" data-aos="fade-up" data-aos-delay="200">
              <?php if (!empty($post['images'])): ?>
                <div class="post-images">
                  <?php if (count($post['images']) > 1): ?>
                    <div class="swiper">
                      <div class="swiper-wrapper">
                        <?php foreach ($post['images'] as $image): ?>
                          <div class="swiper-slide">
                            <img src="admin/<?php echo htmlspecialchars($image); ?>" alt="Post image" loading="lazy">
                          </div>
                        <?php endforeach; ?>
                      </div>
                      <div class="swiper-button-prev"></div>
                      <div class="swiper-button-next"></div>
                    </div>
                  <?php else: ?>
                    <img src="admin/<?php echo htmlspecialchars($post['images'][0]); ?>" alt="Post image" loading="lazy">
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="post-content">
                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                <div class="post-meta">
                  <i class="bi bi-calendar3"></i>
                  Posted on <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?>
                </div>
                <div class="post-excerpt short-content" id="short-content-<?php echo $post_id; ?>">
                  <?php echo htmlspecialchars(wrapText($post['content'], 150)); ?>
                </div>
                <div class="post-excerpt full-content" id="full-content-<?php echo $post_id; ?>">
                  <?php echo htmlspecialchars($post['content']); ?>
                </div>
                <?php if (strlen($post['content']) > 150): ?>
                  <button class="read-toggle" 
                          aria-expanded="false" 
                          aria-controls="full-content-<?php echo $post_id; ?>" 
                          data-post-id="<?php echo $post_id; ?>">
                    <span class="read-more-text">Read More</span>
                    <span class="read-less-text">Read Less</span>
                  </button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="no-posts">No posts found.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/mobile-nav.php'; ?>
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <a href="#" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
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
    AOS.init({ duration: 1000 });

    const blogRail = document.querySelector('.posts-container');
    const blogCards = blogRail ? [...blogRail.querySelectorAll('.post-card')] : [];
    const railControls = document.querySelectorAll('[data-blog-direction]');
    const smallScreen = window.matchMedia('(max-width: 767.98px)');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let autoAdvanceTimer = null;
    let resumeTimer = null;
    let pointerIsOverRail = false;

    function updateRailControls() {
      if (!blogRail || blogCards.length < 2) return;
      const maxScroll = blogRail.scrollWidth - blogRail.clientWidth;
      const atStart = blogRail.scrollLeft <= 2;
      const atEnd = blogRail.scrollLeft >= maxScroll - 2;
      railControls.forEach(button => {
        const isPrevious = button.dataset.blogDirection === 'previous';
        button.disabled = isPrevious ? atStart : atEnd;
      });
    }

    function scrollRail(direction) {
      if (!blogRail || blogCards.length < 2) return;
      const card = blogCards[0];
      const distance = card.getBoundingClientRect().width + parseFloat(getComputedStyle(blogRail).columnGap || '0');
      scrollRailBy(direction * distance);
      pauseAutoAdvance();
    }

    function scrollRailBy(distance) {
      const previousBehavior = blogRail.style.scrollBehavior;
      blogRail.style.scrollBehavior = 'auto';
      blogRail.scrollBy({ left: distance, behavior: 'auto' });
      blogRail.style.scrollBehavior = previousBehavior;
      updateRailControls();
    }

    function stopAutoAdvance() {
      window.clearInterval(autoAdvanceTimer);
      autoAdvanceTimer = null;
    }

    function startAutoAdvance() {
      stopAutoAdvance();
      if (!blogRail || blogCards.length < 2 || !smallScreen.matches || reducedMotion.matches || pointerIsOverRail) return;
      autoAdvanceTimer = window.setInterval(() => {
        const maxScroll = blogRail.scrollWidth - blogRail.clientWidth;
        if (blogRail.scrollLeft >= maxScroll - 4) {
          scrollRailBy(-maxScroll);
        } else {
          const card = blogCards[0];
          const distance = card.getBoundingClientRect().width + parseFloat(getComputedStyle(blogRail).columnGap || '0');
          scrollRailBy(distance);
        }
      }, 4500);
    }

    function pauseAutoAdvance() {
      stopAutoAdvance();
      window.clearTimeout(resumeTimer);
      if (!pointerIsOverRail) {
        resumeTimer = window.setTimeout(startAutoAdvance, 8000);
      }
    }

    railControls.forEach(button => {
      button.addEventListener('click', () => {
        scrollRail(button.dataset.blogDirection === 'next' ? 1 : -1);
      });
    });

    if (blogRail && blogCards.length > 1) {
      blogRail.addEventListener('scroll', updateRailControls, { passive: true });
      blogRail.addEventListener('pointerenter', () => {
        pointerIsOverRail = true;
        stopAutoAdvance();
      });
      blogRail.addEventListener('pointerleave', () => {
        pointerIsOverRail = false;
        startAutoAdvance();
      });
      blogRail.addEventListener('touchstart', pauseAutoAdvance, { passive: true });
      blogRail.addEventListener('touchend', pauseAutoAdvance, { passive: true });
      blogRail.addEventListener('focusin', pauseAutoAdvance);
      blogRail.addEventListener('focusout', event => {
        if (!blogRail.contains(event.relatedTarget)) startAutoAdvance();
      });
      smallScreen.addEventListener('change', startAutoAdvance);
      reducedMotion.addEventListener('change', startAutoAdvance);
      window.addEventListener('resize', updateRailControls, { passive: true });
      updateRailControls();
      startAutoAdvance();
    }

    // Read More/Less Functionality
    document.querySelectorAll('.read-toggle').forEach(button => {
      button.addEventListener('click', () => {
        const postId = button.getAttribute('data-post-id');
        const shortContent = document.getElementById(`short-content-${postId}`);
        const fullContent = document.getElementById(`full-content-${postId}`);
        const isExpanded = button.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
          fullContent.style.display = 'none';
          shortContent.style.display = 'block';
          shortContent.classList.add('collapsed');
          button.setAttribute('aria-expanded', 'false');
        } else {
          shortContent.style.display = 'none';
          fullContent.style.display = 'block';
          fullContent.classList.add('expanded');
          button.setAttribute('aria-expanded', 'true');
        }
      });

      // Keyboard accessibility
      button.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          button.click();
        }
      });
    });

    // Initialize Swiper for multiple images
    document.querySelectorAll('.post-images .swiper').forEach(swiperEl => {
      new Swiper(swiperEl, {
        loop: true,
        navigation: {
          nextEl: swiperEl.querySelector('.swiper-button-next'),
          prevEl: swiperEl.querySelector('.swiper-button-prev'),
        },
        speed: 600,
        spaceBetween: 0,
        autoplay: {
          delay: 5000,
          disableOnInteraction: false,
        },
      });
    });
  </script>
</body>
</html>