<?php
$isLoggedIn = isset($_SESSION['usuario_id']);
?>

<?php if ($isLoggedIn): ?>
        </div><!-- /.content-area -->
    </main><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar Toggle for Mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }
</script>
<?php else: ?>
</div><!-- /.non-auth-wrapper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>

</body>
</html>