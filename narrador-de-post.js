function narrarPost() {
    const audioElement = document.getElementById('narrador-audio');
    const content = document.querySelector('.entry-content').innerText; // Altere para a classe ou tag do conteúdo do post

    // Use a API Web Speech ou um serviço externo.
    if ('speechSynthesis' in window) {
        const synth = window.speechSynthesis;
        const utterance = new SpeechSynthesisUtterance(content);

        utterance.lang = 'pt-BR';
        utterance.onend = () => {
            audioElement.style.display = 'none';
        };

        synth.speak(utterance);
    } else {
        alert('Seu navegador não suporta a funcionalidade de síntese de fala.');
    }
}
