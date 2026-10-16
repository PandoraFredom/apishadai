<?php

use Database\Seeders\TransferPermissionCatalogSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new TransferPermissionCatalogSeeder)->run();
    }

    public function down(): void
    {
        // Assignment and publication history cannot be safely rolled back automatically.
    }
};
