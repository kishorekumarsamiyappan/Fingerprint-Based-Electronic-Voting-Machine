// voters.js - Complete implementation with fixes
let currentVoterToDelete = null;

// Load all voters
function loadVoters() {
    showLoadingState();
    
    console.log('Loading voters...');
    
    fetch('api/get_voters.php')
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Voters data received:', data);
            if(data.status === 'success') {
                if (data.voters && Array.isArray(data.voters)) {
                    displayVoters(data.voters);
                    updateVoterCounts(data.stats);
                } else {
                    console.error('Invalid voters data format:', data);
                    showNoVoters();
                    showAlert('Invalid data format received from server', 'danger');
                }
            } else {
                showNoVoters();
                console.error('Error from server:', data.message);
                showAlert('Server error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading voters:', error);
            showNoVoters();
            showAlert('Error loading voters: ' + error.message, 'danger');
        });
}

// Display voters in table
function displayVoters(voters) {
    const tableBody = document.getElementById('votersTable');
    const loadingDiv = document.getElementById('loadingVoters');
    const noVotersDiv = document.getElementById('noVoters');
    
    console.log('Displaying voters:', voters);
    
    if(!voters || voters.length === 0) {
        showNoVoters();
        return;
    }
    
    loadingDiv.style.display = 'none';
    noVotersDiv.style.display = 'none';
    
    let html = '';
    voters.forEach((voter, index) => {
        const registerDate = voter.created_at ? new Date(voter.created_at).toLocaleDateString() : 'N/A';
        const statusBadge = voter.status === 'completed' ? 
            '<span class="badge bg-success">Completed</span>' : 
            '<span class="badge bg-warning">Pending</span>';
        
        // Handle null values safely
        const voterName = voter.name || 'Pending Registration';
        const voterDOB = voter.dob ? new Date(voter.dob).toLocaleDateString() : 'N/A';
        const voterAadhaar = voter.aadhaar || 'N/A';
        const voterID = voter.voter_id || 'N/A';
        const voterPlace = voter.place || 'Unknown';
        
        html += `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${voter.fingerprint_id}</strong></td>
                <td>${voterName}</td>
                <td>${voterDOB}</td>
                <td>${voterAadhaar}</td>
                <td>${voterID}</td>
                <td>${voterPlace}</td>
                <td>${statusBadge}</td>
                <td>${registerDate}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger delete-voter" 
                            data-id="${voter.fingerprint_id}" 
                            data-name="${voterName}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    
    // Add event listeners to delete buttons
    document.querySelectorAll('.delete-voter').forEach(button => {
        button.addEventListener('click', function() {
            const fingerprintId = this.getAttribute('data-id');
            const voterName = this.getAttribute('data-name');
            showDeleteModal(fingerprintId, voterName);
        });
    });
}

// Update voter counts
function updateVoterCounts(stats) {
    if (!stats) {
        console.warn('No stats provided');
        return;
    }
    
    console.log('Updating voter counts:', stats);
    
    document.getElementById('voterCount').textContent = `${stats.total} voters`;
    document.getElementById('completedCount').textContent = `${stats.completed} completed`;
    document.getElementById('pendingCount').textContent = `${stats.pending} pending`;
}

// Show loading state
function showLoadingState() {
    document.getElementById('loadingVoters').style.display = 'block';
    document.getElementById('noVoters').style.display = 'none';
    document.getElementById('votersTable').innerHTML = '';
}

// Show no voters state
function showNoVoters() {
    document.getElementById('loadingVoters').style.display = 'none';
    document.getElementById('noVoters').style.display = 'block';
    document.getElementById('votersTable').innerHTML = '';
}

// Show delete confirmation modal
function showDeleteModal(fingerprintId, voterName) {
    currentVoterToDelete = fingerprintId;
    document.getElementById('deleteFingerprintId').textContent = fingerprintId;
    document.getElementById('deleteVoterName').textContent = voterName;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Delete voter
function deleteVoter() {
    if(!currentVoterToDelete) return;
    
    console.log('Deleting voter with fingerprint ID:', currentVoterToDelete);
    
    fetch(`api/delete_voter.php?fingerprint_id=${currentVoterToDelete}`, {
        method: 'DELETE'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Delete response:', data);
        if(data.status === 'success') {
            // Reload voters
            loadVoters();
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            // Show success message
            showAlert('Voter deleted successfully!', 'success');
        } else {
            showAlert('Error deleting voter: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error deleting voter: ' + error.message, 'danger');
    });
}

// Load voting activity
function loadVotingActivity() {
    console.log('Loading voting activity...');
    
    fetch('api/get_voting_activity.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Voting activity data:', data);
            if(data.status === 'success') {
                displayVotingActivity(data.recentVotes);
            } else {
                document.getElementById('votingActivity').innerHTML = 
                    '<p class="text-muted text-center">No voting activity available.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading voting activity:', error);
            document.getElementById('votingActivity').innerHTML = 
                '<p class="text-danger text-center">Error loading voting activity: ' + error.message + '</p>';
        });
}

// Display voting activity
function displayVotingActivity(votes) {
    const activityDiv = document.getElementById('votingActivity');
    
    if(!votes || votes.length === 0) {
        activityDiv.innerHTML = '<p class="text-muted text-center">No voting activity yet.</p>';
        return;
    }
    
    let html = '';
    votes.forEach(vote => {
        const time = vote.voted_at ? new Date(vote.voted_at).toLocaleString() : 'Unknown time';
        const voterName = vote.name || 'Unknown Voter';
        const fingerprintId = vote.fingerprint_id || 'Unknown';
        const candidateId = vote.candidate_id || 'Unknown';
        
        html += `
            <div class="vote-activity-item mb-3 p-2 border rounded">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${voterName}</strong><br>
                        <small class="text-muted">Fingerprint ID: ${fingerprintId}</small>
                    </div>
                    <span class="badge bg-primary">Candidate ${candidateId}</span>
                </div>
                <div class="mt-2">
                    <small class="text-muted"><i class="fas fa-clock"></i> ${time}</small>
                </div>
            </div>
        `;
    });
    
    activityDiv.innerHTML = html;
}

// Load voting statistics
function loadVotingStats() {
    console.log('Loading voting stats...');
    
    fetch('api/get_voting_stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Voting stats data:', data);
            if(data.status === 'success') {
                displayVotingStats(data.stats);
            } else {
                document.getElementById('votingStats').innerHTML = 
                    '<p class="text-muted text-center">No statistics available.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading voting stats:', error);
            document.getElementById('votingStats').innerHTML = 
                '<p class="text-danger text-center">Error loading statistics: ' + error.message + '</p>';
        });
}

// Display voting statistics
function displayVotingStats(stats) {
    const statsDiv = document.getElementById('votingStats');
    
    if (!stats) {
        statsDiv.innerHTML = '<p class="text-muted text-center">No statistics available.</p>';
        return;
    }
    
    const turnoutPercentage = stats.totalVoters > 0 ? 
        ((stats.votedCount / stats.totalVoters) * 100).toFixed(1) : 0;
    
    statsDiv.innerHTML = `
        <div class="stats-item">
            <div class="d-flex justify-content-between">
                <span>Total Voters:</span>
                <strong>${stats.totalVoters}</strong>
            </div>
        </div>
        <div class="stats-item">
            <div class="d-flex justify-content-between">
                <span>Voted:</span>
                <strong>${stats.votedCount}</strong>
            </div>
        </div>
        <div class="stats-item">
            <div class="d-flex justify-content-between">
                <span>Not Voted:</span>
                <strong>${stats.notVotedCount}</strong>
            </div>
        </div>
        <div class="stats-item">
            <div class="d-flex justify-content-between">
                <span>Voter Turnout:</span>
                <strong>${turnoutPercentage}%</strong>
            </div>
            <div class="progress mt-1" style="height: 8px;">
                <div class="progress-bar bg-success" style="width: ${turnoutPercentage}%"></div>
            </div>
        </div>
    `;
}

// Export voters to Excel
function exportVoters() {
    console.log('Exporting voters...');
    
    const link = document.createElement('a');
    link.href = 'api/export_voters.php';
    link.download = `voters_export_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('Voter export started!', 'success');
}

// Show alert message
function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.row.mb-4'));
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Search voters
function searchVoters() {
    const searchTerm = document.getElementById('searchVoter').value.toLowerCase();
    const rows = document.querySelectorAll('#votersTable tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update count
    if (searchTerm) {
        document.getElementById('voterCount').textContent = `${visibleCount} voters (filtered)`;
    } else {
        // Reload to get original count
        loadVoters();
    }
}

// Filter voters by status
function filterVotersByStatus() {
    const status = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('#votersTable tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (!status) {
            row.style.display = '';
            visibleCount++;
            return;
        }
        
        const statusBadge = row.querySelector('.badge');
        if (statusBadge) {
            const rowStatus = statusBadge.textContent.toLowerCase().trim();
            if (rowStatus === status) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
    });
    
    // Update count for filtered results
    if (status) {
        document.getElementById('voterCount').textContent = `${visibleCount} voters (filtered)`;
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Voters page initialized');
    
    // Load initial data
    loadVoters();
    loadVotingActivity();
    loadVotingStats();
    
    // Event listeners
    document.getElementById('refreshVoters').addEventListener('click', function() {
        console.log('Refreshing data...');
        loadVoters();
        loadVotingActivity();
        loadVotingStats();
        showAlert('Data refreshed successfully!', 'info');
    });
    
    document.getElementById('refreshActivity').addEventListener('click', loadVotingActivity);
    document.getElementById('exportVoters').addEventListener('click', exportVoters);
    document.getElementById('confirmDelete').addEventListener('click', deleteVoter);
    document.getElementById('searchBtn').addEventListener('click', searchVoters);
    document.getElementById('searchVoter').addEventListener('input', searchVoters);
    
    // Filter by status
    document.getElementById('filterStatus').addEventListener('change', filterVotersByStatus);
    
    // Auto-refresh every 30 seconds
    setInterval(() => {
        loadVoters();
        loadVotingActivity();
        loadVotingStats();
    }, 30000);
});