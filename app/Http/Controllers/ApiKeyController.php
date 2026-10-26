<?php

namespace App\Http\Controllers;

use App\Domain\ApiKeys\ApiKeyManager;
use App\Domain\ApiKeys\EndpointFamily;
use App\Domain\ApiKeys\IpAllowList;
use App\Domain\Usage\Entitlements;
use App\Models\ApiKey;
use App\Models\ApiKeyAuditEvent;
use App\Notifications\ApiKeyActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ApiKeyController extends Controller
{
    public function index(Request $request, Entitlements $entitlements, ApiKeyManager $manager): Response
    {
        $user = $request->user();
        $plan = $entitlements->forUser($user);

        return Inertia::render('dashboard/api-keys', [
            'apiKeys' => $user->apiKeys()
                ->latest()
                ->get()
                ->map(fn (ApiKey $key): array => [
                    'id' => $key->id,
                    'name' => $key->name,
                    'prefix' => $manager->prefix($key),
                    'environment' => $key->environment,
                    'status' => $this->effectiveStatus($key),
                    'scopes' => $key->scopes,
                    'ip_allow_list' => $key->ip_allow_list ?? [],
                    'last_used_endpoint_family' => $key->last_used_endpoint_family,
                    'last_used_endpoint_family_label' => $key->last_used_endpoint_family
                        ? EndpointFamily::label($key->last_used_endpoint_family)
                        : null,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'expires_at' => $key->expires_at?->toIso8601String(),
                    'created_at' => $key->created_at->toIso8601String(),
                    'revoked_at' => $key->revoked_at?->toIso8601String(),
                ]),
            'auditEvents' => ApiKeyAuditEvent::query()
                ->whereHas('apiKey', fn ($query) => $query->where('user_id', $user->id))
                ->with('apiKey:id,user_id,public_id,name,environment')
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (ApiKeyAuditEvent $event): array => [
                    'id' => $event->id,
                    'action' => $event->action,
                    'request_id' => $event->request_id,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'key_name' => $event->apiKey->name,
                    'key_prefix' => $manager->prefix($event->apiKey),
                    'details' => $event->details,
                ]),
            'scopeOptions' => EndpointFamily::options(),
            'canUseIpAllowLists' => in_array('ip_allow_lists', $plan['features'], true),
            'planLabel' => $plan['label'],
        ]);
    }

    public function store(
        Request $request,
        Entitlements $entitlements,
        ApiKeyManager $manager,
    ): RedirectResponse {
        $identity = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'environment' => ['required', 'in:live,test'],
        ]);
        $policy = $this->validatePolicy($request, $entitlements);
        $issued = $manager->issue(
            user: $request->user(),
            name: $identity['name'],
            environment: $identity['environment'],
            scopes: $policy['scopes'],
            ipAllowList: $policy['ip_allow_list'],
            requestId: $request->header('X-Request-Id'),
        );
        $request->user()->notify(ApiKeyActivityNotification::created($issued['key']));

        return to_route('api-keys.index')->with('api_key_created', [
            'name' => $issued['key']->name,
            'token' => $issued['token'],
            'activity' => 'created',
        ]);
    }

    public function update(
        Request $request,
        ApiKey $apiKey,
        Entitlements $entitlements,
        ApiKeyManager $manager,
    ): RedirectResponse {
        $this->authoriseOwner($request, $apiKey);
        $attributes = $this->validatePolicy($request, $entitlements, $apiKey->ip_allow_list ?? []);
        $manager->updatePolicy(
            apiKey: $apiKey,
            actor: $request->user(),
            scopes: $attributes['scopes'],
            ipAllowList: $attributes['ip_allow_list'],
            requestId: $request->header('X-Request-Id'),
        );

        return to_route('api-keys.index')->with('status', 'API key policy updated.');
    }

    public function rotate(Request $request, ApiKey $apiKey, ApiKeyManager $manager): RedirectResponse
    {
        $this->authoriseOwner($request, $apiKey);
        if ($this->effectiveStatus($apiKey) !== 'active') {
            throw ValidationException::withMessages(['api_key' => 'Only an active key can be rotated.']);
        }

        $attributes = $request->validate([
            'overlap_hours' => [
                'required',
                'integer',
                'min:0',
                'max:'.max(0, (int) config('ukapi.api_key_rotation_max_overlap_hours', 24)),
            ],
        ]);
        $issued = $manager->rotate(
            apiKey: $apiKey,
            actor: $request->user(),
            overlapHours: (int) $attributes['overlap_hours'],
            requestId: $request->header('X-Request-Id'),
        );
        $request->user()->notify(ApiKeyActivityNotification::created($issued['key']));

        return to_route('api-keys.index')->with('api_key_created', [
            'name' => $issued['key']->name,
            'token' => $issued['token'],
            'activity' => 'rotated',
            'overlap_hours' => (int) $attributes['overlap_hours'],
        ]);
    }

    public function destroy(Request $request, ApiKey $apiKey, ApiKeyManager $manager): RedirectResponse
    {
        $this->authoriseOwner($request, $apiKey);
        if ($manager->revoke($apiKey, $request->user(), $request->header('X-Request-Id'))) {
            $request->user()->notify(ApiKeyActivityNotification::revoked($apiKey));
        }

        return to_route('api-keys.index');
    }

    /**
     * @param  list<string>  $existingIpAllowList
     * @return array{scopes: list<string>|null, ip_allow_list: list<string>}
     */
    private function validatePolicy(
        Request $request,
        Entitlements $entitlements,
        array $existingIpAllowList = [],
    ): array {
        $attributes = $request->validate([
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', Rule::in(EndpointFamily::keys())],
            'ip_allow_list' => ['nullable', 'string', 'max:2000'],
        ]);
        try {
            $ipAllowList = IpAllowList::fromText($attributes['ip_allow_list'] ?? null);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['ip_allow_list' => $exception->getMessage()]);
        }
        $plan = $entitlements->forUser($request->user());
        if (
            $ipAllowList !== []
            && $ipAllowList !== $existingIpAllowList
            && ! in_array('ip_allow_lists', $plan['features'], true)
        ) {
            throw ValidationException::withMessages([
                'ip_allow_list' => 'IP allow lists are available on Pro and Scale plans.',
            ]);
        }

        $scopes = array_values(array_unique($attributes['scopes'] ?? []));

        return [
            'scopes' => $scopes === [] ? null : $scopes,
            'ip_allow_list' => $ipAllowList,
        ];
    }

    private function authoriseOwner(Request $request, ApiKey $apiKey): void
    {
        abort_unless($apiKey->user_id === $request->user()->id, 404);
    }

    private function effectiveStatus(ApiKey $apiKey): string
    {
        if ($apiKey->status === 'active' && $apiKey->expires_at) {
            return $apiKey->expires_at->isPast() ? 'expired' : 'rotating';
        }

        return $apiKey->status;
    }
}
