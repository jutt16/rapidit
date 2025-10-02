// Init AOS
AOS.init({ once:true, duration:700 });

// Mobile hamburger toggle
const hamburger = document.getElementById('hamburger');
const siteNav = document.getElementById('siteNav');

hamburger.addEventListener('click', () => {
  const open = siteNav.classList.toggle('nav-open');
  hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
});

// Services Swiper (smooth continuous feel, peeking on mobile via slidesPerView fractional)
const servicesSwiper = new Swiper('.services-swiper', {
  loop: true,
  autoplay: { delay: 2000, disableOnInteraction: false },
  spaceBetween: 20,
  centeredSlides: false,
  breakpoints: {
    0: { slidesPerView: 1.15 },    // peek next on mobile
    640: { slidesPerView: 1.6 },
    900: { slidesPerView: 2.2 },
    1200: { slidesPerView: 3 }
  }
});

// How-it-works mobile tiny swiper (fast autoplay)
const howMobileSwiper = new Swiper('.how-swiper-mobile', {
  loop: true,
  autoplay: { delay: 1800, disableOnInteraction: false },
  slidesPerView: 1.2,
  spaceBetween: 12,
  breakpoints: { 0: { slidesPerView: 1.1 }, 480: { slidesPerView: 1.2 } }
});

// Testimonials Swiper (no loop warning: slidesPerView small)
const testimonialSwiper = new Swiper('.testimonial-swiper', {
  loop: true,
  autoplay: { delay: 2500, disableOnInteraction: false },
  spaceBetween: 24,
  breakpoints: {
    0: { slidesPerView: 1.1 },
    640: { slidesPerView: 2 },
    1024: { slidesPerView: 3 }
  }
});

// Workers Swiper
const workerSwiper = new Swiper('.worker-swiper', {
  loop: true,
  autoplay: { delay: 2500, disableOnInteraction: false },
  spaceBetween: 24,
  breakpoints: {
    0: { slidesPerView: 1.1 },
    640: { slidesPerView: 2 },
    1024: { slidesPerView: 3 }
  }
});

// FAQ toggle: smooth expand/collapse and +/- icon
document.querySelectorAll('.faq-item').forEach(item => {
  const btn = item.querySelector('.faq-q');
  const ans = item.querySelector('.faq-a');
  const icon = item.querySelector('.faq-icon');

  btn.addEventListener('click', () => {
    const isOpen = ans.style.maxHeight && ans.style.maxHeight !== '0px';
    // close all (optional: keep only one open)
    document.querySelectorAll('.faq-a').forEach(a => { a.style.maxHeight = null; a.previousElementSibling.querySelector('.faq-icon').textContent = '+'; });
    if (!isOpen) {
      ans.style.maxHeight = ans.scrollHeight + 'px';
      icon.textContent = '−';
      // scroll into view a bit so user sees expanded content (mobile friendly)
      setTimeout(() => { item.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 220);
    } else {
      ans.style.maxHeight = null;
      icon.textContent = '+';
    }
  });
});

// Ensure phones composite reposition on resize (keeps ~30% peek on mobile)
function adjustPhonesPeek() {
  const comp = document.querySelector('.phones-composite');
  if (!comp) return;
  if (window.innerWidth <= 768) {
    comp.style.transform = 'translateX(-15%)'; // shows ~30% of next phone
  } else {
    comp.style.transform = 'translateX(0)';
  }
}
window.addEventListener('resize', adjustPhonesPeek);
adjustPhonesPeek();

// Navbar shrink on scroll
window.addEventListener('scroll', function() {
  const header = document.querySelector('.site-header');
  if (window.scrollY > 50) {
    header.classList.add('shrink');
  } else {
    header.classList.remove('shrink');
  }
});
