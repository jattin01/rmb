<?php

use App\Helpers\RouteConstantHelper;
use App\Http\Controllers\ApprovalSetupController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchingPlantController;
use App\Http\Controllers\CapacityController;
use App\Http\Controllers\ChatRoomController;
use App\Http\Controllers\CompanyLocationController;
use App\Http\Controllers\CustomerProductController;
use App\Http\Controllers\CustomerProjectController;
use App\Http\Controllers\CustomerProjectSiteController;
use App\Http\Controllers\CustomerTeamMemberController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\GroupCompanyController;
use App\Http\Controllers\LiveOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GlobalController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\TransitMixerController;
use App\Http\Controllers\UserController;
use App\Models\CompanyLocation;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy_policy');

Route::get('/terms-and-conditions', function () {
    return view('terms-and-conditions');
})->name('terms_and_conditions');

Route::get('/contact-us', function () {
    return view('contact-us');
})->name('contact_us');

Route::controller(AuthController::class)->group(function () {
    Route::get('/', 'loginView')->name(RouteConstantHelper::LOGIN);
    Route::get('/forgot-password', 'forgotPasswordView')->name(RouteConstantHelper::FORGOT_PASSWORD);
    Route::post('/forgot-password/send/otp', 'forgotPassword')->name(RouteConstantHelper::FORGOT_PASSWORD_ACTION);
    Route::get('/forgot-password/otp', 'otpVerifyView')->name(RouteConstantHelper::FORGOT_PASSWORD_OTP_VERIFY_VIEW);
    Route::post('/forgot-password/otp/resend', 'resendOTPForPassword')->name(RouteConstantHelper::FORGOT_PASSWORD_OTP_RESEND);
    Route::post('/verfiy/forgot/otp', 'verifyOtp')->name(RouteConstantHelper::FORGOT_PASSWORD_OTP_VERIFY);
    Route::get('/reset/password', 'resetPasswordView')->name(RouteConstantHelper::RESET_PASSWORD_VIEW);
    Route::post('/reset/password/confirm', 'resetPassword')->name(RouteConstantHelper::RESET_PASSWORD_ACTION);
    Route::post('/login', 'login')->name(RouteConstantHelper::LOGIN_ACTION);
});

Route::group(['middleware' => ['auth:web', 'admin']], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/logout', 'logout')->name(RouteConstantHelper::LOGOUT_ACTION);
    });
    // Dashboard Route
    Route::get('/home', [DashboardController::class, 'index'])->name(RouteConstantHelper::DASHBOARD);
    Route::get('/home/order-volume-graph', [DashboardController::class, 'ordervolumeGraph'])->name(RouteConstantHelper::DASHBOARD_ORDER_VOLUME_GRAPH);
    Route::get('/home/order-trends-graph', [DashboardController::class, 'OrderTrendGraph'])->name(RouteConstantHelper::DASHBOARD_ORDER_TRENDS_GRAPH);
    Route::get('/home/order-graph', [DashboardController::class, 'OrderGraph'])->name(RouteConstantHelper::DASHBOARD_ORDER_GRAPH);


    // Resource Route
    Route::prefix('resources')->controller(ResourceController::class)->group(function () {
        Route::get('/index', 'index')->name(RouteConstantHelper::RESOURCES_INDEX);
        Route::get('/batching-plant-create', 'createBatchingPlant')->name(RouteConstantHelper::RESOURCES_BATCHING_PLANT_CREATE);
        Route::post('/batching-plant-store', 'storeBatchingPlant')->name(RouteConstantHelper::RESOURCES_BATCHING_PLANT_STORE);
        Route::get('/batching-plant-edit', 'editBatchingPlant')->name(RouteConstantHelper::RESOURCES_BATCHING_PLANT_EDIT);

        Route::get('/transit-mixer-create', 'createTransitMixer')->name(RouteConstantHelper::RESOURCES_TRANSIT_MIXER_CREATE);
        Route::post('/transit-mixer-store', 'storeTransitMixer')->name(RouteConstantHelper::RESOURCES_TRANSIT_MIXER_STORE);
        Route::get('/transit-mixer-edit', 'editTransitMixer')->name(RouteConstantHelper::RESOURCES_TRANSIT_MIXER_EDIT);

        Route::get('/pump-create', 'createPump')->name(RouteConstantHelper::RESOURCES_PUMP_CREATE);
        Route::post('/pump-store', 'storePump')->name(RouteConstantHelper::RESOURCES_PUMP_STORE);
        Route::get('/pump-edit', 'editPump')->name(RouteConstantHelper::RESOURCES_PUMP_EDIT);
    });

    Route::prefix('customers')->controller(CustomerController::class)->group(function () {
        Route::get('/index', 'index')->name(RouteConstantHelper::CUSTOMER_INDEX);
        Route::get('/create', 'create')->name(RouteConstantHelper::CUSTOMER_CREATE);
        Route::post('/store', 'store')->name(RouteConstantHelper::CUSTOMER_STORE);
        Route::get('/edit', 'edit')->name(RouteConstantHelper::CUSTOMER_EDIT);
        Route::get('/export', 'exportCustomers')->name(RouteConstantHelper::CUSTOMER_EXPORT);
        // Route::get('/create/project', 'createProject')->name(RouteConstantHelper::CUSTOMER_CREATE_PROJECT);
        // Route::post('/project/store', 'storeProject')->name(RouteConstantHelper::CUSTOMER_STORE_PROJECT);
        // Route::get('/project/edit', 'editProject')->name(RouteConstantHelper::CUSTOMER_EDIT_PROJECT);
        Route::post('/project/site/store', 'storeProjectSiteAddress')->name(RouteConstantHelper::PROJECT_SITE_ADDRESS_STORE);
        // Route::post('/product/details', 'getProductDetails')->name(RouteConstantHelper::PRODUCT_DETAILS);
        Route::post('/product/store', 'storeProduct')->name(RouteConstantHelper::CUSTOMER_STORE_PRODUCT);
        Route::post('/delete-project-site', 'deleteProjectSite')->name(RouteConstantHelper::CUSTOMER_DELETE_PROJECT_SITE);
        Route::post('/delete-project-product', 'deleteProjectProduct')->name(RouteConstantHelper::CUSTOMER_DELETE_PROJECT_PRODUCT);
        Route::post('/edit-project-site', 'editProjectSite')->name(RouteConstantHelper::CUSTOMER_EDIT_PROJECT_SITE);
        Route::post('/edit-project-product', 'editProjectProduct')->name(RouteConstantHelper::CUSTOMER_EDIT_PROJECT_PRODUCT);
    });

    // Setting Route
    Route::prefix('setting')->controller(SettingController::class)->group(function () {
        Route::get('/index', 'index')->name(RouteConstantHelper::SETTING_INDEX);
        // Route::get('/home', 'home')->name(RouteConstantHelper::SETTINGS_HOME);
        Route::get('/users/create', 'createUser')->name(RouteConstantHelper::USERS_CREATE);
        Route::post('/users/store', 'storeUser')->name(RouteConstantHelper::USER_STORE);
        Route::get('/user/edit', 'editUser')->name(RouteConstantHelper::USER_EDIT);

        Route::get('/location/create', 'createLocation')->name(RouteConstantHelper::LOCATION_CREATE);
        Route::post('/location/store', 'storeLocation')->name(RouteConstantHelper::LOCATION_STORE);
        Route::get('/location/edit', 'editLocation')->name(RouteConstantHelper::LOCATION_EDIT);
        Route::post('/provinces', 'getProvinces')->name(RouteConstantHelper::PROVINCES);
    });

    Route::controller(OrderController::class)->group(function () {
        Route::get('/generate-schedule-step-1', 'scheduleViewStepOne')->name(RouteConstantHelper::ORDER_SCHEDULE_STEP_1);
        Route::post('/update-order', 'updateOrder')->name(RouteConstantHelper::UPDATE_SINGLE_ORDER);
        Route::get('/create-order', 'createNewOrder')->name("order.create.new");
        Route::post('/store-order', 'storeSingleOrder')->name("order.store.new");
        Route::post('/reset_orders_priority', 'resetOrders')->name(RouteConstantHelper::RESET_ORDERS);
        Route::post('/update_selected_orders', 'updateSelectedOrders')->name(RouteConstantHelper::UPDATE_SELECTED_ORDERS);
        Route::get('/generate-schedule-step-2', 'scheduleViewStepTwo')->name(RouteConstantHelper::ORDER_SCHEDULE_STEP_2);
        Route::get('/generate-schedule-step-3', 'scheduleViewStepThree')->name(RouteConstantHelper::ORDER_SCHEDULE_STEP_3);
        Route::post('/generate_schedule', 'generateSchedule')->name(RouteConstantHelper::GENERATE_SCHEDULE);
        Route::get('/order-schedule-match', 'orderScheduleView')->name(RouteConstantHelper::ORDER_SCHEDULE_VIEW);
        Route::get('/live-schedule', 'liveScheduleView')->name('web.order.live.schedule');
        Route::post('/order-import', 'importOrders')->name(RouteConstantHelper::ORDER_IMPORT);
        Route::get('/order-export', 'exportOrders')->name(RouteConstantHelper::ORDER_EXPORT);

        Route::post('/orders-schedule-publish', 'publishOrders')->name(RouteConstantHelper::PUBLISH_ORDERS);
        Route::get('/orders-overview', 'ordersOverview')->name(RouteConstantHelper::HOME);
        Route::get('/get/order-creation-data/{groupCompanyId}', 'getCompanyMastersForOrderCreation');
        Route::post('/update/order-site-status', 'updateSiteStatus')->name('web.order.update.site.status');
        Route::get('/edit/order/{orderId}', 'editOrder')->name('web.order.edit.view');
        //new

        Route::get('/edit/live-schedule/{orderId}', 'editliveSchedule')->name('web.edit.live.schedule');
        Route::post('/update/live-schedule-order', 'updateLiveScheduleOrder')->name('update.live-schedule-order');


        Route::post('order/add/approval', 'markAsApprove')->name('web.order.add.approval');
        // API
        Route::get('/order/pump/detail', 'orderPumpDetail')->name('web.order.pump.detail');

    });

    Route::controller(CustomerProjectController::class)->prefix('customer-projects')->group(function () {
        //Helper APIs
        Route::get('get/{customerId}', 'getList')->name('web.customer_projects.get');
        Route::get('get/chat/all', 'getProjectsForChat')->name('web.customer_projects.get.all.for.chat');
        Route::get('get/chat/all/project/ids', 'getProjectsForChatHeader')->name('web.customer_projects.get.all.for.chat.header');
        Route::get('get/chat/{projectId}', 'getProjectChat')->name('web.customer_projects.get.single.for.chat');
        //Project
        Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECTS_INDEX);
        Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECTS_CREATE);
        Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECTS_STORE);
        Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECTS_EDIT);
        Route::get('/export', [CustomerProjectController::class, 'export'])->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECTS_EXPORT);
    });

    Route::controller(LiveOrderController::class)->group(function () {
        Route::get('live-order/trip-details', 'getAssignedResources')->name('web.liveOrder.trip.resources');
        Route::post('live-order/assign-trip', 'assignTrip')->name('web.liveOrder.trip.assign');
        Route::get('live-order/details/{orderId}', 'getOrderDetail')->name('web.liveOrder.details');
        Route::get('live-trip/details/{tripId}', 'getTripDetail')->name('web.liveTrip.details');
    });

    Route::controller(CustomerProductController::class)->prefix('customer-products')->group(function () {
        Route::get('show/{id}', 'show')->name('web.customer_products.get');
        //Customer-Product
        Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_PRODUCTS_INDEX);
        Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_PRODUCTS_STORE);
        Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_PRODUCTS_EDIT);
        Route::post('product/details', 'getProductDetails')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_PRODUCTS_DETAILS);
        Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_MIX_EXPORT);
    });
    Route::controller(CustomerProjectSiteController::class)->prefix('project-sites')->group(function () {
        Route::get('get/{projectId}', 'getAllList')->name('web.project_sites.get');
        Route::get('/get/details/{id}', 'getDetails')->name('settings.projectSites.get.details');
        //Customer-Project-Site
        Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_SITES_INDEX);
        Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_SITES_STORE);
        Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_SITES_EDIT);
        Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_CUSTOMER_PROJECT_SITES_EXPORT);
    });

    //Group Company
    Route::controller(GroupCompanyController::class)->prefix('group-company')->group(function () {
        Route::get('/get/locations/{groupCompanyId}', 'getUserCompanyLocations')->name(RouteConstantHelper::GROUP_COMPANY_GET_LOCATIONS);
    });
    //Chat
    Route::controller(ChatRoomController::class)->prefix('chat')->group(function () {
        Route::get('/get/rooms', 'index')->name(RouteConstantHelper::CHAT_ROOMS_INDEX);
        Route::get('/get/rooms/id', 'getRoomIds')->name(RouteConstantHelper::CHAT_ROOMS_GET_IDS);
        Route::get('/get/room/details/{roomId}', 'getChatRoomDetails')->name(RouteConstantHelper::CHAT_ROOMS_GET_DETAILS);
        Route::get('/get/driver/chatroom/{project_id}', 'getDriverChatroom')->name(RouteConstantHelper::CHAT_ROOMS_GET_DRIVERS);
    });

    Route::post('/profile/update', [UserController::class, 'updateMyProfile'])->name(RouteConstantHelper::SETTINGS_USER_UPDATE_MY_PROFILE);

    Route::prefix('settings')->group(function () {
        Route::get('/home', function () {
            return view('components.settings.index');
        })->name(RouteConstantHelper::SETTINGS_HOME);

        //Batching Plant
        Route::controller(BatchingPlantController::class)->prefix('batching-plants')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_BATCHING_PLANT_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_BATCHING_PLANT_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_BATCHING_PLANT_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_BATCHING_PLANT_STORE);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_BATCHING_PLANT_EXPORT);
        });
        //Transit-Mixer
        Route::controller(TransitMixerController::class)->prefix('transit-mixers')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_TRANSIT_MIXER_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_TRANSIT_MIXER_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_TRANSIT_MIXER_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_TRANSIT_MIXER_STORE);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_TRANSIT_MIXER_EXPORT);
        });
        //Pump
        Route::controller(PumpController::class)->prefix('pumps')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_PUMP_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_PUMP_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_PUMP_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_PUMP_STORE);
            Route::get('/get-size/{type}', 'getSize')->name(RouteConstantHelper::SETTINGS_PUMP_GET_SIZE);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_PUMP_GET_EXPORT);
        });
        //Users
        Route::controller(UserController::class)->prefix('users')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_USER_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_USER_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_USER_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_USER_STORE);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_USER_EXPORT);
        });
        //Company-Location
        Route::controller(CompanyLocationController::class)->prefix('locations')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_COMPANY_LOCATIONS_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_COMPANY_LOCATIONS_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_COMPANY_LOCATIONS_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_COMPANY_LOCATIONS_STORE);
            Route::get('/get/users/{locationId}', 'getUsers')->name(RouteConstantHelper::SETTINGS_COMPANY_LOCATIONS_GET_USERS);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_COMPANY_LOCATIONS_GET_EXPORT);
        });
        //Product-Type
        Route::controller(ProductTypeController::class)->prefix('product_types')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_PRODUCT_TYPES_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_PRODUCT_TYPES_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_PRODUCT_TYPES_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_PRODUCT_TYPES_STORE);
            Route::get('/get/{groupCompanyId}', 'getByCompany')->name(RouteConstantHelper::SETTINGS_PRODUCT_TYPES_STORE);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_PRODUCT_MIX_TYPE_EXPORT);
        });
        // Product
        Route::controller(ProductController::class)->prefix('products')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_PRODUCTS_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_PRODUCTS_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_PRODUCTS_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_PRODUCTS_STORE);
            //Content
            Route::post('/content/store', 'storeProductContent')->name(RouteConstantHelper::SETTINGS_PRODUCT_CONTENTS_STORE);
            Route::get('/content/edit', 'editProductContent')->name(RouteConstantHelper::SETTINGS_PRODUCT_CONTENTS_EDIT);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_PRODUCT_CONTENTS_EXPORT);
        });
        Route::controller(CustomerTeamMemberController::class)->prefix('customer-team')->group(function () {
            Route::get('/{customerId}', 'index')->name(RouteConstantHelper::SETTINGS_CUSTOMER_TEAM_INDEX);
            Route::get('/create/member', 'create')->name(RouteConstantHelper::SETTINGS_CUSTOMER_TEAM_CREATE);
            Route::get('/edit/member', 'edit')->name(RouteConstantHelper::SETTINGS_CUSTOMER_TEAM_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_CUSTOMER_TEAM_STORE);
            Route::get('/team/export/{customerId}', 'export')->name(RouteConstantHelper::SETTINGS_CUSTOMER_TEAM_EXPORT);
        });
        //Driver
        Route::controller(DriverController::class)->prefix('drivers')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_DRIVERS_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_DRIVERS_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_DRIVERS_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_DRIVERS_STORE);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_DRIVERS_EXPORT);
        });
        //Approval Setup
        Route::controller(ApprovalSetupController::class)->prefix('approvals/orders')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_ORDER_APPROVAL_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_ORDER_APPROVAL_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_ORDER_APPROVAL_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_ORDER_APPROVAL_STORE);
            Route::get('/exort', 'export')->name(RouteConstantHelper::SETTINGS_ORDER_APPROVAL_EXPORT);
        });
        Route::controller(StructureController::class)->prefix('structures')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_STRUCTURE_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_STRUCTURE_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_STRUCTURE_EDIT);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_STRUCTURE_STORE);
            Route::get('/export', 'export')->name(RouteConstantHelper::SETTINGS_STRUCTURE_EXPORT);
        });

        Route::controller(GlobalController::class)->prefix('global')->group(function () {
            Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_GLOBAL_INDEX);
            Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_GLOBAL_CREATE);
            Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_GLOBAL_EDIT);
            // Route::get('/settings/global/edit/{id}','edit')->name(RouteConstantHelper::SETTINGS_GLOBAL_EDIT);
            Route::post('/update', 'update')->name(RouteConstantHelper::SETTINGS_GLOBAL_UPDATE);
            Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_GLOBAL_STORE);
        });
    });

    // Reports
    Route::controller(ReportController::class)->prefix('resources')->group(function () {
        Route::get('/dashboard', 'index')->name(RouteConstantHelper::BATCHING_DASHBOARD);
        Route::get('/batching-details', 'batchingDetail')->name(RouteConstantHelper::BATCHING_DETAILS);
    });
    Route::controller(CapacityController::class)->prefix('capacity')->group(function () {
        Route::get('/', 'index')->name(RouteConstantHelper::SETTINGS_CAPACITY);
        Route::get('/create', 'create')->name(RouteConstantHelper::SETTINGS_CAPACITY_CREATE);
        Route::post('/store', 'store')->name(RouteConstantHelper::SETTINGS_CAPACITY_STORE);
        Route::get('/edit', 'edit')->name(RouteConstantHelper::SETTINGS_CAPACITY_EDIT);
    });
});


