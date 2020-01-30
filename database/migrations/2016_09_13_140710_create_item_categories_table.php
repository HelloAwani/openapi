<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ItemCategory', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('CategoryName');
            $table->string('CategoryCode');
            $table->string('LocalID')->nullable();
            $table->string('Image')->nullable();
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
        Schema::dropIfExists('ItemCategory');
    }
}
