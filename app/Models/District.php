<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['division_id', 'name', 'bn_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function thanas()
    {
        return $this->hasMany(Thana::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
