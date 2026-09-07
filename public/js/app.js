document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.enrollment-form');
    const previews = {
        foto: document.querySelector('.preview--photo'),
        documento: document.querySelector('.preview--document'),
        declaracion_jurada: document.querySelector('.preview--declaracion_jurada')
    };

    document.querySelectorAll('input[type="file"]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (file && file.size > 5 * 1024 * 1024) {
                input.value = ''; // clear the input
                resetPreview(input.id);
                Swal.fire({
                    title: 'Archivo demasiado grande',
                    text: 'El archivo no debe pesar más de 5MB.',
                    icon: 'error',
                    confirmButtonColor: '#23313b'
                });
                return;
            }
            showFilePreview(input);
        });
    });

    document.querySelectorAll('[data-remove-file]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.removeFile);
            if (!input) return;
            input.value = '';
            resetPreview(input.id);
        });
    });

    document.querySelectorAll('[data-preview]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.preview);
            const file = input?.files?.[0];
            if (!input) return;
            if (!file) {
                input.click();
                return;
            }

            window.open(URL.createObjectURL(file), '_blank', 'noopener,noreferrer');
        });
    });

    document.getElementById('download-form')?.addEventListener('click', () => window.print());

    const dniInput = document.getElementById('numero-documento');
    const dniStatus = document.getElementById('dni-status');
    let dniRequest = null;
    dniInput?.addEventListener('input', async () => {
        dniInput.value = dniInput.value.replace(/\D/g, '').slice(0, 8);
        if (dniInput.value.length !== 8) {
            dniStatus.textContent = '';
            return;
        }

        dniStatus.textContent = 'Consultando...';
        dniRequest?.abort();
        dniRequest = new AbortController();
        try {
            const response = await fetch(`/cepre_untels/public/api/dni.php?numero=${encodeURIComponent(dniInput.value)}`, {
                signal: dniRequest.signal,
                headers: { Accept: 'application/json' }
            });
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch {
                throw new Error('El servidor devolvió una respuesta no válida.');
            }
            if (!response.ok) throw new Error(result.error || 'No se pudo consultar el DNI.');

            document.getElementById('apellido-paterno').value = result.apellido_paterno || '';
            document.getElementById('apellido-materno').value = result.apellido_materno || '';
            document.getElementById('nombres').value = result.nombres || '';
            dniStatus.textContent = 'Datos encontrados.';
        } catch (error) {
            if (error.name === 'AbortError') return;
            dniStatus.textContent = error.message;
        }
    });

    const guardianDniInput = document.getElementById('apoderado-documento');
    const guardianDocumentType = document.getElementById('apoderado-tipo-documento');
    const guardianDniStatus = document.getElementById('apoderado-dni-status');
    const guardianRelationship = document.getElementById('apoderado-parentesco');
    const otherGuardianRelationshipWrap = document.getElementById('apoderado-parentesco-otro-wrap');
    const otherGuardianRelationship = document.getElementById('apoderado-parentesco-otro');
    const guardianPaternalSurname = document.getElementById('apoderado-apellido-paterno');
    const guardianMaternalSurname = document.getElementById('apoderado-apellido-materno');
    const guardianNames = document.getElementById('apoderado-nombres');
    const guardianFullName = document.getElementById('apoderado-nombres-completos');
    let guardianDniRequest = null;

    const syncGuardianName = () => {
        if (!guardianFullName) return;
        guardianFullName.value = [
            guardianPaternalSurname?.value,
            guardianMaternalSurname?.value,
            guardianNames?.value
        ].filter(Boolean).join(' ');
    };

    [guardianPaternalSurname, guardianMaternalSurname, guardianNames].forEach((input) => {
        input?.addEventListener('input', syncGuardianName);
    });

    const toggleOtherGuardianRelationship = () => {
        const isOther = guardianRelationship?.value === 'otro';
        if (otherGuardianRelationshipWrap) otherGuardianRelationshipWrap.hidden = !isOther;
        if (otherGuardianRelationship) {
            otherGuardianRelationship.required = isOther;
            if (!isOther) otherGuardianRelationship.value = '';
        }
    };

    guardianRelationship?.addEventListener('change', toggleOtherGuardianRelationship);
    toggleOtherGuardianRelationship();

    guardianDniInput?.addEventListener('input', async () => {
        guardianDniInput.value = guardianDniInput.value.replace(/\D/g, '').slice(0, 8);
        if (guardianDocumentType?.value !== 'DNI' || guardianDniInput.value.length !== 8) {
            if (guardianDniStatus) guardianDniStatus.textContent = '';
            return;
        }

        if (guardianDniStatus) guardianDniStatus.textContent = 'Consultando...';
        guardianDniRequest?.abort();
        guardianDniRequest = new AbortController();
        try {
            const response = await fetch(`/cepre_untels/public/api/dni.php?numero=${encodeURIComponent(guardianDniInput.value)}`, {
                signal: guardianDniRequest.signal,
                headers: { Accept: 'application/json' }
            });
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch {
                throw new Error('El servidor devolvió una respuesta no válida.');
            }
            if (!response.ok) throw new Error(result.error || 'No se pudo consultar el DNI.');

            guardianPaternalSurname.value = result.apellido_paterno || '';
            guardianMaternalSurname.value = result.apellido_materno || '';
            guardianNames.value = result.nombres || '';
            syncGuardianName();
            if (guardianDniStatus) guardianDniStatus.textContent = 'Datos encontrados.';
        } catch (error) {
            if (error.name === 'AbortError') return;
            if (guardianDniStatus) guardianDniStatus.textContent = error.message;
        }
    });

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

    setupForeignLocationSet({
        countryId: 'pais-actual',
        foreignCountryId: 'pais-actual-extranjero',
        stateId: 'estado-actual-extranjero',
        cityId: 'ciudad-actual-extranjero',
        countryWrapId: 'pais-actual-extranjero-wrap',
        stateWrapId: 'estado-actual-extranjero-wrap',
        cityWrapId: 'ciudad-actual-extranjero-wrap',
        peruIds: ['departamento-actual', 'provincia-actual', 'distrito-actual']
    });
    setupForeignLocationSet({
        countryId: 'pais-estudios',
        foreignCountryId: 'pais-estudios-extranjero',
        stateId: 'estado-estudios-extranjero',
        cityId: 'ciudad-estudios-extranjero',
        countryWrapId: 'pais-estudios-extranjero-wrap',
        stateWrapId: 'estado-estudios-extranjero-wrap',
        cityWrapId: 'ciudad-estudios-extranjero-wrap',
        peruIds: ['departamento-estudios', 'provincia-estudios', 'distrito-estudios']
    });
    setupForeignLocationSet({
        countryId: 'pais',
        foreignCountryId: 'pais-nacimiento-extranjero',
        stateId: 'estado-nacimiento-extranjero',
        cityId: 'ciudad-nacimiento-extranjero',
        countryWrapId: 'pais-nacimiento-extranjero-wrap',
        stateWrapId: 'estado-nacimiento-extranjero-wrap',
        cityWrapId: 'ciudad-nacimiento-extranjero-wrap',
        peruIds: ['departamento-nacimiento', 'provincia-nacimiento', 'distrito-nacimiento']
    });

    const disabilitySelect = document.getElementById('discapacidad');
    const disabilitySection = document.getElementById('seccion-discapacidad');
    const disabilityType = document.getElementById('tipo-discapacidad');
    const otherDisability = document.getElementById('otro-tipo-discapacidad');
    const toggleDisability = () => {
        const enabled = disabilitySelect?.value === '1';
        if (!disabilitySection) return;
        disabilitySection.hidden = !enabled;
        disabilitySection.querySelectorAll('select, input, textarea').forEach((field) => {
            field.required = enabled && field.id !== 'otro-tipo-especificar';
            if (!enabled) field.value = field.tagName === 'SELECT' ? '0' : '';
        });
        if (!enabled) disabilityType.value = '';
        toggleOtherDisability();
    };
    const toggleOtherDisability = () => {
        if (!otherDisability) return;
        const enabled = disabilitySelect?.value === '1' && disabilityType?.value === 'otra';
        otherDisability.hidden = !enabled;
        const field = document.getElementById('otro-tipo-especificar');
        if (field) { field.required = enabled; if (!enabled) field.value = ''; }
    };
    disabilitySelect?.addEventListener('change', toggleDisability);
    disabilityType?.addEventListener('change', toggleOtherDisability);
    toggleDisability();

    const certificateSelect = document.getElementById('certificado-discapacidad');
    const certificateSection = document.getElementById('adjunto-certificado-discapacidad');
    const certificateInput = document.getElementById('archivo-certificado-discapacidad');
    const toggleCertificate = () => {
        const enabled = disabilitySelect?.value === '1' && certificateSelect?.value === '1';
        if (!certificateSection || !certificateInput) return;
        certificateSection.hidden = !enabled;
        certificateInput.disabled = !enabled;
        certificateInput.required = enabled && certificateInput.dataset.hasExisting !== 'true';
        if (!enabled) certificateInput.value = '';
    };
    certificateSelect?.addEventListener('change', toggleCertificate);
    disabilitySelect?.addEventListener('change', toggleCertificate);
    toggleCertificate();

    const discoverySelect = document.getElementById('como-se-entero-cepre');
    const otherDiscovery = document.getElementById('otro-como-se-entero');
    const otherDiscoveryInput = document.getElementById('especificar-como-se-entero');
    const toggleOtherDiscovery = () => {
        const enabled = discoverySelect?.value === 'otro';
        if (!otherDiscovery || !otherDiscoveryInput) return;
        otherDiscovery.hidden = !enabled;
        otherDiscoveryInput.disabled = !enabled;
        otherDiscoveryInput.required = enabled;
        if (!enabled) otherDiscoveryInput.value = '';
    };
    discoverySelect?.addEventListener('change', toggleOtherDiscovery);
    toggleOtherDiscovery();

    const turnSelect = document.getElementById('turno');
    const codeInput = document.getElementById('codigo-cepre');
    const formTitle = document.getElementById('titulo-ficha');
    const updateTurnPresentation = () => {
        if (!turnSelect || !codeInput) return;
        const selectedTurn = turnSelect.options[turnSelect.selectedIndex]?.textContent || '';
        const isSchool = selectedTurn.toLocaleLowerCase().includes('escolar');
        const code = isSchool ? codeInput.dataset.codigoEscolar : codeInput.dataset.codigoRegular;
        codeInput.value = code || '';
        if (formTitle) {
            const number = formTitle.dataset.numeroFicha || '';
            formTitle.textContent = `FICHA DE MATRÍCULA N.° ${number} - ${isSchool ? 'TURNO ESCOLAR' : 'TURNO MAÑANA/TARDE'}`;
        }
    };
    turnSelect?.addEventListener('change', updateTurnPresentation);
    updateTurnPresentation();

    document.getElementById('fecha-nacimiento')?.addEventListener('change', (event) => {
        const birthDate = new Date(`${event.target.value}T00:00:00`);
        if (!event.target.value || Number.isNaN(birthDate.getTime())) return;

        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const birthdayHasNotHappened = today.getMonth() < birthDate.getMonth()
            || (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate());
        if (birthdayHasNotHappened) age -= 1;

        if (age < 18) {
            toggleAcademicFields(true);
            Swal.fire({
                title: 'Upps ! eres menor de edad',
                text: 'Debes brindarnos los datos de tus apoderados.',
                icon: 'info',
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#23313b'
            }).then((result) => {
                if (!result.isConfirmed) return;
                const guardianSection = document.getElementById('datos-apoderado');
                guardianSection.hidden = false;
                guardianSection.querySelectorAll('input').forEach((field) => { field.required = true; });
                guardianSection.querySelector('input')?.focus();
            });
        } else {
            toggleAcademicFields(false);
        }
    });

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
            resetConditionalFields();
            Swal.fire({ title: 'Ficha limpiada', text: 'Los datos ingresados fueron eliminados.', icon: 'success', confirmButtonColor: '#23313b' });
        });
    });

    document.getElementById('new-enrollment')?.addEventListener('click', () => {
        const hasData = [...form.querySelectorAll('input, select, textarea')]
            .some((field) => field.type === 'file' ? field.files.length > 0 : field.value !== '');
        const startNew = () => {
            form.reset();
            resetPreviews();
            resetConditionalFields();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
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
            .filter((field) => field.required && !field.disabled && field.files.length === 0)
            .map((field) => {
                if (field.id === 'foto') return 'la foto carnet';
                if (field.id === 'declaracion_jurada') return 'la declaración jurada';
                return 'la copia del documento';
            });
        if (missingFiles.length > 0) {
            event.preventDefault();
            Swal.fire({
                title: 'Faltan archivos',
                text: `Adjunte ${missingFiles.join(', ').replace(/,([^,]*)$/, ' y$1')} para guardar la matrícula.`,
                icon: 'warning',
                confirmButtonColor: '#23313b'
            });
        }
    });

    function resetPreviews() {
        Object.keys(previews).forEach((fileId) => resetPreview(fileId));
    }

    function resetPreview(fileId) {
        const preview = previews[fileId];
        if (!preview) return;
        const labels = { foto: 'Foto', documento: 'DNI', declaracion_jurada: 'DJ' };
        const removeButton = preview.querySelector('[data-remove-file]');
        preview.replaceChildren(document.createTextNode(labels[fileId] || 'Archivo'));
        if (removeButton) {
            removeButton.hidden = true;
            preview.appendChild(removeButton);
        }
    }
});

function toggleAcademicFields(isMinor) {
    document.querySelectorAll('[data-academic-adult]').forEach((field) => {
        field.hidden = isMinor;
        field.style.display = isMinor ? 'none' : '';
    });
}

function resetConditionalFields() {
    toggleAcademicFields(false);
    const guardianSection = document.getElementById('datos-apoderado');
    if (!guardianSection) return;
    guardianSection.querySelectorAll('input').forEach((field) => { field.required = false; });
}

function showFilePreview(input) {
    const file = input.files[0];
    const editPhotoPreview = input.id === 'foto' ? document.getElementById('edit-photo-preview') : null;
    if (editPhotoPreview) {
        if (!file) return;
        editPhotoPreview.src = URL.createObjectURL(file);
        editPhotoPreview.hidden = false;
        return;
    }

    let previewClass = '.preview--document';
    if (input.id === 'foto') previewClass = '.preview--photo';
    if (input.id === 'declaracion_jurada') previewClass = '.preview--declaracion_jurada';

    const preview = document.querySelector(previewClass);
    if (!file || !preview) return;
    const removeButton = preview.querySelector('[data-remove-file]');

    const setPreviewContent = (content) => {
        preview.replaceChildren(content);
        if (removeButton) {
            removeButton.hidden = false;
            preview.appendChild(removeButton);
        }
    };

    if (file.type.startsWith('image/')) {
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);

        let altText = 'Vista previa del documento';
        if (input.id === 'foto') altText = 'Vista previa de la foto carnet';
        if (input.id === 'declaracion_jurada') altText = 'Vista previa de la declaración jurada';
        image.alt = altText;

        image.onload = () => URL.revokeObjectURL(image.src);
        setPreviewContent(image);
        return;
    }

    if (file.type === 'application/pdf') {
        const documentPreview = document.createElement('iframe');
        documentPreview.src = URL.createObjectURL(file);
        documentPreview.title = 'Vista previa del documento PDF';
        setPreviewContent(documentPreview);
        return;
    }

    const label = document.createElement('span');
    label.textContent = `${file.name} (${formatFileSize(file.size)})`;
    setPreviewContent(label);
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

function setupForeignLocationSet(config) {
    const country = document.getElementById(config.countryId);
    const foreignCountry = document.getElementById(config.foreignCountryId);
    const state = document.getElementById(config.stateId);
    const city = document.getElementById(config.cityId);
    if (!country || !foreignCountry || !state || !city) return;

    const department = document.getElementById(config.peruIds[0]);
    config.departmentOptions = department
        ? Array.from(department.options).map((option) => ({ value: option.value, text: option.textContent }))
        : [];

    loadForeignCountries(foreignCountry);
    country.addEventListener('change', () => toggleForeignLocationSet(config));
    foreignCountry.addEventListener('change', async () => {
        resetSelect(state, 'Seleccione estado o región');
        resetSelect(city, 'Seleccione ciudad o municipio');
        if (foreignCountry.value) await loadForeignLocations('estados', state, { pais: foreignCountry.value });
    });
    state.addEventListener('change', async () => {
        resetSelect(city, 'Seleccione ciudad o municipio');
        if (state.value) await loadForeignLocations('ciudades', city, { pais: foreignCountry.value, estado: state.value });
    });
    toggleForeignLocationSet(config);
}

function toggleForeignLocationSet(config) {
    const country = document.getElementById(config.countryId);
    const foreignCountry = document.getElementById(config.foreignCountryId);
    const state = document.getElementById(config.stateId);
    const city = document.getElementById(config.cityId);
    const isPeru = country?.value === 'Perú';

    config.peruIds.forEach((id, index) => {
        const select = document.getElementById(id);
        if (!select) return;
        const field = select.closest('.field');
        if (field) field.hidden = !isPeru;
        if (isPeru && index === 0 && select.options.length <= 1 && config.departmentOptions?.length) {
            select.replaceChildren(...config.departmentOptions.map((option) => new Option(option.text, option.value)));
        }
        select.disabled = !isPeru || (index > 0 && !select.value);
        if (!isPeru) {
            if (index === 0) {
                select.value = '';
            } else {
                resetSelect(select, ['Seleccione departamento', 'Seleccione provincia', 'Seleccione distrito'][index]);
            }
        }
    });

    ['countryWrapId', 'stateWrapId', 'cityWrapId'].forEach((key) => {
        const wrapper = document.getElementById(config[key]);
        if (wrapper) wrapper.hidden = isPeru;
    });
    foreignCountry.disabled = isPeru;
    state.disabled = isPeru || !foreignCountry.value;
    city.disabled = isPeru || !state.value;
    foreignCountry.required = !isPeru;
    state.required = !isPeru;
    city.required = !isPeru;
}

async function loadForeignCountries(select) {
    try {
        const response = await fetch('/cepre_untels/public/api/extranjeras.php?operacion=paises');
        if (!response.ok) throw new Error('No se pudieron cargar los países.');
        const countries = await response.json();
        countries.forEach((country) => {
            if (country.nombre.toLocaleLowerCase() !== 'perú') {
                select.add(new Option(country.nombre, country.codigo));
            }
        });
    } catch (error) {
        Swal.fire({ title: 'No se pudieron cargar los países', text: error.message, icon: 'error', confirmButtonColor: '#23313b' });
    }
}

async function loadForeignLocations(operation, select, parameters) {
    try {
        const query = new URLSearchParams({ operacion: operation, ...parameters });
        const response = await fetch(`/cepre_untels/public/api/extranjeras.php?${query}`);
        if (!response.ok) {
            throw new Error('No hay ubicaciones locales disponibles para la selección.');
        }
        const locations = await response.json();
        if (!Array.isArray(locations)) throw new Error('La respuesta de ubicaciones no es válida.');
        locations.forEach((location) => select.add(new Option(location.nombre, location.codigo)));
        select.disabled = locations.length === 0;
    } catch (error) {
        Swal.fire({ title: 'No se pudo cargar la ubicación', text: error.message, icon: 'error', confirmButtonColor: '#23313b' });
    }
}
