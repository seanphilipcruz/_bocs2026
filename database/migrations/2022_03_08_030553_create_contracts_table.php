<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->string('station');
            $table->unsignedBigInteger('agency_id');
            $table->unsignedBigInteger('advertiser_id');
            $table->string('product');
            $table->string('bo_type');
            $table->string('bo_number');
            $table->string('parent_bo')->nullable();
            $table->string('ce_number')->nullable();
            $table->date('bo_date')->nullable();
            $table->date('commencement')->nullable();
            $table->date('end_of_broadcast')->nullable();
            $table->longText('detail');
            $table->string('package_cost')->default('Package Cost(GROSS)');
            $table->string('package_cost_vat')->nullable();
            $table->integer('package_cost_salesdc')->nullable();
            $table->decimal('manila_cash', 50)->nullable()->default(0);
            $table->decimal('cebu_cash', 50)->nullable()->default(0);
            $table->decimal('davao_cash', 50)->nullable()->default(0);
            $table->decimal('total_cash', 50)->nullable()->default(0);
            $table->decimal('manila_ex', 50)->nullable()->default(0);
            $table->decimal('cebu_ex', 50)->nullable()->default(0);
            $table->decimal('davao_ex', 50)->nullable()->default(0);
            $table->decimal('total_ex', 50)->nullable()->default(0);
            $table->decimal('total_amount', 50)->nullable()->default(0);
            $table->string('prod_cost')->default('Production Cost(GROSS)');
            $table->string('prod_cost_vat')->nullable();
            $table->integer('prod_cost_salesdc')->nullable();
            $table->decimal('manila_prod', 50)->nullable()->default(0);
            $table->decimal('cebu_prod', 50)->nullable()->default(0);
            $table->decimal('davao_prod', 50)->nullable()->default(0);
            $table->decimal('total_prod', 50)->nullable()->default(0);
            $table->unsignedBigInteger('employee_id');
            $table->boolean('is_printed')->default(0);
            $table->boolean('is_active')->default(1);
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
        Schema::dropIfExists('contracts');
    }
}
