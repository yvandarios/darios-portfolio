
/* =========================================================
   DARIOS PORTFOLIO
   Main Public JavaScript
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    /* --------------------------------
       1. MOBILE NAVIGATION
    -------------------------------- */

    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');

    if (menuToggle && navMenu) {

        menuToggle.addEventListener('click', function () {
            const isOpen = navMenu.classList.toggle('active');

            menuToggle.setAttribute('aria-expanded', String(isOpen));

            const icon = menuToggle.querySelector('i');

            if (icon) {
                icon.classList.toggle('fa-bars', !isOpen);
                icon.classList.toggle('fa-xmark', isOpen);
            }
        });

        // Close the mobile menu after clicking a navigation link.
        navMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navMenu.classList.remove('active');

                menuToggle.setAttribute('aria-expanded', 'false');

                const icon = menuToggle.querySelector('i');

                if (icon) {
                    icon.classList.remove('fa-xmark');
                    icon.classList.add('fa-bars');
                }
            });
        });

        // Close the menu when clicking outside it.
        document.addEventListener('click', function (event) {
            const clickedInsideMenu = navMenu.contains(event.target);
            const clickedToggle = menuToggle.contains(event.target);

            if (!clickedInsideMenu && !clickedToggle) {
                navMenu.classList.remove('active');

                menuToggle.setAttribute('aria-expanded', 'false');

                const icon = menuToggle.querySelector('i');

                if (icon) {
                    icon.classList.remove('fa-xmark');
                    icon.classList.add('fa-bars');
                }
            }
        });

        // Close the mobile menu when returning to desktop width.
        window.addEventListener('resize', function () {
            if (window.innerWidth > 800) {
                navMenu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');

                const icon = menuToggle.querySelector('i');

                if (icon) {
                    icon.classList.remove('fa-xmark');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }


    /* --------------------------------
       2. SCROLL REVEAL ANIMATIONS
    -------------------------------- */

    const revealElements = document.querySelectorAll(
        '.section-heading, ' +
        '.project-card, ' +
        '.service-card, ' +
        '.value-card, ' +
        '.stat-item, ' +
        '.timeline-item, ' +
        '.about-preview-content, ' +
        '.skill-tag, ' +
        '.contact-method'
    );

    revealElements.forEach(function (element) {
        element.classList.add('reveal');
    });

    if ('IntersectionObserver' in window) {

        const observer = new IntersectionObserver(function (entries, observerInstance) {

            entries.forEach(function (entry) {

                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observerInstance.unobserve(entry.target);
                }

            });

        }, {
            threshold: 0.12
        });

        revealElements.forEach(function (element) {
            observer.observe(element);
        });

    } else {

        revealElements.forEach(function (element) {
            element.classList.add('visible');
        });

    }


    /* --------------------------------
       3. CONTACT FORM FEEDBACK
    -------------------------------- */

    const contactForm = document.querySelector('.contact-form');

    if (contactForm) {

        contactForm.addEventListener('submit', function (event) {

            if (!contactForm.checkValidity()) {
                event.preventDefault();
                contactForm.reportValidity();
                return;
            }

            const submitButton = contactForm.querySelector(
                'button[type="submit"]'
            );

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
            }

        });

    }


    /* --------------------------------
       4. PROJECT IMAGE FALLBACK
    -------------------------------- */

    const projectImages = document.querySelectorAll('.project-image img');

    projectImages.forEach(function (image) {

        image.addEventListener('error', function () {

            const placeholder = document.createElement('div');

            placeholder.className = 'project-image-placeholder';

            placeholder.innerHTML =
                '<i class="fa-solid fa-code"></i>';

            image.parentElement.replaceChildren(placeholder);

        });

    });


    /* --------------------------------
       5. SMOOTH INTERNAL NAVIGATION
    -------------------------------- */

    document.querySelectorAll('a[href^="#"]').forEach(function (link) {

        link.addEventListener('click', function (event) {

            const targetId = link.getAttribute('href');

            if (!targetId || targetId === '#') {
                return;
            }

            const target = document.querySelector(targetId);

            if (target) {
                event.preventDefault();

                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

        });

    });


    /* --------------------------------
       6. CURRENT YEAR
    -------------------------------- */

    const yearElement = document.getElementById('currentYear');

    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }

});
