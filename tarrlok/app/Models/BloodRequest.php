<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BloodRequest extends Model
{
    protected $fillable = [
        'request_code',
        'requesting_hospital_id',
        'fulfilling_hospital_id',
        'blood_group',
        'quantity',
        'urgency',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'fulfilled_by',
        'fulfilled_at',
        'reversed_by',
        'reversed_at',
        'reverse_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BloodRequest $request) {
            if (! $request->request_code) {
                $request->request_code = 'REQ-'.str_pad((string) (static::max('id') + 1), 4, '0', STR_PAD_LEFT).'-'.strtoupper(substr(md5(uniqid('', true)), 0, 1));
            }
        });
    }

    public function requestingHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'requesting_hospital_id');
    }

    public function fulfillingHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'fulfilling_hospital_id');
    }

    public function bloodUnits(): BelongsToMany
    {
        return $this->belongsToMany(BloodUnit::class, 'blood_request_unit');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function fulfilledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function isActionable(): bool
    {
        return in_array($this->status, ['pending', 'approved'], true);
    }

    public function availableStockAt(Hospital $hospital): int
    {
        return $hospital->availableUnitsCount($this->blood_group);
    }

    /**
     * Cleared units still free after other approved (not yet issued) requests.
     */
    public function freeStockAt(Hospital $hospital): int
    {
        $onHand = $this->availableStockAt($hospital);

        $reserved = static::query()
            ->where('fulfilling_hospital_id', $hospital->id)
            ->where('blood_group', $this->blood_group)
            ->where('status', 'approved')
            ->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))
            ->sum('quantity');

        return max(0, $onHand - (int) $reserved);
    }

    public function hasSufficientStockAt(Hospital $hospital): bool
    {
        return $this->freeStockAt($hospital) >= $this->quantity;
    }

    public function stockShortfallAt(Hospital $hospital): int
    {
        return max(0, $this->quantity - $this->freeStockAt($hospital));
    }

    /**
     * @return list<array{label: string, detail: string, at: ?\Illuminate\Support\Carbon}>
     */
    public function auditTrail(): array
    {
        $events = [];

        $events[] = [
            'label' => 'Requested',
            'detail' => ($this->requestingHospital?->name ?? 'Requesting hospital').' asked for '.$this->quantity.' '.$this->blood_group,
            'at' => $this->created_at,
        ];

        if ($this->approved_at) {
            $events[] = [
                'label' => 'Approved',
                'detail' => ($this->approvedByUser?->name ?? 'Hospital staff').' approved the request',
                'at' => $this->approved_at,
            ];
        }

        if ($this->reversed_at) {
            $events[] = [
                'label' => 'Reversed',
                'detail' => ($this->reversedByUser?->name ?? 'Hospital staff')
                    .($this->reverse_reason ? ' — '.$this->reverse_reason : ' returned the request to pending'),
                'at' => $this->reversed_at,
            ];
        }

        if ($this->rejected_at) {
            $events[] = [
                'label' => str_starts_with((string) $this->rejection_reason, 'Cancelled')
                    ? 'Cancelled'
                    : 'Rejected',
                'detail' => ($this->rejectedByUser?->name ?? 'Hospital staff')
                    .($this->rejection_reason ? ' — '.$this->rejection_reason : ''),
                'at' => $this->rejected_at,
            ];
        } elseif ($this->status === 'rejected') {
            $events[] = [
                'label' => 'Rejected / cancelled',
                'detail' => $this->rejection_reason ?: 'Closed without further detail',
                'at' => $this->updated_at,
            ];
        }

        if ($this->fulfilled_at) {
            $events[] = [
                'label' => 'Issued',
                'detail' => ($this->fulfilledByUser?->name ?? 'Hospital staff').' transferred '.$this->quantity.' unit(s)',
                'at' => $this->fulfilled_at,
            ];
        }

        usort($events, function (array $a, array $b) {
            $aTime = $a['at']?->getTimestamp() ?? 0;
            $bTime = $b['at']?->getTimestamp() ?? 0;

            return $aTime <=> $bTime;
        });

        return $events;
    }
}
