<?php

use App\Http\Controllers\API\CustomerProductController;
use App\Http\Controllers\API\CustomerProjectController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\CustomerProjectSiteController;
use App\Http\Controllers\API\CustomerTeamMemberController;
use App\Http\Controllers\API\DriverController;
use App\Http\Controllers\API\GroupCompanyController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\LiveOrderController;
use App\Http\Controllers\API\LiveOrderScheduleRejectionController;
use App\Http\Controllers\API\LiveOrderScheduleReportController;
use App\Http\Controllers\API\PumpController;
use App\Http\Controllers\API\PumpTypeController;
use App\Http\Controllers\API\RejectedQuantityReasonController;
use App\Http\Controllers\API\StructuralReferenceController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\TemperatureController;
use App\Http\Controllers\API\TripReportReasonController;
use App\Http\Controllers\API\TransitMixerController;
use App\Http\Controllers\API\LiveOrderScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\Auth\AuthController as AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware' => ['apiresponse', 'sanitization']], function () {

    // Mobile App Auth APIs
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login')->name('api.login');
        Route::post('/password/reset', 'resetPassword')->name('api.reset.password');
        Route::post('/password/reset/verify', 'verifyResetPassword')->name('api.verify.password')->middleware('auth:api');
        Route::post('/password/reset/verify/otp', 'verifyOtp')->name('api.verify.otp')->middleware('auth:api');
        Route::post('/logout', 'logout')->name('api.logout')->middleware('auth:api');
        Route::get('/unauthenticated', 'unauthenticated')->name('api.unauthenticated');
    });
    Route::post('/add-icon/{id}', [GroupCompanyController::class, 'addIcon']);
    Route::middleware('auth:api')->group(function () {
        //Customer APIs
        Route::middleware('customer')->group(function () {
            //Access Rights
            Route::get('/me/access-rights', [UserController::class, 'getCustomerUserAccessRights'])->name('customer.accessRights.get');
            //Structural References --
            Route::controller(StructuralReferenceController::class)->prefix('structural-references')->group(function () {
                Route::get('/get', 'index') -> name('structural_references.get');
            });
            //Team Members --
            Route::controller(CustomerTeamMemberController::class)->prefix('team-members')->group(function () {
                Route::get('', 'getTeamMembers') -> name('customers.team_members.get');
                Route::post('/store', 'storeTeamMember') -> name('customers.team_members.store');
            });
            //Customer Project --
            Route::controller(CustomerProjectController::class)->prefix('customer-projects')->group(function () {
                Route::get('/get/all', 'getCustomerProjects') -> name('customer_projects.get');
                Route::get('/get/{id}', 'getSingleCustomerProject') -> name('customer_projects.get.single');
                Route::get('/count/get', 'getActiveProjectsCount') -> name('customer_projects.get.count');
            });
            //Customer Project Sites -
            Route::controller(CustomerProjectSiteController::class)->prefix('project-sites')->group(function () {
                Route::get('/get/{projectId}', 'index') -> name('project_sites.get');
                Route::put('/mark-as-default', 'markAsDefault') -> name('project_sites.mark.default');
                Route::post('/save', 'storeOrUpdate') -> name('project_sites.create');
                Route::get('/get/details/{id}', 'view') -> name('project_sites.details');
            });
            //Customer Order
            Route::controller(OrderController::class)->prefix('customer-orders')->group(function () {
                Route::get('/get', 'getCustomerOrders') -> name('customer_orders.get');
                Route::get('/get/{id}', 'getOrderAdditionalInformation') -> name('customer_orders.get.single');
                Route::get('booked/get/{orderId}', 'getOrderDetails') -> name('customer_orders.booked.get.single');
                Route::post('/confirm', 'confirmCustomerOrder') -> name('customer_orders.book');
                Route::post('/confirm/single-order', 'addConfirmationToOrder') -> name('customer_orders.confirm');
                Route::post('/update', 'updateProductQuantity') -> name('customer_orders.update');
                Route::put('/cancel/{orderId}', 'cancelOrder') -> name('customer_orders.cancel');
                //Cart
                Route::get('/cart', 'getCartOrders') -> name('customer_orders.cart.get');
                Route::post('/cart/remove/{orderId}', 'removeOrderFromCart') -> name('customer_orders.cart.remove');
                Route::post('/cart/remove-all', 'removeAllOrderFromCart') -> name('customer_orders.cart.remove.all');
                Route::get('/cart/check/{customerProductId}', 'checkCartForMultipleProjects') -> name('customer_orders.cart.check');
                Route::put('/cart/order-all', 'confirmCartOrders') -> name('customer_orders.cart.order.all');
                Route::get('/cart/items/count', 'getCartItemsCount') -> name('customer_orders.cart.order.count');
                //Schedule Slot status
                Route::get('/slots', 'getTimeSlotsStatus') -> name('customer_orders.slots.status');
            });
            //Customer Product
            Route::controller(CustomerProductController::class) -> prefix('customer-products') -> group(function () {
                Route::get('/get/{id}', 'show') -> name('customer.products.get');
            });
            //Pump
            Route::controller(PumpController::class) -> prefix('group-companies/pumps') -> group(function () {
                Route::get('/get/sizes', 'getPumpSizes') -> name('pumps.get.sizes');
            });
            //Pump Type
            Route::controller(PumpTypeController::class) -> prefix('group-companies/pumps') -> group(function () {
                Route::get('/get/types', 'index') -> name('pumps.get.type');
            });
            //Customer
            Route::controller(CustomerController::class) -> prefix('customer') -> group(function () {
                Route::get('/get/suppliers', 'switchGroupCompany') -> name('customer.suppliers.get');
                Route::get('/get/access_rights', 'getAccessRights') -> name('customer.access_rights.get');
                Route::get('/dashboard', 'dashboard') -> name('customer.dashboard');
            });
            Route::controller(TemperatureController::class) -> prefix('order-temperatures') -> group(function () {
                Route::get('/get', 'index') -> name('customer.order.temp.get');
            });
            Route::controller(LiveOrderScheduleController::class) -> prefix('live-orders') -> group(function () {
                Route::get('/track/{orderId}', 'getLiveOrderTruckLocations') -> name('customer.order.temp.get');
                Route::post('/send/notification', 'sendNotifications') -> name('customer.notifications.send');
            });
        });

        //Driver APIs
        Route::middleware('driver')->group(function () {
            //Driver
            Route::controller(DriverController::class) -> prefix('driver') -> group(function () {
                Route::get('/dashboard', 'getDashboardData') -> name('driver.dashboard');
                Route::get('/my/delivery/{id}', 'getTripDetails') -> name('driver.delivery.current');
                Route::post('/my/trips/update-status', 'updateLiveScheduleTrip') -> name('driver.dashboard');
                Route::get('/get/transit-mixers', 'switchTransitMixer') -> name('driver.transit_mixers.get');
                Route::get('/get/past/deliveries', 'getPastDeliveries') -> name('driver.deliveries.past.get');
                Route::get('/get/assigned/projects', 'getAssignedProjects') -> name('driver.assigned.projects.get');
                Route::get('/get/assigned/projects/count', 'getAssignedProjectsCount') -> name('driver.assigned.projects.get.count');

                // operator

                Route::get('/operator/dashboard', 'operatorDashboardData') -> name('driver.operator-dashboard');
                Route::get('/my/operator-delivery/{id}', 'getOperatorTripDetails') -> name('driver.operator-delivery');
                Route::post('/my/operator-trips/update-status', 'updateOperatorLiveScheduleTrip') -> name('driver.operator-trips');

            });
            //Trip Report (GET)
            Route::controller(TripReportReasonController::class) -> prefix('trip') -> group(function () {
                Route::get('/report/reasons', 'index') -> name('driver.trip.report.reasons');
            });
            //Trip Report (SAVE)
            Route::controller(LiveOrderScheduleReportController::class) -> prefix('trip') -> group(function () {
                Route::post('/report', 'store') -> name('driver.trip.report');
            });
            //Rejected Reasons (GET)
            Route::controller(RejectedQuantityReasonController::class) -> prefix('trip-quantity') -> group(function () {
                Route::get('/reject/reasons', 'index') -> name('driver.trip_quantity.reasons.get');
            });
            //Rejected Reason (SAVE)
            Route::controller(LiveOrderScheduleRejectionController::class) -> prefix('trip-quantity') -> group(function () {
                Route::post('/reject', 'store') -> name('driver.trip_quantity.reject');
            });
        });
        //User
        Route::get('user/me', [UserController::class, 'getUserDetails']);
        Route::post('user/me/profile/update', [UserController::class, 'saveProfileImage']);
        Route::post('transit-mixer/update/icon/{id}', [TransitMixerController::class, 'addIcon']);
        Route::post('generateLiveSchedule', [LiveOrderController::class, 'generateLiveSchedule']);
        Route::post('/group/icon/save/{id}', [GroupController::class, 'addImageIcon']);
        Route::post('/user/profile/update', [UserController::class, 'updateUserProfile']);
    });
});


Route::controller(LiveOrderScheduleController::class) -> group(function () {
    Route::post('/send/notification', 'sendNotifications') -> name('customer.notifications.send');
});



Route::post('/pumps-import', [App\Http\Controllers\OrderController::class,'importPumps']);
Route::post('/transit-mixers-import', [App\Http\Controllers\OrderController::class,'importTransitMixers']);
Route::post('/request-access', [App\Http\Controllers\API\UserController::class,'requestAccess']);

