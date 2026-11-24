<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressValidationController;
use App\Http\Controllers\Admin\CopyController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\GameImageController;
use App\Http\Controllers\Admin\GameImportExportController;
use App\Http\Controllers\Admin\MechanicController as AdminMechanicController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\PickupEventController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CookieController;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameSearchController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanSettingController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\MembershipCancellationController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MollieWebhookController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackageLoanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PickupLocationController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SponsorController;
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
// Rate limits are defined in AppServiceProvider::configureRateLimits().
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/verify-email/{id}', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:auth-token')
        ->name('auth.verify-email');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:auth-email');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:auth-email');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:auth-token');
});

Route::get('/loan-settings', [LoanSettingController::class, 'show']);
Route::get('/tokens/packages', [TokenController::class, 'packages']);
Route::get('/membership/pricing', [MembershipController::class, 'pricing']);

Route::get('/games', [GameController::class, 'index']);
Route::get('/games/smart-search', [GameSearchController::class, 'index']);
Route::get('/games/{game:slug}', [GameController::class, 'show']);
Route::get('/mechanics', [MechanicController::class, 'index']);
Route::get('/packages', [PackageController::class, 'index']);
Route::get('/packages/{package:slug}', [PackageController::class, 'show']);
Route::get('/terms', [TermsController::class, 'show']);
Route::get('/privacy', [PrivacyController::class, 'show']);
Route::get('/cookies', [CookieController::class, 'show']);
Route::get('/languages', [LanguageController::class, 'index']);
Route::get('/sponsors', [SponsorController::class, 'index']);
Route::get('/events', [EventController::class, 'index'])->middleware(['auth:sanctum', 'maintenance', 'active']);

// Mollie ruft diesen Endpoint direkt an — kein Sanctum-Token. Der Body enthält nur
// die Zahlungs-ID; der Status wird immer mit unserem API-Key bei Mollie abgefragt.
Route::post('/webhooks/mollie', [MollieWebhookController::class, 'handle'])->middleware('throttle:60,1');

// ----------------------------------------------------------------
// Authentifizierte Routen (aktive Mitglieder)
// ----------------------------------------------------------------
Route::middleware(['auth:sanctum', 'maintenance', 'active'])->group(function () {
    // Auth
    Route::post('/auth/logout', [LoginController::class, 'destroy']);
    Route::get('/auth/me', [LoginController::class, 'me']);

    // Ausleihen
    Route::get('/pickup-locations', [PickupLocationController::class, 'index']);
    Route::get('/pickup-events', [PickupLocationController::class, 'events']);
    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::get('/loans/{loan}', [LoanController::class, 'show']);
    Route::post('/loans/{loan}/return', [LoanController::class, 'return']);
    Route::get('/loans/{loan}/return-options', [LoanController::class, 'returnOptions']);
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

    // Sachspenden
    Route::post('/donations', [DonationController::class, 'store']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Konto
    Route::patch('/account', [AccountController::class, 'update']);
    Route::post('/address/validate', [AddressValidationController::class, 'validate'])->middleware('throttle:address');

    // Mitgliedschaft & Token
    Route::post('/membership/checkout', [MembershipController::class, 'checkout']);
    Route::get('/membership/cancel/preview', [MembershipCancellationController::class, 'preview']);
    Route::post('/membership/cancel', [MembershipCancellationController::class, 'store']);
    Route::post('/membership/activate-full', [MembershipController::class, 'activateFullMembership']);
    Route::post('/tokens/checkout', [TokenController::class, 'checkout']);
    // Rückkehr von der Mollie-Bezahlseite: Status abfragen und verbuchen
    Route::get('/payments/{kind}/{id}', [PaymentController::class, 'show'])
        ->whereIn('kind', ['tokens', 'membership'])->whereNumber('id');

    // Paket-Ausleihen
    Route::get('/package-loans', [PackageLoanController::class, 'index']);
    Route::post('/package-loans', [PackageLoanController::class, 'store']);
    Route::post('/package-loans/{packageLoan}/return', [PackageLoanController::class, 'return']);
    Route::get('/package-loans/{packageLoan}/return-options', [PackageLoanController::class, 'returnOptions']);

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

    // Kündigungen
    Route::get('/cancellations', [App\Http\Controllers\Admin\MembershipCancellationController::class, 'index']);
    Route::patch('/cancellations/{cancellation}/refunded', [App\Http\Controllers\Admin\MembershipCancellationController::class, 'markRefunded']);

    // Spieleverwaltung
    Route::post('/games/import', [GameImportExportController::class, 'import']);
    Route::get('/games/export', [GameImportExportController::class, 'export']);
    Route::apiResource('games', App\Http\Controllers\Admin\GameController::class);
    Route::post('/games/{game}/images', [GameImageController::class, 'store']);
    Route::delete('/games/{game}/images/{image}', [GameImageController::class, 'destroy']);
    Route::apiResource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('mechanics', AdminMechanicController::class)->only(['index', 'store', 'update', 'destroy']);

    // Kopienverwaltung
    Route::get('/copies/lookup', [CopyController::class, 'lookup']);
    Route::apiResource('copies', CopyController::class);
    Route::post('/copies/{copy}/approve', [CopyController::class, 'approve']);
    Route::post('/copies/{copy}/mark-damaged', [CopyController::class, 'markDamaged']);

    // Ausleihverwaltung
    Route::get('/loans', [App\Http\Controllers\Admin\LoanController::class, 'index']);
    Route::patch('/loans/{loan}/overdue', [App\Http\Controllers\Admin\LoanController::class, 'markOverdue']);
    Route::post('/loans/{loan}/remind', [App\Http\Controllers\Admin\LoanController::class, 'sendOverdueReminder']);
    Route::post('/loans/{loan}/return', [App\Http\Controllers\Admin\LoanController::class, 'return']);
    Route::patch('/loans/{loan}/pickup-location', [App\Http\Controllers\Admin\LoanController::class, 'updatePickupLocation']);

    // Verlängerungsanträge

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
    Route::patch('/package-loans/{packageLoan}/pickup-location', [App\Http\Controllers\Admin\PackageLoanController::class, 'updatePickupLocation']);

    // Token-Transaktionen (Admin: pro User)
    Route::get('/users/{user}/pickup-locations', [App\Http\Controllers\Admin\PickupLocationController::class, 'forUser']);
    Route::get('/users/{user}/token-transactions', [App\Http\Controllers\Admin\TokenTransactionController::class, 'index']);

    // Events
    Route::apiResource('events', App\Http\Controllers\Admin\EventController::class)->except(['show']);
    Route::get('/events/{event}/packing-list', [PickupEventController::class, 'show']);
    Route::post('/events/{event}/returns', [PickupEventController::class, 'returnAtEvent']);
    Route::patch('/loans/{loan}/hand-out', [PickupEventController::class, 'handOutLoan']);
    Route::patch('/package-loans/{packageLoan}/hand-out', [PickupEventController::class, 'handOutPackage']);
    Route::post('/loans/{loan}/cancel-pickup', [PickupEventController::class, 'cancelLoan']);
    Route::post('/package-loans/{packageLoan}/cancel-pickup', [PickupEventController::class, 'cancelPackage']);

    // Sponsoren
    Route::put('/sponsors/order', [App\Http\Controllers\Admin\SponsorController::class, 'reorder']);
    Route::patch('/sponsors/{sponsor}/toggle', [App\Http\Controllers\Admin\SponsorController::class, 'toggle']);
    Route::apiResource('sponsors', App\Http\Controllers\Admin\SponsorController::class)->except(['show']);

    // Abholorte
    Route::put('/pickup-locations/order', [App\Http\Controllers\Admin\PickupLocationController::class, 'reorder']);
    Route::patch('/pickup-locations/{pickupLocation}/toggle', [App\Http\Controllers\Admin\PickupLocationController::class, 'toggle']);
    Route::apiResource('pickup-locations', App\Http\Controllers\Admin\PickupLocationController::class)->except(['show']);
});
