<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ApiKeyActivityNotification;
use App\Notifications\ApiKeyRotationReminderNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\SubscriptionNotification;
use App\Notifications\UsageLimitNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('transactionalNotifications')]
    public function test_transactional_notification_renders_the_branded_ui_template(string $notificationType): void
    {
        $user = User::factory()->create(['name' => 'Alex Smith']);
        $key = $user->apiKeys()->create([
            'public_id' => 'k_testkey',
            'secret_hash' => 'not-a-real-secret',
            'name' => 'Local development',
            'environment' => 'test',
        ]);
        $notification = match ($notificationType) {
            'verify email' => new VerifyEmailNotification,
            'reset password' => new ResetPasswordNotification('test-reset-token'),
            'two-factor enabled' => SecurityAlertNotification::twoFactorEnabled(),
            'signed in' => SecurityAlertNotification::signedIn('203.0.113.42', 'Example Browser'),
            'usage limit' => new UsageLimitNotification(used: 4_000, limit: 5_000, percentage: 80, resetsAt: now()->addMonth()->timestamp),
            'API key created' => ApiKeyActivityNotification::created($key),
            'API key revoked' => ApiKeyActivityNotification::revoked($key),
            'API key rotation reminder' => new ApiKeyRotationReminderNotification([['name' => $key->name, 'environment' => 'Test', 'ageDays' => 90]]),
            'subscription confirmed' => SubscriptionNotification::confirmed('Pro'),
        };

        $html = $notification->toMail($user)->render();

        $this->assertStringContainsString('UKApi.co.uk', $html);
        $this->assertStringContainsString('border-top:4px solid #2563c7', $html);
        $this->assertStringContainsString('Alex Smith', $html);
        $this->assertStringNotContainsString('[COMPANY NUMBER]', $html);
        $this->assertStringNotContainsString('[REGISTERED OFFICE ADDRESS]', $html);
        $this->assertStringNotContainsString('color-scheme" content="dark', $html);
    }

    /** @return iterable<string, array{string}> */
    public static function transactionalNotifications(): iterable
    {
        yield 'verify email' => ['verify email'];
        yield 'reset password' => ['reset password'];
        yield 'two-factor enabled' => ['two-factor enabled'];
        yield 'signed in' => ['signed in'];
        yield 'usage limit' => ['usage limit'];
        yield 'API key created' => ['API key created'];
        yield 'API key revoked' => ['API key revoked'];
        yield 'API key rotation reminder' => ['API key rotation reminder'];
        yield 'subscription confirmed' => ['subscription confirmed'];
    }

    public function test_transactional_footer_omits_non_applicable_registration_fields_without_placeholders(): void
    {
        config()->set([
            'legal.entity_name' => 'Approved Operator Name',
            'legal.company_number' => null,
            'legal.registered_office' => '1 Example Street, London',
            'legal.contact_email' => 'contact@ukapi.co.uk',
        ]);

        $html = view('emails.layouts.transactional', [
            'title' => 'Test message',
            'preheader' => 'Test preheader',
            'slot' => 'Test content',
        ])->render();
        $text = view('emails.layouts.transactional-text-footer')->render();

        foreach ([$html, $text] as $rendered) {
            $this->assertStringContainsString('Approved Operator Name', $rendered);
            $this->assertStringContainsString('1 Example Street, London', $rendered);
            $this->assertStringNotContainsString('company number', $rendered);
            $this->assertStringNotContainsString('[COMPANY NUMBER]', $rendered);
            $this->assertStringNotContainsString('[REGISTERED OFFICE ADDRESS]', $rendered);
        }
    }
}
