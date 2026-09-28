<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DealerTarget extends Model
{
    protected $fillable = [
        'dealer_id', 'month', 'amount', 'note', 'set_by_type', 'set_by_id',
        'achieved_amount', 'settlement_note', 'settled_at', 'settled_by',
    ];

    protected $casts = [
        'month' => 'date',
        'amount' => 'decimal:2',
        'achieved_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function settledByUser()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    /** Parses a 'Y-m' month input (falls back to the current month) to 'Y-m-01'. */
    public static function monthFrom(?string $input): string
    {
        if ($input && preg_match('/^\d{4}-\d{2}$/', $input)) {
            return Carbon::createFromFormat('Y-m-d', $input . '-01')->toDateString();
        }

        return now()->startOfMonth()->toDateString();
    }

    public static function currentMonth(): string
    {
        return now()->startOfMonth()->toDateString();
    }
}
