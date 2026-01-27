<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Helpers\OrderHelper;
use App\Http\Controllers\Controller;
use App\Models\BatchingPlant;
use App\Models\CompanyLocation;
use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Models\CustomerProject;
use App\Models\CustomerProjectSite;
use App\Models\Group;
use App\Models\GroupCompany;
use App\Models\LiveOrder;
use App\Models\Order;
use App\Models\OrderCubeMould;
use App\Models\OrderPump;
use App\Models\OrderTempControl;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\Order as Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Str;

class OrderController extends Controller
{
    public function getCustomerOrders(Request $request)
    {
        $validator = (new Validator($request))->getCustomerProjects();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $orders = null;
            $search = $request -> search ? $request -> search : "";
            if ($request -> filter_type === ConstantHelper::UPCOMING_ORDERS) {
                $orders = OrderHelper::getSortedCustomerUpcomingOrders($request -> customer_id, $request -> is_user_admin, $request -> project_ids, $search);
            } else if ($request -> filter_type === ConstantHelper::LIVE_ORDERS) {
                $orders = OrderHelper::getCustomerOngoingOrders($request -> customer_id, $request -> is_user_admin, $request -> project_ids, $search);
            } else if ($request -> filter_type === ConstantHelper::PAST_ORDERS) {
                $orders = OrderHelper::getCustomerPastOrders($request -> customer_id, $request -> is_user_admin, $request -> project_ids, $search);
            } else {
                throw new ApiGenericException("Invalid filter type");
            }
            foreach ($orders as &$order) {
                $order = OrderHelper::appendKeysToOrderForMobileUi($request -> filter_type, $order);            
            }
            return array(
                'message' => __("message.records_returned_successfully", ['static' => __("static.orders")]),
                'data' => array(
                    'orders' => $orders,
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getOrderDetails(Request $request, String $orderId)
    {
        $validator = (new Validator($request))->getCustomerProjectOrderDetails();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $order = null;
            $groupCompany = null;
            $groupCompanyLocation = null;
            if ($request -> filter_type === ConstantHelper::LIVE_ORDERS) {
                $order = LiveOrder::with('mobile_user_access_right')  -> where([
                    ['customer_id', $request -> customer_id],
                    ['status', ConstantHelper::ACTIVE],
                    ['id', $orderId]
                ]) -> select("id", "og_order_id", "order_no", "delivery_date", "interval", "quantity", "cust_product_id", "project_id", "actual_deviation AS deviation", "planned_start_time AS start_time", "planned_end_time AS end_time", "structural_reference_id", "is_technician_required", "delivered_quantity", "site_id", "group_company_id", "company_location_id") -> with('schedule') -> with('customer_product', function ($query) {
                    $query -> select('id', 'total_quantity', 'product_id', 'ordered_quantity', 'project_id') -> with('customer_project', function ($subQuery) {
                        $subQuery -> select('id' , 'name', 'type') -> with('address');
                    });
                }) -> whereHas('schedule', function ($query) {
                    $query -> whereNotNull('actual_loading_start');
                }) ->  with('schedule') -> first();
            } else {
                $order = Order::with('mobile_user_access_right')  -> where([
                    ['customer_id', $request -> customer_id],
                    ['status', ConstantHelper::ACTIVE],
                    ['in_cart', 0],
                    ['id', $orderId]
                ]) -> select("id", "order_no", "delivery_date", "interval", "quantity", "cust_product_id", "deviation", "project_id", "start_time", "is_technician_required", "has_customer_confirmed", "structural_reference_id", "remarks", "site_id", "published_by", "group_company_id", "company_location_id") -> with('customer_product', function ($query) {
                    $query -> select('id', 'total_quantity', 'ordered_quantity', 'project_id', 'product_id') -> with('customer_project', function ($subQuery) {
                        $subQuery -> select('id' , 'name', 'type') -> with('address');
                    });
                }) -> with('schedule') -> first();
                if (!isset($order)) {
                    $order = LiveOrder::with('mobile_user_access_right')  -> where([
                        ['customer_id', $request -> customer_id],
                        ['status', ConstantHelper::ACTIVE],
                        ['id', $orderId]
                    ]) -> select("id", "og_order_id", "order_no", "delivery_date", "interval", "quantity", "cust_product_id", "project_id", "actual_deviation AS deviation", "planned_start_time AS start_time", "planned_end_time AS end_time", "structural_reference_id", "is_technician_required", "delivered_quantity", "site_id", "group_company_id", "company_location_id") -> with('schedule') -> with('customer_product', function ($query) {
                        $query -> select('id', 'total_quantity', 'product_id', 'ordered_quantity', 'project_id') -> with('customer_project', function ($subQuery) {
                            $subQuery -> select('id' , 'name', 'type') -> with('address');
                        });
                    }) -> whereHas('schedule', function ($query) {
                        $query -> whereNotNull('actual_loading_start');
                    }) ->  with('schedule') -> first();
                }
            }
            if (isset($order)) {
                $order = OrderHelper::appendKeysToOrderForMobileUi($request -> filter_type, $order);
                $groupCompany = GroupCompany::find($order -> group_company_id);
                $groupCompanyLocation = CompanyLocation::find($order -> company_location_id);
                $order -> order_pumps_display = $order -> order_pumps_display();
                $order -> order_cube_mould_display = $order -> order_cube_mould_display();
                $order -> order_temp_control_display = $order -> order_temp_control_display();
                $order -> makeHidden(['schedule']);
                return array(
                    'message' => __("message.records_returned_successfully", ['static' => __("static.order")]),
                    'data' => array(
                        'order' => $order,
                        'group_company' => $groupCompany ?-> comp_name,
                        'group_company_address' => $groupCompanyLocation ?-> location,
                    )
                );
            } else {
                throw new ApiGenericException("Order Not Found");
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function confirmCustomerOrder(Request $request)
    {
        $validator = (new Validator($request))->confirmCustomerOrder();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            DB::beginTransaction();
            $orderQty = $request -> quantity;
            $customerProduct = CustomerProduct::find($request -> id);
            $customerProject = CustomerProject::with('address') -> find($customerProduct -> project_id);
            $customerProjectSite = CustomerProjectSite::find($request -> site_id);
            $groupCompanyLocation = CompanyLocation::find($customerProjectSite -> service_company_location ?-> id);
            $customer = Customer::find($request -> customer_id);
            //Add up all quantity from Temp Control
            $tempControlQty = 0;
            foreach ($request -> temp_control as $tmpCtrl) {
                $tempControlQty += $tmpCtrl['qty'];
            }
            if ($tempControlQty > $orderQty) {
                throw new ApiGenericException("Temperature control quantity cannot be greater than total order quantity");
            }
            //Restrict order creation if cart contains order of another project
            $orders = Order::where([
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                ['in_cart', 1]
            ]) -> get();

            $allowOrder = true;

            foreach ($orders as $order) {
                if ($order -> project_id != $customerProject -> id) {
                    $allowOrder = false;
                }
            }
            if (!$allowOrder) {
                throw new ApiGenericException("Multiple Project cannot be added in cart");
            }
            $previousOrders = Order::withTrashed() -> where([
                ['customer_id' , $customer -> id],
            ]) -> get();

            $previousOrdersCount = isset($previousOrders) && count($previousOrders) > 0 ? count($previousOrders) : 0;

            $updateConditionsWhereCondition = ($request -> order_id) ? [['id', $request -> order_id]] : [
                ['cust_product_id' , $customerProduct -> id],
                ['customer_id' , $customer -> id],
                ['project_id' , $customerProject -> id],
                ['in_cart' , true],
            ];

            $existingOrder = Order::where($updateConditionsWhereCondition) -> first();

            $order = null;

            if ($request -> order_id) {
                $order = Order::where('id', $request -> order_id) -> update([
                    'customer' => $customer -> name,
                    'customer_id' => $request -> customer_id,
                    'project' => $customerProject -> name,
                    'project_id' => $customerProject -> id,
                    'site' => $customerProjectSite ?-> name ?? "Jebel Ali",
                    'site_id' => $customerProjectSite ?-> id ?? null,
                    'mix_code' => $customerProduct -> mix_code,
                    'quantity' => $orderQty,
                    'pump_qty' => count($request -> pump_req),
                    'delivery_date' => $request -> delivery_date . " " . $request -> delivery_time,
                    'travel_to_site' => 20,
                    'return_to_plant' => 20,
                    'interval' => $request -> interval,
                    'pump' => $request -> pump_req[0]['pump_size'] ?? null,
                    'location' => $groupCompanyLocation ?-> location ?? "Jebel Ali",
                    'company_location_id' => $groupCompanyLocation ?-> id ?? null,
                    'deviation' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'deviation_reason' => null,
                    'published_by' => null,
                    'structural_reference_id' => $request -> structural_reference_id,
                    'is_technician_required' => $request -> is_tech_required ? 1 : 0,
                    'remarks' => $request -> remarks ? $request -> remarks : null,
                ]);
            } else {
                if (isset($existingOrder)) {
                    $order = Order::where($updateConditionsWhereCondition) -> update(
                        [
                            'customer' => $customer -> name,
                            'customer_id' => $request -> customer_id,
                            'project' => $customerProject -> name,
                            'project_id' => $customerProject -> id,
                            'site' => $customerProjectSite ?-> name ?? "Jebel Ali",
                            'site_id' => $customerProjectSite ?-> id ?? null,
                            'mix_code' => $customerProduct -> mix_code,
                            'quantity' => $orderQty,
                            'pump_qty' => count($request -> pump_req),
                            'delivery_date' => $request -> delivery_date . " " . $request -> delivery_time,
                            'travel_to_site' => 20,
                            'return_to_plant' => 20,
                            'interval' => $request -> interval,
                            'pump' => $request -> pump_req[0]['pump_size'] ?? null,
                            'location' => $groupCompanyLocation ?-> location ?? "Jebel Ali",
                            'company_location_id' => $groupCompanyLocation ?-> id ?? null,
                            'deviation' => null,
                            'start_time' => null,
                            'end_time' => null,
                            'deviation_reason' => null,
                            'published_by' => null,
                            'structural_reference_id' => $request -> structural_reference_id,
                            'is_technician_required' => $request -> is_tech_required ? 1 : 0,
                            'remarks' => $request -> remarks ? $request -> remarks : null,
                        ]
                    );
                } else {
                    $newOrderNo = auth() -> user() -> id . $request -> customer_id . $previousOrdersCount;
                    $order = Order::create([
                        'group_company_id' => $customerProjectSite -> service_company_location ?-> group_company ?-> id,
                        'order_no' => $newOrderNo,
                        'cust_product_id' => $customerProduct -> id,
                        'in_cart' => true,
                        'customer' => $customer -> name,
                        'customer_id' => $request -> customer_id,
                        'project' => $customerProject -> name,
                        'project_id' => $customerProject -> id,
                        'site' => $customerProjectSite ?-> name ?? "Jebel Ali",
                        'site_id' => $customerProjectSite ?-> id ?? null,
                        'mix_code' => $customerProduct -> mix_code,
                        'quantity' => $orderQty,
                        'pump_qty' => count($request -> pump_req),
                        'delivery_date' => $request -> delivery_date . " " . $request -> delivery_time,
                        'travel_to_site' => 20,
                        'return_to_plant' => 20,
                        'interval' => $request -> interval,
                        'pump' => $request -> pump_req[0]['pump_size'] ?? null,
                        'location' => $groupCompanyLocation ?-> location ?? "JEBEL ALI",
                        'company_location_id' => $customerProjectSite -> service_company_location ?-> id,
                        'deviation' => null,
                        'start_time' => null,
                        'end_time' => null,
                        'deviation_reason' => null,
                        'published_by' => null,
                        'structural_reference_id' => $request -> structural_reference_id,
                        'is_technician_required' => $request -> is_tech_required ? 1 : 0,
                        'remarks' => $request -> remarks ? $request -> remarks : null,
                    ]);
                }
            }

            $order = isset($order -> id) ? $order : $existingOrder;

            if (!$order -> wasRecentlyCreated) { // Edited
                OrderTempControl::where('order_id', $order -> id)->delete();
                OrderPump::where('order_id', $order -> id)->delete();
                OrderCubeMould::where('order_id', $order -> id)->delete();
                $resetQuantity = ($customerProduct -> ordered_quantity - $existingOrder ?-> quantity ?? 0);
                $customerProduct -> ordered_quantity = $resetQuantity + $orderQty;
                $customerProduct -> save();
            } else {
                $customerProduct -> ordered_quantity = $customerProduct -> ordered_quantity + $orderQty;
                $customerProduct -> save();
            }
            foreach ($request -> temp_control as $tmpCtrl) {
                OrderTempControl::create([
                    'order_id' => $order -> id,
                    'temp' => $tmpCtrl['temp'],
                    'quantity' => $tmpCtrl['qty']
                ]);
            }
            foreach ($request -> pump_req as $pump) {
                OrderPump::create([
                    'order_id' => $order -> id,
                    'capacity' => $pump['pump_size'],
                    'pipe_size' => isset($pump['pipe_size']) ? $pump['pipe_size'] : null,
                    'type' => $pump['type'],
                    'quantity' => $pump['qty']
                ]);
            }
            foreach ($request -> cube_mould_req as $cubeMould) {
                OrderCubeMould::create([
                    'order_id' => $order -> id,
                    'mould_size' => null,
                    'quantity' => $cubeMould['qty']
                ]);
            }
            DB::commit();
            return array(
                'message' => $request -> order_id ? "Order updated successfully" : __("message.added_to_cart", ['static' => __("static.order")]),
                'data' => array(
                    'order_id' => $order -> id
                )
            );
        } catch (Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function addConfirmationToOrder(Request $request)
    {
        $validator = (new Validator($request))->addConfirmationToOrder();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            DB::beginTransaction();
            $order = Order::where([
                ['id', $request -> id],
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                ['has_customer_confirmed', 0]
            ]) -> first();

            if (isset($order)) {
                $order -> order_status = 'Confirmed';
                $order -> has_customer_confirmed = 1;
                $order -> customer_confirm_remarks = isset($request -> remarks) ? $request -> remarks : null;
                $order -> save();

                if ($request->hasFile('documents')) {
                    foreach ($request->file('documents') as $document) {
                        $order->addMedia($document)->toMediaCollection(ConstantHelper::CUST_CONFIRMATION_DOC_COLLECTION_NAME);
                    }
                }
                DB::commit();
                return array(
                    'message' => $request -> has('cart') ? 'Order added to cart successfully' : 'Order update success',
                );
            } else {
                DB::rollBack();
                throw new ApiGenericException("Order Not found");
            }
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getCartOrders(Request $request)
    {
        try {
            $group = Group::find(auth() -> user() -> id);
            $products = CustomerProduct::select('id', 'project_id', 'product_id', 'total_quantity', 'ordered_quantity') -> where([
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
            ]) -> whereHas('order', function ($query) {
                $query -> where("in_cart", 1);
            }) -> get();
            foreach ($products as &$product) {
                $product -> order -> order_pumps_display = $product -> order -> order_pumps_display();
                $product -> order -> order_cube_mould_display = $product -> order -> order_cube_mould_display();
                $product -> order -> order_temp_control_display = $product -> order -> order_temp_control_display();
                $product -> order -> site = $product -> order -> customer_site -> name ?? "Jebel Ali";
                $product -> order -> makeHidden(['customer_site']);
            }
            if ($products -> count() > 0) {
                $project = CustomerProject::select('id', 'customer_id', 'code', 'name', 'type', 'start_date', 'end_date') -> with('address') -> find($products -> first() -> project_id);
                return array(
                    'message' => "Cart details found",
                    'data' => array(
                        'products' => $products,
                        'group_company' => $group ?-> name,
                        'project_details' => $project,
                        'group_address' => ""
                    )
                );  
            } else {
                return array(
                    'message' => "Cart is empty",
                    'data' => array(
                        'products' => [],
                        'group_company' => $group ?-> name,
                        'group_address' => "",
                        'project_details' => null
                    )
                );  
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function confirmCartOrders(Request $request)
    {
        try {
            $orders = Order::where([
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                ['in_cart', 1]
            ]) -> when(!$request -> is_user_admin, function ($query) use($request) {
                $query -> whereIn('project_id', $request -> project_ids);
            }) -> get();

            $ordersData = [];

            foreach($orders as $order) {
                $order -> in_cart = 0;
                $order -> save();
                $ordersData[] = [
                    'order_no' => $order -> order_no,
                    'delivery_date' => $order -> delivery_date,
                    'product_name' => $order -> customer_product -> product_name,
                    'location' => $order -> site
                ];
            }
            return array(
                'message' => 'Order has been placed successfully',
                'data' => array(
                    'orders' => $ordersData
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getOrderAdditionalInformation(Request $request, String $id)
    {
        try {
            $order = Order::select('id', 'is_technician_required', 'order_no', 'cust_product_id', 'project_id', 'quantity', 'delivery_date', 'interval', 'structural_reference_id', 'site_id', 'group_company_id', 'remarks')  -> with('order_pumps', 'order_cube_moulds', 'order_temp_control', 'customer_site') -> find($id);
            if (isset($order)) {
                $order -> site = $order -> customer_site ?-> name;
                $order -> address = $order -> customer_site ?-> address;
                $order -> makeHidden(['customer_site']);
                return array(
                    'message' => "Order details found",
                    'data' =>array(
                        'order_details' => $order
                    )
                );
            } else {
                throw new ApiGenericException("Order not found");
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function checkCartForMultipleProjects(Request $request, String $customerProductId)
    {
        try {
            $customerProduct = CustomerProduct::find($customerProductId);
            if (isset($customerProduct) && isset($customerProduct -> id)) {
                $customerProject = CustomerProject::find($customerProduct -> project_id);
                if (isset($customerProduct) && isset($customerProduct -> id)) {
                    $orders = Order::where([
                        ['customer_id', $request -> customer_id],
                        ['status', ConstantHelper::ACTIVE],
                        ['in_cart', 1]
                    ]) -> when(!$request -> is_user_admin, function ($query) use($request) {
                        $query -> whereIn('project_id', $request -> project_ids);
                    }) -> get();
                    $multipleProduct = false;
                    foreach ($orders as $order) {
                        if ($order -> project_id != $customerProject -> id) {
                            $multipleProduct = true;
                        }
                    }
                    return array(
                        'message' => 'Status returned successfully',
                        'data' => array(
                            'multiple_items' => $multipleProduct
                        )
                    );
                } else {
                    throw new ApiGenericException("Customer Project not found");
                }
            } else {
                throw new ApiGenericException("Customer Product Not Found");
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function removeOrderFromCart(Request $request, String $orderId)
    {
        try {
            DB::beginTransaction();
            $order = Order::where([
                ['customer_id', $request -> customer_id],
                ['id', $orderId],
                ['status', ConstantHelper::ACTIVE]
            ]) -> whereIn('project_id', $request -> project_id) -> first();
            if (isset($order)) {
                $customerProduct = CustomerProduct::find($order -> cust_product_id);
                if (isset($customerProduct)) {
                    $customerProduct -> ordered_quantity = $customerProduct -> ordered_quantity - $order -> quantity;
                    $customerProduct -> save();
                }
                OrderTempControl::where('order_id', $orderId) -> delete();
                OrderPump::where('order_id', $orderId) -> delete();
                OrderCubeMould::where('order_id', $orderId) -> delete();
                $order -> delete();
                DB::commit();
                return array(
                    'message' => 'Order Removed from cart successfully'
                );
            } else {
                DB::rollBack();
                throw new ApiGenericException("Order Not Found");
            }
            
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }
    public function removeAllOrderFromCart(Request $request)
    {
        try {
            DB::beginTransaction();
            $orders = Order::where([
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE]
            ]) -> whereIn('project_id', $request -> project_id) -> get();
            foreach ($orders as $order) {
                $customerProduct = CustomerProduct::find($order -> cust_product_id);
                if (isset($customerProduct)) {
                    $customerProduct -> ordered_quantity = $customerProduct -> ordered_quantity - $order -> quantity;
                    $customerProduct -> save();
                }
                OrderTempControl::where('order_id', $order -> id) -> delete();
                OrderPump::where('order_id', $order -> id) -> delete();
                OrderCubeMould::where('order_id', $order -> id) -> delete();
                $order -> delete();
            }
            DB::commit();
            return array(
                'message' => 'All cart items cleared'
            );
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function updateProductQuantity(Request $request)
    {
        $validator = (new Validator($request))->updateProductQuantity();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            DB::beginTransaction();
            $product = CustomerProduct::where([
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                ['id', $request -> id]
            ]) -> with('order') -> first();
            if ($request -> type === "ADD") {
                $qty = $product -> ordered_quantity + 1;
                $product -> ordered_quantity =  $qty;
                $order = Order::find($product -> order ?-> id);
                if ($order) {
                    $order -> quantity = $qty;
                    $order -> save();
                }
            } else {
                $qty = $product -> ordered_quantity - 1;
                $product -> ordered_quantity =  $qty;
                $order = Order::find($product -> order ?-> id);
                if ($order) {
                    $order -> quantity = $qty;
                    $order -> save();
                }
            }
            $product -> save();
            // $product -> order -> save();
            DB::commit();
            return array(
                'message' => 'Order updated successfully'
            );

        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function cancelOrder(Request $request, String $orderId)
    {
        try {
            DB::beginTransaction();
            $order = Order::where([
                ['id', $orderId],
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                // ['has_customer_confirmed', 0],
            ]) -> when(!$request -> is_user_admin, function ($query) use($request) {
                $query -> whereIn('project_id', $request -> project_ids);
            }) -> first();
            if (isset($order)) {
                $customerProduct = CustomerProduct::find($order -> cust_product_id);
                if (isset($customerProduct)) {
                    $customerProduct -> ordered_quantity = $customerProduct -> ordered_quantity - $order -> quantity;
                    $customerProduct -> save();
                }
                $order -> delete();
                DB::commit();
                return array(
                    'message' => "Order cancelled successfully"
                );
            } else {
                DB::rollBack();
                throw new ApiGenericException("Order Not Found");
            }
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getCartItemsCount(Request $request)
    {
        try {
            $orders = Order::select("id") -> where([
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                ['in_cart', 1]
            ]) -> when(!$request -> is_user_admin, function ($query) use($request) {
                $query -> whereIn('project_id', $request -> project_ids);
            }) -> get();
            $ordersCount = 0;
            if (isset($orders) && count($orders) > 0) {
                $ordersCount = count($orders);
            }
            return array(
                'message' => "Cart items count retrieved successfully",
                'data' => array(
                    'cart_items' => $ordersCount
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getTimeSlotsStatus(Request $request)
    {
        $validator = (new Validator($request))->getTimeSlotsStatus();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $customerId = $request -> customer_id;
            $isUserAdmin = $request -> is_user_admin;
            $projectIds = $request -> project_ids;
            $totalBpCapacity = BatchingPlant::where('group_company_id', $request -> group_company_id) -> where('status', ConstantHelper::ACTIVE) -> get() -> sum('capacity');
            $totalBpCapacity = $totalBpCapacity > 0 ? $totalBpCapacity : 1; // Safe
            $slots = new Collection();
            $orders = Order::where([
                ['customer_id', $customerId],
                ['status', ConstantHelper::ACTIVE],
                ['in_cart', 0],
            ])-> when(!$isUserAdmin, function ($query) use($projectIds) {
                $query -> whereIn('project_id', $projectIds);
            }) -> whereDate('delivery_date', $request -> schedule_date)->select("id", "order_no", "delivery_date", "interval", "quantity") -> get();
            foreach (ConstantHelper::TIME_SLOTS as $timeSlot) {
                $startTime = Carbon::parse($request -> schedule_date . ' ' . $timeSlot['start_time']);
                $endTime = Carbon::parse($request -> schedule_date . ' ' . $timeSlot['end_time']);
                $currentTimeOrders = $orders -> filter(function ($singleOrder) use($startTime, $endTime) {
                    $deliveryTime = Carbon::parse($singleOrder -> delivery_time);
                    return $deliveryTime -> between($startTime, $endTime);
                });
                $currentTimeOrdersQty = $currentTimeOrders -> sum('quantity');
                $percentageUsed = ($currentTimeOrdersQty / $totalBpCapacity) * 100;
                $status = ConstantHelper::SLOT_AVL;
                if ($percentageUsed >= 50 && $percentageUsed < 90) {
                    $status = ConstantHelper::SLOT_FILLING_FAST;
                } else if ($percentageUsed >= 90) {
                    $status = ConstantHelper::SLOT_BOOKED;
                } else {
                    $status = ConstantHelper::SLOT_AVL;
                }
                $slots -> push([
                    'time_slot' => $timeSlot['default_name'],
                    'status' => $status
                ]);
            }
            return array(
                'message' => 'Slots returned successfully',
                'data' => array(
                    'slots' => $slots
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
