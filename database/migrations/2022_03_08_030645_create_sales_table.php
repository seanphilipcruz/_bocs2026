<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('advertiser_id');
            $table->unsignedBigInteger('agency_id');
            $table->string('station');
            $table->string('month');
            $table->year('year');
            $table->string('type');
            $table->string('transaction');
            $table->decimal('amount', 50);
            $table->decimal('gross_amount', 50);
            $table->decimal('invoice_amount');
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
}
