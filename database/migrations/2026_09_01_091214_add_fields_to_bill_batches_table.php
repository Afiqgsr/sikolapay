<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: The fields 'description', 'semester', 'amount', 'due_date', 'target_type', and 'target_value'
        // are already included in the initial 'create_bill_batches_table' migration.
    }

    public function down(): void
    {
        // No-op: Do not drop columns as they are managed by 'create_bill_batches_table'.
    }
};
