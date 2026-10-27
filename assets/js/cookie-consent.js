// ClinicFlow - Vanilla Privacy & Cookie Consent Banner
(function() {
    const CONSENT_KEY = 'clinicflow_cookie_consent';

    function initCookieBanner() {
        if (localStorage.getItem(CONSENT_KEY)) return;

        // Determine correct relative path to legal.html depending on current directory
        const isSubdir = window.location.pathname.includes('/patient/') || 
                         window.location.pathname.includes('/staff/') || 
                         window.location.pathname.includes('/doctor/') || 
                         window.location.pathname.includes('/admin/');
        const legalPath = isSubdir ? '../legal.html' : 'legal.html';

        const banner = document.createElement('div');
        banner.id = 'clinicflow-cookie-banner';
        banner.setAttribute('role', 'region');
        banner.setAttribute('aria-label', 'Cookie and Privacy Consent');

        banner.innerHTML = `
            <div class="cf-cookie-content">
                <span class="cf-cookie-icon">🍪</span>
                <p>
                    We use essential cookies to securely maintain your session and ensure smooth appointment scheduling. 
                    By continuing to use ClinicFlow, you agree to our 
                    <a href="${legalPath}#privacy" target="_blank" rel="noopener noreferrer">Privacy Policy</a> and 
                    <a href="${legalPath}#cookies" target="_blank" rel="noopener noreferrer">Cookie Policy</a>.
                </p>
                <div class="cf-cookie-actions">
                    <button type="button" id="cf-cookie-accept-btn" class="cf-cookie-btn">Got it</button>
                </div>
            </div>
        `;

        const style = document.createElement('style');
        style.textContent = `
            #clinicflow-cookie-banner {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                width: calc(100% - 40px);
                max-width: 820px;
                background: rgba(12, 124, 119, 0.95);
                backdrop-filter: blur(10px);
                color: #ffffff;
                padding: 16px 22px;
                border-radius: 14px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
                z-index: 99999;
                font-family: 'Quicksand', -apple-system, sans-serif;
                animation: cfSlideUp 0.35s ease-out;
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
            .cf-cookie-content {
                display: flex;
                align-items: center;
                gap: 16px;
                flex-wrap: wrap;
            }
            .cf-cookie-icon {
                font-size: 1.6rem;
                line-height: 1;
            }
            .cf-cookie-content p {
                margin: 0;
                flex: 1;
                font-size: 0.92rem;
                line-height: 1.5;
                min-width: 260px;
                color: rgba(255, 255, 255, 0.95);
            }
            .cf-cookie-content a {
                color: #FFE082;
                text-decoration: underline;
                font-weight: 600;
            }
            .cf-cookie-content a:hover {
                color: #FFFFFF;
            }
            .cf-cookie-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .cf-cookie-btn {
                background: #FFFFFF;
                color: #0C7C77;
                border: none;
                padding: 9px 20px;
                border-radius: 999px;
                font-size: 0.9rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s ease;
                white-space: nowrap;
            }
            .cf-cookie-btn:hover {
                background: #FFE082;
                transform: translateY(-1px);
            }
            @keyframes cfSlideUp {
                from { opacity: 0; transform: translate(-50%, 25px); }
                to { opacity: 1; transform: translate(-50%, 0); }
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(banner);

        document.getElementById('cf-cookie-accept-btn').addEventListener('click', function() {
            localStorage.setItem(CONSENT_KEY, 'accepted');
            banner.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            banner.style.opacity = '0';
            banner.style.transform = 'translate(-50%, 20px)';
            setTimeout(() => banner.remove(), 300);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieBanner);
    } else {
        initCookieBanner();
    }
})();
