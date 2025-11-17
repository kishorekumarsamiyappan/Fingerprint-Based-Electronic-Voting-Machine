// Function to update system status
function updateSystemStatus() {
    fetch('api/check_status.php')
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                // Update WiFi status
                const wifiBadge = document.getElementById('wifiStatus');
                wifiBadge.textContent = data.wifi_status;
                wifiBadge.className = 'badge ' + (data.wifi_status === 'Connected' ? 'bg-success' : 'bg-danger') + ' status-badge';
                
                // Update sensor status
                const sensorBadge = document.getElementById('sensorStatus');
                sensorBadge.textContent = data.sensor_status;
                sensorBadge.className = 'badge ' + (data.sensor_status === 'Connected' ? 'bg-success' : 'bg-danger') + ' status-badge';
                
                // Update device status
                const deviceBadge = document.getElementById('deviceStatus');
                deviceBadge.textContent = data.esp32_active ? 'Online' : 'Offline';
                deviceBadge.className = 'badge ' + (data.esp32_active ? 'bg-success' : 'bg-danger') + ' status-badge';
                
                // Update quick stats
                document.getElementById('totalVoters').textContent = data.total_voters;
                document.getElementById('votesCast').textContent = data.votes_cast;
                
                // Calculate and display voter turnout
                const turnout = data.total_voters > 0 ? 
                    Math.round((data.votes_cast / data.total_voters) * 100) : 0;
                document.getElementById('voterTurnout').textContent = turnout + '%';
                
                // Show/hide warning based on ESP32 activity
                if(!data.esp32_active) {
                    showWarning('ESP32 device appears to be offline. Check power and network connection.');
                } else {
                    hideWarning();
                }
            } else {
                showError('Failed to fetch system status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Network error fetching status');
        });
}

// Function to show warning message
function showWarning(message) {
    let warningDiv = document.getElementById('connectionWarning');
    if(!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'connectionWarning';
        warningDiv.className = 'alert alert-warning alert-dismissible fade show';
        warningDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.container').insertBefore(warningDiv, document.querySelector('.row.mb-4'));
    } else {
        warningDiv.style.display = 'block';
        warningDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
    }
}

// Function to hide warning
function hideWarning() {
    const warningDiv = document.getElementById('connectionWarning');
    if(warningDiv) {
        warningDiv.style.display = 'none';
    }
}

// Function to show error
function showError(message) {
    const wifiBadge = document.getElementById('wifiStatus');
    const sensorBadge = document.getElementById('sensorStatus');
    const deviceBadge = document.getElementById('deviceStatus');
    
    wifiBadge.textContent = 'Error';
    wifiBadge.className = 'badge bg-danger status-badge';
    
    sensorBadge.textContent = 'Error';
    sensorBadge.className = 'badge bg-danger status-badge';
    
    deviceBadge.textContent = 'Error';
    deviceBadge.className = 'badge bg-danger status-badge';
    
    console.error('Dashboard Error:', message);
}

// Initialize dashboard when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Update status immediately
    updateSystemStatus();
    
    // Update status every 10 seconds
    setInterval(updateSystemStatus, 10000);
    
    // Add click handlers for manual refresh
    document.getElementById('wifiStatus').addEventListener('click', updateSystemStatus);
    document.getElementById('sensorStatus').addEventListener('click', updateSystemStatus);
    document.getElementById('deviceStatus').addEventListener('click', updateSystemStatus);
});