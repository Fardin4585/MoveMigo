// Debug: Check if elements exist
console.log('Signin.js loaded');

document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM loaded');
  
  // Role selection functionality
  const roleOptions = document.querySelectorAll('.role-option');
  let selectedRole = 'tenant'; // Default selection
  const selectedUserTypeInput = document.getElementById('selectedUserType');

  roleOptions.forEach(option => {
    option.addEventListener('click', () => {
      // Remove active class from all options
      roleOptions.forEach(opt => opt.classList.remove('active'));
      
      // Add active class to clicked option
      option.classList.add('active');
      
      // Update selected role
      selectedRole = option.dataset.role;
      
      // Update hidden input
      if (selectedUserTypeInput) {
        selectedUserTypeInput.value = selectedRole;
      }
      
      console.log('Selected role:', selectedRole);
    });
  });

  // Set default active role
  const defaultRole = document.querySelector('[data-role="tenant"]');
  if (defaultRole) {
    defaultRole.classList.add('active');
    if (selectedUserTypeInput) {
      selectedUserTypeInput.value = 'tenant';
    }
  }

  // Password toggle functionality
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', () => {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      // Change eye icon
      togglePassword.textContent = type === 'password' ? '👁' : '🙈';
    });
  }

  // Form validation
  const form = document.getElementById('signinForm');
  const emailInput = document.getElementById('email');
  const emailError = document.getElementById('emailError');
  const passwordError = document.getElementById('passwordError');

  // Email validation
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Real-time validation
  if (emailInput && emailError) {
    emailInput.addEventListener('input', () => {
      const email = emailInput.value.trim();
      
      if (email === '') {
        emailError.textContent = '';
      } else if (!validateEmail(email)) {
        emailError.textContent = 'Please enter a valid email address';
      } else {
        emailError.textContent = '';
      }
    });
  }

  if (passwordInput && passwordError) {
    passwordInput.addEventListener('input', () => {
      const password = passwordInput.value;
      
      if (password === '') {
        passwordError.textContent = '';
      } else if (password.length < 6) {
        passwordError.textContent = 'Password must be at least 6 characters';
      } else {
        passwordError.textContent = '';
      }
    });
  }

  // Form submission
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const email = emailInput.value.trim();
      const password = passwordInput.value;
      
      // Clear previous errors
      if (emailError) emailError.textContent = '';
      if (passwordError) passwordError.textContent = '';
      
      let isValid = true;
      
      // Validate email
      if (email === '') {
        if (emailError) emailError.textContent = 'Email is required';
        isValid = false;
      } else if (!validateEmail(email)) {
        if (emailError) emailError.textContent = 'Please enter a valid email address';
        isValid = false;
      }
      
      // Validate password
      if (password === '') {
        if (passwordError) passwordError.textContent = 'Password is required';
        isValid = false;
      } else if (password.length < 6) {
        if (passwordError) passwordError.textContent = 'Password must be at least 6 characters';
        isValid = false;
      }
      
      // Validate role selection
      if (!selectedRole) {
        alert('Please select a role (Tenant or Homeowner)');
        isValid = false;
      }
      
      if (isValid) {
        // Update hidden input with selected role
        if (selectedUserTypeInput) {
          selectedUserTypeInput.value = selectedRole;
        }
        
        // Show loading state
        const signinBtn = document.querySelector('.signin-btn');
        if (signinBtn) {
          const originalText = signinBtn.textContent;
          signinBtn.textContent = 'Signing In...';
          signinBtn.disabled = true;
          
          // Submit the form
          form.submit();
        }
      }
    });
  }

  // Link functionality
  const signupLinks = document.querySelectorAll('.signup-link');
  signupLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      // Allow the links to work normally - no preventDefault
      console.log('Signup link clicked:', link.href);
    });
  });

  const forgotPasswordLink = document.querySelector('.forgot-password');
  if (forgotPasswordLink) {
    forgotPasswordLink.addEventListener('click', (e) => {
      e.preventDefault();
      alert('Password reset functionality coming soon...');
    });
  }
}); 