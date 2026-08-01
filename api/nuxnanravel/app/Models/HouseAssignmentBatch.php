<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseAssignmentBatch extends Model
{
    protected $table = 'house_assignment_batches';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = ['options' => 'array', 'summary' => 'array', 'committed_at' => 'datetime', 'undone_at' => 'datetime'];

    public function rows(): HasMany
    {
        return $this->hasMany(HouseAssignmentRow::class, 'batch_id');
    }

    public function isUndoable(): bool
    {
        return $this->status === 'committed' && $this->committed_at?->copy()->addDay()->isFuture();
    }
}
