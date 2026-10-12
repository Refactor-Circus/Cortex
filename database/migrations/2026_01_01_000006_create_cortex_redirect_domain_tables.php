<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Cortex\Support\Tables;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::name('redirect_domains'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('domain');
            // Any model may own a domain (an organization, a user); none is
            // a domain for every client. Strings hold integer and ULID keys alike.
            $table->string('owner_type')->nullable();
            $table->string('owner_id')->nullable();
            $table->timestamps();

            $table->index('domain');
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('redirect_domains'));
    }
};
