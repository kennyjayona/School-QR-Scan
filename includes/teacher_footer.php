        </div><!-- End dashboard-container -->
    </main><!-- End main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;

        // Load saved theme
        const savedTheme = localStorage.getItem('smart-classroom-theme') || 'light';
        html.classList.toggle('dark', savedTheme === 'dark');
        themeToggle.querySelector('i').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

        themeToggle.addEventListener('click', () => {
            const isDark = html.classList.toggle('dark');
            const newTheme = isDark ? 'dark' : 'light';
            localStorage.setItem('smart-classroom-theme', newTheme);
            themeToggle.querySelector('i').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        });

        // Bootstrap modal fix
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-toggle="modal"]').forEach(function(element) {
                const target = element.getAttribute('data-target');
                element.setAttribute('data-bs-toggle', 'modal');
                element.setAttribute('data-bs-target', target);
                element.removeAttribute('data-toggle');
                element.removeAttribute('data-target');
            });
        });
    </script>
</body>
</html>
