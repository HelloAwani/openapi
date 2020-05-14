<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('UserCode');
            $table->bigInteger('ShiftID');
            $table->bigInteger('UserTypeID');
            $table->string('Fullname');
            $table->date('JoinDate');
            $table->bigInteger('BrandID');
            $table->bigInteger('BranchID');
            $table->text('Description')->nullable();
            $table->enum('ActiveStatus', ['A', 'D'])->default('A');
            $table->char('PIN',6);
            $table->string('StatusOnline',20);
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
        Schema::dropIfExists('Users');
    }
}
