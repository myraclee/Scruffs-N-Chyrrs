<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $missingColumns = [];

        if (!Schema::hasColumn('users', 'login_attempts')) {
            $missingColumns[] = 'login_attempts';
        }

        if (!Schema::hasColumn('users', 'lockout_until')) {
            $missingColumns[] = 'lockout_until';
        }

        if (!Schema::hasColumn('users', 'is_locked')) {
            $missingColumns[] = 'is_locked';
        }

        if ($missingColumns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($missingColumns) {
            if (in_array('login_attempts', $missingColumns, true)) {
                $table->integer('login_attempts')->default(0)->after('updated_at');
            }

            if (in_array('lockout_until', $missingColumns, true)) {
                $table->timestamp('lockout_until')->nullable()->after('login_attempts');
            }

            if (in_array('is_locked', $missingColumns, true)) {
                $table->boolean('is_locked')->default(false)->after('lockout_until');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingColumns = [];

        if (Schema::hasColumn('users', 'is_locked')) {
            $existingColumns[] = 'is_locked';
        }

        if (Schema::hasColumn('users', 'lockout_until')) {
            $existingColumns[] = 'lockout_until';
        }

        if (Schema::hasColumn('users', 'login_attempts')) {
            $existingColumns[] = 'login_attempts';
        }

        if ($existingColumns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }
};
