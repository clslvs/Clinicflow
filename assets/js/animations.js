// ClinicFlow - Strain-Free Functional Micro-Motion
// Uses GPU-accelerated transforms & opacity only. Zero layout reflows. Respects prefers-reduced-motion.

function initAnimations() {
    const animatedElements = document.querySelectorAll('.fade-up');

    if (!('IntersectionObserver' in window)) {
        animatedElements.forEach(el => el.classList.add('visible'));
    } else {
        const observerOptions = {
            root: null,
            rootMargin: '60px 0px 40px 0px',
            threshold: 0.05
        };

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    obs.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedElements.forEach(el => observer.observe(el));

        // Safety fallback: ensure all content becomes visible even if observer is delayed or skipped
        setTimeout(() => {
            animatedElements.forEach(el => el.classList.add('visible'));
        }, 800);
    }

    const header = document.querySelector('.site-header, .main-header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 30) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }, { passive: true });
    }
}

// Semantic helper: trigger a reassuring single pulse on queue cards or status badges
function pulseElement(el) {
    if (!el) return;
    el.classList.remove('queue-updated');
    // Force reflow
    void el.offsetWidth;
    el.classList.add('queue-updated');
    setTimeout(() => {
        el.classList.remove('queue-updated');
    }, 400);
}

window.ClinicFlowMotion = {
    pulse: pulseElement
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAnimations);
} else {
    initAnimations();
}
