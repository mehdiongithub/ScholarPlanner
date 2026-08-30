        </div> <!-- .admin-content -->
    </main> <!-- .admin-main -->
</div> <!-- .admin-layout -->

<script>
    // Initialize Lucide Icons
    lucide.createIcons();

    // Responsive Sidebar toggle drawer logic
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

    // Auto-fade notifications alerts after 5 seconds
    const alerts = document.querySelectorAll('.admin-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
</script>
</body>
</html>
