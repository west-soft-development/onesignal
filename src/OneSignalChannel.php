<?php

namespace NotificationChannels\OneSignal;

use Berkayk\OneSignal\OneSignalClient;
use NotificationChannels\OneSignal\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;
use Psr\Http\Message\ResponseInterface;

class OneSignalChannel
{
    /** @var OneSignalClient */
    protected $oneSignal;

    public function __construct(OneSignalClient $oneSignal)
    {
        $this->oneSignal = $oneSignal;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @throws \NotificationChannels\OneSignal\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $userIds = $notifiable->routeNotificationFor('OneSignal')) {
            return;
        }

        $payload = $notification->toOneSignal($notifiable)->toArray();
        $payload['include_player_ids'] = collect($userIds);
        
        // if badge is set as data, include it in the request
        if (isset($payload['data']['badge']) && is_array($payload['data']['badge'])) {
            if (isset($payload['data']['badge']['type'])) {
                $payload['ios_badgeType'] = $payload['data']['badge']['type'];
            }

            if (isset($payload['data']['badge']['type'])) {
                $payload['ios_badgeCount'] = $payload['data']['badge']['count'];
            }
        }

        /** @var ResponseInterface $response */
        $response = $this->oneSignal->sendNotificationCustom($payload);

        if ($response->getStatusCode() !== 200) {
            throw CouldNotSendNotification::serviceRespondedWithAnError($response);
        }
    }
}
