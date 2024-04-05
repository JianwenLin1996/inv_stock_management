<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory, SoftDeletes;
        
    protected $fillable = [
        'name',
        'description'
    ];
    
    public function itemLogs(): HasMany
    {
        return $this->hasMany(ItemLog::class);
    }
}
