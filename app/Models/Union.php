<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Union extends Model
{
    protected $fillable = ['thana_id', 'name', 'bn_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function thana()
    {
        return $this->belongsTo(Thana::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
