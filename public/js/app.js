document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.enrollment-form');
    const previews = {
        foto: document.querySelector('.preview--photo'),
        documento: document.querySelector('.preview--document')
    };

    document.querySelectorAll('input[type="file"]').forEach((input) => {
        input.addEventListener('change', () => showFilePreview(input));
    });

    document.querySelectorAll('[data-preview]').forEach((button) => {
        button.addEventListener('click', () => document.getElementById(button.dataset.preview)?.click());
    });

    document.getElementById('download-form')?.addEventListener('click', () => window.print());

    document.querySelectorAll('select[data-location="departamento"]').forEach((departmentSelect) => {
        const prefix = departmentSelect.id.replace('departamento', '');
        const provinceSelect = document.getElementById(`provincia${prefix}`);
        const districtSelect = document.getElementById(`distrito${prefix}`);

        departmentSelect.addEventListener('change', async () => {
            setLocationName(departmentSelect);
            resetSelect(provinceSelect, 'Seleccione provincia');
            resetSelect(districtSelect, 'Seleccione distrito');
            if (departmentSelect.value) await loadLocations(departmentSelect.value, provinceSelect);
        });

        provinceSelect.addEventListener('change', async () => {
            setLocationName(provinceSelect);
            resetSelect(districtSelect, 'Seleccione distrito');
            if (provinceSelect.value) await loadLocations(provinceSelect.value, districtSelect);
        });
    });

    const birthCountry = document.getElementById('pais');
    birthCountry?.addEventListener('change', () => {
        toggleBirthplaceFields(birthCountry.value);
        toggleForeignCountry(birthCountry.value);
    });
    if (birthCountry) toggleBirthplaceFields(birthCountry.value);
    if (birthCountry) toggleForeignCountry(birthCountry.value);

    document.querySelectorAll('#semestre').forEach((field) => {
        field.addEventListener('change', updateStudentCode);
    });
    updateStudentCode();

    document.getElementById('clear-form')?.addEventListener('click', () => {
        Swal.fire({
            title: '¿Está seguro?',
            text: 'Se eliminarán los datos ingresados en esta ficha.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, limpiar ficha',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;
            form.reset();
            resetPreviews();
            Swal.fire({ title: 'Ficha limpiada', text: 'Los datos ingresados fueron eliminados.', icon: 'success', confirmButtonColor: '#23313b' });
        });
    });

    document.getElementById('new-enrollment')?.addEventListener('click', () => {
        const hasData = [...form.querySelectorAll('input, select, textarea')]
            .some((field) => field.type === 'file' ? field.files.length > 0 : field.value !== '');
        const startNew = () => { form.reset(); resetPreviews(); window.scrollTo({ top: 0, behavior: 'smooth' }); };
        if (!hasData) return startNew();

        Swal.fire({
            title: '¿Registrar una nueva matrícula?',
            text: 'Se limpiarán los datos actuales de la ficha.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#23313b',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, iniciar nueva',
            cancelButtonText: 'Continuar editando'
        }).then((result) => { if (result.isConfirmed) startNew(); });
    });

    form?.addEventListener('submit', (event) => {
        if (!form.reportValidity()) {
            event.preventDefault();
            return;
        }

        const missingFiles = [...form.querySelectorAll('input[type="file"]')]
            .filter((field) => field.files.length === 0)
            .map((field) => field.id === 'foto' ? 'la foto carnet' : 'la copia del documento');
        if (missingFiles.length > 0) {
            event.preventDefault();
            Swal.fire({
                title: 'Faltan archivos',
                text: `Adjunte ${missingFiles.join(' y ')} para guardar la matrícula.`,
                icon: 'warning',
                confirmButtonColor: '#23313b'
            });
        }
    });

    function resetPreviews() {
        previews.foto.textContent = 'Foto';
        previews.documento.textContent = 'DNI';
    }
});

function showFilePreview(input) {
    const preview = document.querySelector(input.id === 'foto' ? '.preview--photo' : '.preview--document');
    const file = input.files[0];
    if (!file || !preview) return;

    if (file.type.startsWith('image/')) {
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = input.id === 'foto' ? 'Vista previa de la foto carnet' : 'Vista previa del documento';
        image.onload = () => URL.revokeObjectURL(image.src);
        preview.replaceChildren(image);
        return;
    }

    if (file.type === 'application/pdf') {
        const documentPreview = document.createElement('iframe');
        documentPreview.src = URL.createObjectURL(file);
        documentPreview.title = 'Vista previa del documento PDF';
        preview.replaceChildren(documentPreview);
        return;
    }

    preview.replaceChildren();
    const label = document.createElement('span');
    label.textContent = `${file.name} (${formatFileSize(file.size)})`;
    preview.appendChild(label);
}

function formatFileSize(bytes) {
    return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
}

function resetSelect(select, placeholder) {
    select.replaceChildren(new Option(placeholder, ''));
    select.disabled = true;
}

function setLocationName(select) {
    const hiddenName = `${select.name}_nombre`;
    let hidden = document.querySelector(`input[name="${hiddenName}"]`);
    if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = hiddenName;
        select.form.appendChild(hidden);
    }
    hidden.value = select.selectedOptions[0]?.textContent || '';
}

function toggleBirthplaceFields(country) {
    const usePeruLocations = country === 'Perú';
    document.querySelectorAll('[data-peru-location]').forEach((select) => {
        const foreignInput = document.getElementById(`${select.id}-extranjero`);
        select.hidden = !usePeruLocations;
        select.disabled = !usePeruLocations || (select.dataset.location !== 'departamento' && !select.value);
        if (!foreignInput) return;
        foreignInput.hidden = usePeruLocations;
        foreignInput.disabled = usePeruLocations;
    });

    if (!usePeruLocations) {
        document.querySelectorAll('[name$="_nombre"]').forEach((field) => field.remove());
    }
}

function toggleForeignCountry(country) {
    const countryInput = document.getElementById('pais-extranjero');
    if (!countryInput) return;
    const isForeign = country === 'Otro';
    countryInput.hidden = !isForeign;
    countryInput.disabled = !isForeign;
    countryInput.required = isForeign;
}

function updateStudentCode() {
    const codeInput = document.getElementById('codigo-cepre');
    const semesterInput = document.getElementById('semestre');
    const studentCode = document.getElementById('codigo-alumno');
    if (!codeInput || !semesterInput || !studentCode) return;

    const code = codeInput.value;
    const year = String(new Date().getFullYear()).slice(-2);
    studentCode.value = code.length === 5
        ? `${year}${semesterInput.value}${code}`
        : 'Se generará al completar';
}

async function loadLocations(parentCode, select) {
    try {
        const response = await fetch(`/cepre_untels/public/api/ubigeos.php?padre=${encodeURIComponent(parentCode)}`);
        if (!response.ok) throw new Error('No se pudo cargar la ubicación.');
        const locations = await response.json();
        locations.forEach((location) => select.add(new Option(location.nombre, location.codigo)));
        select.disabled = locations.length === 0;
    } catch (error) {
        Swal.fire({ title: 'No se pudo cargar la ubicación', text: error.message, icon: 'error', confirmButtonColor: '#23313b' });
    }
}

