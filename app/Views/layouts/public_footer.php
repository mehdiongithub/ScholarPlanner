    </main> <!-- .public-main-content -->

    <!-- Public Website Footer -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <div class="footer-logo-icon">
                            <i data-lucide="graduation-cap"></i>
                        </div>
                        <span class="footer-logo-text">ScholarMatch</span>
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
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                    <a href="#">Refund Policy</a>
                    <a href="#">WhatsApp Policy</a>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; <?= date('Y') ?> ScholarMatch. All rights reserved.</span>
                <span>
                    <a href="#" style="margin-left:16px;color:var(--text-400);transition:color 150ms" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-400)'">Help Center</a>
                </span>
            </div>
        </div>
    </footer>

    <!-- Initialize icons & scripts -->
    <script src="<?= asset('assets/js/main.js') ?>"></script>
    <script>
        try {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        } catch (e) {
            console.error("Lucide load error:", e);
        }

        // Initialize Select2 for all dropdowns on public pages
        $(document).ready(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%',
                    minimumResultsForSearch: 10
                });
            }
        });
    </script>
</body>
</html>
