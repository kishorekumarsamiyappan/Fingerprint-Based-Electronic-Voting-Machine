let resultsChart;
let autoRefreshInterval;

// Function to update all results
function updateResults() {
    console.log('Updating results...');
    
    fetch('api/get_results.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Results data received:', data);
            if(data.status === 'success') {
                updateStatistics(data);
                updateChart(data);
                updateLiveResults(data);
                updateResultsTable(data);
                updateWinnerAnnouncement(data);
                
                // Update last updated time
                document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString();
            } else {
                console.error('Error fetching results:', data.message);
                showError('Failed to load results: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Network error loading results: ' + error.message);
        });
}

// Update statistics cards
function updateStatistics(data) {
    document.getElementById('totalVoters').textContent = data.totalVoters.toLocaleString();
    document.getElementById('totalVotes').textContent = data.totalVotes.toLocaleString();
    document.getElementById('voterTurnout').textContent = data.voterTurnout + '%';
    document.getElementById('leadingCandidate').textContent = data.leadingCandidate;
}

// Update the chart
function updateChart(data) {
    const ctx = document.getElementById('resultsChart');
    
    if (!ctx) {
        console.error('Chart canvas not found');
        return;
    }
    
    const chartContext = ctx.getContext('2d');
    
    if(resultsChart) {
        resultsChart.destroy();
    }
    
    resultsChart = new Chart(chartContext, {
        type: 'bar',
        data: {
            labels: data.candidateLabels,
            datasets: [{
                label: 'Votes',
                data: data.voteData,
                backgroundColor: data.backgroundColors,
                borderColor: data.borderColors,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Vote Distribution by Candidate'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Update live results panel
function updateLiveResults(data) {
    const liveResults = document.getElementById('liveResults');
    
    if (!liveResults) {
        console.error('Live results container not found');
        return;
    }
    
    let html = '';
    
    data.candidateResults.forEach((candidate, index) => {
        const percentage = data.totalVotes > 0 ? ((candidate.votes / data.totalVotes) * 100).toFixed(1) : 0;
        const isLeading = candidate.name === data.leadingCandidate;
        
        html += `
            <div class="candidate-result mb-3 p-3 border rounded ${isLeading ? 'leading-candidate bg-light' : ''}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="${isLeading ? 'text-success' : ''}">${candidate.name}</strong>
                    <span class="badge ${isLeading ? 'bg-success' : 'bg-secondary'}">${candidate.votes} votes</span>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar ${getProgressBarColor(index)}" 
                         role="progressbar" 
                         style="width: ${percentage}%"
                         aria-valuenow="${percentage}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ${percentage}%
                    </div>
                </div>
            </div>
        `;
    });
    
    liveResults.innerHTML = html;
}

// Update results table
function updateResultsTable(data) {
    const tableBody = document.getElementById('resultsTable');
    
    if (!tableBody) {
        console.error('Results table body not found');
        return;
    }
    
    let html = '';
    
    data.candidateResults.forEach((candidate, index) => {
        const percentage = data.totalVotes > 0 ? ((candidate.votes / data.totalVotes) * 100).toFixed(2) : 0;
        const isLeading = candidate.name === data.leadingCandidate;
        
        html += `
            <tr ${isLeading ? 'class="table-success"' : ''}>
                <td>
                    <strong>${candidate.name}</strong>
                    ${isLeading ? '<i class="fas fa-crown text-warning ms-2"></i>' : ''}
                </td>
                <td>${candidate.votes}</td>
                <td>${percentage}%</td>
                <td>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar ${getProgressBarColor(index)}" 
                             style="width: ${percentage}%">
                        </div>
                    </div>
                </td>
                <td>
                    ${isLeading ? '<span class="badge bg-success">Leading</span>' : ''}
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

// Update winner announcement
function updateWinnerAnnouncement(data) {
    const announcement = document.getElementById('winnerAnnouncement');
    
    if (!announcement) {
        console.error('Winner announcement container not found');
        return;
    }
    
    if(data.totalVotes === 0) {
        announcement.innerHTML = '<i class="fas fa-info-circle"></i> No votes cast yet.';
    } else {
        announcement.innerHTML = `
            <i class="fas fa-trophy text-warning"></i> 
            <strong>${data.leadingCandidate}</strong> is leading with ${data.leadingVotes} votes 
            (${data.leadingPercentage}% of total votes)
        `;
    }
}

// Helper function for progress bar colors
function getProgressBarColor(index) {
    const colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning'];
    return colors[index % colors.length];
}

// Show error message
function showError(message) {
    const liveResults = document.getElementById('liveResults');
    if (liveResults) {
        liveResults.innerHTML = `<div class="alert alert-danger">${message}</div>`;
    }
    
    // Also show alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.querySelector('.row.mb-4'));
    }
}

// Export results to Excel
function exportResults() {
    console.log('Exporting results...');
    
    const link = document.createElement('a');
    link.href = 'api/export_results.php';
    link.download = `election_results_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
    alertDiv.innerHTML = `
        Results export started!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.querySelector('.row.mb-4'));
    }
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Toggle auto-refresh
function toggleAutoRefresh() {
    const autoRefresh = document.getElementById('autoRefresh');
    
    if(autoRefresh.checked) {
        autoRefreshInterval = setInterval(updateResults, 10000); // 10 seconds
        console.log('Auto-refresh enabled');
    } else {
        clearInterval(autoRefreshInterval);
        console.log('Auto-refresh disabled');
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Results page initialized');
    
    // Load results immediately
    updateResults();
    
    // Set up auto-refresh
    autoRefreshInterval = setInterval(updateResults, 10000);
    
    // Event listeners
    document.getElementById('refreshResults').addEventListener('click', function() {
        console.log('Manual refresh triggered');
        updateResults();
    });
    
    document.getElementById('exportResults').addEventListener('click', exportResults);
    document.getElementById('autoRefresh').addEventListener('change', toggleAutoRefresh);
});