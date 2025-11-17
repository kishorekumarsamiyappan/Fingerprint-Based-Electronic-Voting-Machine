// admin_management.js
let currentAdminToDelete = null;

// Load all admins
function loadAdmins() {
    showLoadingState();
    
    fetch('api/get_admins.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Admins data received:', data);
            if(data.status === 'success') {
                displayAdmins(data.admins);
            } else {
                showError('Failed to load admins: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error loading admins:', error);
            showError('Error loading admins: ' + error.message);
        });
}

// Display admins in table
function displayAdmins(admins) {
    const tableBody = document.getElementById('adminsTable');
    const loadingDiv = document.getElementById('loadingAdmins');
    
    if(!admins || admins.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <p>No admin accounts found.</p>
                </td>
            </tr>
        `;
        loadingDiv.style.display = 'none';
        return;
    }
    
    loadingDiv.style.display = 'none';
    
    let html = '';
    admins.forEach((admin, index) => {
        const createdDate = admin.created_at ? new Date(admin.created_at).toLocaleDateString() : 'N/A';
        
        html += `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${admin.username}</strong></td>
                <td>${admin.place}</td>
                <td>${createdDate}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger delete-admin" 
                            data-id="${admin.id}" 
                            data-username="${admin.username}"
                            data-place="${admin.place}">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    
    // Add event listeners to delete buttons
    document.querySelectorAll('.delete-admin').forEach(button => {
        button.addEventListener('click', function() {
            const adminId = this.getAttribute('data-id');
            const username = this.getAttribute('data-username');
            const place = this.getAttribute('data-place');
            showDeleteAdminModal(adminId, username, place);
        });
    });
}

// Show loading state
function showLoadingState() {
    document.getElementById('loadingAdmins').style.display = 'block';
    document.getElementById('adminsTable').innerHTML = '';
}

// Show delete confirmation modal
function showDeleteAdminModal(adminId, username, place) {
    currentAdminToDelete = adminId;
    document.getElementById('deleteAdminUsername').textContent = username;
    document.getElementById('deleteAdminPlace').textContent = place;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
    deleteModal.show();
}

// Delete admin
function deleteAdmin() {
    if(!currentAdminToDelete) return;
    
    fetch(`api/delete_admin.php?id=${currentAdminToDelete}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // Reload admins
            loadAdmins();
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('deleteAdminModal')).hide();
            // Show success message
            showAlert('Admin deleted successfully!', 'success');
        } else {
            showAlert('Error deleting admin: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error deleting admin', 'danger');
    });
}

// Add new admin
function addAdmin(formData) {
    fetch('api/add_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // Reset form
            document.getElementById('addAdminForm').reset();
            // Reload admins
            loadAdmins();
            // Show success message
            showAlert('Admin added successfully!', 'success');
        } else {
            showAlert('Error adding admin: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error adding admin', 'danger');
    });
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
    document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.card'));
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Show error message
function showError(message) {
    const tableBody = document.getElementById('adminsTable');
    const loadingDiv = document.getElementById('loadingAdmins');
    
    loadingDiv.style.display = 'none';
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center text-danger py-4">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <p>${message}</p>
            </td>
        </tr>
    `;
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data
    loadAdmins();
    
    // Event listeners
    document.getElementById('refreshAdmins').addEventListener('click', loadAdmins);
    document.getElementById('confirmAdminDelete').addEventListener('click', deleteAdmin);
    
    // Add admin form submission
    document.getElementById('addAdminForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            username: document.getElementById('newUsername').value,
            password: document.getElementById('newPassword').value,
            place: document.getElementById('newPlace').value
        };
        
        addAdmin(formData);
    });
});