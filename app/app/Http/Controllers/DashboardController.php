<?php

namespace App\Http\Controllers;

use App\Models\LiveOrder;
use App\Models\Order;
use App\Models\OrderSchedule;
use App\Models\SelectedOrderSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user->access_rights->pluck('group_company_id');
            $order = Order::where('status', '=', 'active')->count();

            // for 30 days filter

            $pendingOrdersCount = Order::wherenull('published_by')->whereBetween('delivery_date',  [
                Carbon::now()->subDays(30)->startOfDay(),
                Carbon::now()->endOfDay()
            ])
            ->count();

            $delivered = Order::wherenotnull('published_by')-> whereBetween('delivery_date', [
                Carbon::now()->subDays(30)->startOfDay(),
                Carbon::now()->endOfDay()
            ])
            ->whereHas('schedule')
            ->count();

            $inprograces = 0;
            // end days

            // $pendingOrdersCount = Order::whereDate('delivery_date', Carbon::today())
            // ->whereDoesntHave('schedule')->get()
            // ->count();
            // $inprograces = LiveOrder::whereDate('delivery_date', Carbon::today())->get()->count();
            // $delivered = LiveOrder::whereDate('delivery_date', Carbon::today())->where('delivered_quantity','>',0)->get()->count();

            $orderVolume = Order::whereNotNull('quantity')
                ->orderBy('quantity')
                ->pluck('quantity')
                ->toArray();

                $actualEndTimes = OrderSchedule::whereNotNull('loading_start')
                ->selectRaw("DATE_FORMAT(loading_start, '%h %p') as hour, batching_qty")
                ->whereDate('loading_start', Carbon::today())
                ->groupBy("hour", "batching_qty")
                ->orderBy('hour')
                ->pluck('hour')
                ->toArray();


            $deliveredQuantity = OrderSchedule::select('loading_start','batching_qty')->whereDate('loading_start', Carbon::today())->orderBy('loading_start')
                ->pluck('batching_qty')
                ->toArray();


            $orderVolumeDate = Order::whereDate('delivery_date', Carbon::today())
                ->selectRaw("TIME_FORMAT(delivery_date, '%h:%i %p') as delivery_date, quantity")->orderBy('delivery_date')
                ->pluck('quantity', 'delivery_date')
                ->toArray();
            $orderVolume = array_values($orderVolumeDate);
            $orderVolumeDate = array_keys($orderVolumeDate);

            $orderCount = 0;
            foreach ($orderVolume as $order) {
                $orderCount  +=$order;
                }
                $totalOrdersCount =
                $pendingOrdersCount + $inprograces + $delivered;


            //Order completion graph
            $liveOrders = LiveOrder::whereIn('group_company_id', $group_company_ids) -> get();
            $liveOnTimeOrders = $liveOrders -> filter(function($liveOrder) {
                return $liveOrder -> planned_start_time == $liveOrder -> actual_start_time;
            });
            $liveDelayedOrders = $liveOrders -> filter(function($liveOrder) {
                return isset($liveOrder -> actual_start_time) && $liveOrder -> planned_start_time != $liveOrder -> actual_start_time;
            });
            $livePendingOrders = $liveOrders -> filter(function($liveOrder) {
                return $liveOrder -> actual_start_time == null;
            });
            //Need to improve after client payment
            return view('dashboard', [
                'totalOrdersCount' => $totalOrdersCount,
                'orderCount' => $orderCount,
                'pendingOrdersCount' => $pendingOrdersCount,
                'inprograces' => $inprograces,
                'delivered' => $delivered,
                'actualEndTimes' => $actualEndTimes,
                'deliveredQuantity' => $deliveredQuantity,
                'order' => $order,
                'orderVolume' => $orderVolume,
                'orderVolumeDate' => $orderVolumeDate,
                'live_on_time' => $liveOnTimeOrders,
                'live_pending' => $livePendingOrders,
                'live_delayed' => $liveDelayedOrders,
                'total_live_orders' => $liveOrders
            ]);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function ordervolumeGraph(Request $request)
    {
        $type = $request->type;

        $orderVolumeDate = Order::whereDate('delivery_date', Carbon::today())
            ->selectRaw("TIME_FORMAT(delivery_date, '%h:%i %p') as delivery_date, quantity")->orderBy('delivery_date')
            ->pluck('quantity', 'delivery_date')
            ->toArray();

        if ($type === 'Yesterday') {
            $orderVolumeDate = Order::whereDate('delivery_date', Carbon::today()->subDay())
                ->selectRaw("TIME_FORMAT(delivery_date, '%h:%i %p') as delivery_date, quantity")->orderBy('delivery_date')
                ->pluck('quantity', 'delivery_date')
                ->toArray();
        }
        else if ($type === 'six_month') {
            $orderVolumeDate = Order::whereDate('delivery_date', '<=', Carbon::today())
                ->whereDate('delivery_date', '>=', Carbon::today()->subMonths(6))
                ->selectRaw("DATE_FORMAT(delivery_date, '%b') as delivery_month, SUM(quantity) as total_quantity, DATE_FORMAT(delivery_date, '%m') as delivery_month_number")
                ->groupBy('delivery_month', 'delivery_month_number') // Group by the formatted month
                ->orderBy("delivery_month_number")
                ->pluck('total_quantity', 'delivery_month')
                ->toArray();
        }

        else if ($type === 'seven_days') {

            $dates = collect();
            for ($i = 6; $i > 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $dates->put($date->format('d-M'), 0); // Initialize with 0
            }

            // Fetch the data from the database
            $orderVolumeData = Order::whereDate('delivery_date', '>=', Carbon::today()->subDays(6)) // Last 7 days
                ->whereDate('delivery_date', '<=', Carbon::today())
                ->selectRaw("DATE_FORMAT(delivery_date, '%d-%b') as formatted_date, SUM(quantity) as total_quantity")
                ->groupBy('formatted_date')
                ->orderBy('formatted_date', 'asc')
                ->get()
                ->pluck('total_quantity', 'formatted_date')
                ->toArray();

            $orderVolumeDate = $dates->merge($orderVolumeData);

            $orderVolumeDate = $orderVolumeDate->toArray();


        } else if ($type === 'one_month') {
            $orderVolumeDate = Order::whereDate('delivery_date', "<=", Carbon::today())->whereDate('delivery_date', ">=", Carbon::today()->subMonth())
                ->selectRaw("DATE_FORMAT(delivery_date, '%d-%b') as formatted_month, SUM(quantity) AS total_quantity, delivery_date")->groupBy('delivery_date')
                ->orderBy('delivery_date', 'asc')
                ->pluck('total_quantity', 'formatted_month')
                ->toArray();
        }

        $orderVolume = array_values($orderVolumeDate);
        $orderVolumeDate = array_keys($orderVolumeDate);
        $orderCount = 0;
        foreach ($orderVolume as $order) {

            $orderCount  +=$order;
            }

        return response()->json([
            'data' => array(
                'orderVolume' => $orderVolume,
                'orderVolumeDate' => $orderVolumeDate,
                'orderCount' => $orderCount,
            )
        ]);
    }


    public function orderGraph(Request $request)
    {


        $type = $request->type;

if($type=='today'){

    $pendingOrdersCount = Order::whereDate('delivery_date', Carbon::today())
       ->whereDoesntHave('schedule')->get()
       ->count();
       $inprograces = LiveOrder::whereDate('delivery_date', Carbon::today())->get()->count();
       $delivered = LiveOrder::whereDate('delivery_date', Carbon::today())->where('delivered_quantity','>',0)->get()->count();
}else{

    $pendingOrdersCount = Order::wherenull('published_by')->whereBetween('delivery_date',  [
        Carbon::now()->subDays(30)->startOfDay(),
        Carbon::now()->endOfDay()
    ])
    ->count();

    $delivered = Order::wherenotnull('published_by')-> whereBetween('delivery_date', [
        Carbon::now()->subDays(30)->startOfDay(),
        Carbon::now()->endOfDay()
    ])
    ->whereHas('schedule')
    ->count();

    $inprograces = 0;
}





        return response()->json([
            'data' => array(
                'pendingOrdersCount' => $pendingOrdersCount,
                'inprograces' => $inprograces,
                'delivered' => $delivered,
            )
        ]);
    }
}



