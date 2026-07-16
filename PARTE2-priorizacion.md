# Parte 2 — Priorización y alcance para una semana

## Contexto

El cliente entregó requisitos escuetos y exige el máximo valor posible en producción en una semana, con un único desarrollador **junior** disponible para implementar. Este documento define qué entra en esa semana, qué queda fuera, y por qué — junto con las directrices técnicas necesarias para que el junior pueda ejecutarlo sin bloquearse.

**Importante:** este documento describe un **MVP funcional para la primera semana**, no la versión final del producto. Varias decisiones aceptan limitaciones conscientes (ver "Fuera de alcance") para maximizar valor en el tiempo disponible.

---

## 1. Ambigüedades resueltas

El pedido original ("Usuarios y asignación", "Catálogo de etiquetas", etc.) es intencionalmente abierto. Antes de que el junior empiece, se fija el alcance exacto de cada uno:

| Funcionalidad pedida | Interpretación elegida |
|---|---|
| Usuarios y asignación | Tabla `usuarios` simple (solo nombre), **sin login ni autenticación**. Se asigna un usuario a una tarea únicamente al crearla, igual que las categorías — no hay reasignación posterior. |
| Catálogo de etiquetas | Confirmado: "etiquetas" = las categorías ya existentes en el sistema (PHP/Javascript/CSS). Se construye un ABM para que dejen de estar fijas por seeder. |
| Estados de tarea | Reducido de un abanico abierto (iniciado/en curso/pausa/revisión/terminado...) a **dos valores fijos**: `pendiente` / `completada`. Sin reglas de transición. |
| Prioridad y fecha objetivo | Prioridad reducida a **booleano** (sí/no) en vez de niveles múltiples (alta/media/baja). Fecha objetivo como campo de fecha simple, opcional. |
| Comentarios en tareas | Reinterpretado como **campo de notas libre**, cargado una sola vez al crear la tarea (contexto, pasos a seguir). Se descarta la interpretación de "hilo de incidencias en el tiempo" (ver sección 3). |
| Historial de cambios | No aplica en esta iteración — depende de edición y autenticación, ninguna de las dos incluida (ver sección 3). |
| Búsqueda por texto | Búsqueda simple sobre nombre de tarea, usuario asignado y categoría, en un único campo. |

---

## 2. Qué entra en la semana

| Funcionalidad | ¿Entra? | Valor | Esfuerzo | Riesgo |
|---|---|---|---|---|
| Usuarios y asignación (alcance reducido) | ✅ Sí | Alto | Bajo | Cubierto por riesgo transversal (sección 3) |
| Catálogo de etiquetas | ✅ Sí | Alto | Bajo | Bajo — mitigado con validación al borrar |
| Estados de tarea (2 valores) | ✅ Sí | Alto | Bajo | Cubierto por riesgo transversal |
| Prioridad y fecha objetivo | ✅ Sí | Medio-alto | Bajo | Bajo |
| Comentarios (como notas) | ✅ Sí | Medio | Bajo | Bajo |
| Búsqueda por texto | ✅ Sí | Alto | Bajo | Medio — mitigado con directrices técnicas (sección 5) |
| Historial de cambios | ❌ No | — | — | Depende de funcionalidades no incluidas esta semana |

**Por qué las 6 primeras entran:** en todos los casos, la versión de alcance reducido reutiliza patrones que el sistema ya tiene (agregar un campo al formulario de creación existente, o replicar el ABM que ya existe para categorías) — bajo esfuerzo, alto valor.

**Por qué "Historial de cambios" no entra:** depende de dos capacidades explícitamente fuera de esta iteración — edición de tareas y autenticación de usuarios. Sin edición no hay "cambios" que registrar; sin login no hay forma confiable de saber quién los hizo.

---

## 3. Riesgo transversal

Ninguna funcionalidad de esta semana incluye autenticación ni permisos. Esto significa que **cualquier persona con acceso a la aplicación puede crear, asignar o cambiar el estado de cualquier tarea**, sin restricciones por usuario.

Se documenta una sola vez, como riesgo compartido por Usuarios/Asignación y Estados de tarea, en vez de repetirlo en cada una. Se acepta como limitación conocida para maximizar valor funcional en la primera semana. Quedaría resuelto si en una futura iteración se agrega un sistema de login.

---

## 4. Fuera de alcance de esta iteración

- **Autenticación y control de permisos** — alto esfuerzo y riesgo para un junior en una semana; no es crítico para validar el valor funcional del sistema.
- **Edición general de tareas** (cambiar nombre/categorías después de creadas) — el sistema actual solo soporta crear y borrar; se decide no expandir esto todavía.
- **Historial de cambios** — depende de las dos anteriores.
- **Hilo de comentarios con seguimiento en el tiempo** (incidencias que se van agregando) — requeriría una vista de detalle por tarea y edición post-creación, ninguna existente hoy.

Estas exclusiones son decisiones conscientes, no omisiones. Se recomienda evaluarlas en una segunda iteración, una vez validado el uso real del sistema con el cliente.

---

## 5. Directrices técnicas para el junior

### Usuarios y asignación
- Nueva tabla `usuarios` (solo `id`, `nombre`, timestamps).
- `tareas` gana una columna `usuario_id` (foreignId, nullable, sin `cascadeOnDelete` forzoso — a decidir si se borra un usuario con tareas asignadas).
- Un `<select>` más en el mismo formulario de creación de tareas, igual que las categorías.

### Catálogo de etiquetas
- Reutilizar el modelo `Categoria` ya existente — no se crea nada nuevo, solo pantallas de alta/baja.
- **Al borrar una etiqueta, validar antes si está en uso:**
  ```php
  if ($categoria->tareas()->exists()) {
      return response()->json([
          'message' => "No se puede eliminar: está asignada a {$categoria->tareas()->count()} tarea(s)."
      ], 422);
  }
  ```
  Mismo patrón que ya usa el sistema para la unicidad de nombres de tareas.

### Estados de tarea
- Columna `estado` en `tareas` (string o enum, con `default('pendiente')`).
- Un `<select>` con las dos opciones, editable inline en el listado (no requiere pantalla de edición completa).

### Prioridad y fecha objetivo
- Dos columnas nuevas en `tareas`: `prioritaria` (boolean, `default(false)`) y `fecha_objetivo` (date, `nullable()`).
- Se agregan al mismo formulario de creación existente.

### Comentarios (como notas)
- Una columna `notas` (texto largo, `nullable()`) en `tareas`. Se carga solo al crear, igual que el resto de los campos nuevos.

### Búsqueda por texto
- Se agrega sobre la misma consulta que ya arma el listado de tareas con categorías (con `with()`), sin crear una consulta aparte:
  ```php
  if ($texto) {
      $query->where(function ($sub) use ($texto) {
          $sub->where('nombre', 'like', "%{$texto}%")
              ->orWhereHas('usuario', fn ($u) => $u->where('nombre', 'like', "%{$texto}%"))
              ->orWhereHas('categorias', fn ($c) => $c->where('nombre', 'like', "%{$texto}%"));
      });
  }
  ```
  **Punto crítico a explicar al junior:** el bloque de búsqueda debe ir envuelto en su propio `where(function ($sub) {...})`. Si los `orWhere`/`orWhereHas` se agregan sueltos al mismo nivel que el filtro de categorías, el operador `OR` se combina mal con el `AND` del filtro existente — una tarea podría aparecer en los resultados solo por coincidir el texto, ignorando el filtro de categorías. Antes de dar por cerrada esta funcionalidad, **probar manualmente combinando categoría + texto + usuario** para confirmar que no se mezclan.

---

## Orden sugerido de implementación

1. Catálogo de etiquetas (reutiliza lo que ya existe, menor riesgo de bloqueo)
2. Usuarios y asignación
3. Estados de tarea
4. Prioridad, fecha objetivo y notas (mismos formularios, se pueden hacer juntos)
5. Búsqueda por texto (depende de que usuarios y categorías ya existan)
