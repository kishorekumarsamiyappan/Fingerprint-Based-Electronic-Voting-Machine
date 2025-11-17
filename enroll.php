<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Voter Enrollment - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <span class="navbar-text">
                Place: <?php echo $_SESSION['admin_place']; ?>
            </span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h4><i class="fas fa-users"></i> Pending Voter Enrollments</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Instructions:</strong><br>
                            1. Voters enroll their fingerprint on the hardware device<br>
                            2. Their enrollment appears here as "Pending Registration"<br>
                            3. Fill in their details and click "Complete Enrollment"<br>
                            4. The voter will then be able to vote
                        </div>

                        <div id="pendingEnrollments">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p>Loading pending enrollments...</p>
                            </div>
                        </div>

                        <div id="noPending" class="alert alert-success text-center" style="display: none;">
                            <i class="fas fa-check-circle fa-2x mb-3"></i>
                            <h5>No Pending Enrollments</h5>
                            <p>All voter enrollments have been completed.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollment Modal -->
    <div class="modal fade" id="enrollmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Voter Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="completeEnrollmentForm">
                        <input type="hidden" id="modal_fingerprint_id" name="fingerprint_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Fingerprint ID</label>
                            <input type="text" class="form-control" id="modal_display_fingerprint_id" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Place</label>
                            <input type="text" class="form-control" id="modal_place" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="modal_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_dob" class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control" id="modal_dob" name="dob" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_aadhaar" class="form-label">Aadhaar Number *</label>
                            <input type="text" class="form-control" id="modal_aadhaar" name="aadhaar" maxlength="12" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_voter_id" class="form-label">Voter ID *</label>
                            <input type="text" class="form-control" id="modal_voter_id" name="voter_id" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="completeEnrollmentBtn">
                        <i class="fas fa-check"></i> Complete Enrollment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let pendingEnrollments = [];

        // Load pending enrollments
        function loadPendingEnrollments() {
            fetch('api/get_pending_enrollments.php')
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        pendingEnrollments = data.data;
                        displayPendingEnrollments();
                    } else {
                        showError('Failed to load pending enrollments');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Network error loading enrollments');
                });
        }

        // Display pending enrollments
        function displayPendingEnrollments() {
            const container = document.getElementById('pendingEnrollments');
            const noPending = document.getElementById('noPending');

            if(pendingEnrollments.length === 0) {
                container.style.display = 'none';
                noPending.style.display = 'block';
                return;
            }

            container.style.display = 'block';
            noPending.style.display = 'none';

            let html = '<div class="row">';
            
            pendingEnrollments.forEach(enrollment => {
                const date = new Date(enrollment.created_at).toLocaleString();
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Fingerprint ID: ${enrollment.fingerprint_id}</h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt"></i> ${enrollment.place}<br>
                                        <i class="fas fa-clock"></i> ${date}<br>
                                        <span class="badge bg-warning">Pending Registration</span>
                                    </small>
                                </p>
                                <button class="btn btn-primary btn-sm w-100" 
                                        onclick="openEnrollmentModal(${enrollment.fingerprint_id}, '${enrollment.place}')">
                                    <i class="fas fa-edit"></i> Complete Registration
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        // Open enrollment modal
        function openEnrollmentModal(fingerprintId, place) {
            document.getElementById('modal_fingerprint_id').value = fingerprintId;
            document.getElementById('modal_display_fingerprint_id').value = fingerprintId;
            document.getElementById('modal_place').value = place;
            document.getElementById('modal_name').value = '';
            document.getElementById('modal_dob').value = '';
            document.getElementById('modal_aadhaar').value = '';
            document.getElementById('modal_voter_id').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('enrollmentModal'));
            modal.show();
        }

        // Complete enrollment
        document.getElementById('completeEnrollmentBtn').addEventListener('click', function() {
            const form = document.getElementById('completeEnrollmentForm');
            const formData = new FormData(form);
            const btn = this;
            
            // Basic validation
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
            
            const jsonData = {};
            for(let [key, value] of formData.entries()) {
                jsonData[key] = value;
            }
            
            fetch('api/complete_enrollment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // Close modal and refresh list
                    bootstrap.Modal.getInstance(document.getElementById('enrollmentModal')).hide();
                    showSuccess(data.message);
                    loadPendingEnrollments();
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error completing enrollment');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Complete Enrollment';
            });
        });

        // Show success message
        function showSuccess(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.card'));
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Show error message
        function showError(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.card'));
        }

        // Load enrollments on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPendingEnrollments();
            
            // Refresh every 30 seconds to check for new enrollments
            setInterval(loadPendingEnrollments, 30000);
        });
    </script>
</body>
</html>