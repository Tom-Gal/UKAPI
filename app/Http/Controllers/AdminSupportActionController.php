<?php

namespace App\Http\Controllers;

use App\Domain\ApiKeys\ApiKeyManager;
use App\Models\AdminAuditEvent;
use App\Models\ApiKey;
use App\Notifications\ApiKeyActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdminSupportActionController extends Controller
{
    public function revokeApiKey(Request $request, ApiKey $apiKey, ApiKeyManager $keys): RedirectResponse
    {
        $attributes = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $apiKey, $keys, $attributes): void {
            $apiKey = ApiKey::query()->with('user')->lockForUpdate()->findOrFail($apiKey->id);
            if (! $keys->revoke($apiKey, $request->user(), $request->header('X-Request-Id'))) {
                throw ValidationException::withMessages(['reason' => 'This API key is no longer active.']);
            }
            AdminAuditEvent::query()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'api_key_revoked',
                'subject_type' => 'api_key',
                'subject_id' => (string) $apiKey->id,
                'reason' => $attributes['reason'],
                'metadata' => [
                    'owner_user_id' => $apiKey->user_id,
                    'environment' => $apiKey->environment,
                    'prefix' => $keys->prefix($apiKey),
                ],
                'request_id' => $request->header('X-Request-Id'),
            ]);
        }, 3);

        try {
            $apiKey->user->notify(ApiKeyActivityNotification::revoked($apiKey));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return to_route('admin.users')->with('status', 'API key revoked and the account owner notified.');
    }
}
