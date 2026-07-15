<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tarea extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'nombre_normalizado'];

    protected static function booted(): void
    {
        static::saving(function (Tarea $tarea) {
            $tarea->nombre_normalizado = mb_strtolower(trim($tarea->nombre));
        });
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class);
    }
}
