<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Programme extends Model
{
    protected $table = 'programmer';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'code_matiere';

    protected $fillable = ['code_matiere', 'code_filiere', 'code_niveau', 'coefficient'];

    protected function casts(): array
    {
        return ['coefficient' => 'decimal:2'];
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'code_matiere', 'code_matiere');
    }
}
