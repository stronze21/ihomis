<?php

use App\Http\Livewire\Pusher;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Trash\SampleView;
use App\Http\Livewire\References\Manual;
use App\Http\Livewire\Records\PatientsList;
use App\Http\Livewire\Records\PrescriptionEr;
use App\Http\Livewire\Records\PrescriptionOpd;
use App\Http\Livewire\References\CreateManual;
use App\Http\Livewire\Pharmacy\Drugs\StockList;
use App\Http\Livewire\Records\PrescriptionList;
use App\Http\Livewire\Records\PrescriptionWard;
use App\Http\Livewire\Pharmacy\Drugs\IoTransList;
use App\Http\Livewire\Pharmacy\Reports\DrugsIssued;
use App\Http\Livewire\Pharmacy\Reports\DrugsReturned;
use App\Http\Livewire\References\Users\UserManagement;
use App\Http\Livewire\Pharmacy\Deliveries\DeliveryList;
use App\Http\Livewire\Pharmacy\Deliveries\DeliveryView;
use App\Http\Livewire\Pharmacy\References\ListLocation;
use App\Http\Livewire\Pharmacy\Dispensing\RxoChargeSlip;
use App\Http\Livewire\Pharmacy\References\ListDrugHomis;
use App\Http\Livewire\Pharmacy\Reports\DrugsChargeSlips;
use App\Http\Livewire\Pharmacy\Reports\DrugsIssuedWards;
use App\Http\Livewire\Pharmacy\Drugs\IoTransListRequestor;
use App\Http\Livewire\Pharmacy\Reports\ConssumptionReport;
use App\Http\Livewire\Pharmacy\Reports\EmergencyPurchases;
use App\Http\Livewire\References\Security\ListPermissions;
use App\Http\Livewire\Pharmacy\Reports\DrugsTransactionLog;
use App\Http\Livewire\Pharmacy\Reports\IoTransIssuedReport;
use App\Http\Livewire\Pharmacy\Reports\IoTransReceivedReport;
use App\Http\Livewire\Pharmacy\Reports\DrugsIssuedDepartments;
use App\Http\Livewire\Pharmacy\Dispensing\EncounterTransactionView;
use App\Http\Livewire\Pharmacy\Dispensing\PendingOrders;
use App\Http\Livewire\Pharmacy\Drugs\StockPullOutList;
use App\Http\Livewire\Pharmacy\Drugs\ViewIotrans;
use App\Http\Livewire\Pharmacy\Drugs\ViewIoTransDate;
use App\Http\Livewire\Pharmacy\References\PndfGenerics;
use App\Http\Livewire\Pharmacy\Reports\DailyStockCard;
use App\Http\Livewire\Pharmacy\Reports\DeliverySummary;
use App\Http\Livewire\Pharmacy\Reports\DrugsReturnedSummary;
use App\Http\Livewire\Pharmacy\Reports\TotalDrugsIssued;
use App\Http\Livewire\Records\PatientRegister;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware([
    'auth:sanctum', config('jetstream.auth_session'), 'verified'
])->group(function () {

    Route::get('/', function () {
        return view('dashboard');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/patients', PatientsList::class)->name('patients.list');
    Route::get('/patients/register', PatientRegister::class)->name('patients.new');
    Route::get('/prescriptions', PrescriptionList::class)->name('rx.list');

    Route::name('rx.')->prefix('prescriptions')->group(function () {
        Route::get('/ward', PrescriptionWard::class)->name('ward');
        Route::get('/opd', PrescriptionOpd::class)->name('opd');
        Route::get('/er', PrescriptionEr::class)->name('er');
    });

    Route::name('dmd.')->prefix('drugsandmedicine')->group(function () {
        Route::get('/stocks', StockList::class)->name('stk');
        Route::get('/stocks/for-pull-out', StockPullOutList::class)->name('stk.pullout');
    });

    Route::name('iotrans.')->prefix('iotrans')->group(function () {
        Route::get('/view-date/{date}', ViewIoTransDate::class)->name('view_date');
        Route::get('/view-ref/{reference_no}', ViewIotrans::class)->name('view');
        Route::get('/list', IoTransList::class)->name('list');
        Route::get('/requests', IoTransListRequestor::class)->name('requests');
    });

    Route::name('dispensing.')->prefix('dispensing')->group(function () {
        Route::get('/encounter/trans/{enccode}', EncounterTransactionView::class)->name('view.enctr');
        Route::get('/encounter/charge/{pcchrgcod}', RxoChargeSlip::class)->name('rxo.chargeslip');
        Route::get('/pending-orders', PendingOrders::class)->name('rxo.pending');
    });

    Route::name('delivery.')->prefix('delivery')->group(function () {
        Route::get('/list', DeliveryList::class)->name('list');
        Route::get('/emergency-purchase', EmergencyPurchases::class)->name('ep');
        Route::get('/view/{delivery_id}', DeliveryView::class)->name('view');
    });

    Route::name('ref.')->prefix('/reference')->group(function () {
        Route::get('/location', ListLocation::class)->name('location');
        Route::get('/drugsandmedicine', ListDrugHomis::class)->name('dmd');
        Route::get('/PNDF-Generics', PndfGenerics::class)->name('pndf');
        Route::get('/permissions', ListPermissions::class)->name('permissions');
        Route::get('/users', UserManagement::class)->name('users');
        Route::get('/manual', Manual::class)->name('manual');
        Route::get('/manual/create', CreateManual::class)->name('manual.add');
    });

    Route::name('reports.')->prefix('/reports')->group(function () {
        Route::get('/delivery-summary', DeliverySummary::class)->name('delivery.sum');
        Route::get('/stock-card', DailyStockCard::class)->name('stkcrd');
        Route::get('/issuance/log', DrugsTransactionLog::class)->name('issuance.log');
        Route::get('/issuance/all', DrugsIssued::class)->name('issuance.all');
        Route::get('/issuance/total', TotalDrugsIssued::class)->name('issuance.total');
        Route::get('/issuance/returns', DrugsReturned::class)->name('issuance.returns');
        Route::get('/issuance/returns-summary', DrugsReturnedSummary::class)->name('issuance.returns.summary');
        Route::get('/issuance/chargeslips', DrugsChargeSlips::class)->name('issuance.charges');
        Route::get('/consumption', ConssumptionReport::class)->name('consumption');
        Route::get('/iotrans/issued', IoTransIssuedReport::class)->name('iotrans.issued');
        Route::get('/iotrans/received', IoTransReceivedReport::class)->name('iotrans.received');
        Route::get('/consumption/wards', DrugsIssuedWards::class)->name('consumption.wards');
        Route::get('/consumption/departments', DrugsIssuedDepartments::class)->name('consumption.depts');
    });

    Route::get('/pusher', Pusher::class)->name('pusher');
    Route::get('/sample', SampleView::class)->name('sample');
});
