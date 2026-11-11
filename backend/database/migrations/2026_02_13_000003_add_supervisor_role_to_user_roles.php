<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_roles')) {
            DB::statement("ALTER TABLE user_roles MODIFY role_type ENUM('pathologist','verifier','performer','supervisor')");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_roles')) {
            DB::statement("ALTER TABLE user_roles MODIFY role_type ENUM('pathologist','verifier','performer')");
        }
    }
};
