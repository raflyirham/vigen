<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoGenerationController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $baseQuery = $request->user()->videoGenerations();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'fallbacks' => (clone $baseQuery)
                    ->whereIn('status', ['script_only', 'failed_with_fallback'])
                    ->count(),
            ],
            'recentGenerations' => $request->user()
                ->videoGenerations()
                ->latest()
                ->limit(4)
                ->get(['id', 'video_type', 'topic', 'status', 'created_at']),
        ]);
    })->name('dashboard');

    Route::get('/generate', [VideoGenerationController::class, 'create'])->name('generator.create');
    Route::get('/generations', [VideoGenerationController::class, 'index'])->name('generations.index');
    Route::post('/generations', [VideoGenerationController::class, 'store'])->name('generations.store');
    Route::get('/generations/{generation}/status', [VideoGenerationController::class, 'status'])->name('generations.status');
    Route::get('/generations/{generation}', [VideoGenerationController::class, 'show'])->name('generations.show');
    Route::delete('/generations/{generation}', [VideoGenerationController::class, 'destroy'])->name('generations.destroy');
    Route::get('/generations/{generation}/export-script', [VideoGenerationController::class, 'exportScript'])->name('generations.export-script');
    Route::get('/generations/{generation}/video', [VideoGenerationController::class, 'video'])->name('generations.video');
    Route::get('/generations/{generation}/download-video', [VideoGenerationController::class, 'downloadVideo'])->name('generations.download-video');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/{any}', function () {
  return view('app');
})->where('any', '.*');

require __DIR__.'/auth.php';
