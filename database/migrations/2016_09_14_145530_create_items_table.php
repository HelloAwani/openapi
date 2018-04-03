<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Item', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ItemCode');
            $table->string('ItemName');
            $table->string('LocalID')->nullable();
            $table->bigInteger('ItemCategoryID');
            $table->decimal('Price', 20, 2);
            $table->bigInteger('BranchID');
            $table->bigInteger('BrandID');
            $table->string('Image')->nullable();
            $table->decimal('CurrentStock', 20, 2)->default(0);
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
        Schema::dropIfExists('Item');
    }
}
