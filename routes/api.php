<?php

use App\Http\Controllers\AdvertiserController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'name' => config('app.name'),
        'message' => 'BOCS API is running.',
        'login_url' => url('/api/login'),
    ]);
});

Route::post('/login', [AuthenticationController::class, 'login'])->name('authentication.login');

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/logout', [AuthenticationController::class, 'logout'])->name('authentication.logout');

    Route::group(['prefix' => 'home'], function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/rankings', [HomeController::class, 'getRanked'])->name('home.get-ranked');
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('discrepancy', [NotificationController::class, 'index'])->name('get-discrepancies');
    });

    Route::group(['prefix' => 'advertisers'], function () {
        Route::get('/', [AdvertiserController::class, 'index'])->name('advertiser.index');
        Route::get('show/{id}', [AdvertiserController::class, 'show'])->name('advertiser.show');
        Route::post('store', [AdvertiserController::class, 'store'])->name('advertiser.store');
        Route::match(['put', 'patch'], 'update/{id}', [AdvertiserController::class, 'update'])->name('advertiser.update');
        Route::get('/check', [AdvertiserController::class, 'checkName'])->name('advertiser.check-name');
    });

    Route::group(['prefix' => 'agencies'], function () {
        Route::get('/', [AgencyController::class, 'index'])->name('agency.index');
        Route::get('show/{id}', [AgencyController::class, 'show'])->name('agency.show');
        Route::post('store', [AgencyController::class, 'store'])->name('agency.store');
        Route::match(['put', 'patch'], 'update/{id}', [AgencyController::class, 'update'])->name('agency.update');
        Route::get('/check', [AgencyController::class, 'checkName'])->name('agency.check-name');
    });

    Route::group(['prefix' => 'contracts'], function () {
        Route::get('/', [ContractController::class, 'index'])->name('contract.index');
        Route::get('show/{id}', [ContractController::class, 'show'])->name('contract.show');
        Route::post('store', [ContractController::class, 'store'])->name('contract.store');
        Route::match(['put', 'patch'], 'update/{id}', [ContractController::class, 'update'])->name('contract.update');
        Route::get('/{id}/generate/pdf', [ContractController::class, 'generatePDF'])->name('contract.generate-pdf');
        Route::get('/{id}/generate/text', [ContractController::class, 'generateText'])->name('contract.generate-text');
        Route::get('/{id}/set/status', [ContractController::class, 'setContractStatus'])->name('contract.set-contract-status');
        Route::get('get/account/executive/{id}', [ContractController::class, 'getExecutive'])->name('get.account.executive');
        Route::get('get/sales', [ContractController::class, 'getContractSales'])->name('get.contract.sales');
    });

    Route::group(['prefix' => 'sales'], function () {
        Route::get('/', [SaleController::class, 'index'])->name('sale.index');
        Route::get('show/{id}', [SaleController::class, 'show'])->name('sale.show');
        Route::post('store', [SaleController::class, 'store'])->name('sale.store');
        Route::match(['put', 'patch'], 'update/{id}', [SaleController::class, 'update'])->name('sale.update');
        Route::post('delete/{id}', [SaleController::class, 'destroy'])->name('sale.destroy');
        Route::put('store/invoice/{id}', [SaleController::class, 'save_invoice'])->name('save_invoice');
        Route::get('revision/show/{id}', [SaleController::class, 'get_revision'])->name('sale.get_revision');

        Route::group(['prefix' => 'reports'], function () {
            Route::get('/', [SalesReportController::class, 'index'])->name('sales.report');
            Route::get('/monthly', [SalesReportController::class, 'getMonthly'])->name('sales-report.get-monthly');
            Route::get('/executives', [SalesReportController::class, 'getExecutives'])->name('sales-report.get-executives');
            Route::get('/accounting/monitoring', [SalesReportController::class, 'getAccounting'])->name('sales-report.get-accounting');
        });
    });

    Route::group(['prefix' => 'employees'], function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('employee.index');
        Route::get('show/{id}', [EmployeeController::class, 'show'])->name('employee.show');
        Route::post('store', [EmployeeController::class, 'store'])->name('employee.store');
        Route::match(['put', 'patch'], 'update/{id}', [EmployeeController::class, 'update'])->name('employee.update');
        Route::patch('{id}/status', [EmployeeController::class, 'setStatus'])->name('employee.set-status');
        Route::post('/change/password/{id}', [EmployeeController::class, 'changePassword'])->name('employee.change-password');
    });

    Route::group(['prefix' => 'jobs'], function () {
        Route::get('/', [JobController::class, 'index'])->name('job.index');
        Route::get('show/{id}', [JobController::class, 'show'])->name('job.show');
        Route::post('store', [JobController::class, 'store'])->name('job.store');
        Route::match(['put', 'patch'], 'update/{id}', [JobController::class, 'update'])->name('job.update');
    });

    Route::get('logs', [LogController::class, 'index'])->name('log.index');

    Route::post('find/total', [ContractController::class, 'findTotal'])->name('contract.find-total');
    Route::post('find/sales/total', [ContractController::class, 'findSalesTotal'])->name('contract.find-sales-total');
});
