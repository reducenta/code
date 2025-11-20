<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hold extends Model
{
    public const STATUS_HELD = 'held';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'slot_id',
        'status',
        'idempotency_key',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'slot_id' => 'int',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }
}
