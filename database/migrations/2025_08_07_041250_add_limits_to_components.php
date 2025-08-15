```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLimitsToComponents extends Migration
{
    public function up()
    {
        Schema::table('components', function (Blueprint $table) {
            $table->float('minLimit')->default(0)->after('desc');
            $table->float('maxLimit')->default(100)->after('minLimit');
        });
    }

    public function down()
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['minLimit', 'maxLimit']);
        });
    }
}