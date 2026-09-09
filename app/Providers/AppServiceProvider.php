<?php

namespace App\Providers;

use App\Observers\GlobalActivityObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerGlobalActivityLogging();
    }

    /**
     * Scan semua model di app/Models, lalu daftarkan GlobalActivityObserver
     * ke masing-masing secara otomatis. Ini yang membuat kamu TIDAK PERLU
     * "use LogsActivity" atau boot() manual di tiap model baru yang dibuat.
     *
     * Model yang tidak mau di-log tinggal tambahkan property:
     *   public static bool $activityLogDisabled = true;
     */
    protected function registerGlobalActivityLogging(): void
    {
        $modelPath = app_path('Models');

        if (! File::exists($modelPath)) {
            return;
        }

        $excluded = [
            \App\Models\ActivityLog::class, // hindari observer log-in-log (infinite noise)
        ];

        foreach (File::allFiles($modelPath) as $file) {
            $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();

            if (! class_exists($class) || in_array($class, $excluded, true)) {
                continue;
            }

            if (is_subclass_of($class, Model::class)) {
                $class::observe(GlobalActivityObserver::class);
            }
        }
    }
}
