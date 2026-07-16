<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Tarea;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    /**
     * Vista principal: lista de tareas + checkboxes de categorías.
     */
    public function index(Request $request)
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $tareas = $this->tareasFiltradas($request);

        return view('tareas.index', compact('categorias', 'tareas'));
    }

    /**
     * Endpoint AJAX: devuelve el listado (aplicando filtro de categorías si viene).
     * "Una sola consulta de Eloquent": with('categorias') evita el problema N+1.
     */
    public function buscar(Request $request)
    {
        return response()->json($this->tareasFiltradas($request));
    }

    private function tareasFiltradas(Request $request)
    {
        $categoriaIds = array_filter((array) $request->input('categorias', []));

        $query = Tarea::with('categorias')->latest();

        // Filtro AND: la tarea debe tener TODAS las categorías seleccionadas,
        // no solo alguna. Se encadena un whereHas por cada categoría elegida.
        foreach ($categoriaIds as $categoriaId) {
            $query->whereHas('categorias', function ($q) use ($categoriaId) {
                $q->where('categorias.id', $categoriaId);
            });
        }

        return $query->get();
    }

    /**
     * Crear tarea vía AJAX.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer', 'exists:categorias,id'],
        ], [
            'nombre.required' => 'El nombre de la tarea es obligatorio.',
            'categorias.*.exists' => 'Una de las categorías seleccionadas no es válida.',
        ]);

        $normalizado = mb_strtolower(trim($validated['nombre']));

        // Chequeo optimista para dar un mensaje rápido y amigable en el caso normal.
        if (Tarea::where('nombre_normalizado', $normalizado)->exists()) {
            return response()->json([
                'message' => 'Ya existe una tarea con ese nombre.',
                'errors' => ['nombre' => ['Ya existe una tarea con ese nombre.']],
            ], 422);
        }

        try {
            $tarea = Tarea::create(['nombre' => $validated['nombre']]);
        } catch (QueryException $e) {
            // Red de seguridad real contra condiciones de carrera: si dos peticiones
            // pasan el chequeo anterior casi al mismo tiempo, el índice unique de
            // "nombre_normalizado" en la base de datos frena la segunda inserción aquí.
            if ((int) $e->getCode() === 23000 || str_contains($e->getMessage(), '1062')) {
                return response()->json([
                    'message' => 'Ya existe una tarea con ese nombre.',
                    'errors' => ['nombre' => ['Ya existe una tarea con ese nombre.']],
                ], 422);
            }
            throw $e;
        }

        $tarea->categorias()->attach($validated['categorias'] ?? []);

        return response()->json($tarea->load('categorias'), 201);
    }

    /**
     * Borrado idempotente vía AJAX: borrar una tarea que ya no existe
     * (doble clic, petición repetida) se trata como éxito, nunca como error 500.
     */
    public function destroy($id)
    {
        Tarea::whereKey($id)->delete();

        return response()->json(['ok' => true]);
    }
}
