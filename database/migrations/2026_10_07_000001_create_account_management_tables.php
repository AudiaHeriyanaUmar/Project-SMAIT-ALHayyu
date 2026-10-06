<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('account_activity_sessions')) {
            Schema::create('account_activity_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('session_hash', 64)->index();
                $table->dateTime('started_at');
                $table->dateTime('last_activity_at')->index();
                $table->dateTime('ended_at')->nullable()->index();
                $table->unsignedBigInteger('active_seconds')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'ended_at']);
            });
        }

        if (! Schema::hasTable('account_activity_daily')) {
            Schema::create('account_activity_daily', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('activity_date');
                $table->unsignedBigInteger('active_seconds')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'activity_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_activity_daily');
        Schema::dropIfExists('account_activity_sessions');
    }
};
