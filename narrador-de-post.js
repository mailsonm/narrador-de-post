/**
 * Narrador de Post - Frontend Player Script
 * Versão: 2.0.0 (Híbrido)
 */

document.addEventListener('DOMContentLoaded', () => {
    const players = document.querySelectorAll('.narrador-player-container');
    if (!players.length) return;

    players.forEach(player => initNarradorPlayer(player));
});

function initNarradorPlayer(container) {
    const mode = container.dataset.mode || 'browser';
    const lang = container.dataset.lang || 'pt-BR';
    let defaultRate = parseFloat(container.dataset.rate || '1.0');

    const btnPlay = container.querySelector('.narrador-btn-play');
    const btnPause = container.querySelector('.narrador-btn-pause');
    const btnStop = container.querySelector('.narrador-btn-stop');
    const speedSelect = container.querySelector('.narrador-speed-select');
    const controlsPanel = container.querySelector('.narrador-controls-panel');
    const progressFill = container.querySelector('.narrador-progress-fill');
    const progressText = container.querySelector('.narrador-progress-text');
    const audioElement = container.querySelector('.narrador-audio-element');

    let isPlaying = false;
    let isPaused = false;
    let textChunks = [];
    let currentChunkIndex = 0;
    let currentUtterance = null;
    let selectedVoice = null;

    // Se houver arquivo MP3 em cache
    if (mode === 'audio_file' && audioElement) {
        setupAudioFileMode();
        return;
    }

    // Modo Web Speech API (Navegador)
    setupBrowserSpeechMode();

    function setupBrowserSpeechMode() {
        if (!('speechSynthesis' in window)) {
            if (btnPlay) {
                btnPlay.disabled = true;
                btnPlay.title = (window.NarradorConfig && window.NarradorConfig.i18nNoSupport) || 'Síntese de voz não suportada.';
            }
            return;
        }

        const synth = window.speechSynthesis;

        // Carrega e seleciona as melhores vozes neurais
        function loadVoices() {
            const voices = synth.getVoices();
            if (!voices.length) return;

            // Prioriza vozes neurais / online naturais
            const langPrefix = lang.split('-')[0].toLowerCase();
            const matchingVoices = voices.filter(v => v.lang.toLowerCase().startsWith(langPrefix));

            selectedVoice = matchingVoices.find(v => 
                v.name.includes('Natural') || 
                v.name.includes('Online') || 
                v.name.includes('Neural') || 
                v.name.includes('Google')
            ) || matchingVoices[0] || voices[0];
        }

        loadVoices();
        if (synth.onvoiceschanged !== undefined) {
            synth.onvoiceschanged = loadVoices;
        }

        btnPlay.addEventListener('click', () => {
            if (isPlaying && isPaused) {
                resumeSpeech();
            } else if (!isPlaying) {
                startSpeech();
            }
        });

        btnPause.addEventListener('click', () => {
            if (isPlaying && !isPaused) {
                pauseSpeech();
            } else if (isPaused) {
                resumeSpeech();
            }
        });

        btnStop.addEventListener('click', () => {
            stopSpeech();
        });

        if (speedSelect) {
            speedSelect.addEventListener('change', (e) => {
                defaultRate = parseFloat(e.target.value);
                if (isPlaying && !isPaused) {
                    // Reinicia no chunk atual com nova velocidade
                    synth.cancel();
                    playChunk(currentChunkIndex);
                }
            });
        }

        function extractPostText() {
            const selectors = [
                '.entry-content',
                '.wp-block-post-content',
                '.post-content',
                'article .content',
                'article',
                '.elementor-widget-theme-post-content',
                'main'
            ];

            let contentEl = null;
            for (const sel of selectors) {
                const el = document.querySelector(sel);
                if (el && el.innerText.trim().length > 50) {
                    contentEl = el;
                    break;
                }
            }

            if (!contentEl) {
                contentEl = document.body;
            }

            // Clona para limpar elementos indesejados sem afetar o DOM visível
            const clone = contentEl.cloneNode(true);
            const removeSelectors = [
                '.narrador-player-container',
                'script',
                'style',
                'nav',
                'footer',
                'header',
                '.social-share',
                '.sharedaddy',
                '.comments-area',
                '#comments'
            ];

            removeSelectors.forEach(s => {
                clone.querySelectorAll(s).forEach(node => node.remove());
            });

            return clone.innerText.replace(/\s+/g, ' ').trim();
        }

        // Divide o texto em chunks para evitar o congelamento de 15s do Chrome
        function splitIntoChunks(text) {
            const sentences = text.match(/[^.!?;\n]+[.!?;\n]+/g) || [text];
            const chunks = [];
            let current = '';

            for (const s of sentences) {
                if ((current + s).length > 180) {
                    if (current.trim()) chunks.push(current.trim());
                    current = s;
                } else {
                    current += ' ' + s;
                }
            }
            if (current.trim()) chunks.push(current.trim());
            return chunks.length ? chunks : [text];
        }

        function startSpeech() {
            const fullText = extractPostText();
            if (!fullText) return;

            textChunks = splitIntoChunks(fullText);
            currentChunkIndex = 0;
            isPlaying = true;
            isPaused = false;

            if (controlsPanel) controlsPanel.style.display = 'block';
            updatePlayButtonState(true);

            playChunk(0);
        }

        function playChunk(index) {
            if (!isPlaying || index >= textChunks.length) {
                stopSpeech();
                return;
            }

            currentChunkIndex = index;
            updateProgress();

            currentUtterance = new SpeechSynthesisUtterance(textChunks[index]);
            currentUtterance.lang = lang;
            currentUtterance.rate = defaultRate;
            if (selectedVoice) currentUtterance.voice = selectedVoice;

            currentUtterance.onend = () => {
                if (isPlaying && !isPaused) {
                    playChunk(index + 1);
                }
            };

            currentUtterance.onerror = (e) => {
                console.warn('Narrador TTS notice:', e);
                if (isPlaying && !isPaused) {
                    playChunk(index + 1);
                }
            };

            synth.speak(currentUtterance);
        }

        function pauseSpeech() {
            synth.pause();
            isPaused = true;
            updatePlayButtonState(false);
            if (btnPause) btnPause.querySelector('.narrador-icon-pause').textContent = '▶️';
        }

        function resumeSpeech() {
            synth.resume();
            isPaused = false;
            updatePlayButtonState(true);
            if (btnPause) btnPause.querySelector('.narrador-icon-pause').textContent = '⏸️';
        }

        function stopSpeech() {
            synth.cancel();
            isPlaying = false;
            isPaused = false;
            currentChunkIndex = 0;
            updatePlayButtonState(false);
            if (progressFill) progressFill.style.width = '0%';
            if (progressText) progressText.textContent = '0%';
            if (controlsPanel) controlsPanel.style.display = 'none';
        }

        function updateProgress() {
            if (!textChunks.length) return;
            const pct = Math.min(100, Math.round(((currentChunkIndex + 1) / textChunks.length) * 100));
            if (progressFill) progressFill.style.width = `${pct}%`;
            if (progressText) progressText.textContent = `${pct}%`;
        }

        function updatePlayButtonState(playing) {
            if (!btnPlay) return;
            const icon = btnPlay.querySelector('.narrador-icon-play');
            const text = btnPlay.querySelector('.narrador-btn-text');
            if (playing) {
                if (icon) icon.textContent = '🔊';
                if (text) text.textContent = (window.NarradorConfig && window.NarradorConfig.btnPause) || 'Pausar';
            } else {
                if (icon) icon.textContent = '▶️';
                if (text) text.textContent = (window.NarradorConfig && window.NarradorConfig.btnListen) || 'Ouvir este artigo';
            }
        }
    }

    function setupAudioFileMode() {
        btnPlay.addEventListener('click', () => {
            if (audioElement.paused) {
                audioElement.play();
                if (controlsPanel) controlsPanel.style.display = 'block';
                btnPlay.querySelector('.narrador-icon-play').textContent = '⏸️';
            } else {
                audioElement.pause();
                btnPlay.querySelector('.narrador-icon-play').textContent = '▶️';
            }
        });

        btnPause.addEventListener('click', () => {
            if (audioElement.paused) {
                audioElement.play();
            } else {
                audioElement.pause();
            }
        });

        btnStop.addEventListener('click', () => {
            audioElement.pause();
            audioElement.currentTime = 0;
            if (controlsPanel) controlsPanel.style.display = 'none';
            btnPlay.querySelector('.narrador-icon-play').textContent = '🔊';
        });

        if (speedSelect) {
            speedSelect.addEventListener('change', (e) => {
                audioElement.playbackRate = parseFloat(e.target.value);
            });
        }

        audioElement.addEventListener('timeupdate', () => {
            if (audioElement.duration) {
                const pct = Math.round((audioElement.currentTime / audioElement.duration) * 100);
                if (progressFill) progressFill.style.width = `${pct}%`;
                if (progressText) progressText.textContent = `${pct}%`;
            }
        });

        audioElement.addEventListener('ended', () => {
            btnPlay.querySelector('.narrador-icon-play').textContent = '🔊';
            if (controlsPanel) controlsPanel.style.display = 'none';
        });
    }
}
