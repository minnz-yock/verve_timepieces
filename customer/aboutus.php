<?php
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php"; // Ensure this path is correct

// Function to get brand ID from brand name
function get_brand_id($conn, $brandName)
{
  $sql = "SELECT brand_id FROM brands WHERE brand_name = ?";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$brandName]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  return $result ? (int)$result['brand_id'] : null;
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>About Us — Verve Timepieces</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"> <!-- for bi-search, bi-cart3 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      color: #352826;
      background: #fdfdfd;
    }

    .page-wrap {
      max-width: 1200px;
      margin: 0 auto;
      padding: 24px 12px 48px;
    }

    .soft-card {
      background: #ffffff;
      border: 1px solid #EDE7E1;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
    }

    .chip {
      display: inline-block;
      padding: .4rem .7rem;
      border: 1px solid #DED2C8;
      border-radius: 999px;
      font-size: .85rem;
      margin: .25rem .25rem 0 0;
      text-decoration: none;
      color: inherit;
      transition: background-color .2s;
    }

    .chip:hover {
      background-color: #f5efe9;
    }

    .section-title {
      font-weight: 700;
      letter-spacing: .3px;
      color: #3a2f28;
    }

    .muted {
      color: #6c757d;
    }
        .hero {
            width: 100vw;
            max-width: 100vw;
            margin-left: 50%;
            transform: translateX(-50%);
            background-color: #3f352e;
            background-image:
                linear-gradient(160deg, #352826, #785A49, #785A49, #352826);
            color: #ffffff;
            border-radius: 0;
            box-shadow: none;
            text-align: center;
        }

    .hero-wrap {
      padding: 90px 24px;
      border-radius: 0;
    }

    .hero h1 {
      font-weight: 800;
      letter-spacing: .5px;
    }

    .hero p {
      font-size: 1.25rem;
      opacity: .95;
    }

    .feature-card {
      border-radius: 12px;
      border: 1px solid #EDE7E1;
      background: #ffffff;
    }

    .feature-dot {
      width: .55rem;
      height: .55rem;
      border-radius: 999px;
      background: #b8865b;
      display: inline-block;
      margin-right: .6rem;
    }

    .stat {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .4rem;
    }

    .stat .icon-wrap {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      background: radial-gradient(ellipse at 40% 30%, #ffffff, #f5efe9);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .06), inset 0 0 0 1px #EDE7E1;
    }

    .stat .icon-wrap i {
      font-size: 1.35rem;
      color: #7a5a44;
    }

    .stat .num {
      font-weight: 800;
      font-size: 1.35rem;
      color: #3a2f28;
    }

    .stat .label {
      font-size: .9rem;
      color: #6c757d;
    }

    .team-card {
      border: 1px solid #EDE7E1;
      border-radius: 12px;
      background: #ffffff;
      height: 100%;
    }

    .avatar {
      width: 92px;
      height: 92px;
      border-radius: 50%;
      background: radial-gradient(120px 120px at 30% 30%, #8b6a53, #4a3b33);
      margin: 0 auto 12px auto;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .08), inset 0 0 0 1px rgba(255, 255, 255, .25);
    }


    hr {
      border: none;
      border-top: 1px solid #352826;
      margin: 24px 0;
      width: 100%;
    }

    @media (max-width: 576px) {
      .hero-wrap {
        padding: 64px 18px;
      }
    }
  </style>
</head>

<body>

  <div class="row">
    <?php include 'navbarnew.php'; ?>
  </div>


  <section class="hero">
    <div class="hero-wrap container text-center">
      <h1 class="display-5 mb-3">Our Story</h1>
      <p class="mx-auto" style="max-width:900px;">
        Crafting timeless elegance, we’re passionate about connecting watch lovers with pieces they’ll keep for years.
      </p>
    </div>
  </section>

  <main class="container my-5">

    <section class="mb-5">
      <div class="row g-4 align-items-start">
        <div class="col-lg-7">
          <h2 class="h3 section-title mb-3">Our Mission</h2>
          <p class="mb-3">
            At Verve Timepieces, a watch is more than a timekeeper—it’s character and craft. We curate modern icons
            and everyday classics, list specs in plain language, and make ownership simple and secure.
          </p>
          <p class="mb-0">
            Every listing is photographed clearly, priced fairly, and shipped with care. If you need help with size,
            water-resistance, or movements, we’ll guide you with honest, practical advice.
          </p>
        </div>
        
        <div class="col-lg-5">
          <div class="feature-card p-4">
            <h3 class="h5 mb-3">What Sets Us Apart</h3>
            <ul class="list-unstyled m-0">
              <li class="mb-2"><span class="feature-dot"></span>Expert, plain-English guidance</li>
              <li class="mb-2"><span class="feature-dot"></span>Clear specs (case size, movement, WR)</li>
              <li class="mb-2"><span class="feature-dot"></span>Secure checkout & tracked shipping</li>
              <li class="mb-0"><span class="feature-dot"></span>No-nonsense photos & pricing</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <hr>

    <section class="mb-5">
      <div class="row text-center g-4">
        <div class="col-6 col-md-3">
          <div class="stat">
            <div class="icon-wrap"><i class="bi bi-clock-history"></i></div>
            <div class="num">25+</div>
            <div class="label">Years of Passion*</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat">
            <div class="icon-wrap"><i class="bi bi-emoji-smile"></i></div>
            <div class="num">50,000+</div>
            <div class="label">Happy Customers*</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat">
            <div class="icon-wrap"><i class="bi bi-award"></i></div>
            <div class="num">100+</div>
            <div class="label">Brands & Styles</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat">
            <div class="icon-wrap"><i class="bi bi-shield-check"></i></div>
            <div class="num">Trusted</div>
            <div class="label">Secure & Transparent</div>
          </div>
        </div>
      </div>
      <p class="text-center muted mt-2 mb-0" style="font-size:.85rem;">*Illustrative figures for layout; replace with your actual numbers.</p>
    </section>

    <hr>

    <section class="mb-5">
      <div class="text-center mb-4">
        <h2 class="h4 section-title mb-2">Meet Our Team</h2>
        <p class="muted m-0">Friendly people who care about fit, function, and your experience.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="team-card p-4 text-center">
            <div class="avatar" aria-hidden="true"></div>
            <h3 class="h6 mb-1">Alexander Chen</h3>
            <div class="muted mb-2">Founder & CEO</div>
            <p class="mb-0">Master horology nerd; believes specs should be simple, not confusing.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="team-card p-4 text-center">
            <div class="avatar" aria-hidden="true"></div>
            <h3 class="h6 mb-1">Isabella Rodriguez</h3>
            <div class="muted mb-2">Head of Curation</div>
            <p class="mb-0">Picks versatile pieces—from daily beaters to modern legends.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="team-card p-4 text-center">
            <div class="avatar" aria-hidden="true"></div>
            <h3 class="h6 mb-1">Marcus Thompson</h3>
            <div class="muted mb-2">Client Support Lead</div>
            <p class="mb-0">Here to help with sizing, water-resistance, and strap questions.</p>
          </div>
        </div>
      </div>
    </section>

<hr>

    <section class="mb-5">
      <div class="text-center mb-4">
        <h2 class="h4 section-title mb-2">Our Values</h2>
        <p class="muted m-0">How we work—every product, every reply, every shipment.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="soft-card p-4 h-100 text-center">
            <h3 class="h6 mb-2">Authenticity</h3>
            <p class="mb-0">Specs and photos you can trust—no fluff, no surprises.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="soft-card p-4 h-100 text-center">
            <h3 class="h6 mb-2">Excellence</h3>
            <p class="mb-0">From curation to packing, we sweat the small details.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="soft-card p-4 h-100 text-center">
            <h3 class="h6 mb-2">Care</h3>
            <p class="mb-0">Clear guidance, fast replies, and careful shipping.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="mb-4">
      <div class="soft-card p-4">
        <h2 class="h4 section-title mb-2">Brands we carry</h2>
        <p class="mb-3">A selection that balances heritage, reliability, and value:</p>
        <div>
          <?php
          // Array of brand names to display
          $brands = ['Rolex', 'Omega', 'Seiko', 'Casio', 'Patek Philippe', 'Timex'];
          foreach ($brands as $brandName) {
            $brandId = get_brand_id($conn, $brandName);
            if ($brandId) {
              echo '<a href="view_products.php?brand[]=' . htmlspecialchars($brandId) . '" class="chip">' . htmlspecialchars($brandName) . '</a>';
            } else {
              // Fallback if brand ID is not found, to keep the layout
              echo '<span class="chip">' . htmlspecialchars($brandName) . '</span>';
            }
          }
          ?>
        </div>
      </div>
    </section>

    <section class="mb-5">
      <div class="soft-card p-4 text-center">
        <h2 class="h5 mb-2">Questions? We’re here to help.</h2>
        <p class="mb-3">Need help choosing a size, movement, or style? Send us a note—we’ll reply with plain, useful advice.</p>
        <a href="contact.php" class="btn btn-dark"><i class="bi bi-envelope me-2"></i>Contact Verve Timepieces</a>
      </div>
    </section>
  </main>

  <footer class="container pb-4 text-center text-muted">
    <small>© <?= date('Y') ?> Verve Timepieces. All rights reserved.</small>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>