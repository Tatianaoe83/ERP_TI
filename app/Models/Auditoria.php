<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cabecera de una corrida de auditoría. Los totales se guardan congelados: recalcular
 * contra el inventario actual haría que una auditoría vieja dejara de reflejar el
 * momento en que se generó.
 */
class Auditoria extends Model
{
    use HasFactory;

    public $table = 'auditorias';

    protected $fillable = [
        'Folio',
        'generada_por',
        'generada_por_nombre',
        'generada_en',
        'total_equipos',
        'total_laptops',
        'total_pcs',
        'total_otros',
        'total_propios',
        'total_piratas',
        'licencias_auditadas',
        'total_licencias_auditadas',
    ];

    protected $casts = [
        'generada_en'         => 'datetime',
        'licencias_auditadas' => 'array',
    ];

    /**
     * Las corridas anteriores a la selección de licencias no guardan lista: en ellas
     * se revisó todo el catálogo.
     */
    public function auditoTodasLasLicencias(): bool
    {
        return $this->licencias_auditadas === null;
    }

    public function equipos()
    {
        return $this->hasMany(AuditoriaEquipo::class, 'auditoria_id');
    }
}
