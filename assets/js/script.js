/* ============================================================
   NAGAR PALIKA PARISHAD Khalilabad — script.js
   ============================================================ */

'use strict';

/* ── 1. HEADER STICKY + SCROLLED CLASS ── */
const header = document.getElementById('header');

window.addEventListener('scroll', () => {
  header.classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });


/* ── 2. ACTIVE NAV LINK (Intersection Observer) ── */
const sections = document.querySelectorAll('section[id]');
const navItems = document.querySelectorAll('.nav-item');

const sectionObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      navItems.forEach(link => {
        link.classList.toggle(
          'active',
          link.getAttribute('href') === '#' + entry.target.id
        );
      });
    }
  });
}, { threshold: 0.4, rootMargin: '-80px 0px -40% 0px' });

sections.forEach(sec => sectionObserver.observe(sec));


/* ── 3. HAMBURGER MOBILE MENU ── */
const hamburger = document.getElementById('hamburger');
const mobileNav = document.getElementById('mobileNav');

hamburger.addEventListener('click', () => {
  const isOpen = hamburger.classList.toggle('open');
  mobileNav.classList.toggle('open', isOpen);
  hamburger.setAttribute('aria-expanded', isOpen);
});

// Close on link click
mobileNav.querySelectorAll('a').forEach(link => {
  link.addEventListener('click', () => {
    hamburger.classList.remove('open');
    mobileNav.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
  });
});

// Close on outside click
document.addEventListener('click', e => {
  if (!header.contains(e.target)) {
    hamburger.classList.remove('open');
    mobileNav.classList.remove('open');
  }
});


/* ── 4. SMOOTH SCROLL FOR ANCHOR LINKS ── */
document.querySelectorAll('a[href^="#"]').forEach(link => {
  link.addEventListener('click', e => {
    const target = document.querySelector(link.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const offset = 80; // header height
      const top = target.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  });
});


/* ── 5. ANIMATED COUNTERS ── */
function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const duration = 1800;
  const start = performance.now();

  function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    const value = Math.floor(easeOutCubic(progress) * target);
    el.textContent = value.toLocaleString('en-IN');
    if (progress < 1) requestAnimationFrame(step);
    else el.textContent = target.toLocaleString('en-IN');
  }
  requestAnimationFrame(step);
}

const counterEls = document.querySelectorAll('.hs-num');
const counterObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      animateCounter(entry.target);
      counterObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.6 });

counterEls.forEach(el => counterObserver.observe(el));


/* ── 6. SCROLL REVEAL ANIMATION ── */
const revealTargets = document.querySelectorAll(
  '.ql-card, .about-text, .about-cards-col, .info-card, ' +
  '.leader-card, .es-card, .ci-item, .contact-form-wrap, .ft-links-group'
);

revealTargets.forEach(el => el.classList.add('reveal'));

const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      // Stagger sibling cards
      const siblings = Array.from(entry.target.parentElement.children);
      const idx = siblings.indexOf(entry.target);
      entry.target.style.transitionDelay = `${idx * 80}ms`;
      entry.target.classList.add('in');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

revealTargets.forEach(el => revealObserver.observe(el));


/* ── 7. BACK TO TOP BUTTON ── */
const backTop = document.getElementById('backTop');

window.addEventListener('scroll', () => {
  backTop.classList.toggle('visible', window.scrollY > 400);
}, { passive: true });

backTop.addEventListener('click', () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
});


/* ── 8. CONTACT FORM VALIDATION & SUBMIT ── */
const contactForm = document.getElementById('contactForm');
const cfBtn       = document.getElementById('cfBtn');
const cfSuccess   = document.getElementById('cfSuccess');
const cfError     = document.getElementById('cfError');

const FIELDS = ['cname', 'cemail', 'csubject', 'cmsg'];

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function clearMessages() {
  cfSuccess.classList.remove('show');
  cfError.classList.remove('show');
}

contactForm.addEventListener('submit', e => {
  e.preventDefault();
  clearMessages();

  let isValid = true;

  FIELDS.forEach(id => {
    const el = document.getElementById(id);
    el.classList.remove('err');
    if (!el.value.trim()) {
      el.classList.add('err');
      isValid = false;
    }
  });

  // Email format check
  const emailEl = document.getElementById('cemail');
  if (emailEl.value.trim() && !validateEmail(emailEl.value.trim())) {
    emailEl.classList.add('err');
    isValid = false;
  }

  if (!isValid) {
    cfError.classList.add('show');
    return;
  }

  // Simulate sending
  cfBtn.disabled = true;
  cfBtn.innerHTML = `
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M21 12a9 9 0 11-6.219-8.56"/>
    </svg>
    Sending...
  `;

  // Rotate the SVG as a spinner
  const spinnerSvg = cfBtn.querySelector('svg');
  if (spinnerSvg) spinnerSvg.style.animation = 'spin 0.8s linear infinite';

  setTimeout(() => {
    cfBtn.disabled = false;
    cfBtn.innerHTML = `
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
      </svg>
      Send Message
    `;
    cfSuccess.classList.add('show');
    contactForm.reset();
    FIELDS.forEach(id => document.getElementById(id).classList.remove('err'));
  }, 1400);
});

// Remove error state on input
FIELDS.forEach(id => {
  document.getElementById(id).addEventListener('input', function () {
    this.classList.remove('err');
    clearMessages();
  });
});

// CSS for spinner (injected dynamically)
const style = document.createElement('style');
style.textContent = `@keyframes spin { to { transform: rotate(360deg); } }`;
document.head.appendChild(style);


/* ── 9. ACCESSIBILITY: FONT SIZE CONTROLS ── */
const root = document.documentElement;
let currentSize = 16;

document.getElementById('fontIncrease').addEventListener('click', () => {
  currentSize = Math.min(currentSize + 1, 20);
  root.style.fontSize = currentSize + 'px';
});

document.getElementById('fontDecrease').addEventListener('click', () => {
  currentSize = Math.max(currentSize - 1, 13);
  root.style.fontSize = currentSize + 'px';
});


/* ── 10. KEYBOARD TRAP: CLOSE MOBILE MENU ON ESC ── */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && mobileNav.classList.contains('open')) {
    hamburger.classList.remove('open');
    mobileNav.classList.remove('open');
    hamburger.focus();
  }
});


/* ── 11. LEADER CARDS: SUBTLE TILT ON HOVER ── */
document.querySelectorAll('.leader-card').forEach(card => {
  card.addEventListener('mousemove', e => {
    const rect = card.getBoundingClientRect();
    const cx = rect.left + rect.width / 2;
    const cy = rect.top + rect.height / 2;
    const rx = ((e.clientY - cy) / (rect.height / 2)) * -5;
    const ry = ((e.clientX - cx) / (rect.width / 2)) * 5;
    card.style.transform = `perspective(600px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(-6px)`;
  });
  card.addEventListener('mouseleave', () => {
    card.style.transform = '';
    card.style.transition = 'transform 0.4s ease';
  });
  card.addEventListener('mouseenter', () => {
    card.style.transition = 'transform 0.12s ease';
  });
});


/* ── 12. SERVICE CARDS: HOVER RIPPLE ── */
document.querySelectorAll('.es-card').forEach(card => {
  card.addEventListener('click', function (e) {
    const ripple = document.createElement('span');
    const rect = this.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    ripple.style.cssText = `
      position:absolute; border-radius:50%;
      background:rgba(255,107,0,0.12);
      width:${size}px; height:${size}px;
      left:${e.clientX - rect.left - size / 2}px;
      top:${e.clientY - rect.top - size / 2}px;
      transform:scale(0); animation:rippleAnim 0.5s ease-out;
      pointer-events:none;
    `;
    this.style.position = 'relative';
    this.style.overflow = 'hidden';
    this.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove());
  });
});

// Ripple keyframes
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
  @keyframes rippleAnim {
    to { transform: scale(2.5); opacity: 0; }
  }
`;
document.head.appendChild(rippleStyle);


/* ── 13. PAGE INIT COMPLETE ── */
console.log('%c Nagar Palika Parishad Khalilabad 🏛️', 'color:#FF6B00;font-weight:700;font-size:14px;');
console.log('%c Official Website Loaded Successfully', 'color:#128807;font-size:12px;');