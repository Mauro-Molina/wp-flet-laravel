<?php

namespace App\Http\Controllers\Api\V1\Plugin;

use App\Domain\Commands\ListPendingCommandsAction;
use App\Domain\Hmac\HmacService;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plugin\HeartbeatRequest;
use App\Jobs\ProcessHeartbeatSnapshotJob;
use App\Models\Command;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class HeartbeatController extends Controller
{
    public function store(
        HeartbeatRequest $request,
        ListPendingCommandsAction $listPending,
        HmacService $hmac,
    ): JsonResponse {
        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');
        /** @var \App\Models\SiteCredential $credential */
        $credential = $request->attributes->get('plugin_credential');

        TenantContext::set($site->tenant_id);

        $wasPending = $site->status === 'pending';

        $site->forceFill([
            'last_seen_at' => now(),
            'status' => 'connected',
            'connected_at' => $site->connected_at ?? now(),
        ])->save();

        if ($request->has('plugins') || $request->has('themes')) {
            ProcessHeartbeatSnapshotJob::dispatch(
                $site->id,
                $request->input('plugins'),
                $request->input('themes'),
            );
        }

        $pending = $listPending->execute($site);

        $commandsPayload = $pending->map(fn (Command $cmd) => [
            'id' => $cmd->id,
            'type' => $cmd->type,
            'payload' => $cmd->payload,
            'expires_at' => $cmd->expires_at?->toIso8601String(),
        ])->values()->all();

        $signed = $hmac->signOutbound($credential->plainSecret(), ['commands' => $commandsPayload]);

        return ApiResponse::success([
            'site_id' => $site->id,
            'status' => $site->status,
            'first_connection' => $wasPending,
            'commands' => $commandsPayload,
            'commands_signature' => $signed['signature'],
            'commands_timestamp' => $signed['timestamp'],
        ]);
    }
}
