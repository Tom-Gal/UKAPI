<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\ApiRequestLog;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_request_logs_capture_safe_error_context_without_query_values(): void
    {
        [$customer, $token] = $this->key();
        $requestId = 'req_supportsuccess1';
        $errorRequestId = 'req_supporterror1';

        $this->getJson('/v1/vat/calculate?amount=98765.43&rate=20', [
            'Authorization' => "Bearer {$token}",
            'X-Request-Id' => $requestId,
        ])->assertOk();
        $this->getJson('/v1/vat/calculate?amount=98765.43&rate=invalid', [
            'Authorization' => "Bearer {$token}",
            'X-Request-Id' => $errorRequestId,
        ])->assertUnprocessable();
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => 'Bearer definitely-not-a-key',
            'X-Request-Id' => 'req_supportauth1',
        ])->assertUnauthorized();

        $this->assertDatabaseHas('api_request_logs', [
            'request_id' => $requestId,
            'endpoint' => 'vat.calculate',
            'status' => 200,
            'user_id' => $customer->id,
            'error_code' => null,
        ]);
        $this->assertDatabaseHas('api_request_logs', [
            'request_id' => $errorRequestId,
            'endpoint' => 'vat.calculate',
            'status' => 422,
            'user_id' => $customer->id,
            'error_code' => 'invalid_parameter',
            'error_reason' => 'validation',
            'error_message' => 'One or more request parameters are invalid.',
        ]);
        $this->assertDatabaseHas('api_request_logs', [
            'request_id' => 'req_supportauth1',
            'status' => 401,
            'error_code' => 'invalid_api_key',
            'error_reason' => 'authentication_or_authorisation',
        ]);

        $log = ApiRequestLog::query()->where('request_id', $errorRequestId)->firstOrFail();
        $this->assertFalse(str_contains((string) $log->route_path, '98765.43'));
        $this->assertStringNotContainsString('98765.43', (string) $log->toJson());
    }

    public function test_admin_can_search_logs_open_a_ticket_and_close_it_with_audit_events(): void
    {
        [$customer, $token] = $this->key();
        $requestId = 'req_supportticket1';
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}",
            'X-Request-Id' => $requestId,
        ])->assertOk();

        $admin = User::factory()->admin()->withTwoFactor()->create();
        $response = $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', app(HandleInertiaRequests::class)->version(request()))
            ->get(route('admin.support', ['request_id' => $requestId]));

        $response->assertOk()
            ->assertJsonPath('component', 'admin/support')
            ->assertJsonPath('props.requestLogs.0.request_id', $requestId)
            ->assertJsonPath('props.requestLogs.0.user.email', $customer->email)
            ->assertJsonPath('props.requestLogs.0.endpoint', 'vat.calculate');

        $ticketResponse = $this->actingAs($admin)->post(route('admin.support.tickets.store'), [
            'request_id' => $requestId,
            'title' => 'Customer reports a calculation issue',
            'notes' => 'Investigate the request outcome and confirm the response contract.',
        ]);
        $ticketResponse->assertRedirect(route('admin.support', ['request_id' => $requestId]));

        $this->assertDatabaseHas('support_tickets', [
            'request_id' => $requestId,
            'status' => 'open',
            'opened_by_user_id' => $admin->id,
        ]);
        $ticket = SupportTicket::query()->firstOrFail();
        $this->assertDatabaseHas('admin_audit_events', [
            'action' => 'support_ticket_opened',
            'subject_id' => (string) $ticket->id,
        ]);

        $this->actingAs($admin)->post(route('admin.support.tickets.close', $ticket), [
            'resolution' => 'Reviewed the request and recorded the outcome for the next customer reply.',
        ])->assertRedirect(route('admin.support', ['request_id' => $requestId]));

        $ticket->refresh();
        $this->assertSame('closed', $ticket->status);
        $this->assertSame($admin->id, $ticket->closed_by_user_id);
        $this->assertNotNull($ticket->closed_at);
        $this->assertDatabaseHas('admin_audit_events', [
            'action' => 'support_ticket_closed',
            'subject_id' => (string) $ticket->id,
        ]);
    }

    public function test_non_admin_cannot_access_support(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.support'))
            ->assertForbidden();
    }

    /** @return array{User, string} */
    private function key(): array
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $key = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Support test',
            'environment' => 'test',
        ]);

        return [$user, "uk_test_{$key->public_id}.{$secret}"];
    }
}
