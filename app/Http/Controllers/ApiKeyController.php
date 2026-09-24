<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Notifications\ApiKeyActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('dashboard/api-keys', [
            'apiKeys' => $request->user()->apiKeys()
                ->latest()
                ->get()
                ->map(fn (ApiKey $key) => [
                    'id' => $key->id,
                    'name' => $key->name,
                    'prefix' => $this->prefix($key),
                    'environment' => $key->environment,
                    'status' => $key->status,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'created_at' => $key->created_at->toIso8601String(),
                    'revoked_at' => $key->revoked_at?->toIso8601String(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'environment' => ['required', 'in:live,test'],
        ]);

        $publicId = 'k_'.Str::lower(Str::random(10));
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $key = $request->user()->apiKeys()->create([
            'public_id' => $publicId,
            'secret_hash' => Hash::make($secret),
            'name' => $attributes['name'],
            'environment' => $attributes['environment'],
        ]);

        $key->auditEvents()->create([
            'actor_user_id' => $request->user()->id,
            'action' => 'created',
            'request_id' => $request->header('X-Request-Id'),
        ]);

        $request->user()->notify(ApiKeyActivityNotification::created($key));

        return to_route('api-keys.index')->with('api_key_created', [
            'name' => $key->name,
            'token' => "uk_{$key->environment}_{$key->public_id}.{$secret}",
        ]);
    }

    public function destroy(Request $request, ApiKey $apiKey): RedirectResponse
    {
        abort_unless($apiKey->user_id === $request->user()->id, 404);

        if ($apiKey->status === 'active') {
            $apiKey->forceFill([
                'status' => 'revoked',
                'revoked_at' => now(),
            ])->save();

            $apiKey->auditEvents()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'revoked',
                'request_id' => $request->header('X-Request-Id'),
            ]);

            $request->user()->notify(ApiKeyActivityNotification::revoked($apiKey));
        }

        return to_route('api-keys.index');
    }

    private function prefix(ApiKey $key): string
    {
        return "uk_{$key->environment}_{$key->public_id}";
    }
}
