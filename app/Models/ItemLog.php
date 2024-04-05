<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mavinoo\Batch\Traits\HasBatch;

class ItemLog extends Model
{
    use HasFactory, SoftDeletes, HasBatch;
    
    protected $fillable = [
        'item_id',
        'transaction_id',
        'total_stock',
        'total_value',
        'cost_per_item',
        'logged_at',
    ];
    
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
