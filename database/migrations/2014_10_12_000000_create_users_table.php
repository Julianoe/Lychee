<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    if(!Schema::hasTable('users')) {
		    Schema::create('users', function (Blueprint $table) {
			    $table->increments('id');
			    $table->string('username')->unique();
			    $table->string('password');
			    $table->boolean('upload')->default(false);
			    $table->boolean('lock')->default(false);
			    $table->rememberToken();
			    $table->timestamps();
		    });
	    }
	    else {
		    echo "Table users already exists\n";
	    }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	if(env('DB_DROP_CLEAR_TABLES_ON_ROLLBACK',false))
	    {
		    Schema::dropIfExists('users');
	    }
    }
}
