// Aula Virtual Académica — utilidades globales.
(function () {
    'use strict';

    // Cierre automático de alertas.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-auto-dismiss]').forEach(function (el) {
            setTimeout(function () {
                el.classList.remove('show');
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 5000);
        });
    });

    window.AV = {
        /**
         * Petición POST con fetch y manejo de errores.
         * @param {string} url
         * @param {object} data
         * @returns {Promise<object>}
         */
        post: async function (url, data) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                },
                body: JSON.stringify(data || {})
            });
            const body = await resp.json();
            if (!resp.ok) {
                throw new Error((body && body.error && body.error.message) || 'Error de red');
            }
            return body;
        }
    };

    // Contenido H5P: inicializa el reproductor standalone para cada bloque.
    function initH5P() {
        if (typeof window.H5PStandalone === 'undefined') {
            return;
        }
        document.querySelectorAll('.h5p-container').forEach(function (el) {
            if (el.dataset.initialized) {
                return;
            }
            el.dataset.initialized = '1';
            new window.H5PStandalone.H5P(el, {
                h5pJsonPath: el.dataset.h5pPath,
                frameJs: el.dataset.frameJs,
                frameCss: el.dataset.frameCss
            }).catch(function () {
                el.innerHTML = '<div class="alert alert-danger small">No se pudo cargar el contenido interactivo H5P.</div>';
            });
        });
    }
    document.addEventListener('DOMContentLoaded', initH5P);

    // Actividades interactivas: envío y retroalimentación inmediata.
    document.addEventListener('click', async function (ev) {
        const btn = ev.target.closest('.activity-submit');
        if (!btn) {
            return;
        }
        const container = btn.closest('.activity');
        const activityId = container.dataset.activityId;
        const input = container.querySelector('input[name="answer"]');
        let answer;
        const checkboxes = container.querySelectorAll('input[name="answer"]:checked');
        const isMultiple = container.querySelector('input[name="answer"][type="checkbox"]');
        if (isMultiple) {
            answer = Array.from(checkboxes).map(function (c) { return parseInt(c.value, 10); });
        } else {
            answer = input && input.value !== undefined ? parseInt(input.value, 10) : null;
        }

        const feedback = container.querySelector('.activity-feedback');
        btn.disabled = true;
        try {
            const result = await window.AV.post('/api/activity/' + activityId + '/submit', { answer: answer });
            feedback.textContent = result.data.feedback;
            feedback.className = 'activity-feedback mt-2 small fw-semibold ' + (result.data.is_correct ? 'text-success' : 'text-danger');
            if (result.data.is_correct) {
                container.querySelectorAll('input').forEach(function (i) { i.disabled = true; });
                btn.remove();
            }
        } catch (err) {
            feedback.textContent = err.message;
            feedback.className = 'activity-feedback mt-2 small fw-semibold text-danger';
        } finally {
            btn.disabled = false;
        }
    });
})();
