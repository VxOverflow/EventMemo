class MediaRecorderController {
    constructor() {
        this.mediaRecorder = null;
        this.stream = null;
        this.chunks = [];
        this.stopTimer = null;
    }

    async start(type, maximumDuration, onTick) {
        this.stopStream();

        const constraints = type === 'video'
            ? { video: true, audio: true }
            : { audio: true };

        this.stream = await navigator.mediaDevices.getUserMedia(constraints);
        this.chunks = [];

        const mimeType = this.getSupportedMimeType(type);
        this.mediaRecorder = mimeType
            ? new MediaRecorder(this.stream, { mimeType })
            : new MediaRecorder(this.stream);

        this.mediaRecorder.addEventListener('dataavailable', (event) => {
            if (event.data.size > 0) {
                this.chunks.push(event.data);
            }
        });

        const startedAt = Date.now();
        const interval = window.setInterval(() => {
            const remaining = Math.max(0, maximumDuration - (Date.now() - startedAt));
            onTick(remaining);
        }, 250);

        this.stopTimer = window.setTimeout(() => {
            if (this.mediaRecorder?.state === 'recording') {
                this.stop();
            }
        }, maximumDuration);

        this.mediaRecorder.addEventListener('stop', () => {
            window.clearInterval(interval);
            window.clearTimeout(this.stopTimer);
            onTick(0);
        }, { once: true });

        this.mediaRecorder.start();
        return this.stream;
    }

    stop() {
        return new Promise((resolve) => {
            if (!this.mediaRecorder || this.mediaRecorder.state === 'inactive') {
                resolve(null);
                return;
            }

            this.mediaRecorder.addEventListener('stop', () => {
                const mimeType = this.mediaRecorder.mimeType || 'video/webm';
                const blob = new Blob(this.chunks, { type: mimeType });
                this.stopStream();
                resolve(blob);
            }, { once: true });

            this.mediaRecorder.stop();
        });
    }

    stopStream() {
        this.stream?.getTracks().forEach((track) => track.stop());
        this.stream = null;
    }

    getSupportedMimeType(type) {
        const mimeTypes = type === 'video'
            ? ['video/webm;codecs=vp9,opus', 'video/webm', 'video/mp4']
            : ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus'];

        return mimeTypes.find((mimeType) => MediaRecorder.isTypeSupported(mimeType)) || '';
    }
}

window.MediaRecorderController = MediaRecorderController;
