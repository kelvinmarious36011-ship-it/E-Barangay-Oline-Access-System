/* ================================================================
   E-Barangay Online Access System — script.js
   Features: Sidebar toggle, Notification dropdown, Show/Hide PW,
             Tabs, Confirm modal, Toast notifications, Alert dismiss
================================================================ */

(function () {
  'use strict';

  /* ============================================================
     UTILITY
  ============================================================ */
  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  /* ============================================================
     SIDEBAR TOGGLE (mobile)
  ============================================================ */
  function initSidebar() {
    const toggle  = $('#sidebarToggle');
    const sidebar = $('#sidebar');
    const overlay = $('#sidebarOverlay');
    if (!toggle || !sidebar) return;

    function openSidebar() {
      sidebar.classList.add('open');
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    toggle.addEventListener('click', () => {
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeSidebar();
    });
  }

  /* ============================================================
     NOTIFICATION DROPDOWN
  ============================================================ */
  function initNotificationDropdown() {
    const wrapper  = $('#notifWrapper');
    const btn      = $('#notifBtn');
    const dropdown = $('#notifDropdown');
    if (!btn || !dropdown) return;

    let isOpen = false;

    function open() {
      dropdown.classList.add('open');
      isOpen = true;
      // Mark as read via AJAX-style (page request)
      const markAllLink = dropdown.querySelector('.notif-mark-all');
      // Mark all read silently on open
      fetch(window.location.pathname.includes('/')
        ? (window.location.pathname.split('/').slice(0,-1).join('/') + '/notification.php?mark_all=1')
        : 'notification.php?mark_all=1'
      ).catch(() => {});
      // Remove unread badge immediately
      const count = btn.querySelector('.notif-count');
      if (count) {
        setTimeout(() => {
          count.style.opacity = '0';
          setTimeout(() => count.remove(), 300);
        }, 500);
      }
      btn.classList.remove('has-unread');
      // Mark unread items as read visually
      dropdown.querySelectorAll('.notif-item.unread').forEach(el => {
        el.classList.remove('unread');
        const dot = el.querySelector('.notif-dot');
        if (dot) dot.style.background = 'var(--border)';
      });
    }

    function close() {
      dropdown.classList.remove('open');
      isOpen = false;
    }

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      isOpen ? close() : open();
    });

    document.addEventListener('click', (e) => {
      if (isOpen && !wrapper.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen) close();
    });
  }

  /* ============================================================
     SHOW / HIDE PASSWORD TOGGLE
  ============================================================ */
  function initPasswordToggles() {
    $$('.pw-toggle').forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetId = btn.dataset.target;
        const input    = document.getElementById(targetId);
        if (!input) return;

        const eyeShow = btn.querySelector('.eye-show');
        const eyeHide = btn.querySelector('.eye-hide');

        if (input.type === 'password') {
          input.type = 'text';
          if (eyeShow) eyeShow.style.display = 'none';
          if (eyeHide) eyeHide.style.display = '';
        } else {
          input.type = 'password';
          if (eyeShow) eyeShow.style.display = '';
          if (eyeHide) eyeHide.style.display = 'none';
        }
        input.focus();
      });
    });
  }

  /* ============================================================
     TABS
  ============================================================ */
  function initTabs() {
    $$('.tab-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const tabName  = btn.dataset.tab;
        const wrapper  = btn.closest('.tabs-wrapper');
        if (!wrapper) return;

        // Deactivate all
        wrapper.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        wrapper.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

        // Activate selected
        btn.classList.add('active');
        const panel = wrapper.querySelector('#tab-' + tabName);
        if (panel) panel.classList.add('active');
      });
    });
  }

  /* ============================================================
     CONFIRM MODAL
  ============================================================ */
  function initConfirmModal() {
    const modal   = $('#confirmModal');
    if (!modal) return;

    const titleEl = $('#confirmTitle');
    const msgEl   = $('#confirmMessage');
    const confirmBtn = $('#confirmBtn');
    let pendingCallback = null;

    function openModal(title, message, onConfirm) {
      if (titleEl) titleEl.textContent = title || 'Confirm Action';
      if (msgEl)   msgEl.textContent   = message || 'Are you sure you want to proceed?';
      pendingCallback = onConfirm;
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      // Focus confirm button for keyboard
      setTimeout(() => confirmBtn && confirmBtn.focus(), 100);
    }

    function closeModal() {
      modal.style.display = 'none';
      document.body.style.overflow = '';
      pendingCallback = null;
    }

    // Close on backdrop / close buttons
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
    modal.querySelectorAll('[data-close="confirmModal"]').forEach(el => {
      el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.style.display !== 'none') closeModal();
    });

    // Confirm button
    if (confirmBtn) {
      confirmBtn.addEventListener('click', () => {
        if (pendingCallback) pendingCallback();
        closeModal();
      });
    }

    // --- Trigger: confirm-link (anchor) ---
    $$('.confirm-link').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const msg  = link.dataset.confirm || 'Are you sure?';
        const href = link.href;
        openModal('Confirm Action', msg, () => {
          window.location.href = href;
        });
      });
    });

    // --- Trigger: confirm-btn (submit button inside form) ---
    $$('.confirm-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const msg  = btn.dataset.confirm || 'Are you sure?';
        const form = btn.closest('form');
        openModal('Confirm Action', msg, () => {
          if (form) form.submit();
        });
      });
    });
  }

  /* ============================================================
     ALERT DISMISS
  ============================================================ */
  function initAlertDismiss() {
    $$('.alert-close').forEach((btn) => {
      btn.addEventListener('click', () => {
        const alert = btn.closest('.alert');
        if (alert) {
          alert.style.transition = 'opacity .25s, max-height .3s';
          alert.style.opacity = '0';
          alert.style.overflow = 'hidden';
          alert.style.maxHeight = alert.scrollHeight + 'px';
          setTimeout(() => { alert.style.maxHeight = '0'; alert.style.padding = '0'; }, 10);
          setTimeout(() => alert.remove(), 400);
        }
      });
    });
  }

  /* ============================================================
     TOAST NOTIFICATIONS (manual trigger from PHP flash)
  ============================================================ */
  function showToast(message, type, duration) {
    const container = $('#toastContainer');
    if (!container) return;
    duration = duration || 3500;

    const toast = document.createElement('div');
    toast.className = 'toast ' + (type || 'info');
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.transition = 'opacity .4s, transform .4s';
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      setTimeout(() => toast.remove(), 400);
    }, duration);
  }

  // Expose globally so PHP-generated JS can call it
  window.showToast = showToast;

  /* ============================================================
     AUTO-DISMISS ALERTS (with flash messages)
  ============================================================ */
  function autoDismissAlerts() {
    $$('.alert-success, .alert-info').forEach((alert) => {
      if (alert.classList.contains('alert-dismissible')) {
        setTimeout(() => {
          alert.style.transition = 'opacity .5s';
          alert.style.opacity = '0';
          setTimeout(() => { if (alert.parentNode) alert.remove(); }, 500);
        }, 5000);
      }
    });
  }

  /* ============================================================
     TABLE ROW CLICK (row click goes to review link)
  ============================================================ */
  function initTableRowClick() {
    $$('.data-table tbody tr').forEach((row) => {
      const link = row.querySelector('a[href]');
      if (link && !row.dataset.noClick) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', (e) => {
          // Don't trigger if clicking on a button or anchor
          if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a, button')) return;
          link.click();
        });
      }
    });
  }

  /* ============================================================
     PROGRESS BARS — animate on load
  ============================================================ */
  function initProgressBars() {
    const bars = $$('.progress-bar-fill');
    if (!bars.length) return;

    // Store target widths and start from 0
    bars.forEach((bar) => {
      const targetWidth = bar.style.width;
      bar.style.width = '0%';
      bar.dataset.targetWidth = targetWidth;
    });

    // Animate after short delay
    setTimeout(() => {
      bars.forEach((bar) => {
        bar.style.transition = 'width .8s cubic-bezier(.4,0,.2,1)';
        bar.style.width = bar.dataset.targetWidth || '0%';
      });
    }, 150);
  }

  /* ============================================================
     STAT CARD NUMBERS — count-up animation
  ============================================================ */
  function initCountUp() {
    $$('.stat-card-number').forEach((el) => {
      const target = parseInt(el.textContent.replace(/,/g, ''), 10);
      if (isNaN(target) || target === 0) return;

      let current = 0;
      const step  = Math.max(1, Math.ceil(target / 40));
      const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString();
        if (current >= target) clearInterval(timer);
      }, 30);
    });
  }

  /* ============================================================
     ANNOUNCEMENT FORM TOGGLE (in announcement.php)
  ============================================================ */
  function initAnnouncementForm() {
    // Already handled inline in announcement.php, but also handle escape
    const card = $('#annFormCard');
    if (card && card.style.display !== 'none') {
      card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  /* ============================================================
     REQUEST DOCUMENT — doc type card hover effect
  ============================================================ */
  function initDocTypeCards() {
    $$('.doc-type-card').forEach((card) => {
      card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-4px)';
      });
      card.addEventListener('mouseleave', () => {
        card.style.transform = '';
      });
    });
  }

  /* ============================================================
     FORM VALIDATION — client side hints
  ============================================================ */
  function initFormValidation() {
    $$('form[novalidate]').forEach((form) => {
      form.addEventListener('submit', (e) => {
        let valid = true;
        $$('[required]', form).forEach((field) => {
          if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger)';
            field.style.boxShadow   = '0 0 0 3px rgba(220,53,69,.15)';
            valid = false;
            field.addEventListener('input', () => {
              field.style.borderColor = '';
              field.style.boxShadow   = '';
            }, { once: true });
          }
        });
        if (!valid) {
          e.preventDefault();
          const firstInvalid = form.querySelector('[required][value=""], [required]:invalid');
          if (firstInvalid) firstInvalid.focus();
          showToast('Please fill in all required fields.', 'error');
        }
      });
    });
  }

  /* ============================================================
     SMOOTH SCROLL for landing page anchors
  ============================================================ */
  function initSmoothScroll() {
    $$('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener('click', (e) => {
        const target = document.querySelector(anchor.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  /* ============================================================
     LANDING NAV — active link highlight on scroll
  ============================================================ */
  function initLandingNav() {
    const nav = $('.landing-nav');
    if (!nav) return;
    window.addEventListener('scroll', () => {
      if (window.scrollY > 60) {
        nav.style.boxShadow = '0 4px 20px rgba(15,42,74,.12)';
      } else {
        nav.style.boxShadow = '';
      }
    }, { passive: true });
  }

  /* ============================================================
     FILTER FORM — auto submit on select change
  ============================================================ */
  function initAutoFilterSubmit() {
    $$('.filter-form').forEach((form) => {
      $$('select', form).forEach((sel) => {
        sel.addEventListener('change', () => form.submit());
      });
    });
  }

  /* ============================================================
     NOTIFICATION PAGE — mark all read on visit
  ============================================================ */
  function initNotifPageMarkRead() {
    // The PHP already does this, but update sidebar badge too
    const navBadge = $('.nav-badge');
    const topBadge = $('.notif-count');
    if (navBadge) navBadge.style.display = 'none';
    if (topBadge) topBadge.style.display = 'none';
    const bellBtn = $('#notifBtn');
    if (bellBtn) bellBtn.classList.remove('has-unread');
  }

  /* ============================================================
     STAGGERED CARD ANIMATIONS (fade in on load)
  ============================================================ */
  function initCardAnimations() {
    const cards = $$('.stat-card, .service-card, .doc-type-card, .ann-full-card, .notif-full-item, .quick-action-card');
    cards.forEach((card, i) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(16px)';
      setTimeout(() => {
        card.style.transition = 'opacity .4s ease, transform .4s ease';
        card.style.opacity = '1';
        card.style.transform = '';
      }, 80 + i * 40);
    });
  }

  /* ============================================================
     SIDEBAR ACTIVE LINK HIGHLIGHT
     Ensures exact current page stays highlighted
  ============================================================ */
  function initSidebarActiveLink() {
    const currentPath = window.location.pathname.split('/').pop();
    $$('.nav-item').forEach((link) => {
      const linkPath = (link.getAttribute('href') || '').split('/').pop().split('?')[0];
      if (linkPath && linkPath === currentPath) {
        link.classList.add('active');
      }
    });
  }

  /* ============================================================
     TOPBAR DROPDOWN CLOSE ON OUTSIDE CLICK
  ============================================================ */
  function initTopbarClickAway() {
    document.addEventListener('click', () => {
      $$('.topbar-dropdown.open').forEach(d => d.classList.remove('open'));
    });
  }

  /* ============================================================
     KEYBOARD SHORTCUT: / to focus filter search
  ============================================================ */
  function initKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      if (e.key === '/' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'SELECT') {
        e.preventDefault();
        const search = $('.filter-search');
        if (search) { search.focus(); search.select(); }
      }
    });
  }

  /* ============================================================
     STEP INDICATOR (request_documents.php)
  ============================================================ */
  function initStepIndicator() {
    // Handled inline in request_documents.php,
    // but ensure step 3 activates on form submit
    const reqForm = $('#requestForm');
    if (!reqForm) return;
    reqForm.addEventListener('submit', () => {
      const s3 = $('#step3ind');
      if (s3) {
        s3.classList.add('active');
        s3.querySelector('.step-num').textContent = '3';
      }
    });
  }

  /* ============================================================
     INPUT FIELD — clear error style on focus
  ============================================================ */
  function initInputFocusClear() {
    $$('.form-input').forEach((input) => {
      input.addEventListener('focus', () => {
        input.style.borderColor = '';
        input.style.boxShadow   = '';
      });
    });
  }

  /* ============================================================
     BUSINESS: Annual income auto-calculate
  ============================================================ */
  function initIncomeAutoCalc() {
    const monthly = document.getElementById('monthly_income') ||
                    document.querySelector('[name="monthly_income"]');
    const annual  = document.getElementById('annual_income') ||
                    document.querySelector('[name="annual_income"]');
    if (!monthly || !annual) return;
    monthly.addEventListener('input', () => {
      const m = parseFloat(monthly.value);
      if (!isNaN(m)) {
        annual.value = (m * 12).toFixed(2);
      }
    });
  }

  /* ============================================================
     TOOLTIP on hover for truncated text
  ============================================================ */
  function initTruncatedTooltips() {
    $$('td, .detail-row strong').forEach((el) => {
      if (el.scrollWidth > el.clientWidth) {
        el.title = el.textContent.trim();
      }
    });
  }

  /* ============================================================
     PAGE TRANSITIONS (subtle fade)
  ============================================================ */
  function initPageTransitions() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity .25s ease';
    requestAnimationFrame(() => {
      document.body.style.opacity = '1';
    });

    $$('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not([href^="mailto"])').forEach((link) => {
      if (link.hostname === window.location.hostname) {
        link.addEventListener('click', (e) => {
          if (e.ctrlKey || e.metaKey || e.shiftKey) return;
          // Don't fade for confirm modals
          if (link.classList.contains('confirm-link')) return;
          document.body.style.opacity = '0';
        });
      }
    });
  }

  /* ============================================================
     NOTIFICATION PAGE DETECTION
  ============================================================ */
  function isNotificationPage() {
    return window.location.pathname.includes('notification.php');
  }

  /* ============================================================
     INIT ALL
  ============================================================ */
  ready(function () {
    initSidebar();
    initNotificationDropdown();
    initPasswordToggles();
    initTabs();
    initConfirmModal();
    initAlertDismiss();
    autoDismissAlerts();
    initProgressBars();
    initCountUp();
    initDocTypeCards();
    initFormValidation();
    initSmoothScroll();
    initLandingNav();
    initAutoFilterSubmit();
    initCardAnimations();
    initSidebarActiveLink();
    initKeyboardShortcuts();
    initStepIndicator();
    initInputFocusClear();
    initIncomeAutoCalc();
    initAnnouncementForm();
    initPageTransitions();

    // Notification page extras
    if (isNotificationPage()) {
      initNotifPageMarkRead();
    }

    // Table interactions
    setTimeout(() => {
      initTableRowClick();
      initTruncatedTooltips();
    }, 200);

    // Show any queued toasts from data attributes
    const toastQueue = document.querySelectorAll('[data-toast]');
    toastQueue.forEach((el) => {
      showToast(el.dataset.toast, el.dataset.toastType || 'info');
    });

    console.log('%c E-Barangay Online Access System ', 'background:#0f2a4a;color:#7efff6;font-weight:bold;padding:4px 8px;border-radius:4px;');
  });

})();