<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'is_purchased',
        'item_count',
        'total_item_price',
        'transaction_at',
    ];
    
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function itemLog():  HasOne
    {
        return $this->hasOne(ItemLog::class);
    }
}
