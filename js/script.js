// Login functionality
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const messageDiv = document.getElementById('loginMessage');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
    
    fetch('api/login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1000);
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = `<div class="alert alert-danger">Login failed: ${error}</div>`;
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Login';
    });
});