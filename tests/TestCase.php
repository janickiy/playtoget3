<?php

namespace Tests;

use App\Http\Middleware\TrackUserOnlineStatus;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(TrackUserOnlineStatus::class);

        if (! Schema::hasTable('user_activity')) {
            Schema::create('user_activity', function (Blueprint $table): void {
                $table->integer('id', true);
                $table->integer('user_id')->nullable();
                $table->dateTime('last_activity')->nullable();
                $table->index('user_id', 'id_user');
                $table->index(['user_id', 'last_activity'], 'idx_user_activity_user_last');
            });
        }
    }
}
