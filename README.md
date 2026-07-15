# Gestor de tareas — Prueba técnica Netberry

Listado de tareas pendientes con creación, borrado y categorización (PHP, Javascript, CSS), sin recarga de página. Backend en Laravel 10, frontend en Blade + JavaScript nativo (sin librerías externas).

## Recursos externos utilizados

- **PHP 8.1 o superior**
- **Composer 2.x**
- **Servidor MySQL** (o MariaDB compatible) — se necesita acceso a un servidor en ejecución antes de instalar, la aplicación no lo incluye

No se requiere ningún otro servicio externo (no usa Redis, colas, ni servicios de terceros).

## Instalación

1. Clonar el repositorio y entrar a la carpeta del proyecto:
   ```bash
   cd prueba-netberry
   ```

2. Instalar las dependencias de PHP:
   ```bash
   composer install
   ```

3. Copiar el archivo de entorno de ejemplo y generar la clave de aplicación:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Crear una base de datos vacía en MySQL (el nombre debe coincidir con `DB_DATABASE` del paso 5):
   ```sql
   CREATE DATABASE prueba_netberry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. Editar el archivo `.env` con los datos de conexión a esa base de datos:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=prueba_netberry
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. Ejecutar las migraciones y el seeder (este último carga las 3 categorías fijas: PHP, Javascript, CSS):
   ```bash
   php artisan migrate --seed
   ```

## Ejecución

```bash
php artisan serve
```

La aplicación queda disponible en [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Funcionalidad implementada

- **Crear tarea**: nombre + una o varias categorías (checkboxes). Se guarda vía AJAX y aparece en el listado sin recargar la página.
- **Borrar tarea**: botón "Borrar" por fila, vía AJAX. El borrado es idempotente — repetir la petición sobre una tarea ya borrada (o un ID inexistente) responde `200 OK` en vez de un error.
- **Filtro por categorías**: al marcar varias categorías en el filtro, se listan únicamente las tareas que tienen **todas** las categorías seleccionadas (no basta con tener alguna).
- **Unicidad normalizada**: no se permiten dos tareas con el mismo nombre si solo difieren en mayúsculas/minúsculas o espacios al inicio/final. Validado tanto en la aplicación (mensaje inmediato) como con una restricción `unique` en base de datos (protección real ante condiciones de carrera).
- **Listado con categorías en una sola consulta Eloquent**: se usa `with('categorias')` (eager loading) para evitar el problema N+1.

## Estructura relevante

- `app/Models/Tarea.php`, `app/Models/Categoria.php` — modelos y relación `belongsToMany`
- `app/Http/Controllers/TareaController.php` — lógica de listado/filtro, creación y borrado
- `database/migrations/` — esquema: `tareas`, `categorias`, tabla pivote `categoria_tarea`
- `database/seeders/CategoriaSeeder.php` — carga las categorías fijas
- `resources/views/tareas/index.blade.php` — vista única con formulario, listado y JavaScript de la parte AJAX
