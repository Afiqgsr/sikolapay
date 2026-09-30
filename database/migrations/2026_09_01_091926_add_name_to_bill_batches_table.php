<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: The 'name' column is already included in the initial 'create_bill_batches_table' migration.
    }

    public function down(): void
    {
        // No-op: Do not drop column as it is managed by 'create_bill_batches_table'.
    }
};
