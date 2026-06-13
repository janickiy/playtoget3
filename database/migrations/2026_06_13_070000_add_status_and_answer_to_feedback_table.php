<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table): void {
            if (! Schema::hasColumn('feedback', 'status')) {
                $table->tinyInteger('status')->default(0)->after('message');
            }

            if (! Schema::hasColumn('feedback', 'answer')) {
                $table->text('answer')->nullable()->after('status');
            }
        });

        $this->addIndexIfMissing('idx_feedback_status', ['status']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('idx_feedback_status');

        Schema::table('feedback', function (Blueprint $table): void {
            foreach (['answer', 'status'] as $column) {
                if (Schema::hasColumn('feedback', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Добавляет индекс, если он еще не создан в текущей базе.
     *
     * @param array<int, string> $columns
     */
    private function addIndexIfMissing(string $indexName, array $columns): void
    {
        if ($this->indexExists($indexName)) {
            return;
        }

        Schema::table('feedback', function (Blueprint $table) use ($columns, $indexName): void {
            $table->index($columns, $indexName);
        });
    }

    /**
     * Удаляет индекс, если он есть в текущей базе.
     */
    private function dropIndexIfExists(string $indexName): void
    {
        if (! $this->indexExists($indexName)) {
            return;
        }

        Schema::table('feedback', function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }

    /**
     * Проверяет наличие индекса таблицы feedback.
     */
    private function indexExists(string $indexName): bool
    {
        $indexes = Schema::getIndexes('feedback');

        foreach ($indexes as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
