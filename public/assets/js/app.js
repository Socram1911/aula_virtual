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
            var blockId = el.dataset.blockId;
            new window.H5PStandalone.H5P(el, {
                h5pJsonPath: el.dataset.h5pPath,
                frameJs: el.dataset.frameJs,
                frameCss: el.dataset.frameCss,
                // El runtime informa el resultado (score/maxScore) a este endpoint
                // cuando el contenido se completa; el servidor registra la nota.
                postUserStatistics: true,
                ajax: {
                    setFinishedUrl: '/api/h5p/' + blockId + '/result'
                }
            }).catch(function () {
                el.innerHTML = '<div class="alert alert-danger small">No se pudo cargar el contenido interactivo H5P.</div>';
            });
        });
    }
    document.addEventListener('DOMContentLoaded', initH5P);

    // Seguimiento automático de visualización: registra que el estudiante "vio" un
    // bloque cuando este entra en el viewport (al menos 60% visible). Se dispara una vez.
    function initContentTracking() {
        var blocks = document.querySelectorAll('[data-track-block]');
        if (!blocks.length || typeof IntersectionObserver === 'undefined') {
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && !entry.target.dataset.tracked) {
                    entry.target.dataset.tracked = '1';
                    window.AV.post('/api/progress/view', {
                        block_id: parseInt(entry.target.dataset.trackBlock, 10)
                    }).catch(function () { /* sin conexión u otro error: se ignora */ });
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.6 });

        blocks.forEach(function (b) { observer.observe(b); });
    }

    // Seguimiento de videos de YouTube mediante la IFrame API:
    // registra inicio, avance (porcentaje) y finalización.
    function initYouTubeTracking() {
        var players = {};
        var started = {};
        var lastSent = {};

        function fire(blockId, event, percent) {
            window.AV.post('/api/progress/video', {
                block_id: parseInt(blockId, 10),
                event: event,
                percent: percent || 0
            }).catch(function () { /* ignora */ });
        }

        function bind(iframe) {
            var blockId = iframe.dataset.blockId;
            if (!blockId || players[blockId]) {
                return;
            }
            players[blockId] = new YT.Player(iframe, {
                events: {
                    onStateChange: function (event) {
                        var player = players[blockId];
                        if (event.data === YT.PlayerState.PLAYING) {
                            if (!started[blockId]) {
                                started[blockId] = true;
                                fire(blockId, 'video_started');
                            }
                            // Mide avance cada 5 s y reporta cambios >= 5%.
                            player.__timer = setInterval(function () {
                                var dur = player.getDuration();
                                var cur = player.getCurrentTime();
                                if (dur > 0) {
                                    var pct = Math.round((cur / dur) * 100);
                                    if (!lastSent[blockId] || pct - lastSent[blockId] >= 5 || pct === 100) {
                                        lastSent[blockId] = pct;
                                        fire(blockId, 'video_progress', pct);
                                    }
                                }
                            }, 5000);
                        } else if (event.data === YT.PlayerState.ENDED) {
                            if (player.__timer) {
                                clearInterval(player.__timer);
                            }
                            fire(blockId, 'video_completed', 100);
                        } else {
                            // Pausa u otro estado: detiene el temporizador de avance.
                            if (player.__timer) {
                                clearInterval(player.__timer);
                                player.__timer = null;
                            }
                        }
                    }
                }
            });
        }

        function ready() {
            document.querySelectorAll('iframe.yt-tracked').forEach(bind);
        }

        var iframes = document.querySelectorAll('iframe.yt-tracked');
        if (!iframes.length) {
            return;
        }

        if (window.YT && window.YT.Player) {
            ready();
        } else {
            window.onYouTubeIframeAPIReady = ready;
            var tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initContentTracking();
        initYouTubeTracking();
    });

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
