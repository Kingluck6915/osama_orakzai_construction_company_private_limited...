(() => {
    const header = document.querySelector('[data-header]');
    const menuButton = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.primary-nav');

    const updateHeader = () => {
        if (header) header.classList.toggle('is-scrolled', window.scrollY > 28);
    };
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    if (menuButton && navigation) {
        menuButton.addEventListener('click', () => {
            const expanded = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!expanded));
            menuButton.setAttribute('aria-label', expanded ? 'Open navigation' : 'Close navigation');
            navigation.classList.toggle('is-open', !expanded);
        });
        navigation.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                menuButton.setAttribute('aria-expanded', 'false');
                menuButton.setAttribute('aria-label', 'Open navigation');
                navigation.classList.remove('is-open');
            }
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const targets = document.querySelectorAll('.intro-copy, .principles > div, .service-row, .project-card, .contact-heading');
        const observer = new IntersectionObserver((entries, instance) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    instance.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        targets.forEach((target) => {
            target.classList.add('reveal');
            observer.observe(target);
        });
    }
})();
