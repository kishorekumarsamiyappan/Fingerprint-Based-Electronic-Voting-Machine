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
    <title>Results - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .live-results { max-height: 400px; overflow-y: auto; }
        .leading-candidate { background-color: #d4edda; }
        .candidate-result { padding: 10px; border-radius: 5px; }
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
        <h2 class="text-center mb-4">Election Results - <?php echo $_SESSION['admin_place']; ?></h2>
        
        <!-- Control Panel -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button class="btn btn-primary" id="refreshResults">
                                    <i class="fas fa-sync-alt"></i> Refresh Results
                                </button>
                                <button class="btn btn-success" id="exportResults">
                                    <i class="fas fa-download"></i> Export to Excel
                                </button>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                                <label class="form-check-label" for="autoRefresh">Auto Refresh (10s)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-users"></i> Total Voters</h5>
                        <h3 id="totalVoters">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-vote-yea"></i> Votes Cast</h5>
                        <h3 id="totalVotes">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-user-check"></i> Voter Turnout</h5>
                        <h3 id="voterTurnout">0%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-trophy"></i> Leading</h5>
                        <h3 id="leadingCandidate">-</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Chart -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Vote Distribution</h5>
                        <span class="badge bg-primary" id="lastUpdated">Just now</span>
                    </div>
                    <div class="card-body">
                        <canvas id="resultsChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Live Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="liveResults" class="live-results">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading live results...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Results Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Detailed Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Candidate</th>
                                        <th>Votes</th>
                                        <th>Percentage</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="resultsTable">
                                    <tr>
                                        <td colspan="5" class="text-center">Loading results...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Winner Announcement -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h4 id="winnerAnnouncement">
                            <i class="fas fa-spinner fa-spin"></i> Calculating results...
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/results.js"></script>
</body>
</html>