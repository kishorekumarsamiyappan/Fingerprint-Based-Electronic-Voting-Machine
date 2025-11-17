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
    <title>Voters - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-responsive { max-height: 500px; }
        .vote-activity-item { background: #f8f9fa; }
        .stats-item { border-bottom: 1px solid #dee2e6; padding: 8px 0; }
        .stats-item:last-child { border-bottom: none; }
    </style>
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
        <h2 class="text-center mb-4">Voter Management - <?php echo $_SESSION['admin_place']; ?></h2>
        
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-md-5">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchVoter" placeholder="Search by name, Aadhaar, or Voter ID...">
                    <button class="btn btn-outline-primary" type="button" id="searchBtn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="filterStatus">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="refreshVoters">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success w-100" id="exportVoters">
                    <i class="fas fa-file-excel"></i> Export Voters
                </button>
            </div>
        </div>

        <!-- Voters Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-users"></i> Registered Voters</h5>
                <div>
                    <span class="badge bg-primary" id="voterCount">0 voters</span>
                    <span class="badge bg-success ms-2" id="completedCount">0 completed</span>
                    <span class="badge bg-warning ms-2" id="pendingCount">0 pending</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Fingerprint ID</th>
                                <th>Name</th>
                                <th>Date of Birth</th>
                                <th>Aadhaar</th>
                                <th>Voter ID</th>
                                <th>Place</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="votersTable">
                            <!-- Voters will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Loading and Empty States -->
                <div id="loadingVoters" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading voters...</p>
                </div>
                
                <div id="noVoters" class="text-center py-4" style="display: none;">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5>No Voters Found</h5>
                    <p class="text-muted">No voters are registered yet.</p>
                    <a href="enroll.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Enroll First Voter
                    </a>
                </div>
            </div>
        </div>

        <!-- Voting Activity -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6><i class="fas fa-history"></i> Recent Voting Activity</h6>
                        <button class="btn btn-sm btn-outline-primary" id="refreshActivity">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="votingActivity">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading voting activity...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-pie"></i> Voting Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div id="votingStats">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading statistics...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this voter? This action cannot be undone.</p>
                    <p><strong>Fingerprint ID:</strong> <span id="deleteFingerprintId"></span></p>
                    <p><strong>Name:</strong> <span id="deleteVoterName"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete Voter</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/voters.js"></script>
</body>
</html>