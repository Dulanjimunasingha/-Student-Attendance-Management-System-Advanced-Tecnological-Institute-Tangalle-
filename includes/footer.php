<?php
/**
 * Footer Template
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */
?>

            <!-- Toast Messages -->
            <?php 
            $flash = getFlash();
            if ($flash):  
            ?>
            <div class="toast-container">
                <div class="toast show align-items-center text-white bg-<?php echo $flash['type']; ?> border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <?php echo htmlspecialchars($flash['message']); ?>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- End Page Content -->
    </div><!-- End Main Content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        // Auto-hide toast after 5 seconds
        setTimeout(function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                const bsToast = bootstrap.Toast.getOrCreateInstance(toast);
                bsToast.hide();
            });
        }, 5000);
        
        // Confirm delete
        function confirmDelete(message) {
            return Swal.fire({
                title: 'Are you sure?',
                text: message || 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            });
        }
        
        // Show success message
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        // Show error message
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message
            });
        }
        
        // Show loading
        function showLoading() {
            Swal.fire({
                title: 'Loading...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        
        // Format percentage color
        function getPercentageColor(percentage, minRequired = 75) {
            if (percentage >= minRequired) {
                return 'text-success';
            } else if (percentage >= 50) {
                return 'text-warning';
            } else {
                return 'text-danger';
            }
        }
    </script>
    
    <?php if (isset($additionalJS)): ?>
    <?php echo $additionalJS; ?>
    <?php endif; ?>
</body>
</html>
