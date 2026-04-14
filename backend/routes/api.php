<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Alle API-Routen der Brettspiel-Ausleihplattform.
| Auth: Laravel Sanctum (Token-basiert)
|
*/

// ----------------------------------------------------------------
// Öffentliche Routen
// ----------------------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'store']);
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'store']);
    Route::get('/verify-email/{id}', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])
        ->name('auth.verify-email');
});

Route::get('/loan-settings', [\App\Http\Controllers\LoanSettingController::class, 'show']);

Route::get('/games', [\App\Http\Controllers\GameController::class, 'index']);
Route::get('/games/{game:slug}', [\App\Http\Controllers\GameController::class, 'show']);
Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index']);
Route::get('/packages', [\App\Http\Controllers\PackageController::class, 'index']);
Route::get('/packages/{package:slug}', [\App\Http\Controllers\PackageController::class, 'show']);
Route::get('/terms', [\App\Http\Controllers\TermsController::class, 'show']);

// ----------------------------------------------------------------
// Authentifizierte Routen (aktive Mitglieder)
// ----------------------------------------------------------------
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Auth
    Route::post('/auth/logout', [\App\Http\Controllers\Auth\LoginController::class, 'destroy']);
    Route::get('/auth/me', [\App\Http\Controllers\Auth\LoginController::class, 'me']);
    Route::post('/auth/email/resend', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'resend']);

    // Ausleihen
    Route::get('/loans', [\App\Http\Controllers\LoanController::class, 'index']);
    Route::post('/loans', [\App\Http\Controllers\LoanController::class, 'store']);
    Route::get('/loans/{loan}', [\App\Http\Controllers\LoanController::class, 'show']);
    Route::post('/loans/{loan}/return', [\App\Http\Controllers\LoanController::class, 'return']);
    Route::post('/loans/{loan}/extend', [\App\Http\Controllers\ExtensionController::class, 'store']);

    // Reservierungen
    Route::get('/reservations', [\App\Http\Controllers\ReservationController::class, 'index']);
    Route::post('/reservations', [\App\Http\Controllers\ReservationController::class, 'store']);
    Route::delete('/reservations/{reservation}', [\App\Http\Controllers\ReservationController::class, 'destroy']);

    // Bewertungen
    Route::post('/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [\App\Http\Controllers\ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [\App\Http\Controllers\ReviewController::class, 'destroy']);

    // Favoriten
    Route::get('/favorites', [\App\Http\Controllers\FavoriteController::class, 'index']);
    Route::post('/favorites', [\App\Http\Controllers\FavoriteController::class, 'store']);
    Route::delete('/favorites/{game}', [\App\Http\Controllers\FavoriteController::class, 'destroy']);

    // Schadensmeldungen
    Route::post('/damage-reports', [\App\Http\Controllers\DamageReportController::class, 'store']);

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);

    // Konto
    Route::patch('/account', [\App\Http\Controllers\AccountController::class, 'update']);

    // Mitgliedschaft & Token
    Route::post('/membership/upgrade', [\App\Http\Controllers\MembershipController::class, 'upgrade']);
    Route::post('/membership/renew', [\App\Http\Controllers\MembershipController::class, 'renew']);
    Route::post('/tokens/add', [\App\Http\Controllers\TokenController::class, 'add']);

    // Paket-Ausleihen
    Route::get('/package-loans', [\App\Http\Controllers\PackageLoanController::class, 'index']);
    Route::post('/package-loans', [\App\Http\Controllers\PackageLoanController::class, 'store']);
    Route::post('/package-loans/{packageLoan}/return', [\App\Http\Controllers\PackageLoanController::class, 'return']);
});

// ----------------------------------------------------------------
// Admin-Routen
// ----------------------------------------------------------------
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Mitgliederverwaltung
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store']);
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show']);
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update']);
    Route::patch('/users/{user}/approve', [\App\Http\Controllers\Admin\UserController::class, 'approve']);
    Route::patch('/users/{user}/reject', [\App\Http\Controllers\Admin\UserController::class, 'reject']);
    Route::patch('/users/{user}/suspend', [\App\Http\Controllers\Admin\UserController::class, 'suspend']);

    // Spieleverwaltung
    Route::post('/games/import', [\App\Http\Controllers\Admin\GameImportExportController::class, 'import']);
    Route::get('/games/export', [\App\Http\Controllers\Admin\GameImportExportController::class, 'export']);
    Route::apiResource('games', \App\Http\Controllers\Admin\GameController::class);
    Route::post('/games/{game}/images', [\App\Http\Controllers\Admin\GameImageController::class, 'store']);
    Route::delete('/games/{game}/images/{image}', [\App\Http\Controllers\Admin\GameImageController::class, 'destroy']);
    Route::post('/categories/import', [\App\Http\Controllers\Admin\CategoryImportExportController::class, 'import']);
    Route::get('/categories/export', [\App\Http\Controllers\Admin\CategoryImportExportController::class, 'export']);
    Route::apiResource('categories', \App\Http\Controllers\Admin\CategoryController::class);
    Route::apiResource('tags', \App\Http\Controllers\Admin\TagController::class)->only(['index', 'store', 'update', 'destroy']);

    // Kopienverwaltung
    Route::apiResource('copies', \App\Http\Controllers\Admin\CopyController::class);

    // Ausleihverwaltung
    Route::get('/loans', [\App\Http\Controllers\Admin\LoanController::class, 'index']);
    Route::patch('/loans/{loan}/overdue', [\App\Http\Controllers\Admin\LoanController::class, 'markOverdue']);

    // Verlängerungsanträge
    Route::get('/extensions', [\App\Http\Controllers\Admin\ExtensionController::class, 'index']);
    Route::patch('/extensions/{extension}/approve', [\App\Http\Controllers\Admin\ExtensionController::class, 'approve']);
    Route::patch('/extensions/{extension}/reject', [\App\Http\Controllers\Admin\ExtensionController::class, 'reject']);

    // Newsletter
    Route::get('/newsletters', [\App\Http\Controllers\Admin\NewsletterController::class, 'index']);
    Route::post('/newsletters', [\App\Http\Controllers\Admin\NewsletterController::class, 'store']);

    // Schadensmeldungen
    Route::get('/damage-reports', [\App\Http\Controllers\Admin\DamageReportController::class, 'index']);

    // Paketverwaltung
    Route::apiResource('packages', \App\Http\Controllers\Admin\PackageController::class);

    // Ausleih-Einstellungen
    Route::get('/loan-settings', [\App\Http\Controllers\Admin\LoanSettingController::class, 'show']);
    Route::patch('/loan-settings', [\App\Http\Controllers\Admin\LoanSettingController::class, 'update']);

    // E-Mail-Vorlagen
    Route::get('/email-templates', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'index']);
    Route::put('/email-templates/{key}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'update']);
    Route::post('/email-templates/{key}/reset', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'reset']);

    // Dashboard-Übersicht
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index']);

    // E-Mail-Log
    Route::get('/email-logs', [\App\Http\Controllers\Admin\EmailLogController::class, 'index']);

    // Paket-Ausleihen
    Route::get('/package-loans', [\App\Http\Controllers\Admin\PackageLoanController::class, 'index']);
});
