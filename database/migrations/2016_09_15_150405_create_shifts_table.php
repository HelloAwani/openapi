<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Shift', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ShiftName');
            $table->string('From',5);
            $table->string('To',5);
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
        Schema::dropIfExists('Shift');
    }
}
