/**
 * Pharmacy Inventory System - Login Page Interactivity
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            // Toggle icon SVG
            if (isPassword) {
                // Eye Off (hide) icon
                toggleBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                `;
                toggleBtn.setAttribute('title', 'Hide password');
                toggleBtn.setAttribute('aria-label', 'Hide password');
            } else {
                // Eye (show) icon
                toggleBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
                toggleBtn.setAttribute('title', 'Show password');
                toggleBtn.setAttribute('aria-label', 'Show password');
            }
        });
    }

    // 2. Demo Credentials Auto-Fill
    const demoPills = document.querySelectorAll('.demo-pill');
    const usernameInput = document.getElementById('username');

    demoPills.forEach(pill => {
        pill.addEventListener('click', () => {
            const user = pill.getAttribute('data-user');
            const pass = pill.getAttribute('data-pass');

            if (usernameInput && passwordInput) {
                usernameInput.value = user;
                passwordInput.value = pass;

                // Visual highlight animation
                usernameInput.style.backgroundColor = '#f0fdf4';
                passwordInput.style.backgroundColor = '#f0fdf4';
                setTimeout(() => {
                    usernameInput.style.backgroundColor = '';
                    passwordInput.style.backgroundColor = '';
                }, 400);

                // Focus password or submit
                passwordInput.focus();
            }
        });
    });

    // 3. Form Submission State
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', (e) => {
            const username = usernameInput.value.trim();
            const password = passwordInput.value;

            if (!username || !password) {
                e.preventDefault();
                alert('Please enter both username and password.');
                return;
            }

            // Disable submit button to prevent double submit
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.85';
            submitBtn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                    <path d="M12 2a10 10 0 0 1 10 10"></path>
                </svg>
                Authenticating...
            `;
        });
    }
});

// Inline keyframe for spin animation
const styleSheet = document.createElement("style");
styleSheet.innerText = `
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
`;
document.head.appendChild(styleSheet);

