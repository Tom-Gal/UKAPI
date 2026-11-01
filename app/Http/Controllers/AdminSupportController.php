<?php

namespace App\Http\Controllers;

use App\Domain\ApiKeys\ApiKeyManager;
use App\Models\AdminAuditEvent;
use App\Models\ApiRequestLog;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class AdminSupportController extends Controller
{
    public function index(Request $request, ApiKeyManager $keys): Response
    {
        $filters = $request->validate([
            'request_id' => ['nullable', 'string', 'max:68', 'regex:/^req_[A-Za-z0-9_-]{0,64}$/'],
            'status' => ['nullable', Rule::in(['open', 'closed', 'all'])],
        ]);
        $requestId = trim((string) ($filters['request_id'] ?? ''));
        $status = (string) ($filters['status'] ?? 'open');

        $tickets = SupportTicket::query()
            ->with(['openedBy:id,name', 'closedBy:id,name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($requestId !== '', fn ($query) => $query->where('request_id', 'like', $this->searchPattern($requestId)))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (SupportTicket $ticket): array => $this->ticketData($ticket))
            ->values()
            ->all();

        $requestLogs = $requestId === ''
            ? []
            : ApiRequestLog::query()
                ->with(['user:id,name,email', 'apiKey:id,public_id,environment'])
                ->where('request_id', 'like', $this->searchPattern($requestId))
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (ApiRequestLog $log): array => $this->requestLogData($log, $keys))
                ->values()
                ->all();

        return Inertia::render('admin/support', [
            'requestId' => $requestId !== '' ? $requestId : null,
            'ticketStatus' => $status,
            'status' => $request->session()->get('status'),
            'openTicketsCount' => SupportTicket::query()->where('status', 'open')->count(),
            'closedTicketsCount' => SupportTicket::query()->where('status', 'closed')->count(),
            'requestLogs' => $requestLogs,
            'tickets' => $tickets,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'request_id' => ['nullable', 'string', 'max:68', 'regex:/^req_[A-Za-z0-9_-]{6,64}$/'],
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'notes' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $ticket = DB::transaction(function () use ($request, $attributes): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'request_id' => $attributes['request_id'] ?? null,
                'title' => $attributes['title'],
                'notes' => $attributes['notes'],
                'status' => 'open',
                'opened_by_user_id' => $request->user()->id,
            ]);

            AdminAuditEvent::query()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'support_ticket_opened',
                'subject_type' => 'support_ticket',
                'subject_id' => (string) $ticket->id,
                'reason' => 'Internal support ticket opened.',
                'metadata' => ['request_id' => $ticket->request_id],
                'request_id' => $request->header('X-Request-Id'),
            ]);

            return $ticket;
        }, 3);

        return to_route('admin.support', array_filter(['request_id' => $ticket->request_id]))
            ->with('status', "Ticket #{$ticket->id} opened.");
    }

    public function close(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $attributes = $request->validate([
            'resolution' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $ticket, $attributes): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            if ($ticket->status === 'closed') {
                return;
            }

            $ticket->forceFill([
                'status' => 'closed',
                'closed_by_user_id' => $request->user()->id,
                'closed_at' => now(),
                'resolution' => $attributes['resolution'],
            ])->save();

            AdminAuditEvent::query()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'support_ticket_closed',
                'subject_type' => 'support_ticket',
                'subject_id' => (string) $ticket->id,
                'reason' => 'Internal support ticket closed.',
                'metadata' => ['request_id' => $ticket->request_id],
                'request_id' => $request->header('X-Request-Id'),
            ]);
        }, 3);

        return to_route('admin.support', array_filter(['request_id' => $ticket->request_id]))
            ->with('status', "Ticket #{$ticket->id} closed.");
    }

    /** @return array<string, mixed> */
    private function requestLogData(ApiRequestLog $log, ApiKeyManager $keys): array
    {
        return [
            'id' => $log->id,
            'request_id' => $log->request_id,
            'method' => $log->method,
            'route_path' => $log->route_path,
            'route_name' => $log->route_name,
            'endpoint' => $log->endpoint,
            'status' => $log->status,
            'duration_ms' => $log->duration_ms,
            'error_code' => $log->error_code,
            'error_reason' => $log->error_reason,
            'error_message' => $log->error_message,
            'cache_state' => $log->cache_state,
            'user' => $log->user ? [
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
            'api_key_prefix' => $log->apiKey ? $keys->prefix($log->apiKey) : null,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function ticketData(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'request_id' => $ticket->request_id,
            'title' => $ticket->title,
            'notes' => $ticket->notes,
            'status' => $ticket->status,
            'opened_by' => $ticket->openedBy !== null ? $ticket->openedBy->name : 'Former administrator',
            'closed_by' => $ticket->closedBy?->name,
            'closed_at' => $ticket->closed_at?->toIso8601String(),
            'resolution' => $ticket->resolution,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    private function searchPattern(string $requestId): string
    {
        return $requestId.'%';
    }
}
