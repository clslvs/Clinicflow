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
                <div class="cf-cookie-icon-wrapper" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                        <path d="M8.5 8.5v.01"></path>
                        <path d="M16 15.5v.01"></path>
                        <path d="M12 12v.01"></path>
                        <path d="M11 17v.01"></path>
                        <path d="M7 14v.01"></path>
                    </svg>
                </div>
                <div class="cf-cookie-body">
                    <p>
                        We use essential cookies to securely maintain your session and ensure smooth appointment scheduling. 
                        By continuing to use ClinicFlow, you agree to our 
                        <a href="${legalPath}#privacy" target="_blank" rel="noopener noreferrer">Privacy Policy</a> and 
                        <a href="${legalPath}#cookies" target="_blank" rel="noopener noreferrer">Cookie Policy</a>.
                    </p>
                </div>
                <div class="cf-cookie-actions">
                    <button type="button" id="cf-cookie-accept-btn" class="cf-cookie-btn">Got it</button>
                </div>
            </div>
        `;

        const style = document.createElement('style');
        style.textContent = `
            #clinicflow-cookie-banner {
                position: fixed;
                bottom: 24px;
                left: 50%;
                transform: translateX(-50%);
                width: min(calc(100% - 32px), 800px);
                background: color-mix(in oklab, var(--color-paper, #fbfdfc) 94%, transparent);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                color: var(--color-ink, oklch(21% 0.025 170));
                padding: 14px 20px;
                border-radius: var(--radius-card, 14px);
                border: 1px solid var(--color-rule, oklch(88% 0.018 160));
                box-shadow: 0 4px 6px -1px oklch(21% 0.025 170 / 0.05), 0 20px 36px -8px oklch(21% 0.025 170 / 0.16);
                z-index: 99999;
                font-family: var(--font-body, 'Albert Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
                animation: cfCookieSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                box-sizing: border-box;
            }

            #clinicflow-cookie-banner *, #clinicflow-cookie-banner *::before, #clinicflow-cookie-banner *::after {
                box-sizing: border-box;
            }

            .cf-cookie-content {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .cf-cookie-icon-wrapper {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                background: var(--color-accent-soft, oklch(90% 0.06 160));
                color: var(--color-accent-deep, oklch(42% 0.11 162));
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                border: 1px solid color-mix(in oklab, var(--color-accent, oklch(50% 0.13 160)) 22%, transparent);
            }

            .cf-cookie-icon-wrapper svg {
                display: block;
            }

            .cf-cookie-body {
                flex: 1;
                min-width: 0;
            }

            .cf-cookie-body p {
                margin: 0;
                font-size: 0.875rem;
                line-height: 1.5;
                color: var(--color-ink-2, oklch(31% 0.02 170));
                letter-spacing: -0.01em;
            }

            .cf-cookie-body a {
                color: var(--color-accent-deep, oklch(42% 0.11 162));
                text-decoration: underline;
                text-underline-offset: 3px;
                text-decoration-thickness: 1px;
                font-weight: 600;
                transition: color 0.15s ease;
            }

            .cf-cookie-body a:hover {
                color: var(--color-accent, oklch(50% 0.13 160));
            }

            .cf-cookie-body a:focus-visible {
                outline: 2px solid var(--color-focus, oklch(45% 0.14 160));
                outline-offset: 2px;
                border-radius: 2px;
            }

            .cf-cookie-actions {
                display: flex;
                align-items: center;
                flex-shrink: 0;
            }

            .cf-cookie-btn {
                background: linear-gradient(180deg, var(--color-accent, oklch(50% 0.13 160)), var(--color-accent-deep, oklch(42% 0.11 162)));
                color: var(--color-accent-ink, #ffffff);
                border: none;
                padding: 8px 20px;
                border-radius: var(--radius-pill, 999px);
                font-family: inherit;
                font-size: 0.875rem;
                font-weight: 600;
                cursor: pointer;
                box-shadow: inset 0 1px 0 var(--glint, rgba(255, 255, 255, 0.4)), 0 0 0 1px var(--color-accent-deep, oklch(42% 0.11 162)), 0 2px 0 var(--color-accent-deep, oklch(42% 0.11 162));
                transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1), filter 0.15s ease, box-shadow 0.15s ease;
                white-space: nowrap;
            }

            .cf-cookie-btn:hover {
                filter: brightness(1.06);
                transform: translateY(-1px);
                box-shadow: inset 0 1px 0 var(--glint, rgba(255, 255, 255, 0.4)), 0 0 0 1px var(--color-accent-deep, oklch(42% 0.11 162)), 0 3px 0 var(--color-accent-deep, oklch(42% 0.11 162));
            }

            .cf-cookie-btn:active {
                transform: translateY(1px);
                box-shadow: inset 0 1px 0 var(--glint, rgba(255, 255, 255, 0.4)), 0 0 0 1px var(--color-accent-deep, oklch(42% 0.11 162)), 0 1px 0 var(--color-accent-deep, oklch(42% 0.11 162));
            }

            .cf-cookie-btn:focus-visible {
                outline: 2px solid var(--color-focus, oklch(45% 0.14 160));
                outline-offset: 2px;
            }

            .cf-cookie-closing {
                opacity: 0 !important;
                transform: translate(-50%, 12px) !important;
                transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1), transform 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
                pointer-events: none;
            }

            @keyframes cfCookieSlideUp {
                from {
                    opacity: 0;
                    transform: translate(-50%, 20px);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, 0);
                }
            }

            @media (max-width: 640px) {
                #clinicflow-cookie-banner {
                    bottom: 16px;
                    padding: 14px 16px;
                }
                .cf-cookie-content {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 12px;
                }
                .cf-cookie-actions {
                    justify-content: flex-end;
                }
                .cf-cookie-btn {
                    width: 100%;
                    text-align: center;
                    padding: 10px 16px;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                #clinicflow-cookie-banner {
                    animation: none !important;
                    transition: none !important;
                }
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(banner);

        document.getElementById('cf-cookie-accept-btn').addEventListener('click', function() {
            localStorage.setItem(CONSENT_KEY, 'accepted');
            banner.classList.add('cf-cookie-closing');
            setTimeout(() => {
                banner.remove();
                style.remove();
            }, 260);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieBanner);
    } else {
        initCookieBanner();
    }
})();
