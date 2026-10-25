(function () {
  'use strict';

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  onReady(function () {
    var nav = document.getElementById('fvNav');
    var toggle = document.getElementById('fvNavToggle');
    var menu = document.getElementById('fvNavMenu');
    var lang = document.getElementById('fvLang');
    var langBtn = document.getElementById('fvLangBtn');
    var langMenu = document.getElementById('fvLangMenu');
    var heroMedia = document.querySelector('.fv-hero__media');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setSolidNav() {
      if (!nav) return;
      if (window.scrollY > 24) {
        nav.classList.add('is-solid');
      } else {
        nav.classList.remove('is-solid');
      }
    }

    setSolidNav();
    window.addEventListener('scroll', setSolidNav, { passive: true });

    // Soft parallax on hero media layer (does not fight Ken Burns on img)
    if (heroMedia && !reduceMotion) {
      window.addEventListener(
        'scroll',
        function () {
          var y = Math.min(window.scrollY, 520);
          heroMedia.style.transform = 'translate3d(0, ' + y * 0.12 + 'px, 0)';
        },
        { passive: true }
      );
    }

    if (toggle && nav) {
      toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }

    if (menu) {
      menu.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function () {
          if (nav) nav.classList.remove('is-open');
          if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
      });
    }

    // Language flag dropdown
    function closeLang() {
      if (!lang || !langBtn || !langMenu) return;
      lang.classList.remove('is-open');
      langBtn.setAttribute('aria-expanded', 'false');
      langMenu.hidden = true;
    }

    function openLang() {
      if (!lang || !langBtn || !langMenu) return;
      lang.classList.add('is-open');
      langBtn.setAttribute('aria-expanded', 'true');
      langMenu.hidden = false;
    }

    if (langBtn && langMenu && lang) {
      langBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (langMenu.hidden) {
          openLang();
        } else {
          closeLang();
        }
      });
      document.addEventListener('click', function (e) {
        if (!lang.contains(e.target)) {
          closeLang();
        }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closeLang();
        }
      });
    }

    // Reveal on scroll
    var reveals = document.querySelectorAll('.fv-reveal');
    if ('IntersectionObserver' in window && reveals.length) {
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-in');
              io.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
      );
      reveals.forEach(function (el) {
        io.observe(el);
      });
    } else {
      reveals.forEach(function (el) {
        el.classList.add('is-in');
      });
    }

    // Hero reveals immediately
    document.querySelectorAll('.fv-hero .fv-reveal').forEach(function (el, i) {
      window.setTimeout(function () {
        el.classList.add('is-in');
      }, 160 + i * 90);
    });

    // Presentation reel
    var stage = document.querySelector('[data-fv-reel]');
    if (!stage) return;

    var slides = Array.prototype.slice.call(stage.querySelectorAll('.fv-reel__slide'));
    var caption = stage.querySelector('[data-fv-reel-caption]');
    var progress = stage.querySelector('[data-fv-reel-progress]');
    var index = 0;
    var duration = 5200;

    function show(i) {
      slides.forEach(function (slide, idx) {
        slide.classList.toggle('is-active', idx === i);
      });
      if (caption) {
        caption.style.opacity = '0';
        caption.style.transform = 'translateY(8px)';
        window.setTimeout(function () {
          caption.textContent = slides[i].getAttribute('data-caption') || '';
          caption.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
          caption.style.opacity = '1';
          caption.style.transform = 'none';
        }, 120);
      }
      if (progress) {
        progress.classList.remove('is-running');
        void progress.offsetWidth;
        if (!reduceMotion) {
          progress.classList.add('is-running');
        } else {
          progress.style.width = '100%';
        }
      }
    }

    show(0);

    if (!reduceMotion && slides.length > 1) {
      setInterval(function () {
        index = (index + 1) % slides.length;
        show(index);
      }, duration);
    }
  });
})();
