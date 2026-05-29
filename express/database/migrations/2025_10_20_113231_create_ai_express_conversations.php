<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     */
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->create('ai_express_conversations', function (Blueprint $collection) {
            // Create indexes for better query performance
            $collection->index('conversation_id');
            $collection->index('user_id');
            $collection->index('platform');
            $collection->index('created_at');
            $collection->index('updated_at');
            $collection->index(['user_id' => 1, 'created_at' => -1]); // Compound index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('ai_express_conversations');
    }
};
