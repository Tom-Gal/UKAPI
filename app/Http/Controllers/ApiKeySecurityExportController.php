<?php

namespace App\Http\Controllers;

use App\Domain\ApiKeys\ApiKeyManager;
use App\Models\ApiKeyAuditEvent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ApiKeySecurityExportController extends Controller
{
    public function __invoke(Request $request, ApiKeyManager $manager): StreamedResponse
    {
        $events = ApiKeyAuditEvent::query()
            ->whereHas('apiKey', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('apiKey:id,user_id,public_id,name,environment')
            ->oldest()
            ->get();

        return response()->streamDownload(function () use ($events, $manager): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['occurred_at_utc', 'action', 'key_name', 'key_prefix', 'environment', 'request_id', 'details']);
            foreach ($events as $event) {
                fputcsv($output, [
                    $event->created_at?->utc()->toIso8601String(),
                    $event->action,
                    self::safeCsvCell($event->apiKey->name),
                    $manager->prefix($event->apiKey),
                    $event->apiKey->environment,
                    self::safeCsvCell($event->request_id),
                    $event->details ? json_encode($event->details, JSON_THROW_ON_ERROR) : '',
                ]);
            }

            fclose($output);
        }, 'ukapi-api-key-security-events.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function safeCsvCell(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'{$value}" : $value;
    }
}
