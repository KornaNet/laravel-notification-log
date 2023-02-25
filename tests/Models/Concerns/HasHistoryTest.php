<?php

use Spatie\NotificationLog\Models\NotificationLogItem;
use Spatie\NotificationLog\Tests\TestSupport\Models\User;
use Spatie\NotificationLog\Tests\TestSupport\Notifications\HasHistoryNotification;
use Spatie\TestTime\TestTime;

beforeEach(function () {
    TestTime::freeze();

    $this->notifiable = User::factory()->create();
});

it('can determine if it was sent in the past hour', function (
    int $createdMinutesAgo,
    bool $expectedResult,
) {
    NotificationLogItem::factory()
        ->forNotifiable($this->notifiable)
        ->create([
            'notification_type' => HasHistoryNotification::class,
            'created_at' => now()->subMinutes($createdMinutesAgo),
        ]);

    $hasHistoryCalls = function ($notifiable) {
        return $this
            ->wasSentTo($notifiable)
            ->inThePastMinutes(60);
    };

    expect(executeInNotification($hasHistoryCalls, $this->notifiable))
        ->toBe($expectedResult);
})->with([
    [59, true],
    [60, true],
    [61, false],
]);

function executeInNotification(Closure $closure, User $notifiable): bool
{
    $closure = Closure::bind($closure, new HasHistoryNotification());

    HasHistoryNotification::setHistoryTestCallable($closure);

    return (new HasHistoryNotification())->historyTest($notifiable);
}
