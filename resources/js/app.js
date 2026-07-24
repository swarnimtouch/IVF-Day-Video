import './bootstrap';

const form = document.querySelector('#video-form');
const input = document.querySelector('#photo-input');
const output = document.querySelector('#cropped-photo');
const picker = document.querySelector('#photo-picker');
const modal = document.querySelector('#crop-modal');
const canvas = document.querySelector('#crop-canvas');
const zoom = document.querySelector('#zoom');
const preview = document.querySelector('#photo-preview');
const photoError = document.querySelector('#photo-error');

if (form && input && canvas) {
    const ctx = canvas.getContext('2d');
    const image = new Image();
    const state = { scale: 1, base: 1, x: 0, y: 0, dragging: false, lastX: 0, lastY: 0 };
    const closeModal = () => { modal.hidden = true; document.body.classList.remove('modal-open'); };
    const openModal = () => { modal.hidden = false; document.body.classList.add('modal-open'); document.querySelector('#crop-apply').focus(); };
    const clamp = () => {
        const w = image.naturalWidth * state.base * state.scale;
        const h = image.naturalHeight * state.base * state.scale;
        state.x = Math.min(0, Math.max(canvas.width - w, state.x));
        state.y = Math.min(0, Math.max(canvas.height - h, state.y));
    };
    const draw = () => {
        if (!image.naturalWidth) return;
        clamp(); ctx.clearRect(0, 0, canvas.width, canvas.height); ctx.save();
        ctx.beginPath(); ctx.arc(180, 180, 180, 0, Math.PI * 2); ctx.clip();
        ctx.drawImage(image, state.x, state.y, image.naturalWidth * state.base * state.scale, image.naturalHeight * state.base * state.scale); ctx.restore();
    };
    const applyCrop = () => canvas.toBlob((blob) => {
        if (!blob) return;
        const file = new File([blob], 'doctor-photo.png', { type: 'image/png' });
        const transfer = new DataTransfer(); transfer.items.add(file); output.files = transfer.files;
        preview.innerHTML = `<img src="${URL.createObjectURL(blob)}" alt="Cropped doctor photo">`;
        photoError.textContent = ''; closeModal();
    }, 'image/png');

    picker.addEventListener('click', () => input.click());
    input.addEventListener('change', () => {
        const file = input.files[0]; if (!file) return;
        if (!['image/jpeg','image/png','image/webp'].includes(file.type)) { photoError.textContent = 'Please choose a JPG, PNG or WebP image.'; return; }
        if (file.size > 8 * 1024 * 1024) { photoError.textContent = 'Please choose a photo smaller than 8 MB.'; return; }
        image.onload = () => { state.base = Math.max(360 / image.naturalWidth, 360 / image.naturalHeight); state.scale = 1; zoom.value = 1; state.x = (360 - image.naturalWidth * state.base) / 2; state.y = (360 - image.naturalHeight * state.base) / 2; draw(); openModal(); };
        image.src = URL.createObjectURL(file); input.value = '';
    });
    const setZoom = value => { const old = state.scale; state.scale = Math.min(4, Math.max(1, Number(value))); zoom.value = state.scale; state.x = 180 - (180 - state.x) * state.scale / old; state.y = 180 - (180 - state.y) * state.scale / old; document.querySelector('#zoom-value').textContent = `${state.scale.toFixed(1)}×`; draw(); };
    zoom.addEventListener('input', () => setZoom(zoom.value));
    document.querySelector('#zoom-out').addEventListener('click', () => setZoom(state.scale - .2));
    document.querySelector('#zoom-in').addEventListener('click', () => setZoom(state.scale + .2));
    canvas.addEventListener('pointerdown', e => { state.dragging = true; state.lastX = e.clientX; state.lastY = e.clientY; canvas.setPointerCapture(e.pointerId); });
    canvas.addEventListener('pointermove', e => { if (!state.dragging) return; const ratio = 360 / canvas.getBoundingClientRect().width; state.x += (e.clientX-state.lastX)*ratio; state.y += (e.clientY-state.lastY)*ratio; state.lastX=e.clientX; state.lastY=e.clientY; draw(); });
    canvas.addEventListener('pointerup', () => state.dragging = false);
    document.querySelector('#crop-apply').addEventListener('click', applyCrop);
    document.querySelector('#crop-cancel').addEventListener('click', closeModal);
    document.querySelector('#crop-back').addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

    const messages = { employee_code: 'Enter an employee code using letters and numbers only.', prefix: 'Please select a prefix.', doctor_name: 'Please enter the doctor name.' };
    const validate = field => {
        const error = document.querySelector(`[data-error-for="${field.name}"]`); let message = '';
        if (!field.value.trim() && field.name !== 'city') message = messages[field.name];
        else if (field.name === 'employee_code' && !/^[A-Za-z0-9]+$/.test(field.value)) message = messages.employee_code;
        else if (field.value.trim() && ['doctor_name','city'].includes(field.name) && !/^[\p{L}\p{M} .'-]+$/u.test(field.value)) message = 'Only letters, spaces, dots, apostrophes and hyphens are allowed.';
        error.textContent = message || ''; field.classList.toggle('invalid', Boolean(message)); return !message;
    };
    form.querySelectorAll('input[name],select[name]').forEach(field => { if (field.type !== 'file') { field.addEventListener('blur', () => validate(field)); field.addEventListener('input', () => { if (field.classList.contains('invalid')) validate(field); }); } });
    form.addEventListener('submit', event => {
        const fields = [...form.querySelectorAll('input[name="employee_code"],input[name="doctor_name"],input[name="city"],select[name="prefix"]')];
        const valid = fields.map(validate).every(Boolean);
        if (!output.files.length) photoError.textContent = 'Please choose and crop a doctor photo.';
        if (!valid || !output.files.length) { event.preventDefault(); form.querySelector('.invalid')?.focus(); return; }
        const button = document.querySelector('#submit-button'); button.disabled = true; button.classList.add('loading'); button.querySelector('.button-label').textContent = 'Generating and uploading…';
    });
}

if (window.location.hash === '#result') document.querySelector('#result')?.scrollIntoView({ behavior: 'smooth' });
