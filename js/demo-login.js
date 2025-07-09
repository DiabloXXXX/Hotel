/**
 * Demo Login Shortcuts
 * Hotel Senang Hati - Quick Admin Access
 */

// Demo login modal HTML
const demoLoginModal = `
<div class="modal fade" id="demoLoginModal" tabindex="-1" aria-labelledby="demoLoginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="demoLoginModalLabel">
          <i class="fas fa-user-shield me-2"></i>Demo Admin Login
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info" role="alert">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Demo Accounts:</strong><br>
          • Username: <code>demo</code> | Password: <code>password123</code><br>
          • Username: <code>admin</code> | Password: <code>password123</code>
        </div>
        
        <form id="demoLoginForm">
          <div class="mb-3">
            <label for="demoUsername" class="form-label">Username</label>
            <input type="text" class="form-control" id="demoUsername" value="demo" required>
          </div>
          <div class="mb-3">
            <label for="demoPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="demoPassword" value="password123" required>
          </div>
          <div id="demoLoginError" class="alert alert-danger d-none" role="alert"></div>
          <div id="demoLoginLoading" class="d-none text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="demoLogin()">
          <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
      </div>
    </div>
  </div>
</div>
`;

// Quick login buttons HTML
const quickLoginButtons = `
<div id="quickLoginButtons" class="position-fixed" style="top: 20px; right: 20px; z-index: 1050; display: none;">
  <div class="btn-group-vertical" role="group">
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="quickLogin('demo')" title="Quick Login as Demo Admin">
      <i class="fas fa-user me-1"></i>Demo
    </button>
    <button type="button" class="btn btn-sm btn-outline-success" onclick="quickLogin('admin')" title="Quick Login as Admin">
      <i class="fas fa-crown me-1"></i>Admin
    </button>
  </div>
</div>
`;

// Initialize demo login functionality
function initDemoLogin() {
  // Add modal to body if it doesn't exist
  if (!document.getElementById('demoLoginModal')) {
    document.body.insertAdjacentHTML('beforeend', demoLoginModal);
  }
  
  // Add quick login buttons if not on staff pages
  if (!window.location.pathname.includes('staff-') && !document.getElementById('quickLoginButtons')) {
    document.body.insertAdjacentHTML('beforeend', quickLoginButtons);
    
    // Show quick login buttons on hover over top-right corner
    let hoverTimeout;
    document.addEventListener('mousemove', function(e) {
      const buttons = document.getElementById('quickLoginButtons');
      if (e.clientX > window.innerWidth - 100 && e.clientY < 100) {
        clearTimeout(hoverTimeout);
        buttons.style.display = 'block';
      } else {
        hoverTimeout = setTimeout(() => {
          buttons.style.display = 'none';
        }, 2000);
      }
    });
  }
  
  // Keyboard shortcut: Ctrl+U for demo login
  document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'u') {
      e.preventDefault();
      showDemoLoginModal();
    }
    
    // Alt+Shift+D for quick demo login
    if (e.altKey && e.shiftKey && e.key === 'D') {
      e.preventDefault();
      quickLogin('demo');
    }
    
    // Alt+Shift+A for quick admin login
    if (e.altKey && e.shiftKey && e.key === 'A') {
      e.preventDefault();
      quickLogin('admin');
    }
  });
}

// Show demo login modal
function showDemoLoginModal() {
  const modal = new bootstrap.Modal(document.getElementById('demoLoginModal'));
  modal.show();
  
  // Focus on username field
  setTimeout(() => {
    document.getElementById('demoUsername').focus();
  }, 500);
}

// Demo login function
async function demoLogin() {
  const username = document.getElementById('demoUsername').value;
  const password = document.getElementById('demoPassword').value;
  const errorDiv = document.getElementById('demoLoginError');
  const loadingDiv = document.getElementById('demoLoginLoading');
  
  // Clear previous errors
  errorDiv.classList.add('d-none');
  loadingDiv.classList.remove('d-none');
  
  try {
    const response = await fetch('/api/auth/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        username: username,
        password: password
      })
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      // Store session info
      localStorage.setItem('staff_session', JSON.stringify(data.data));
      
      // Show success message
      showNotification('Login successful! Redirecting...', 'success');
      
      // Close modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('demoLoginModal'));
      modal.hide();
      
      // Redirect to dashboard
      setTimeout(() => {
        window.location.href = '/staff-dashboard.html';
      }, 1000);
      
    } else {
      errorDiv.textContent = data.message || 'Login failed';
      errorDiv.classList.remove('d-none');
    }
  } catch (error) {
    console.error('Login error:', error);
    errorDiv.textContent = 'Connection error. Please try again.';
    errorDiv.classList.remove('d-none');
  } finally {
    loadingDiv.classList.add('d-none');
  }
}

// Quick login function (bypass modal)
async function quickLogin(username) {
  showNotification('Quick login in progress...', 'info');
  
  try {
    const response = await fetch('/api/auth/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        username: username,
        password: 'password123'
      })
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      // Store session info
      localStorage.setItem('staff_session', JSON.stringify(data.data));
      
      // Show success message
      showNotification(`Quick login as ${username} successful!`, 'success');
      
      // Redirect to dashboard
      setTimeout(() => {
        window.location.href = '/staff-dashboard.html';
      }, 1000);
      
    } else {
      showNotification('Quick login failed: ' + (data.message || 'Unknown error'), 'error');
    }
  } catch (error) {
    console.error('Quick login error:', error);
    showNotification('Quick login failed: Connection error', 'error');
  }
}

// Enhanced notification system
function showNotification(message, type = 'info', duration = 3000) {
  // Remove existing notifications
  const existing = document.querySelector('.demo-notification');
  if (existing) {
    existing.remove();
  }
  
  const typeColors = {
    success: 'bg-success',
    error: 'bg-danger',
    warning: 'bg-warning',
    info: 'bg-info'
  };
  
  const typeIcons = {
    success: 'fas fa-check-circle',
    error: 'fas fa-exclamation-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle'
  };
  
  const notification = document.createElement('div');
  notification.className = `demo-notification position-fixed alert ${typeColors[type]} text-white d-flex align-items-center`;
  notification.style.cssText = `
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    min-width: 300px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
  `;
  
  notification.innerHTML = `
    <i class="${typeIcons[type]} me-2"></i>
    <span>${message}</span>
  `;
  
  document.body.appendChild(notification);
  
  // Auto remove
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, duration);
}

// Add help tooltip
function addDemoHelp() {
  if (!document.getElementById('demoHelp')) {
    const helpButton = document.createElement('div');
    helpButton.id = 'demoHelp';
    helpButton.className = 'position-fixed';
    helpButton.style.cssText = `
      bottom: 20px;
      right: 20px;
      z-index: 1040;
    `;
    
    helpButton.innerHTML = `
      <button type="button" class="btn btn-info btn-sm rounded-circle" 
              data-bs-toggle="tooltip" data-bs-placement="left" 
              title="Demo Help: Ctrl+U for login, Alt+Shift+D for quick demo login"
              onclick="showDemoHelp()">
        <i class="fas fa-question"></i>
      </button>
    `;
    
    document.body.appendChild(helpButton);
    
    // Initialize tooltip
    if (typeof bootstrap !== 'undefined') {
      new bootstrap.Tooltip(helpButton.querySelector('[data-bs-toggle="tooltip"]'));
    }
  }
}

// Show demo help
function showDemoHelp() {
  const helpText = `
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-keyboard me-2"></i>Demo Shortcuts</h6>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li><kbd>Ctrl</kbd> + <kbd>U</kbd> - Open demo login modal</li>
          <li><kbd>Alt</kbd> + <kbd>Shift</kbd> + <kbd>D</kbd> - Quick login as demo</li>
          <li><kbd>Alt</kbd> + <kbd>Shift</kbd> + <kbd>A</kbd> - Quick login as admin</li>
          <li><strong>Hover top-right corner</strong> - Show quick login buttons</li>
        </ul>
        
        <hr>
        <h6>Demo Accounts:</h6>
        <ul class="list-unstyled mb-0">
          <li><strong>Username:</strong> demo | <strong>Password:</strong> password123</li>
          <li><strong>Username:</strong> admin | <strong>Password:</strong> password123</li>
        </ul>
      </div>
    </div>
  `;
  
  showNotification(helpText, 'info', 8000);
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    initDemoLogin();
    addDemoHelp();
  });
} else {
  initDemoLogin();
  addDemoHelp();
}

// For manual initialization
window.initDemoLogin = initDemoLogin;
