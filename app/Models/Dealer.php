<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class Dealer extends Authenticatable
{
    public const LEVELS = ['district' => 'District Dealer', 'thana' => 'Thana Dealer', 'union' => 'Union Dealer'];

    protected $fillable = [
        'name', 'phone', 'email', 'password', 'photo', 'nid_number', 'nid_front', 'nid_back', 'bank_slip',
        'address', 'district_id', 'thana_id', 'union_id', 'level', 'status', 'admin_note',
        'created_by_dealer_id', 'approved_at', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function thana()
    {
        return $this->belongsTo(Thana::class);
    }

    public function union()
    {
        return $this->belongsTo(Union::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Dealer::class, 'created_by_dealer_id');
    }

    public function targets()
    {
        return $this->hasMany(DealerTarget::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /** Target for a month ('Y-m-01'), from the loaded `targets` relation. */
    public function targetFor(string $month): ?DealerTarget
    {
        return $this->targets->first(fn ($t) => $t->month->toDateString() === $month);
    }

    public function levelLabel(): string
    {
        return self::LEVELS[$this->level] ?? 'Not assigned';
    }

    public function locationLabel(): string
    {
        return collect([$this->union?->name, $this->thana?->name, $this->district?->name])->filter()->implode(', ');
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'approved' => 'bg-green-100 text-green-700',
            'pending' => 'bg-yellow-100 text-yellow-700',
            'rejected' => 'bg-red-100 text-red-700',
            'suspended' => 'bg-gray-200 text-gray-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    /**
     * The dealers this dealer manages one level down: a district dealer manages the thana
     * dealers in its district, a thana dealer the union dealers in its thana.
     */
    public function subordinates(): Builder
    {
        $query = static::query()->approved();

        return match ($this->level) {
            'district' => $query->where('level', 'thana')->where('district_id', $this->district_id),
            'thana' => $query->where('level', 'union')->where('thana_id', $this->thana_id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function manages(Dealer $other): bool
    {
        return $this->isApproved() && $this->subordinates()->whereKey($other->id)->exists();
    }

    /**
     * Another approved dealer already holding this district/thana slot, if any. Union
     * level allows any number of dealers, so it never conflicts.
     */
    public static function levelConflict(?string $level, ?int $districtId, ?int $thanaId, ?int $exceptId = null): ?self
    {
        $query = static::approved()->where('level', $level)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        return match ($level) {
            'district' => $query->where('district_id', $districtId)->first(),
            'thana' => $query->where('thana_id', $thanaId)->first(),
            default => null,
        };
    }

    /** Normalises +8801XXXXXXXXX / 8801… / 01… to the 11-digit local form. */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return str_starts_with($digits, '880') ? substr($digits, 2) : $digits;
    }
}
