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
    <title>Dashboard - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .status-badge {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .status-badge:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-fingerprint"></i> Voting System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['admin_username']; ?> | 
                    <i class="fas fa-map-marker-alt"></i> <?php echo $_SESSION['admin_place']; ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4 class="alert-heading">
                        <i class="fas fa-tachometer-alt"></i> Welcome to Voting System Dashboard
                    </h4>
                    <p class="mb-0">You are logged in as <strong><?php echo $_SESSION['admin_username']; ?></strong> for <strong><?php echo $_SESSION['admin_place']; ?></strong> location.</p>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-server"></i> System Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="systemStatus">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold">Fingerprint Sensor:</span>
                                <span id="sensorStatus" class="badge bg-warning status-badge">Checking...</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold">WiFi Connection:</span>
                                <span id="wifiStatus" class="badge bg-warning status-badge">Checking...</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">ESP32 Device:</span>
                                <span id="deviceStatus" class="badge bg-secondary status-badge">Checking...</span>
                            </div>
                            <small class="text-muted mt-3 d-block">
                                <i class="fas fa-info-circle"></i> Click status badges to refresh
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line"></i> Quick Stats
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="quickStats">
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <span class="fw-bold">Total Voters:</span>
                                <strong id="totalVoters" class="fs-5 text-primary">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <span class="fw-bold">Votes Cast:</span>
                                <strong id="votesCast" class="fs-5 text-success">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <span class="fw-bold">Voter Turnout:</span>
                                <strong id="voterTurnout" class="fs-5 text-info">-</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Cards -->
        <div class="row">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card text-center h-100 dashboard-card">
                    <div class="card-body">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Enroll Voter</h5>
                        <p class="card-text">Register new voters with fingerprint authentication</p>
                        <a href="enroll.php" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right"></i> Go to Enrollment
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card text-center h-100 dashboard-card">
                    <div class="card-body">
                        <i class="fas fa-vote-yea fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Voter Management</h5>
                        <p class="card-text">View and manage registered voters</p>
                        <a href="voters.php" class="btn btn-success w-100">
                            <i class="fas fa-arrow-right"></i> View Voters
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card text-center h-100 dashboard-card">
                    <div class="card-body">
                        <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Live Results</h5>
                        <p class="card-text">View real-time election results and analytics</p>
                        <a href="results.php" class="btn btn-info w-100">
                            <i class="fas fa-arrow-right"></i> View Results
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card text-center h-100 dashboard-card">
                    <div class="card-body">
                        <i class="fas fa-user-shield fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Admin Management</h5>
                        <p class="card-text">Manage system administrators and access</p>
                        <a href="admin_management.php" class="btn btn-warning w-100">
                            <i class="fas fa-arrow-right"></i> Manage Admins
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Warning (will be shown if ESP32 is offline) -->
        <div id="connectionWarning" style="display: none;"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>