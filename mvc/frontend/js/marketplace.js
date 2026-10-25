(function () {
  'use strict';

  function reveal() {
    var nodes = document.querySelectorAll('.fv-mp-reveal');
    if (!nodes.length || !('IntersectionObserver' in window)) {
      nodes.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    nodes.forEach(function (el) { io.observe(el); });
  }

  function tabs() {
    var root = document.querySelector('[data-mp-tabs]');
    if (!root) return;
    var buttons = root.querySelectorAll('[data-mp-tab]');
    var panels = document.querySelectorAll('[data-mp-panel]');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var key = btn.getAttribute('data-mp-tab');
        buttons.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
        panels.forEach(function (p) {
          p.hidden = p.getAttribute('data-mp-panel') !== key;
        });
      });
    });
  }

  function carousels() {
    document.querySelectorAll('[data-mp-carousel]').forEach(function (root) {
      var track = root.querySelector('[data-mp-carousel-track]');
      if (!track) return;
      var id = root.getAttribute('data-mp-carousel');
      var nav = document.querySelector('[data-mp-carousel-nav="' + id + '"]');
      var step = function () {
        var item = track.querySelector('.fv-mp-carousel__item');
        if (!item) return Math.min(240, track.clientWidth * 0.7);
        var styles = window.getComputedStyle(track);
        var gap = parseFloat(styles.columnGap || styles.gap || '10') || 10;
        // Avanzar varias miniaturas por clic en carrusel compacto.
        return (item.getBoundingClientRect().width + gap) * 3;
      };
      var scrollByDir = function (dir) {
        track.scrollBy({ left: dir * step(), behavior: 'smooth' });
      };
      if (nav) {
        var prev = nav.querySelector('[data-mp-carousel-prev]');
        var next = nav.querySelector('[data-mp-carousel-next]');
        if (prev) prev.addEventListener('click', function () { scrollByDir(-1); });
        if (next) next.addEventListener('click', function () { scrollByDir(1); });
      }
      var dragging = false;
      var startX = 0;
      var startScroll = 0;
      track.addEventListener('pointerdown', function (e) {
        dragging = true;
        startX = e.clientX;
        startScroll = track.scrollLeft;
        track.classList.add('is-dragging');
        try { track.setPointerCapture(e.pointerId); } catch (err) {}
      });
      track.addEventListener('pointermove', function (e) {
        if (!dragging) return;
        track.scrollLeft = startScroll - (e.clientX - startX);
      });
      var endDrag = function () {
        dragging = false;
        track.classList.remove('is-dragging');
      };
      track.addEventListener('pointerup', endDrag);
      track.addEventListener('pointercancel', endDrag);
    });
  }

  function langMenu() {
    var root = document.querySelector('[data-sf-lang]');
    if (!root) return;
    var btn = root.querySelector('#fvSfLangBtn');
    var menu = root.querySelector('#fvSfLangMenu');
    if (!btn || !menu) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = menu.hasAttribute('hidden');
      if (open) {
        menu.removeAttribute('hidden');
        btn.setAttribute('aria-expanded', 'true');
      } else {
        menu.setAttribute('hidden', '');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('click', function () {
      menu.setAttribute('hidden', '');
      btn.setAttribute('aria-expanded', 'false');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    reveal();
    tabs();
    carousels();
    langMenu();
  });
})();
