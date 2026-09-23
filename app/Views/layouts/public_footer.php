    </main> <!-- .public-main-content -->

    <!-- Public Website Footer -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <img src="<?= asset('assets/images/logo.webp') ?>" alt="ScholarPlanner Logo" class="footer-logo-img" width="152" height="38">
                    </div>
                    <p>Find scholarships that match your profile. Receive personalized alerts through WhatsApp and email.</p>
                </div>

                <div class="footer-col">
                    <h4>Product</h4>
                    <a href="<?= url('/scholarships') ?>">Scholarships</a>
                    <a href="<?= url('/#how-it-works') ?>">How It Works</a>
                    <a href="<?= url('/#features') ?>">Features</a>
                    <a href="<?= url('/pricing') ?>">Pricing</a>
                    <a href="<?= url('/faq') ?>">FAQ</a>
                </div>

                <div class="footer-col">
                    <h4>Resources</h4>
                    <a href="#">Scholarship Guide</a>
                    <a href="#">Application Guide</a>
                    <a href="#">Student Resources</a>
                    <a href="#">Blog</a>
                </div>

                <div class="footer-col">
                    <h4>Company</h4>
                    <a href="<?= url('/about') ?>">About Us</a>
                    <a href="<?= url('/contact') ?>">Contact</a>
                    <a href="#">Careers</a>
                </div>

                <div class="footer-col">
                    <h4>Legal</h4>
                    <a href="<?= url('/privacy') ?>">Privacy Policy</a>
                    <a href="<?= url('/terms') ?>">Terms of Service</a>
                    <a href="<?= url('/faq') ?>">FAQ & Help</a>
                    <a href="<?= url('/contact') ?>">Support</a>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; <?= date('Y') ?> ScholarPlanner. All rights reserved.</span>
                <span>
                    <a href="#" style="margin-left:16px;color:var(--text-400);transition:color 150ms" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-400)'">Help Center</a>
                </span>
            </div>
        </div>
    </footer>

    <!-- Core & Vendor Scripts -->
    <?php if (!empty($needsSelect2)): ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function initPublicSelect2() {
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined' && jQuery.fn.select2) {
                jQuery('.select2').select2({
                    width: '100%',
                    minimumResultsForSearch: 10
                });
            } else {
                setTimeout(initPublicSelect2, 50);
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPublicSelect2);
        } else {
            initPublicSelect2();
        }
    </script>
    <?php endif; ?>
    <script defer src="<?= asset('assets/js/lucide.min.js') ?>"></script>
    <script defer src="<?= asset('assets/js/main.js') ?>"></script>
    <script>
        function initLucideSafe() {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                try {
                    lucide.createIcons();
                } catch (e) {
                    console.error("Lucide load error:", e);
                }
            } else {
                setTimeout(initLucideSafe, 50);
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLucideSafe);
        } else {
            initLucideSafe();
        }
    </script>
</body>
</html>
