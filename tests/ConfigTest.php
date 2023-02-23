<?php

use Spatie\NotificationLog\Exceptions\InvalidActionClass;
use Spatie\NotificationLog\Models\NotificationLogItem;
use Spatie\NotificationLog\Support\Config;
use Spatie\NotificationLog\Tests\TestSupport\CustomConvertEventToModelClass;
use Spatie\NotificationLog\Tests\TestSupport\Models\User;
use Spatie\NotificationLog\Tests\TestSupport\Notifications\TestNotification;

beforeEach(function() {
    $this->user = User::factory()->create();
});

it('will throw an exception when specifying an invalid action class', function() {
   config()->set('notification-log.actions.convertEventToModel', stdClass::class);

   Config::convertEventToModelAction();
})->throws(InvalidActionClass::class);

it('can use another event model class', function() {
    config()->set('notification-log.actions.convertEventToModel', CustomConvertEventToModelClass::class);

    $this->user->notify(new TestNotification());

    $logItem = NotificationLogItem::first();

    expect($logItem->extra)->toBe(['customName' => 'customKey']);
});
