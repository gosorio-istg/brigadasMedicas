/**
 * Navegación de la presentación académica.
 * No se incluyen notas del expositor en el DOM: la pantalla pública contiene
 * únicamente los apoyos visuales que debe ver el jurado.
 */
document.addEventListener('DOMContentLoaded', () => {
    const slides = Array.from(document.querySelectorAll('.slide'));
    const previousButton = document.getElementById('previous-slide');
    const nextButton = document.getElementById('next-slide');
    const fullscreenButton = document.getElementById('toggle-fullscreen');
    const counter = document.getElementById('slide-counter');
    const progress = document.getElementById('progress-bar');
    const qrImage = document.getElementById('android-qr');
    const downloadLink = document.getElementById('android-download');
    const gestureHint = document.getElementById('gesture-hint');

    let currentIndex = readInitialSlide();
    let touchStartX = 0;
    let touchStartY = 0;
    let controlsTimer = null;

    function readInitialSlide() {
        const requested = Number.parseInt(window.location.hash.replace('#', ''), 10);
        return Number.isInteger(requested)
            ? Math.min(Math.max(requested - 1, 0), slides.length - 1)
            : 0;
    }

    function showSlide(index, updateHistory = true) {
        const nextIndex = Math.min(Math.max(index, 0), slides.length - 1);

        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('is-active', slideIndex === nextIndex);
            slide.classList.toggle('was-active', slideIndex < nextIndex);
            slide.setAttribute('aria-hidden', slideIndex === nextIndex ? 'false' : 'true');

            if (slideIndex === nextIndex) {
                slide.scrollTop = 0;
            }
        });

        currentIndex = nextIndex;
        previousButton.disabled = currentIndex === 0;
        nextButton.disabled = currentIndex === slides.length - 1;
        counter.textContent = `${String(currentIndex + 1).padStart(2, '0')} / ${slides.length}`;
        progress.style.width = `${((currentIndex + 1) / slides.length) * 100}%`;
        document.title = `${slides[currentIndex].dataset.title} | BrigadaSalud`;

        if (updateHistory) {
            window.history.replaceState(null, '', `#${currentIndex + 1}`);
        }
    }

    function goNext() {
        if (currentIndex < slides.length - 1) showSlide(currentIndex + 1);
    }

    function goPrevious() {
        if (currentIndex > 0) showSlide(currentIndex - 1);
    }

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
            return;
        }

        document.exitFullscreen?.();
    }

    function showControlsTemporarily() {
        document.body.classList.remove('controls-hidden');
        window.clearTimeout(controlsTimer);
        controlsTimer = window.setTimeout(() => {
            document.body.classList.add('controls-hidden');
        }, 3500);
    }

    /**
     * Consulta la configuración pública para que el QR y el botón respeten la
     * URL definida por el administrador, incluso si apunta a un servidor externo.
     */
    async function loadAndroidDownload() {
        try {
            const response = await fetch('/api/v1/public/configuracion', {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error('No se pudo consultar la configuración pública.');

            const payload = await response.json();
            const configuration = payload.data ?? payload;

            if (configuration.apk_android_download_url) {
                downloadLink.href = configuration.apk_android_download_url;
            }

            if (configuration.qr_android_url) {
                qrImage.src = configuration.qr_android_url;
            }
        } catch (error) {
            // La ruta local incluida en Blade sigue disponible como respaldo.
            console.warn(error.message);
        }
    }

    previousButton.addEventListener('click', goPrevious);
    nextButton.addEventListener('click', goNext);
    fullscreenButton.addEventListener('click', toggleFullscreen);

    document.addEventListener('keydown', (event) => {
        const nextKeys = ['ArrowRight', 'ArrowDown', 'PageDown', 'Enter', ' '];
        const previousKeys = ['ArrowLeft', 'ArrowUp', 'PageUp', 'Backspace'];

        if (nextKeys.includes(event.key)) {
            event.preventDefault();
            goNext();
        } else if (previousKeys.includes(event.key)) {
            event.preventDefault();
            goPrevious();
        } else if (event.key === 'Home') {
            event.preventDefault();
            showSlide(0);
        } else if (event.key === 'End') {
            event.preventDefault();
            showSlide(slides.length - 1);
        } else if (event.key.toLowerCase() === 'f') {
            event.preventDefault();
            toggleFullscreen();
        }

        showControlsTemporarily();
    });

    document.addEventListener('touchstart', (event) => {
        touchStartX = event.changedTouches[0].clientX;
        touchStartY = event.changedTouches[0].clientY;
    }, { passive: true });

    document.addEventListener('touchend', (event) => {
        const deltaX = event.changedTouches[0].clientX - touchStartX;
        const deltaY = event.changedTouches[0].clientY - touchStartY;

        // Solo se interpreta un gesto horizontal claro para conservar el scroll
        // vertical de las diapositivas en teléfonos pequeños.
        if (Math.abs(deltaX) > 55 && Math.abs(deltaX) > Math.abs(deltaY) * 1.25) {
            deltaX < 0 ? goNext() : goPrevious();
        }
    }, { passive: true });

    document.addEventListener('mousemove', showControlsTemporarily, { passive: true });
    document.addEventListener('touchstart', showControlsTemporarily, { passive: true });
    window.addEventListener('hashchange', () => showSlide(readInitialSlide(), false));

    qrImage.addEventListener('error', () => {
        qrImage.closest('.qr-frame')?.classList.add('is-fallback');
    });

    if (window.matchMedia('(max-width: 560px)').matches && !sessionStorage.getItem('presentation-gesture-seen')) {
        gestureHint.classList.add('is-visible');
        window.setTimeout(() => gestureHint.classList.remove('is-visible'), 2600);
        sessionStorage.setItem('presentation-gesture-seen', '1');
    }

    showSlide(currentIndex, false);
    loadAndroidDownload();
    showControlsTemporarily();
});
