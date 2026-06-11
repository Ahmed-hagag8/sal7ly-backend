<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('password_resets', function (Blueprint $table) {
            // Make phone nullable (email OTPs won't have a phone)
            $table->string('phone')->nullable()->change();

            // Add email column for email-based OTPs
            $table->string('email')->nullable()->after('phone');

            // Add index on email for fast lookups
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('password_resets', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn('email');
            $table->string('phone')->nullable(false)->change();
        });
    }
};
