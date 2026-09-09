<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer generik. TIDAK didaftarkan manual di tiap model (bootMethod dsb),
 * melainkan didaftarkan sekali untuk SEMUA model sekaligus lewat
 * AppServiceProvider::boot() -> lihat file AppServiceProvider.php.
 *
 * Kalau butuh custom behaviour per model (misal field sensitif yang tidak boleh
 * di-log seperti password), atur di method shouldIgnoreField() atau override
 * lewat property statis $activityLogExcept pada model terkait (opsional, lihat bawah).
 */
class GlobalActivityObserver
{
    // Field yang tidak pernah dicatat di old_data/new_data untuk model manapun
    protected array $globallyExcluded = ['password', 'remember_token', 'updated_at'];

    public function created(Model $model): void
    {
        $this->write($model, 'created', null, $this->cleanAttributes($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return; // tidak ada perubahan riil, skip (misal touch() saja)
        }

        $original = array_intersect_key($model->getOriginal(), $changes);

        $this->write(
            $model,
            'updated',
            $this->cleanAttributes($original),
            $this->cleanAttributes($changes)
        );
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted', $this->cleanAttributes($model->getOriginal()), null);
    }

    protected function write(Model $model, string $action, ?array $old, ?array $new): void
    {
        // Model boleh opt-out total dengan set: public static bool $activityLogDisabled = true;
        if (($model::$activityLogDisabled ?? false) === true) {
            return;
        }

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'subject_type' => get_class($model),
            'subject_id'   => $model->getKey(),
            'module'       => $this->resolveModule($model),
            'action'       => $action,
            'description'  => $this->buildDescription($model, $action),
            'old_data'     => $old,
            'new_data'     => $new,
            'ip_address'   => request()->ip(),
            'created_at'   => now(),
        ]);
    }

    protected function cleanAttributes(array $attributes): array
    {
        return collect($attributes)
            ->except($this->globallyExcluded)
            ->toArray();
    }

    protected function resolveModule(Model $model): string
    {
        // App\Models\PurchaseOrder -> 'purchase_order'
        $short = class_basename($model);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $short));
    }

    protected function buildDescription(Model $model, string $action): string
    {
        $module = str_replace('_', ' ', $this->resolveModule($model));
        $label  = $model->name ?? $model->po_number ?? $model->so_number ?? $model->getKey();

        return ucfirst($module) . " #{$label} {$action}";
    }
}
