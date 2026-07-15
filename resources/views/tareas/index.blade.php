<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gestor de tareas</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, Segoe UI, Arial, sans-serif;
            max-width: 720px;
            margin: 40px auto;
            padding: 0 16px;
            color: #1f2937;
        }
        h1 { font-size: 22px; margin-bottom: 20px; }

        .form-nueva { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; flex-wrap: wrap; }
        .form-nueva input[type=text] {
            flex: 1; min-width: 200px; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px;
        }
        .form-nueva button {
            padding: 8px 16px; border: none; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer;
        }
        .form-nueva button:hover { background: #1d4ed8; }

        .checkboxes { display: flex; gap: 14px; flex-wrap: wrap; }
        .checkboxes label { font-size: 14px; display: flex; align-items: center; gap: 4px; }

        .error-nombre { color: #dc2626; font-size: 13px; margin: 4px 0 12px; min-height: 16px; }

        fieldset.filtro { margin: 24px 0; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; }
        fieldset.filtro legend { font-size: 13px; color: #6b7280; padding: 0 6px; }

        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 14px; }
        th { color: #6b7280; font-weight: 600; font-size: 12px; text-transform: uppercase; }

        .badge {
            display: inline-block; background: #eef2ff; color: #4338ca; border-radius: 4px;
            padding: 2px 8px; font-size: 12px; margin-right: 4px;
        }
        .btn-borrar {
            border: none; background: none; color: #dc2626; cursor: pointer; font-size: 13px;
        }
        .btn-borrar:hover { text-decoration: underline; }

        #sin-tareas { color: #9ca3af; padding: 16px 0; text-align: center; font-size: 14px; }
    </style>
</head>
<body>

    <h1>Gestor de tareas</h1>

    <form id="form-nueva-tarea" class="form-nueva">
        <input type="text" name="nombre" id="input-nombre" placeholder="Nueva tarea..." required>
        <div class="checkboxes">
            @foreach ($categorias as $categoria)
                <label>
                    <input type="checkbox" name="categorias[]" value="{{ $categoria->id }}">
                    {{ $categoria->nombre }}
                </label>
            @endforeach
        </div>
        <button type="submit">Añadir</button>
    </form>
    <div class="error-nombre" id="error-nombre"></div>

    <fieldset class="filtro">
        <legend>Filtrar por categoría</legend>
        <div class="checkboxes" id="filtro-categorias">
            @foreach ($categorias as $categoria)
                <label>
                    <input type="checkbox" name="filtro[]" value="{{ $categoria->id }}">
                    {{ $categoria->nombre }}
                </label>
            @endforeach
        </div>
    </fieldset>

    <table>
        <thead>
            <tr>
                <th>Tarea</th>
                <th>Categorías</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-tareas">
            @foreach ($tareas as $tarea)
                <tr data-id="{{ $tarea->id }}">
                    <td>{{ $tarea->nombre }}</td>
                    <td>
                        @foreach ($tarea->categorias as $categoria)
                            <span class="badge">{{ $categoria->nombre }}</span>
                        @endforeach
                    </td>
                    <td><button class="btn-borrar" data-id="{{ $tarea->id }}">Borrar</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div id="sin-tareas" style="{{ $tareas->isEmpty() ? '' : 'display:none' }}">No hay tareas todavía.</div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const form = document.getElementById('form-nueva-tarea');
        const inputNombre = document.getElementById('input-nombre');
        const errorNombre = document.getElementById('error-nombre');
        const tabla = document.getElementById('tabla-tareas');
        const sinTareas = document.getElementById('sin-tareas');
        const filtroCategorias = document.getElementById('filtro-categorias');

        function filaHtml(tarea) {
            const badges = tarea.categorias.map(c => `<span class="badge">${c.nombre}</span>`).join('');
            return `
                <tr data-id="${tarea.id}">
                    <td>${tarea.nombre}</td>
                    <td>${badges}</td>
                    <td><button class="btn-borrar" data-id="${tarea.id}">Borrar</button></td>
                </tr>
            `;
        }

        function actualizarEstadoVacio() {
            sinTareas.style.display = tabla.children.length ? 'none' : '';
        }

        function renderizarTareas(tareas) {
            tabla.innerHTML = tareas.map(filaHtml).join('');
            actualizarEstadoVacio();
        }

        // Crear tarea sin recargar la página
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            errorNombre.textContent = '';

            const formData = new FormData(form);

            const respuesta = await fetch('/tareas', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                errorNombre.textContent = datos.message || 'No se pudo crear la tarea.';
                return;
            }

            tabla.insertAdjacentHTML('afterbegin', filaHtml(datos));
            actualizarEstadoVacio();
            form.reset();
        });

        // Borrar tarea sin recargar la página (idempotente: si ya no existe, igual se quita de la vista)
        tabla.addEventListener('click', async function (e) {
            if (!e.target.classList.contains('btn-borrar')) return;

            const boton = e.target;
            const id = boton.dataset.id;
            boton.disabled = true;

            await fetch(`/tareas/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });

            boton.closest('tr').remove();
            actualizarEstadoVacio();
        });

        // Filtro por categorías (AND: deben cumplirse todas las seleccionadas)
        filtroCategorias.addEventListener('change', async function () {
            const seleccionadas = [...filtroCategorias.querySelectorAll('input:checked')].map(cb => cb.value);

            const params = new URLSearchParams();
            seleccionadas.forEach(id => params.append('categorias[]', id));

            const respuesta = await fetch(`/tareas/buscar?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });

            renderizarTareas(await respuesta.json());
        });
    </script>

</body>
</html>
