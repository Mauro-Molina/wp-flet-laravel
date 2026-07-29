<?php

namespace App\Jobs;

use App\Models\NotificationHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Stub dispatcher — replace with FCM/APNs/email providers in production.
 */
class DispatchNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $notificationId) {}

    public function handle(): void
    {
        $notification = NotificationHistory::query()->find($this->notificationId);

        if ($notification === null) {
            return;
        }

        Log::info('notification_dispatch_stub', [
            'id' => $notification->id,
            'channel' => $notification->channel,
            'event_type' => $notification->event_type,
            'payload' => $notification->payload,
        ]);

        $notification->forceFill([
            'status' => 'sent',
            'sent_at' => now(),
        ])->save();
    }
}
