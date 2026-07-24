import './bootstrap';

const input = document.querySelector('#photo-input');
const output = document.querySelector('#cropped-photo');
const picker = document.querySelector('#photo-picker');
const area = document.querySelector('#crop-area');
const canvas = document.querySelector('#crop-canvas');
const zoom = document.querySelector('#zoom');
const preview = document.querySelector('#photo-preview');
const error = document.querySelector('#photo-error');
const form = document.querySelector('#video-form');

if (input && canvas) {
    const ctx = canvas.getContext('2d');
    const image = new Image();
    const state = { scale: 1, base: 1, x: 0, y: 0, dragging: false, lastX: 0, lastY: 0 };

    const clamp = () => {
        const w = image.naturalWidth * state.base * state.scale;
        const h = image.naturalHeight * state.base * state.scale;
        state.x = Math.min(0, Math.max(canvas.width - w, state.x));
        state.y = Math.min(0, Math.max(canvas.height - h, state.y));
    };

    const exportCrop = () => canvas.toBlob((blob) => {
        if (!blob) return;
        const file = new File([blob], 'doctor-photo.png', { type: 'image/png' });
        const transfer = new DataTransfer();
        transfer.items.add(file);
        output.files = transfer.files;
        const url = URL.createObjectURL(blob);
        preview.innerHTML = `<img src="${url}" alt="Cropped doctor photo">`;
        error.textContent = '';
    }, 'image/png');

    const draw = () => {
        if (!image.naturalWidth) return;
        clamp();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.save();
        ctx.beginPath();
        ctx.arc(180, 180, 180, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(image, state.x, state.y, image.naturalWidth * state.base * state.scale, image.naturalHeight * state.base * state.scale);
        ctx.restore();
        exportCrop();
    };

    picker.addEventListener('click', () => input.click());
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        if (file.size > 8 * 1024 * 1024) { error.textContent = 'Please choose a photo smaller than 8 MB.'; return; }
        image.onload = () => {
            state.base = Math.max(canvas.width / image.naturalWidth, canvas.height / image.naturalHeight);
            state.scale = 1; zoom.value = 1;
            state.x = (canvas.width - image.naturalWidth * state.base) / 2;
            state.y = (canvas.height - image.naturalHeight * state.base) / 2;
            area.hidden = false;
            draw();
        };
        image.src = URL.createObjectURL(file);
    });
    zoom.addEventListener('input', () => {
        const old = state.scale;
        state.scale = Number(zoom.value);
        state.x = 180 - (180 - state.x) * state.scale / old;
        state.y = 180 - (180 - state.y) * state.scale / old;
        draw();
    });
    canvas.addEventListener('pointerdown', (event) => { state.dragging = true; state.lastX = event.clientX; state.lastY = event.clientY; canvas.setPointerCapture(event.pointerId); });
    canvas.addEventListener('pointermove', (event) => {
        if (!state.dragging) return;
        const ratio = canvas.width / canvas.getBoundingClientRect().width;
        state.x += (event.clientX - state.lastX) * ratio; state.y += (event.clientY - state.lastY) * ratio;
        state.lastX = event.clientX; state.lastY = event.clientY; draw();
    });
    canvas.addEventListener('pointerup', () => { state.dragging = false; });

    form.addEventListener('submit', (event) => {
        if (!output.files.length) { event.preventDefault(); error.textContent = 'Please choose and crop a doctor photo.'; picker.focus(); return; }
        const button = document.querySelector('#submit-button');
        button.disabled = true; button.classList.add('loading');
        button.querySelector('.button-label').textContent = 'Generating your video…';
    });
}

if (window.location.hash === '#result') document.querySelector('#result')?.scrollIntoView({ behavior: 'smooth' });
