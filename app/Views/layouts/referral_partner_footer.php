        </div> <!-- .admin-content -->
    </main> <!-- .admin-main -->
</div> <!-- .admin-layout -->

<script>
    // Initialize Lucide Icons
    try {
        lucide.createIcons();
    } catch (e) {
        console.error("Lucide icons error:", e);
    }

    // Responsive Sidebar toggle drawer logic
    try {
        const mobileToggle = document.getElementById('mobileToggle');
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (mobileToggle && adminSidebar && sidebarOverlay) {
            mobileToggle.addEventListener('click', () => {
                adminSidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('open');
            });

            sidebarOverlay.addEventListener('click', () => {
                adminSidebar.classList.remove('open');
                sidebarOverlay.classList.remove('open');
            });
        }
    } catch (e) {
        console.error("Responsive sidebar error:", e);
    }

    // Auto-fade notifications alerts after 5 seconds
    try {
        const alerts = document.querySelectorAll('.admin-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    } catch (e) {
        console.error("Alerts auto-fade error:", e);
    }
</script>
</body>
</html>
