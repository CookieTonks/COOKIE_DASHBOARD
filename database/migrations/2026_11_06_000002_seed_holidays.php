<?php

use App\Models\Holiday;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Holiday::ensureYear((int) now()->year);
        Holiday::ensureYear((int) now()->year + 1);
    }

    public function down(): void
    {
        Holiday::query()->delete();
    }
};
