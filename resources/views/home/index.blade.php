<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Rapidit — House Help in 10 Minutes</title>

  <!-- Fonts + Libraries -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <!-- Local CSS -->
  <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body>
  <!-- HEADER (pills) -->
  <header class="site-header">
    <div class="nav-container">
      <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>

      <nav class="nav" id="siteNav">
        <div class="nav-left">
          <a href="#services">Services</a>
          <a href="#how">How it Works</a>
          <a href="#why">Why Us</a>
        </div>

        <div class="brand">Rapidit</div>

        <div class="nav-right">
          <a href="#testimonials">Reviews</a>
          <a href="#workers">Our Rockstars</a>
          <a href="#faq">FAQs</a>
        </div>
      </nav>
    </div>
  </header>

  <!-- HERO -->
  <section id="home" class="hero">
    <div class="container hero-inner">
      <div class="hero-left" data-aos="fade-up">
        <h1>House Help<br><strong>in Just 10 Minutes</strong></h1>
        <p class="subtitle">Background-verified, trained & trusted helpers at your doorstep. Book instantly. Pay before or after — your choice.</p>

        

        
      </div>

      <div class="hero-right" data-aos="zoom-in">
        <!-- composite image with 3 phones -->
        <div class="phones-viewport">
          <img src="{{ asset('images/hero_three_phones.png') }}" alt="Three phone mockups" class="phones-composite">
        </div>
      </div>
    </div>
  </section>

  <!-- SERVICES (smooth horizontal scroll with names) -->
  <section id="services" class="section services">
    <div class="container">
      <h2 data-aos="fade-up">One Booking, Many Services</h2>
      <p class="lead center" data-aos="fade-up">Dishwashing, laundry, cooking help, cleaning and more — all in one booking.</p>

      <div class="swiper services-swiper" data-aos="fade-up">
        <div class="swiper-wrapper">
          <div class="swiper-slide service-card"><img src="{{ asset('images/chores1.jpg') }}" alt="Fan Cleaning"><h4>Fan Cleaning</h4></div>
          <div class="swiper-slide service-card"><img src="{{ asset('images/chores2.jpg') }}" alt="Kitchen Help"><h4>Kitchen Help</h4></div>
          <div class="swiper-slide service-card"><img src="{{ asset('images/chores3.jpg') }}" alt="Toilet Cleaning"><h4>Toilet Cleaning</h4></div>
          <div class="swiper-slide service-card"><img src="{{ asset('images/chores4.jpg') }}" alt="Cleaning Dishes"><h4>Cleaning Dishes</h4></div>
          <div class="swiper-slide service-card"><img src="{{ asset('images/chores5.jpg') }}" alt="Laundry"><h4>Laundry</h4></div>
          <div class="swiper-slide service-card"><img src="{{ asset('images/chores6.jpg') }}" alt="Washing Clothes"><h4>Washing Clothes</h4></div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS: 3 phone images (desktop) / mobile carousel -->
  <section id="how" class="section how">
    <div class="container">
      <h2 data-aos="fade-up">How It Works</h2>

      <!-- Desktop grid -->
      <div class="how-grid" id="howGrid" data-aos="fade-up">
        <div class="how-card"><img src="{{ asset('images/phone_step1.png') }}" alt="Select Task"><h4>Select Task</h4><p>Choose the chore you need done.</p></div>
        <div class="how-card"><img src="{{ asset('images/phone_step2.png') }}" alt="Confirm Location"><h4>Confirm Location</h4><p>Your helper comes to your doorstep.</p></div>
        <div class="how-card"><img src="{{ asset('images/phone_step3.png') }}" alt="Helper Arrives"><h4>Helper Arrives</h4><p>Verified helper in 10 minutes.</p></div>
      </div>

      <!-- Mobile swiper (hidden on desktop via CSS) -->
      <div class="swiper how-swiper-mobile" data-aos="fade-up">
        <div class="swiper-wrapper">
          <div class="swiper-slide"><img src="{{ asset('images/phone_step1.png') }}" alt="Select Task"></div>
          <div class="swiper-slide"><img src="{{ asset('images/phone_step2.png') }}" alt="Confirm Location"></div>
          <div class="swiper-slide"><img src="{{ asset('images/phone_step3.png') }}" alt="Helper Arrives"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- WHY US -->
  <section id="why" class="section why">
    <div class="container">
      <h2 data-aos="fade-up">Why Choose Rapidit</h2>
      <div class="why-grid" data-aos="fade-up">
        <div class="why-card"><img src="{{ asset('images/icons/clock.png') }}" alt=""><h3>Help in 10 Minutes</h3><p>Get a trained helper at your doorstep within minutes of booking.</p></div>
        <div class="why-card"><img src="{{ asset('images/icons/verified.png') }}" alt=""><h3>Background Verified</h3><p>Every helper is background checked and approved before joining.</p></div>
        <div class="why-card"><img src="{{ asset('images/icons/rupee.png') }}" alt=""><h3>Pay Anytime</h3><p>Pay before or after service completion — your choice.</p></div>
        <div class="why-card"><img src="{{ asset('images/icons/family.png') }}" alt=""><h3>Trusted by Families</h3><p>Loved by thousands of households across India.</p></div>
      </div>
    </div>
  </section>

  <!-- REVIEWS -->
  <section id="testimonials" class="section testimonials">
    <div class="container">
      <h2 data-aos="fade-up">What Our Users Say</h2>

      <div class="swiper testimonial-swiper" data-aos="fade-up">
        <div class="swiper-wrapper">
          <div class="swiper-slide testimonial-card">
            <img src="{{ asset('images/review1.jpg') }}" alt="Priya" class="user-photo">
            <div class="stars">★★★★★</div>
            <p>"Booking was smooth and the helper reached in 10 minutes."</p>
            <span>- Priya, Bangalore</span>
          </div>

          <div class="swiper-slide testimonial-card">
            <img src="{{ asset('images/review2.jpg') }}" alt="Arjun" class="user-photo">
            <div class="stars">★★★★★</div>
            <p>"Super easy to use. Rapidit is a lifesaver."</p>
            <span>- Arjun, Bangalore</span>
          </div>

          <div class="swiper-slide testimonial-card">
            <img src="{{ asset('images/review3.jpg') }}" alt="Neha" class="user-photo">
            <div class="stars">★★★★★</div>
            <p>"I trust Rapidit — helpers are trained and professional."</p>
            <span>- Neha, Bangalore</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- WORKERS -->
  <section id="workers" class="section workers">
    <div class="container">
      <h2 data-aos="fade-up">Meet Our Rockstars</h2>

      <div class="swiper worker-swiper" data-aos="fade-up">
        <div class="swiper-wrapper">
          <div class="swiper-slide worker-card">
            <img src="{{ asset('images/worker1.jpg') }}" alt="">
            <h3>Sunita — Bangalore</h3>
            <p>"I get steady bookings and on-time payments."</p>
          </div>

          <div class="swiper-slide worker-card">
            <img src="{{ asset('images/worker2.jpg') }}" alt="">
            <h3>Ramesh — Bangalore</h3>
            <p>"Steady work and respectful customers."</p>
          </div>

          <div class="swiper-slide worker-card">
            <img src="{{ asset('images/worker3.jpg') }}" alt="">
            <h3>Rekha — Bangalore</h3>
            <p>"Better earnings and consistent bookings."</p>
          </div>

          <div class="swiper-slide worker-card">
            <img src="{{ asset('images/worker4.jpg') }}" alt="">
            <h3>Suresh — Bangalore</h3>
            <p>"I appreciate the support and steady work."</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ (pill style with slight blue tint) -->
  <section id="faq" class="section faq">
    <div class="container">
      <h2 data-aos="fade-up">Frequently Asked Questions</h2>

      <div class="faq-grid" data-aos="fade-up">
        <!-- repeated FAQ items -->
        <div class="faq-item">
          <button class="faq-q">Are the helpers background verified?<span class="faq-icon">+</span></button>
          <div class="faq-a">Yes, every helper is background checked, trained and approved before joining Rapidit.</div>
        </div>

        <div class="faq-item">
          <button class="faq-q">How fast can I get a helper?<span class="faq-icon">+</span></button>
          <div class="faq-a">Helpers usually arrive within 10 minutes of booking, depending on availability and location.</div>
        </div>

        <div class="faq-item">
          <button class="faq-q">Can I pay after the service?<span class="faq-icon">+</span></button>
          <div class="faq-a">Yes — pay before or after the service, whichever suits you best.</div>
        </div>

        <div class="faq-item">
          <button class="faq-q">What chores can I book?<span class="faq-icon">+</span></button>
          <div class="faq-a">Dishwashing, laundry, cooking help, cleaning, sweeping, dusting and more — all in one booking.</div>
        </div>

        <div class="faq-item">
          <button class="faq-q">What if I’m not satisfied?<span class="faq-icon">+</span></button>
          <div class="faq-a">Contact support and we’ll resolve it quickly or provide a replacement helper.</div>
        </div>

        <div class="faq-item">
          <button class="faq-q">Which cities do you serve?<span class="faq-icon">+</span></button>
          <div class="faq-a">Rapidit starts in Bangalore and is expanding across India. Check the app for availability in your city.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- DOWNLOAD / FOOTER -->
  
<footer class="site-footer">
  <div class="container footer-grid">
    <!-- Column 1 -->
    <div class="footer-col">
      <div class="footer-logo">Rapidit</div>
      <div class="footer-company">Skilledhands Technologies Pvt. Ltd.</div>
    </div>

    <!-- Column 2 -->
    <div class="footer-col">
      <p>📧 contact@rapidit.in</p>
      <p>☎ +91-9483142734</p>
      <p>© 2025 All rights reserved</p>
    </div>

    <!-- Column 3 -->
    <div class="footer-col">
      <strong>Grievance Officer</strong><br>
      Samir Kumar Swain<br>
      samir@rapidit.in<br>
      +91-9483142734
    </div>
  </div>

  <div class="footer-address">
    DEEPTHI NILAYA, 4TH CROSS, BANGALORE NORTH, NAL, BANGALORE-560017, KARNATAKA
  </div>

  <div class="footer-policies">
    <a href="{{ asset('Terms_of_Use.pdf') }}" target="_blank">Terms of Use</a> • 
    <a href="{{ asset('Privacy_Policy.pdf') }}" target="_blank">Privacy Policy</a> • 
    <a href="{{ asset('Refund_Cancellation_Policy.pdf') }}" target="_blank">Refund & Cancellation Policy</a>
  </div>
</footer>


  <!-- Floating WhatsApp -->
  

  <!-- Scripts -->
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="{{ asset('script.js') }}"></script>
  <script>AOS.init({ once:true, duration:700 });</script>
</body>
</html>
