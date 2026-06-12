<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CategoryImportExportController;
use App\Http\Controllers\Admin\CopyController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\GameImageController;
use App\Http\Controllers\Admin\GameImportExportController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CookieController;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanSettingController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackageLoanController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\TokenTransactionController;
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
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/verify-email/{id}', [EmailVerificationController::class, 'verify'])
        ->name('auth.verify-email');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
    Route::post('/reset-password', [ResetPasswordController::class, 'store']);
});

Route::get('/loan-settings', [LoanSettingController::class, 'show']);

Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{game:slug}', [GameController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/packages', [PackageController::class, 'index']);
Route::get('/packages/{package:slug}', [PackageController::class, 'show']);
Route::get('/terms', [TermsController::class, 'show']);
Route::get('/privacy', [PrivacyController::class, 'show']);
Route::get('/cookies', [CookieController::class, 'show']);
Route::get('/languages', [LanguageController::class, 'index']);
Route::get('/events', [EventController::class, 'index'])->middleware(['auth:sanctum', 'active']);

// ----------------------------------------------------------------
// Authentifizierte Routen (aktive Mitglieder)
// ----------------------------------------------------------------
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Auth
    Route::post('/auth/logout', [LoginController::class, 'destroy']);
    Route::get('/auth/me', [LoginController::class, 'me']);

    // Ausleihen
    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::get('/loans/{loan}', [LoanController::class, 'show']);
    Route::post('/loans/{loan}/return', [LoanController::class, 'return']);
    Route::post('/loans/{loan}/extend', [ExtensionController::class, 'store']);

    // Reservierungen
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);

    // Bewertungen
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Favoriten
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{game}', [FavoriteController::class, 'destroy']);

    // Schadensmeldungen
    Route::post('/damage-reports', [DamageReportController::class, 'store']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Konto
    Route::patch('/account', [AccountController::class, 'update']);

    // Mitgliedschaft & Token
    Route::post('/membership/upgrade', [MembershipController::class, 'upgrade']);
    Route::post('/membership/renew', [MembershipController::class, 'renew']);
    Route::post('/tokens/add', [TokenController::class, 'add']);

    // Paket-Ausleihen
    Route::get('/package-loans', [PackageLoanController::class, 'index']);
    Route::post('/package-loans', [PackageLoanController::class, 'store']);
    Route::post('/package-loans/{packageLoan}/return', [PackageLoanController::class, 'return']);

    // Token-Transaktionen
    Route::get('/token-transactions', [TokenTransactionController::class, 'index']);
});

// ----------------------------------------------------------------
// Admin-Routen
// ----------------------------------------------------------------
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Mitgliederverwaltung
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}/approve', [UserController::class, 'approve']);
    Route::patch('/users/{user}/reject', [UserController::class, 'reject']);
    Route::patch('/users/{user}/suspend', [UserController::class, 'suspend']);

    // Spieleverwaltung
    Route::post('/games/import', [GameImportExportController::class, 'import']);
    Route::get('/games/export', [GameImportExportController::class, 'export']);
    Route::apiResource('games', App\Http\Controllers\Admin\GameController::class);
    Route::post('/games/{game}/images', [GameImageController::class, 'store']);
    Route::delete('/games/{game}/images/{image}', [GameImageController::class, 'destroy']);
    Route::post('/categories/import', [CategoryImportExportController::class, 'import']);
    Route::get('/categories/export', [CategoryImportExportController::class, 'export']);
    Route::apiResource('categories', App\Http\Controllers\Admin\CategoryController::class);
    Route::apiResource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);

    // Kopienverwaltung
    Route::apiResource('copies', CopyController::class);
    Route::post('/copies/{copy}/approve', [CopyController::class, 'approve']);
    Route::post('/copies/{copy}/mark-damaged', [CopyController::class, 'markDamaged']);

    // Ausleihverwaltung
    Route::get('/loans', [App\Http\Controllers\Admin\LoanController::class, 'index']);
    Route::patch('/loans/{loan}/overdue', [App\Http\Controllers\Admin\LoanController::class, 'markOverdue']);
    Route::post('/loans/{loan}/return', [App\Http\Controllers\Admin\LoanController::class, 'return']);

    // Verlängerungsanträge
    Route::get('/extensions', [App\Http\Controllers\Admin\ExtensionController::class, 'index']);
    Route::patch('/extensions/{extension}/approve', [App\Http\Controllers\Admin\ExtensionController::class, 'approve']);
    Route::patch('/extensions/{extension}/reject', [App\Http\Controllers\Admin\ExtensionController::class, 'reject']);

    // Newsletter
    Route::get('/newsletters', [NewsletterController::class, 'index']);
    Route::post('/newsletters', [NewsletterController::class, 'store']);

    // Schadensmeldungen
    Route::get('/damage-reports', [App\Http\Controllers\Admin\DamageReportController::class, 'index']);

    // Paketverwaltung
    Route::apiResource('packages', App\Http\Controllers\Admin\PackageController::class);

    // Ausleih-Einstellungen
    Route::get('/loan-settings', [App\Http\Controllers\Admin\LoanSettingController::class, 'show']);
    Route::patch('/loan-settings', [App\Http\Controllers\Admin\LoanSettingController::class, 'update']);

    // E-Mail-Vorlagen
    Route::get('/email-templates', [EmailTemplateController::class, 'index']);
    Route::put('/email-templates/{key}', [EmailTemplateController::class, 'update']);
    Route::post('/email-templates/{key}/reset', [EmailTemplateController::class, 'reset']);

    // Dashboard-Übersicht
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index']);

    // E-Mail-Log
    Route::get('/email-logs', [EmailLogController::class, 'index']);

    // Paket-Ausleihen
    Route::get('/package-loans', [App\Http\Controllers\Admin\PackageLoanController::class, 'index']);

    // Token-Transaktionen (Admin: pro User)
    Route::get('/users/{user}/token-transactions', [App\Http\Controllers\Admin\TokenTransactionController::class, 'index']);

    // Events
    Route::apiResource('events', App\Http\Controllers\Admin\EventController::class)->except(['show']);
});
