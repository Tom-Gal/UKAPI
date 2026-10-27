<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditEvent;
use App\Models\ServiceHealthIncident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdminIncidentController extends Controller
{
    public function acknowledge(Request $request, ServiceHealthIncident $incident): RedirectResponse
    {
        $attributes = $request->validate([
            'note' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $incident, $attributes): void {
            $incident = ServiceHealthIncident::query()->lockForUpdate()->findOrFail($incident->id);
            $incident->forceFill([
                'acknowledged_by' => $request->user()->id,
                'acknowledged_at' => now(),
                'acknowledgement_note' => $attributes['note'],
            ])->save();
            AdminAuditEvent::query()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'health_incident_acknowledged',
                'subject_type' => 'service_health_incident',
                'subject_id' => (string) $incident->id,
                'reason' => $attributes['note'],
                'metadata' => ['service' => $incident->service],
                'request_id' => $request->header('X-Request-Id'),
            ]);
        }, 3);

        return to_route('admin.operations')->with('status', 'Incident acknowledgement saved.');
    }
}
