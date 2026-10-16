<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    protected $table = 'obtenir';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['matricule', 'num_evaluation', 'note'];

    protected function casts(): array
    {
        return ['note' => 'decimal:2'];
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'matricule', 'matricule');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'num_evaluation', 'num_evaluation');
    }
}
