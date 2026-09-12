<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSalesReportIndexesToSalesTable extends Migration
{
    public function up()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('CREATE INDEX sales_year_month_idx ON sales (year, month(2))');
            DB::statement('CREATE INDEX sales_employee_year_month_idx ON sales (employee_id, year, month(2))');
            DB::statement('CREATE INDEX sales_agency_year_idx ON sales (agency_id, year)');
            DB::statement('CREATE INDEX sales_advertiser_year_idx ON sales (advertiser_id, year)');
            DB::statement('CREATE INDEX sales_station_year_month_idx ON sales (station(64), year, month(2))');
            DB::statement('CREATE INDEX sales_transaction_year_month_idx ON sales (transaction(32), year, month(2))');
            DB::statement('CREATE INDEX sales_invoice_no_idx ON sales (invoice_no(64))');

            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['year', 'month'], 'sales_year_month_idx');
            $table->index(['employee_id', 'year', 'month'], 'sales_employee_year_month_idx');
            $table->index(['agency_id', 'year'], 'sales_agency_year_idx');
            $table->index(['advertiser_id', 'year'], 'sales_advertiser_year_idx');
            $table->index(['station', 'year', 'month'], 'sales_station_year_month_idx');
            $table->index(['transaction', 'year', 'month'], 'sales_transaction_year_month_idx');
            $table->index('invoice_no', 'sales_invoice_no_idx');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_year_month_idx');
            $table->dropIndex('sales_employee_year_month_idx');
            $table->dropIndex('sales_agency_year_idx');
            $table->dropIndex('sales_advertiser_year_idx');
            $table->dropIndex('sales_station_year_month_idx');
            $table->dropIndex('sales_transaction_year_month_idx');
            $table->dropIndex('sales_invoice_no_idx');
        });
    }
}
