<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\HybridRelations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Contract extends Model
{
    use HasFactory, HybridRelations;

    protected $table = 'contracts';
    protected $primaryKey = 'contract_id';

    protected $fillable = [
        'category_id',
        'supplier_id',
        'approval_status_id',
        'workflow_status_id',
        'bp_name',
        'sbu_number',
        'item_code',
        'description',
        'serial_number',
        'region_id',
        'start_date',
        'end_date',
        'created_by',
        'prs_activity_id',
    ];

    protected $casts = [
        'bp_name' => EncryptedCast::class,
        'description' => EncryptedCast::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'prs_activity_id' => 'integer',
    ];

    protected static function booted()
    {
        static::deleting(function ($contract) {
            $associatedDocs = Document::where('contract_id', $contract->contract_id)->get();
            foreach ($associatedDocs as $doc) {
                if ($doc->file_path) {
                    $disk = config('filesystems.default', 'local');
                    \Illuminate\Support\Facades\Storage::disk($disk)->delete($doc->file_path);
                }
                $doc->delete();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContractCategory::class, 'category_id', 'category_id');
    }

    public function approvalStatus(): BelongsTo
    {
        return $this->belongsTo(ContractApprovalStatus::class, 'approval_status_id', 'approval_status_id');
    }

    public function workflowStatus(): BelongsTo
    {
        return $this->belongsTo(ContractStatus::class, 'workflow_status_id', 'status_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(ContractRegion::class, 'region_id', 'region_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'contract_id', 'contract_id');
    }

    /**
     * Get the contract's lifecycle status.
     *
     * NOTE: This is intentionally kept out of $appends to prevent it from being
     * serialized automatically into audit log snapshots (toArray() calls).
     * It must be explicitly mapped in controllers/resources where needed.
     */
    protected function lifecycleStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (!$this->end_date) {
                    return 'active';
                }

                $days = (int) Carbon::today()->diffInDays($this->end_date, false);

                return match (true) {
                    $days < 0 => 'expired',
                    $days <= 30 => 'expiring',
                    default => 'active',
                };
            }
        );
    }
}
