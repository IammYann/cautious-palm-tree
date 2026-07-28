<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations — expand status enum to include refund statuses.
     *
     * SQLite's ALTER TABLE cannot modify columns, so we recreate the table.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->migrateSqlite();
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rollbackSqlite();
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }
    }

    private function migrateSqlite(): void
    {
        DB::statement('CREATE TABLE orders_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            quantity INTEGER DEFAULT 1,
            transaction_id VARCHAR(255) DEFAULT NULL UNIQUE,
            transaction_uuid VARCHAR(255) DEFAULT NULL UNIQUE,
            status VARCHAR(255) DEFAULT \'pending\' NOT NULL,
            payment_date DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )');

        DB::statement('INSERT INTO orders_new SELECT * FROM orders');
        DB::statement('DROP TABLE orders');
        DB::statement('ALTER TABLE orders_new RENAME TO orders');
    }

    private function rollbackSqlite(): void
    {
        DB::statement('CREATE TABLE orders_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            quantity INTEGER DEFAULT 1,
            transaction_id VARCHAR(255) DEFAULT NULL UNIQUE,
            transaction_uuid VARCHAR(255) DEFAULT NULL UNIQUE,
            status VARCHAR(255) DEFAULT \'pending\' NOT NULL
                CHECK (status IN (\'pending\', \'completed\', \'failed\', \'cancelled\')),
            payment_date DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )');

        DB::statement('INSERT INTO orders_new SELECT * FROM orders');
        DB::statement('DROP TABLE orders');
        DB::statement('ALTER TABLE orders_new RENAME TO orders');
    }
};
