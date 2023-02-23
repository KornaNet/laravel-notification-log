<?php

namespace Spatie\NotificationLog\Actions;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSending;
use Spatie\NotificationLog\Exceptions\InvalidExtraContent;
use Spatie\NotificationLog\Models\NotificationLogItem;
use Spatie\NotificationLog\Support\Config;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\AnonymousNotifiable;

class ConvertNotificationSendingEventToLogItem
{
    public function execute(NotificationSending $event): ?NotificationLogItem
    {
        $modelClass = Config::modelClass();

        return $modelClass::create([
            'notification_type' => $this->getNotificationType($event),
            'notifiable_type' => $this->getNotifiableType($event),
            'notifiable_id'  =>  $this->getNotifiableKey($event),
            'channel' => $event->channel,
            'extra' => $this->getExtra($event),
            'anonymous_notifiable_properties' => $this->getAnonymousNotifiableProperties($event)
        ]);
    }

    protected function getNotifiableType(NotificationSending $event): ?string
    {
        /** @var Model|AnonymousNotifiable $notifiable */
        $notifiable = $event->notifiable;

        return $notifiable instanceof Model
            ?  $notifiable->getMorphClass()
            : null;
    }

    protected function getNotifiableKey(NotificationSending $event): mixed
    {
        /** @var Model|AnonymousNotifiable $notifiable */
        $notifiable = $event->notifiable;

        return $notifiable instanceof Model
            ?  $notifiable->getKey()
            : null;
    }

    protected function getNotificationType(NotificationSending $event): string
    {
        return get_class($event->notification);
    }

    /**
     * @return class-string<NotificationLogItem>
     */
    protected function getModelClass(NotificationSending $event): string
    {
        return config('notification-log.model');
    }

    /**
     * @return class-string<NotificationLogItem>
     */
    protected function getExtra(NotificationSending $event): array
    {
        $notification = $event->notification;

        if (method_exists($notification, 'logExtra', )) {
            $extra = $notification->logExtra($event);

            if (! is_array($extra)) {
                throw InvalidExtraContent::make($notification);
            }

            return $extra;
        }

        return [];
    }

    protected function getAnonymousNotifiableProperties(NotificationSending $event): ?array
    {
        if (! $event->notifiable instanceof AnonymousNotifiable) {
            return null;
        }

        return $event->notifiable->routes;
    }
}
