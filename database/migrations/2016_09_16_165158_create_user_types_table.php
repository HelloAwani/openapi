<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('UserType', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('UserTypeCode');
            $table->string('UserTypeName');
            $table->bigInteger('BrandID');
            $table->bigInteger('BranchID');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('UserType');
    }
}
