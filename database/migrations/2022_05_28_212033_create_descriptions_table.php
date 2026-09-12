<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDescriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('descriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('title');
            $table->string('description');
            $table->string('icon')->default('fas fa-question');
            $table->string('color')->default('grey darken-1');
            $table->timestamps();
        });

        $data = [
            [
                'job_id' => '1',
                'title' => 'Managing Contracts',
                'description' => 'Users can create and edit a contract, able to activate/deactivate, generate PDF and Raw Text files for Download, Add Sales and View Breakdowns',
                'icon' => 'fas fa-suitcase',
                'color' => 'cyan darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '1',
                'title' => 'Managing Sales',
                'description' => 'Users can create, edit, and view breakdowns',
                'icon' => 'fas fa-search-dollar',
                'color' => 'red darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '1',
                'title' => 'Managing User Accesses',
                'description' => 'Users can create, edit, and view user accesses in the system',
                'icon' => 'fas fa-user-lock',
                'color' => 'purple darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '1',
                'title' => 'Managing Employees',
                'description' => 'Users can create, edit, and view employee accounts, they are also eligible of updating the user account\'s password.',
                'icon' => 'fas fa-user-cog',
                'color' => 'green darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '1',
                'title' => 'Access the Sales Report',
                'description' => 'Users can view the sales report',
                'icon' => 'fas fa-user-chart',
                'color' => 'teal darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '1',
                'title' => 'See the Discrepancies',
                'description' => 'Users can also see the discrepancy notification for data accuracy',
                'icon' => 'fas fa-ballot-check',
                'color' => 'pink darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '1',
                'title' => 'Check the logs',
                'description' => 'Users can also see the logs which are the actions committed by each user/s using the system.',
                'icon' => 'fas fa-list-ul',
                'color' => 'blue-gray darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '2',
                'title' => 'Managing Contracts',
                'description' => 'Users can create and edit a contract, able to activate/deactivate, generate PDF and Raw Text files for Download, Add Sales and View Breakdowns',
                'icon' => 'fas fa-suitcase',
                'color' => 'cyan darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '2',
                'title' => 'Managing Sales',
                'description' => 'Users can create, edit, and view breakdowns',
                'icon' => 'fas fa-search-dollar',
                'color' => 'red darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '2',
                'title' => 'Access the Sales Report',
                'description' => 'Users can view the sales report',
                'icon' => 'fas fa-user-chart',
                'color' => 'teal darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '3',
                'title' => 'Managing Contracts',
                'description' => 'Users can create and edit a contract, able to activate/deactivate, generate PDF and Raw Text files for Download, Add Sales and View Breakdowns',
                'icon' => 'fas fa-suitcase',
                'color' => 'cyan darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '3',
                'title' => 'Viewing Sales',
                'description' => 'Users view breakdowns',
                'icon' => 'fas fa-search-dollar',
                'color' => 'red darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '3',
                'title' => 'Access the Sales Report',
                'description' => 'Users can view the sales report',
                'icon' => 'fas fa-user-chart',
                'color' => 'teal darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '3',
                'title' => 'See the Discrepancies',
                'description' => 'Users can also see the discrepancy notification for data accuracy',
                'icon' => 'fas fa-ballot-check',
                'color' => 'pink darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '4',
                'title' => 'Managing Sales',
                'description' => 'Users can add invoice, and view breakdowns',
                'icon' => 'fas fa-search-dollar',
                'color' => 'red darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '4',
                'title' => 'Access the Sales Report',
                'description' => 'Users can view the sales report',
                'icon' => 'fas fa-user-chart',
                'color' => 'teal darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '4',
                'title' => 'Monitor Sales Report',
                'description' => 'Users can check if the contract is close to full payment, view for discrepancies such as adjustments and remaining balance of a contract and report it to Traffic',
                'icon' => 'fas fa-ballot',
                'color' => 'amber darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '5',
                'title' => 'Managing Advertisers and Agencies',
                'description' => 'User can create, read, update and delete advertisers',
                'icon' => 'fas fa-ad',
                'color' => 'lime darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '5',
                'title' => 'Managing Contracts',
                'description' => 'Users can create and edit a contract, able to activate/deactivate, generate PDF and Raw Text files for Download, Add Sales and View Breakdowns',
                'icon' => 'fas fa-suitcase',
                'color' => 'cyan darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '5',
                'title' => 'Managing Sales',
                'description' => 'Users can create, edit, and view breakdowns',
                'icon' => 'fas fa-search-dollar',
                'color' => 'red darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '5',
                'title' => 'Managing User Accesses',
                'description' => 'Users can create, edit, and view user accesses in the system',
                'icon' => 'fas fa-user-lock',
                'color' => 'purple darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '5',
                'title' => 'Managing Employees',
                'description' => 'Users can create, edit, and view employee accounts, they are also eligible of updating the user account\'s password.',
                'icon' => 'fas fa-user-cog',
                'color' => 'green darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '5',
                'title' => 'Access the Sales Report',
                'description' => 'Users can view the sales report',
                'icon' => 'fas fa-user-chart',
                'color' => 'teal darken-1',
                'created_at' => Carbon::now(),
            ],
            [
                'job_id' => '5',
                'title' => 'See the Discrepancies',
                'description' => 'Users can also see the discrepancy notification for data accuracy',
                'icon' => 'fas fa-ballot-check',
                'color' => 'pink darken-1',
                'created_at' => Carbon::now(),
            ]];

        DB::table('descriptions')->insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('descriptions');
    }
}
