(() => {
    const token = new URLSearchParams(window.location.search).get('token');
    const recorder = new MediaRecorderController();
    let audioBlob = null;
    let videoBlob = null;

    const elements = {
        contribution: document.querySelector('#contribution-section'),
        invalidEvent: document.querySelector('#invalid-event'),
        success: document.querySelector('#success-message'),
        form: document.querySelector('#contribution-form'),
        message: document.querySelector('#form-message'),
        token: document.querySelector('#event-token'),
        eventName: document.querySelector('#event-name'),
        eventDescription: document.querySelector('#event-description'),
        photos: document.querySelector('#photos'),
        photoPreview: document.querySelector('#photo-preview'),
        audioPreview: document.querySelector('#audio-preview'),
        videoPreview: document.querySelector('#video-preview'),
        videoLivePreview: document.querySelector('#video-live-preview'),
        progress: document.querySelector('#upload-progress'),
        progressBar: document.querySelector('#upload-progress-bar'),
        progressValue: document.querySelector('#upload-progress-value'),
        submit: document.querySelector('#submit-contribution'),
    };

    function showMessage(message, isError = true) {
        elements.message.textContent = message;
        elements.message.hidden = false;
        elements.message.dataset.status = isError ? 'error' : 'success';
    }

    function formatDuration(milliseconds) {
        const seconds = Math.ceil(milliseconds / 1000);
        return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    }

    function clearRecording(type) {
        const preview = type === 'audio' ? elements.audioPreview : elements.videoPreview;
        preview.removeAttribute('src');
        preview.hidden = true;
        if (type === 'audio') {
            audioBlob = null;
        } else {
            videoBlob = null;
        }
    }

    function switchMemoryPanel() {
        const type = document.querySelector('input[name="memory-type"]:checked').value;
        document.querySelectorAll('.memory-panel').forEach((panel) => {
            panel.hidden = panel.id !== `${type}-panel`;
        });
        elements.message.hidden = true;
    }

    async function startRecording(type, maximumDuration) {
        try {
            clearRecording(type);
            const stream = await recorder.start(type, maximumDuration, (remaining) => {
                document.querySelector(`#${type}-timer`).textContent = formatDuration(remaining);
            });

            if (type === 'video') {
                elements.videoLivePreview.srcObject = stream;
                elements.videoLivePreview.hidden = false;
            }

            document.querySelector(`#start-${type}`).hidden = true;
            document.querySelector(`#stop-${type}`).hidden = false;
        } catch (error) {
            showMessage('L’accès à votre microphone ou votre caméra a été refusé.');
        }
    }

    async function stopRecording(type) {
        const blob = await recorder.stop();
        if (!blob || blob.size === 0) {
            showMessage('Aucun enregistrement n’a été capturé.');
            return;
        }

        const preview = type === 'audio' ? elements.audioPreview : elements.videoPreview;
        preview.src = URL.createObjectURL(blob);
        preview.hidden = false;

        if (type === 'audio') {
            audioBlob = blob;
        } else {
            videoBlob = blob;
            elements.videoLivePreview.srcObject = null;
            elements.videoLivePreview.hidden = true;
        }

        document.querySelector(`#start-${type}`).hidden = false;
        document.querySelector(`#stop-${type}`).hidden = true;
    }

    function renderPhotoPreview() {
        elements.photoPreview.replaceChildren();
        [...elements.photos.files].forEach((file) => {
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = `Aperçu de ${file.name}`;
            elements.photoPreview.append(image);
        });
    }

    function upload(formData) {
        return new Promise((resolve, reject) => {
            const request = new XMLHttpRequest();
            request.open('POST', 'api/souvenirs/create.php');
            request.responseType = 'json';

            request.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) {
                    return;
                }

                const percentage = Math.round((event.loaded / event.total) * 100);
                elements.progressBar.value = percentage;
                elements.progressValue.textContent = `${percentage} %`;
            });

            request.addEventListener('load', () => {
                const response = request.response;
                if (request.status >= 200 && request.status < 300 && response?.success) {
                    resolve(response);
                    return;
                }
                reject(new Error(response?.message || 'L’envoi a échoué.'));
            });

            request.addEventListener('error', () => reject(new Error('Erreur réseau pendant l’envoi.')));
            request.send(formData);
        });
    }

    async function submitContribution(event) {
        event.preventDefault();
        elements.message.hidden = true;

        const formData = new FormData(elements.form);
        const selectedType = document.querySelector('input[name="memory-type"]:checked').value;
        const content = formData.get('contenu').trim();

        if (selectedType === 'texte' && content === '') {
            showMessage('Écrivez un message avant de l’envoyer.');
            return;
        }

        if (selectedType === 'photo') {
            if (elements.photos.files.length === 0) {
                showMessage('Sélectionnez au moins une photo.');
                return;
            }
            formData.delete('photos[]');
            [...elements.photos.files].forEach((file) => formData.append('photos[]', file));
        }

        if (selectedType === 'audio') {
            if (!audioBlob) {
                showMessage('Enregistrez puis réécoutez votre message vocal avant l’envoi.');
                return;
            }
            formData.append('audio', audioBlob, 'souvenir-audio.webm');
        }

        if (selectedType === 'video') {
            if (!videoBlob) {
                showMessage('Enregistrez puis relisez votre vidéo avant l’envoi.');
                return;
            }
            formData.append('video', videoBlob, 'souvenir-video.webm');
        }

        elements.progress.hidden = false;
        elements.progressBar.value = 0;
        elements.progressValue.textContent = '0 %';
        elements.submit.disabled = true;

        try {
            await upload(formData);
            elements.contribution.hidden = true;
            elements.success.hidden = false;
        } catch (error) {
            showMessage(error.message);
        } finally {
            elements.progress.hidden = true;
            elements.submit.disabled = false;
        }
    }

    async function loadEvent() {
        if (!token) {
            elements.invalidEvent.hidden = false;
            return;
        }

        try {
            const response = await fetch(`api/events/verify.php?token=${encodeURIComponent(token)}`);
            const result = await response.json();
            if (!result.success) {
                throw new Error();
            }

            elements.token.value = token;
            elements.eventName.textContent = result.data.nom;
            elements.eventDescription.textContent = result.data.description || '';
            elements.contribution.hidden = false;
        } catch (error) {
            elements.invalidEvent.hidden = false;
        }
    }

    document.querySelectorAll('input[name="memory-type"]').forEach((input) => {
        input.addEventListener('change', switchMemoryPanel);
    });
    elements.photos.addEventListener('change', renderPhotoPreview);
    document.querySelector('#start-audio').addEventListener('click', () => startRecording('audio', 120000));
    document.querySelector('#stop-audio').addEventListener('click', () => stopRecording('audio'));
    document.querySelector('#start-video').addEventListener('click', () => startRecording('video', 60000));
    document.querySelector('#stop-video').addEventListener('click', () => stopRecording('video'));
    elements.form.addEventListener('submit', submitContribution);
    document.querySelector('#new-contribution').addEventListener('click', () => window.location.reload());

    loadEvent();
})();
