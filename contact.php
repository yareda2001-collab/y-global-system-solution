<?php
// Include the database connection file
include 'connect.php';

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and escape input data
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $subject = $conn->real_escape_string(trim($_POST['subject']));
    $message = $conn->real_escape_string(trim($_POST['message']));

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format";
    } else {
        // SQL query to insert the form data into the contact table
        $sql = "INSERT INTO contact (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";

        // Execute the query and check if it was successful
        if ($conn->query($sql) === TRUE) {
            $response['success'] = true;
            $response['message'] = "Your message has been sent.";
        } else {
            $response['message'] = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Close the database connection
$conn->close();

// Return the response as JSON
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo json_encode($response);
    exit; // Stop further execution after handling AJAX request
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
  <title>Contact</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon1.png" rel="icon">
  <link href="assets/img/pwa-192.png?v=4" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css?v=public-header-35" rel="stylesheet">

  <!-- jQuery (necessary for AJAX) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- jQuery UI for Dialog -->
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
  <style>
    body.contact-page {
      background: #f3f8fc;
    }

    .contact-page .main {
      padding-top: 30px;
    }

    .contact-page .contact.section {
      padding: 48px 0 70px;
    }

    .contact-heading {
      max-width: 760px;
      margin: 0 auto 34px;
      text-align: center;
    }

    .contact-heading .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      margin-bottom: 12px;
      color: #0879a3;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }

    .contact-heading .eyebrow::before,
    .contact-heading .eyebrow::after {
      width: 22px;
      height: 2px;
      background: #19abd0;
      content: "";
    }

    .contact-heading h1 {
      margin: 0 0 12px;
      color: #102f46;
      font-size: clamp(2.1rem, 4vw, 3.4rem);
      font-weight: 800;
      line-height: 1.15;
    }

    .contact-heading p {
      margin: 0;
      color: #5a7081;
      font-size: 1.04rem;
      line-height: 1.75;
    }

    .contact-map {
      position: relative;
      height: 300px;
      margin-bottom: 30px;
      overflow: hidden;
      border: 5px solid #fff;
      border-radius: 24px;
      background: #dcebf2;
      box-shadow: 0 18px 44px rgba(16, 47, 70, 0.12);
    }

    .contact-map iframe {
      display: block;
      width: 100%;
      height: 100%;
      border: 0;
    }

    .contact-content {
      align-items: stretch;
    }

    .contact-info-list {
      display: grid;
      gap: 14px;
      height: 100%;
    }

    .contact-page .contact-info-card {
      display: flex;
      align-items: center;
      gap: 16px;
      min-height: 112px;
      padding: 22px;
      border: 1px solid #e0ebf2;
      border-radius: 18px;
      background: #fff;
      box-shadow: 0 12px 30px rgba(16, 47, 70, 0.06);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .contact-page .contact-info-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 18px 36px rgba(16, 47, 70, 0.1);
    }

    .contact-info-icon {
      display: grid;
      width: 50px;
      height: 50px;
      flex: 0 0 50px;
      place-items: center;
      border-radius: 15px;
      background: #e8f7fb;
      color: #0879a3;
      font-size: 1.3rem;
    }

    .contact-info-card h2 {
      margin: 0 0 5px;
      color: #102f46;
      font-size: 1.03rem;
      font-weight: 750;
    }

    .contact-info-card p,
    .contact-info-card a {
      margin: 0;
      color: #5a7081;
      font-size: 0.95rem;
      line-height: 1.5;
      overflow-wrap: anywhere;
      text-decoration: none;
    }

    .contact-info-card a:hover {
      color: #0879a3;
    }

    .contact-form-card {
      height: 100%;
      padding: clamp(24px, 4vw, 38px);
      border: 1px solid #e0ebf2;
      border-radius: 22px;
      background: #fff;
      box-shadow: 0 18px 44px rgba(16, 47, 70, 0.09);
    }

    .contact-form-heading {
      margin-bottom: 24px;
    }

    .contact-form-heading h2 {
      margin: 0 0 7px;
      color: #102f46;
      font-size: 1.65rem;
      font-weight: 800;
    }

    .contact-form-heading p {
      margin: 0;
      color: #718392;
      line-height: 1.6;
    }

    .contact-page #contact-form .form-control {
      min-height: 52px;
      padding: 13px 15px;
      border: 1px solid #dce7ee;
      border-radius: 12px;
      background: #f9fbfd;
      color: #183c54;
      box-shadow: none;
    }

    .contact-page #contact-form textarea.form-control {
      min-height: 150px;
      resize: vertical;
    }

    .contact-page #contact-form .form-control:focus {
      border-color: #1594b8;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(21, 148, 184, 0.12);
    }

    .contact-page #contact-form button[type="submit"] {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      min-height: 52px;
      padding: 13px 28px;
      border: 0;
      border-radius: 999px;
      background: linear-gradient(135deg, #0879a3, #12a9cb);
      color: #fff;
      font-size: 1rem;
      font-weight: 700;
      box-shadow: 0 10px 24px rgba(8, 121, 163, 0.22);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .contact-page #contact-form button[type="submit"]:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 28px rgba(8, 121, 163, 0.3);
    }

    @media (max-width: 767px) {
      .contact-page .main {
        padding-top: 12px;
      }

      .contact-page .contact.section {
        padding: 34px 0 48px;
      }

      .contact-map {
        height: 230px;
        margin-bottom: 22px;
        border-width: 3px;
        border-radius: 18px;
      }

      .contact-content {
        gap: 18px;
      }

      .contact-info-list {
        gap: 10px;
      }

      .contact-page .contact-info-card {
        min-height: 90px;
        padding: 16px;
        border-radius: 15px;
      }

      .contact-form-card {
        padding: 24px 18px;
        border-radius: 18px;
      }
    }

  .custom-close {
  background-color: red;    /* Red background */
  color: white;             /* White text */
  border: none;             /* Remove border */
  width: 24px;              /* Set width to match the default close button */
  height: 24px;             /* Set height to match the default close button */
  font-size: 14px;          /* Font size */
  font-weight: bold;        /* Bold text */
  cursor: pointer;          /* Pointer cursor */
  position: absolute;       /* Position it in the corner */
  right: 0;                 /* Align to the right */
  top: 0;                   /* Align to the top */
  border-radius: 0;         /* Remove rounded corners for a square shape */
  text-align: center;       /* Center the text horizontally */
  line-height: 22px;        /* Center the text vertically */
  padding: 0;               /* Remove padding */
}

.custom-close i {
  font-size: 16px;          /* Adjust icon size */
  line-height: inherit;
}

.custom-close:hover {
  background-color: darkred; /* Darker red on hover */
}
  </style>
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

<body class="contact-page mobile-app-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">
<!-- Contact Section -->
<section id="contact" class="contact section">
  <div class="container">
    <div class="contact-heading" data-aos="fade-up">
      <span class="eyebrow">Get in touch</span>
      <h1>Let’s build something meaningful.</h1>
      <p>Have a project or an idea in mind? Share a few details and let’s explore how to make it happen.</p>
    </div>
    <div class="contact-map" data-aos="fade-up" data-aos-delay="100">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.740574004862!2d38.776120674181584!3d8.996002391064025!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164b850028ad82f3%3A0xc9308c65f871e2bb!2sMeksico!5e0!3m2!1sen!2set!4v1746221570524!5m2!1sen!2set" title="Map showing the Y-Global System Solution location" loading="lazy" allowfullscreen></iframe>
    </div>
    <div class="row gy-4 contact-content">
      <div class="col-lg-4">
        <div class="contact-info-list">
          <article class="contact-info-card" data-aos="fade-up" data-aos-delay="100">
            <span class="contact-info-icon"><i class="bi bi-geo-alt"></i></span>
            <div>
              <h2>Visit</h2>
              <p>Ethiopia, Addis Ababa</p>
            </div>
          </article>
          <article class="contact-info-card" data-aos="fade-up" data-aos-delay="150">
            <span class="contact-info-icon"><i class="bi bi-telephone"></i></span>
            <div>
              <h2>Call</h2>
              <a href="tel:+251926862255">+251 926-862-255</a>
            </div>
          </article>
          <article class="contact-info-card" data-aos="fade-up" data-aos-delay="200">
            <span class="contact-info-icon"><i class="bi bi-envelope"></i></span>
            <div>
              <h2>Email</h2>
              <a href="mailto:yared1357@gmail.com">yared1357@gmail.com</a>
            </div>
          </article>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="contact-form-card" data-aos="fade-up" data-aos-delay="150">
          <div class="contact-form-heading">
            <h2>Send a message</h2>
            <p>Tell me what you’re looking to create. I’ll get back to you as soon as possible.</p>
          </div>
          <form id="contact-form" class="php-email-form">
              <div class="row gy-4">
                <div class="col-md-6">
                  <input type="text" name="name" class="form-control" placeholder="Your Name" required="">
                </div>
                <div class="col-md-6 ">
                  <input type="email" class="form-control" name="email" placeholder="Your Email" required="">
                </div>
                <div class="col-md-12">
                  <input type="text" class="form-control" name="subject" placeholder="Subject" required="">
                </div>
                <div class="col-md-12">
                  <textarea class="form-control" name="message" rows="6" placeholder="Message" required=""></textarea>
                </div>
                <div class="col-md-12 text-center">
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Your message has been sent. Thank you!</div>
                  <button type="submit"><i class="bi bi-send"></i> Send Message</button>
                </div>
              </div>
            </form>
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
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <!-- jQuery UI Dialog Script -->
  <script>
    $(document).ready(function() {
  $('#contact-form').on('submit', function(event) {
    event.preventDefault(); // Prevent the default form submission

    $.ajax({
      url: '', // The form action URL should be the same as this file or handle the form submission
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        var data = JSON.parse(response);
        if (data.success) {
          // Create dialog with a custom close button
          var dialog = $('<div></div>').dialog({
            title: 'Success',
            modal: true,
            buttons: {
              Ok: function() {
                $(this).dialog('close');
              }
            },
            open: function() {
              // Hide the default close button
              $(this).parent().find('.ui-dialog-titlebar-close').hide();
              
              // Add custom close button after the dialog is opened
              var closeButton = $('<button type="button" class="custom-close" title="Close"><i class="bi bi-x"></i></button>');
              closeButton.appendTo($(this).parent().find('.ui-dialog-titlebar'));
              
              // Bind click event to close the dialog
              closeButton.on('click', function() {
                dialog.dialog('close');
              });
            },
            close: function() {
              $(this).remove(); // Remove the dialog from the DOM when closed
            }
          }).html(data.message);

          // Reset the form fields
          $('#contact-form')[0].reset();
          $('.error-message').fadeOut();
        } else {
          // Show an error dialog with custom close button
          var dialog = $('<div></div>').dialog({
            title: 'Error',
            modal: true,
            buttons: {
              Ok: function() {
                $(this).dialog('close');
              }
            },
            open: function() {
              // Hide the default close button
              $(this).parent().find('.ui-dialog-titlebar-close').hide();

              // Add custom close button after the dialog is opened
              var closeButton = $('<button type="button" class="custom-close" title="Close"><i class="bi bi-x"></i></button>');
              closeButton.appendTo($(this).parent().find('.ui-dialog-titlebar'));

              // Bind click event to close the dialog
              closeButton.on('click', function() {
                dialog.dialog('close');
              });
            },
            close: function() {
              $(this).remove(); // Remove the dialog from the DOM when closed
            }
          }).html(data.message);

          $('.sent-message').fadeOut();
        }
      },
      error: function(xhr, status, error) {
        // Show an error dialog if AJAX request fails with custom close button
        var dialog = $('<div></div>').dialog({
          title: 'Error',
          modal: true,
          buttons: {
            Ok: function() {
              $(this).dialog('close');
            }
          },
          open: function() {
            // Hide the default close button
            $(this).parent().find('.ui-dialog-titlebar-close').hide();

            // Add custom close button after the dialog is opened
            var closeButton = $('<button type="button" class="custom-close" title="Close"><i class="bi bi-x"></i></button>');
            closeButton.appendTo($(this).parent().find('.ui-dialog-titlebar'));

            // Bind click event to close the dialog
            closeButton.on('click', function() {
              dialog.dialog('close');
            });
          },
          close: function() {
            $(this).remove(); // Remove the dialog from the DOM when closed
          }
        }).html("There was an error sending your message.");

        $('.sent-message').fadeOut();
      }
    });
  });
});

  </script>
</body>
</html>
  
