<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'skype') && ! Schema::hasColumn('users', 'telegram')) {
                $table->renameColumn('skype', 'telegram');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'telegram')) {
                $table->string('telegram')->nullable()->after('contact_email');
            }

            if (! Schema::hasColumn('users', 'whatsapp')) {
                $table->text('whatsapp')->nullable()->after('telegram');
            }

            if (! Schema::hasColumn('users', 'viber')) {
                $table->text('viber')->nullable()->after('whatsapp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'viber')) {
                $table->dropColumn('viber');
            }

            if (Schema::hasColumn('users', 'whatsapp')) {
                $table->dropColumn('whatsapp');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'telegram') && ! Schema::hasColumn('users', 'skype')) {
                $table->renameColumn('telegram', 'skype');
            }
        });
    }
};
