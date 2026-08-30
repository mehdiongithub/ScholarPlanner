// Initialize Lucide icons
if (typeof lucide !== 'undefined' && lucide.createIcons) {
    try {
        lucide.createIcons();
    } catch (e) {
        console.error("Lucide icons error:", e);
    }
}

// ---- Sticky Header ----
const header = document.getElementById('header');
let lastScroll = 0;

function handleHeaderScroll() {
    if (!header) return;
    const scrollY = window.scrollY;
    if (scrollY > 20) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
    lastScroll = scrollY;
}

window.addEventListener('scroll', handleHeaderScroll, { passive: true });

// ---- Mobile Menu ----
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileMenuClose = document.getElementById('mobileMenuClose');
const mobileMenu = document.getElementById('mobileMenu');
const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
const body = document.body;

function openMenu() {
    if (!mobileMenu || !mobileMenuOverlay || !mobileMenuBtn) return;
    mobileMenu.classList.add('active');
    mobileMenuOverlay.classList.add('active');
    mobileMenu.setAttribute('aria-hidden', 'false');
    mobileMenuOverlay.setAttribute('aria-hidden', 'false');
    mobileMenuBtn.setAttribute('aria-expanded', 'true');
    body.classList.add('menu-open');
    if (mobileMenuClose) mobileMenuClose.focus();
}

function closeMenu() {
    if (!mobileMenu || !mobileMenuOverlay || !mobileMenuBtn) return;
    mobileMenu.classList.remove('active');
    mobileMenuOverlay.classList.remove('active');
    mobileMenu.setAttribute('aria-hidden', 'true');
    mobileMenuOverlay.setAttribute('aria-hidden', 'true');
    mobileMenuBtn.setAttribute('aria-expanded', 'false');
    body.classList.remove('menu-open');
    mobileMenuBtn.focus();
}

if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMenu);
if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeMenu);
if (mobileMenuOverlay) mobileMenuOverlay.addEventListener('click', closeMenu);

// Close menu on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && mobileMenu && mobileMenu.classList.contains('active')) {
        closeMenu();
    }
});

// Close menu when clicking a link inside mobile menu
if (mobileMenu) {
    mobileMenu.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            closeMenu();
        });
    });
}

// ---- FAQ Accordion ----
document.querySelectorAll('.faq-question').forEach(function(button) {
    button.addEventListener('click', function() {
        const item = this.closest('.faq-item');
        if (!item) return;
        const isActive = item.classList.contains('active');
        const expanded = !isActive;

        // Close all other items
        document.querySelectorAll('.faq-item.active').forEach(function(activeItem) {
            if (activeItem !== item) {
                activeItem.classList.remove('active');
                activeItem.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
            }
        });

        // Toggle current item
        item.classList.toggle('active');
        this.setAttribute('aria-expanded', String(expanded));
    });
});

// ---- Scroll Reveal Animation ----
const revealElements = document.querySelectorAll('.reveal');

// Check for reduced motion preference
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!prefersReducedMotion) {
    if (typeof IntersectionObserver !== 'undefined') {
        const revealObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        });

        revealElements.forEach(function(el) {
            revealObserver.observe(el);
        });
    }
} else {
    // If reduced motion, show everything immediately
    revealElements.forEach(function(el) {
        el.classList.add('revealed');
    });
}

// ---- Smooth scroll for anchor links ----
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#') return;

        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            const headerHeight = header ? header.offsetHeight : 0;
            const targetPosition = target.getBoundingClientRect().top + window.scrollY - headerHeight - 16;
            window.scrollTo({
                top: targetPosition,
                behavior: prefersReducedMotion ? 'auto' : 'smooth'
            });
        }
    });
});

// ---- Global DataTables Initialization Helper ----
window.ScholarMatchDataTable = function(selector, options) {
    const defaults = {
        processing: true,
        serverSide: true,
        responsive: true
    };
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        return $(selector).DataTable(Object.assign({}, defaults, options));
    }
    console.warn("jQuery or DataTables library not loaded when ScholarMatchDataTable called.");
    return null;
};