<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Loan;
use App\Models\MembershipCancellation;
use App\Models\MembershipPayment;
use App\Models\PackageLoan;
use App\Models\PickupLocation;
use App\Models\User;
use App\Notifications;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Builds every mail the app sends from its real (seeded) template and checks
 * what a member would read: a subject, no "{placeholder}" left unfilled, a
 * working button link, and an HTML body that renders. The other tests mostly
 * check *that* a mail goes out — this one checks *what* is in it.
 */
class NotificationContentTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->member()->create([
            'name' => 'Mia Muster',
            'email' => 'mia@example.at',
            'membership_expires_at' => '2027-01-31 00:00:00',
        ]);
    }

    private function loan(User $user): Loan
    {
        $location = PickupLocation::factory()->create(['name' => 'Linz', 'address' => 'Hauptplatz 1']);

        return Loan::factory()->create([
            'user_id' => $user->id,
            'pickup_location_id' => $location->id,
            'return_location_id' => $location->id,
            'deposit_tokens' => 4,
            'return_condition' => 'GOOD',
            'returned_at' => now(),
        ])->load('copy.game');
    }

    private function packageLoan(User $user): PackageLoan
    {
        $loan = PackageLoan::factory()->create(['user_id' => $user->id]);
        Loan::factory()->create(['user_id' => $user->id, 'package_loan_id' => $loan->id]);

        return $loan->load('package', 'loans.copy.game');
    }

    private function pickupEvent(): Event
    {
        $location = PickupLocation::factory()->create(['is_personal' => true, 'name' => 'Bei Andi', 'address' => 'Hauptplatz 1']);

        return Event::factory()->pickupAt($location)->create(['date' => '2026-12-03', 'time' => '18:00', 'end_time' => '19:00']);
    }

    private function cancellation(User $user): MembershipCancellation
    {
        return MembershipCancellation::create([
            'user_id' => $user->id,
            'previous_role' => 'MEMBER',
            'tokens_total' => 10,
            'bonus_tokens_forfeited' => 5,
            'refund_tokens' => 5,
            'gross_cents' => 1000,
            'fee_cents' => 200,
            'refund_cents' => 800,
            'account_holder' => 'Mia Muster',
            'iban' => 'AT611904300234573201',
            'reason' => 'Umzug',
            'status' => 'REQUESTED',
        ]);
    }

    private function payment(User $user): MembershipPayment
    {
        return MembershipPayment::create([
            'user_id' => $user->id,
            'provider' => 'mollie',
            'provider_payment_id' => 'tr_2',
            'type' => 'MEMBER',
            'price_cents' => 2400,
            'currency' => 'EUR',
            'status' => MembershipPayment::STATUS_DUPLICATE,
        ]);
    }

    /** @return array<string, array{Closure(self, User): Notification}> */
    public static function mails(): array
    {
        return [
            'DepositForfeited' => [fn (self $t, User $u) => new Notifications\DepositForfeited($t->loan($u), 'Karten fehlen')],
            'DepositReleased' => [fn (self $t, User $u) => new Notifications\DepositReleased($t->loan($u))],
            'InactiveAccountNotice' => [fn (self $t, User $u) => new Notifications\InactiveAccountNotice(now()->addMonth())],
            'LoanCancelledNoShow' => [fn (self $t, User $u) => new Notifications\LoanCancelledNoShow($t->loan($u))],
            'LoanCancelledNoShow (package)' => [fn (self $t, User $u) => new Notifications\LoanCancelledNoShow($t->packageLoan($u))],
            'LoanConfirmation' => [fn (self $t, User $u) => new Notifications\LoanConfirmation($t->loan($u))],
            'LoanConfirmation (package)' => [fn (self $t, User $u) => new Notifications\LoanConfirmation($t->packageLoan($u))],
            'LoanDueSoon' => [fn (self $t, User $u) => new Notifications\LoanDueSoon($t->loan($u))],
            'LoanOverdueReminder' => [fn (self $t, User $u) => new Notifications\LoanOverdueReminder($t->loan($u))],
            'LoanReturnConfirmation' => [fn (self $t, User $u) => new Notifications\LoanReturnConfirmation($t->loan($u))],
            'LoanReturnConfirmation (package)' => [fn (self $t, User $u) => new Notifications\LoanReturnConfirmation($t->packageLoan($u))],
            'LoanReturnedAdmin' => [fn (self $t, User $u) => new Notifications\LoanReturnedAdmin($t->loan($u))],
            'MembershipCancellationConfirmation' => [fn (self $t, User $u) => new Notifications\MembershipCancellationConfirmation($t->cancellation($u))],
            'MembershipCancellationRequested' => [fn (self $t, User $u) => new Notifications\MembershipCancellationRequested($t->cancellation($u), $u)],
            'MembershipPaymentDuplicate' => [fn (self $t, User $u) => new Notifications\MembershipPaymentDuplicate($t->payment($u))],
            'MembershipPaymentDuplicateAdmin' => [fn (self $t, User $u) => new Notifications\MembershipPaymentDuplicateAdmin($t->payment($u), $u)],
            'MembershipRenewalReminder' => [fn (self $t, User $u) => new Notifications\MembershipRenewalReminderNotification],
            'NewUserRegistered' => [fn (self $t, User $u) => new Notifications\NewUserRegistered($u)],
            'PickupDigestAdmin' => [fn (self $t, User $u) => new Notifications\PickupDigestAdmin(2, '<ul><li>Azul</li></ul>')],
            'PickupEventMoved' => [fn (self $t, User $u) => new Notifications\PickupEventMoved($t->pickupEvent(), 'Di, 2. Dezember, 18:00 Uhr')],
            'PickupEventReminder' => [fn (self $t, User $u) => new Notifications\PickupEventReminder($t->pickupEvent(), collect([$t->loan($u)]))],
            'ReservationAvailable' => [fn (self $t, User $u) => new Notifications\ReservationAvailable(Game::factory()->create(['title' => 'Azul']))],
            'ResetPassword' => [fn (self $t, User $u) => new Notifications\ResetPasswordNotification('token123')],
            'UserApproved' => [fn (self $t, User $u) => new Notifications\UserApproved],
            'UserRejected' => [fn (self $t, User $u) => new Notifications\UserRejected('Unvollständige Angaben')],
            'VerifyEmail' => [fn (self $t, User $u) => new Notifications\VerifyEmailNotification],
            'WelcomeMember' => [fn (self $t, User $u) => new Notifications\WelcomeMemberNotification],
            'WelcomeSupporter' => [fn (self $t, User $u) => new Notifications\WelcomeSupporterNotification],
        ];
    }

    /** @param  Closure(self, User): Notification  $make */
    #[DataProvider('mails')]
    public function test_the_mail_reads_complete(Closure $make): void
    {
        $user = $this->member();
        $notification = $make($this, $user);

        /** @var MailMessage $mail */
        $mail = $notification->toMail($user); // @phpstan-ignore method.notFound

        $text = implode("\n", array_map('strval', [
            $mail->subject, $mail->greeting, ...$mail->introLines, ...$mail->outroLines, $mail->actionText,
        ]));

        $this->assertNotSame('', trim((string) $mail->subject), 'subject is empty');
        $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', $text, 'a placeholder was left unfilled');
        if ($mail->actionText) {
            $this->assertMatchesRegularExpression('#^https?://#', (string) $mail->actionUrl, 'button without a link');
        }

        // the rendered HTML must not show a placeholder either
        $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', (string) $mail->render());
    }

    public function test_every_mail_class_is_covered(): void
    {
        $classes = array_map(fn (string $f) => basename($f, '.php'), glob(app_path('Notifications/*.php')) ?: []);
        $source = (string) file_get_contents(__FILE__);
        $missing = array_values(array_filter(
            $classes,
            fn (string $c) => ! preg_match('/new Notifications\\\\'.$c.'\b/', $source),
        ));

        $this->assertSame([], $missing, 'mails without a case in mails()');
    }
}
