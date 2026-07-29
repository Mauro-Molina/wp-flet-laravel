<?php

namespace App\Http\Controllers\Api\V1\Plugin;

use App\Domain\Events\IngestEventsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plugin\IngestEventsRequest;
use App\Models\Site;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function store(IngestEventsRequest $request, IngestEventsAction $action): JsonResponse
    {
        /** @var Site $site */
        $site = $request->attributes->get('plugin_site');

        $stored = $action->execute($site, $request->input('events'));

        return ApiResponse::success([
            'ingested' => count($stored),
            'event_ids' => collect($stored)->pluck('id')->values(),
        ], status: 201);
    }
}
