// Debug: Check if elements exist
console.log('Login.js loaded');

document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM loaded');
  
  const homeownerBtn = document.getElementById('homeowner-btn');
  const tenantBtn = document.getElementById('tenant-btn');
  const backBtn = document.getElementById('backToMain');
  const signupBtn = document.getElementById('signup-btn');
  
  console.log('Homeowner button:', homeownerBtn);
  console.log('Tenant button:', tenantBtn);
  
  if (homeownerBtn) {
    homeownerBtn.addEventListener('click', function() {
      console.log('Homeowner button clicked');
      showLoginForm('homeowner');
    });
  }
  
  if (tenantBtn) {
    tenantBtn.addEventListener('click', function() {
      console.log('Tenant button clicked');
      showLoginForm('tenant');
    });
  }
  
  if (backBtn) {
    backBtn.addEventListener('click', function() {
      console.log('Back button clicked');
      hideLoginForm();
    });
  }
  
  if (signupBtn) {
    signupBtn.addEventListener('click', function() {
      console.log('Signup button clicked');
      const type = document.querySelector('input[name="signup-type"]:checked').value;
      if (type === 'tenant') {
        window.location.href = 'signup-tenant.php';
      } else {
        window.location.href = 'signup-homeowner.php';
      }
    });
  }
});



function showLoginForm(userType) {
  console.log('Showing login form for:', userType);
  const loginForm = document.getElementById('loginForm');
  const loginUserType = document.getElementById('loginUserType');
  const signinOptions = document.querySelector('.signin-options');
  const signupBox = document.querySelector('.signup-box');
  
  if (loginForm) loginForm.style.display = 'block';
  if (loginUserType) loginUserType.value = userType;
  if (signinOptions) signinOptions.style.display = 'none';
  if (signupBox) signupBox.style.display = 'none';
}

function hideLoginForm() {
  console.log('Hiding login form');
  const loginForm = document.getElementById('loginForm');
  const signinOptions = document.querySelector('.signin-options');
  const signupBox = document.querySelector('.signup-box');
  
  if (loginForm) loginForm.style.display = 'none';
  if (signinOptions) signinOptions.style.display = 'block';
  if (signupBox) signupBox.style.display = 'block';
} 