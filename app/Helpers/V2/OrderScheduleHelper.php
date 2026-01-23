<?php
namespace App\Helpers\V2;

use App\Models\BatchingPlant;
use App\Models\BatchingPlantAvailability;
use App\Models\GlobalSetting;
use App\Models\GroupCompany;
use App\Models\LiveOrder;
use App\Models\LiveOrderPumpSchedule;
use App\Models\LiveOrderSchedule;
use App\Models\Order;
use App\Models\OrderPump;
use App\Models\SelectedOrderSchedule;
use App\Models\SelectedOrder;
use App\Models\Pump;
use App\Models\SelectedOrderPumpSchedule;
use App\Models\TransitMixer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\PumpHelper;
use App\Helpers\TransitMixerHelper;
use App\Helpers\BatchingPlantHelper;
use App\Helpers\TransitMixerRestrictionHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\CommonHelper;
/**
 * 
 */


class OrderScheduleHelper 
{


	function __construct(int $user_id, string $company, string $schedule_date, array $transit_mixer_ids, array $pump_ids, array $batching_plant_ids, string $schedule_preference, string $shift_start, string $shift_end, int $interval_deviation)
	{

		set_time_limit(5000); //NEED TO REMOVE
	
		$this->company  = $company; 
		$this->user_id  = $user_id; 
		$this->schedule_date  = $schedule_date; 
		$this->transit_mixer_ids  = $transit_mixer_ids; 
		$this->pump_ids  = $pump_ids; 
		$this->batching_plant_ids  = $batching_plant_ids; 
		$this->schedule_preference  = $schedule_preference; 
		$this->shift_start  = $shift_start; 
		$this->shift_end  = $shift_end;
		$this->interval_deviation  = $interval_deviation;	
		$this->sch_adj_from = 0;
		$this->sch_adj_to = 1440;




		$this->qc_time = GlobalSetting::where('group_company_id', $company)->value('batching_quality_inspection') ?? ConstantHelper::QC_TIME;

		$this->insp_time = GlobalSetting::where('group_company_id', $company)->value('site_quality_inspection') ?? ConstantHelper::INSP_TIME;

		$this->cleaning_time = GlobalSetting::where('group_company_id', $company)->value('chute_cleaning_site') ?? ConstantHelper::CLEANING_TIME;

	}

	public function resetOrders() {
		SelectedOrderPumpSchedule::where("group_company_id", $this->company)->where("user_id", $this->user_id)->delete();
        BatchingPlantAvailability::where("group_company_id", $this->company)->where("user_id", $this->user_id)->delete();

        SelectedOrder::where("group_company_id", $this->company)->whereBetween("delivery_date", [$this->shift_start, $this->shift_end])->where("user_id", $this->user_id)->update(['start_time' => null, 'end_time' => null, 'deviation' => null, ]);
        SelectedOrderSchedule::where("group_company_id", $this->company)->where("user_id", $this->user_id)->delete();
            
	}

	public function generateRestrictions() {
		    //Travel restrictions on TM
        $restrictions = TransitMixerRestrictionHelper::getRestrictions($this->company, $this->schedule_date, $this->shift_start);
        $this->restriction_start = $restrictions['restriction_start'];
        $this->restriction_end = $restrictions['restriction_end'];
	}	

	public function generateDefaultParams() {

          
	}


	public function generateSchedule() {

		try
        {

            $batching_qty = 0;

            //Order and Pump Schedules
            $selected_order_pump_schedules = [];
            $schedules = [];

            //Availabilities data
            $transit_mixer_availability = $this->tms_availabilty;
            $pump_availability = $this->pumps_availabilty;
            $batching_plant_availability = $this->bps_availabilty;

            //Copies for rollback
            $transit_mixer_availability_copy = $this->tms_availabilty;
            $pump_availability_copy = $this->pumps_availabilty;
            $batching_plant_availability_copy = $this->bps_availabilty;

            //Shift timings calculation
            $location_start_time = $this->shift_start;
            $location_end_time = $this->shift_end;

            //Restrictions
            $restriction_start_parsed = $this->restriction_start;
            $restriction_end_parsed = $this->restriction_end;
            if (isset($this->restriction_start) && isset($this->restriction_end))
            {
                $restriction_start_parsed = Carbon::parse($this->restriction_start);
                $restriction_end_parsed = Carbon::parse($this->restriction_end);
            }
            //Orders
            $orders = SelectedOrder::select("group_company_id", "id", 'og_order_id', "order_no", "customer", "project", "site", "location", "mix_code", "quantity", "delivery_date", "interval", "interval_deviation", "pump", "pouring_time", "travel_to_site", "return_to_plant", "pump_qty", "priority")
            	->where("group_company_id", $this->company)->where("user_id", $this->user_id)
            	->whereBetween("delivery_date", [$location_start_time, $location_end_time])
            	->whereNull("start_time")
                ->where("selected", true)
                ->orderBy('quantity','DESC')
                ->orderBy('priority','ASC')
                ->get();
            $truck_capacities = array_unique(array_column($transit_mixer_availability, 'truck_capacity'));
            $min_truck_cap = min($truck_capacities);
            $locations = array_unique(array_column($batching_plant_availability, 'location'));

            //Initialize variables, Resources
            $pouring_pump = null;
            $transit_mixer = null;
            $batching_plant = null;

            $pouring_pump_index = null;
            $transit_mixer_index = null;
            $batching_plant_index = null;
            $pump_ids = [];

            $loading_time = 0;
            $pouring_end_prev = "";
            $pouring_start_prev = "";
            $trip_reset_time = "";

            $delivery_date_n = "";
            $delivery_date_p = "";
            $delivery_date = "";
            $travel_time = "";
            $total_time = "";

            $loading_start = "";
            $loading_end = "";

            $first_trip_qc_start = "";
            $qc_start = "";
            $qc_end = "";

            $travel_start = "";
            $travel_end = "";

            $insp_start = "";
            $insp_end = "";

            $pouring_start = "";
            $pouring_end = "";

            $cleaning_start = "";
            $cleaning_end = "";

            $return_start = "";
            $return_end = "";
            $return_time = "";

            $pump_start_time = "";
            $pump_end_time = "";

            $deviation = 0;
            $location = "";
            $sch_adj_time = 0;

            $bpScheduleGap = [];

            $shift_end_exit = 0;

            $orders_copy = $orders->toArray();

            foreach ($orders as $orderKey => $order)
            { // Order loop start
                // dd($order);
                Log::info('start ----------------------------------------------------------- ');
                // Log::info('this is orderLoop1: ' . $order->order_no);
                // dd('an');
                $pouring_time = $order->interval;
                // Assign large values between interval and pouring time
                if ($order->pouring_time > $order->interval)
                {
                    $pouring_time = $order->pouring_time;
                }
                //Divide pouring time acc to pump qty
                $pouring_interval = 0;
                if ($order->pump_qty > 1)
                {
                    $pouring_interval = round(($pouring_time / $order->pump_qty) , 0);
                }

                //Reset variables
                $pouring_pump = null;
                $transit_mixer = null;
                $batching_plant = null;

                $pouring_pump_index = null;
                $transit_mixer_index = null;
                $batching_plant_index = null;

                $pump_ids = [];

                $loading_time = 0;
                $pouring_end_prev = "";
                $pouring_start_prev = "";
                $trip_reset_time = "";

                $delivery_date = $order->delivery_date;

                $deviation = 0;
                $sch_adj_time = 0;

                //Get Locations availability
                $index = array_search($order->location, $locations);
                if ($index !== false && $index > 0)
                {
                    unset($locations[$index]);
                    array_unshift($locations, $order->location);
                }

                //Locations Loop

                foreach ($locations as $loc)
                {
                    $location = $loc;
                    //Check for first available plant time
                    $plant_availability = BatchingPlantHelper::getMinAvailTimeCopy($batching_plant_availability_copy, ConstantHelper::LOADING_TIME, $batching_plant, $batching_plant_index, $restriction_start_parsed, $restriction_end_parsed);

                    $delivery_time = $delivery_date;

                    $sch_adj_time = isset($this->sch_adj_from) ? $this->sch_adj_from : 0;
                    $delivery_date_n = Carbon::parse($delivery_time)->copy()
                        ->addMinutes($sch_adj_time);

                    $delivery_date_p = Carbon::parse($delivery_time)->copy()
                        ->subMinutes($sch_adj_time);

                    $avl = 0;
                    $avlCounter = 0;
                    $restriction_flag = Carbon::parse(ConstantHelper::DEFAULT_DATE_TIME);
                    //Schedule adjustment based on availability LOOP
                    while ($avl == 0 && $avlCounter <= 215)
                    {
                        $avlCounter++;
                        // echo "AAAAAAA".$avlCounter;
                        // Log::info('avl while: order-' . $order->order_no . 'avl' . $avl);
                        //Forward backward adjustment loop
                        foreach (ConstantHelper::TO_FROM_LOOP as $val)
                        {
                            Log::info('tofromLOOP: order-' . $order->order_no . 'val' . $val);
                            if (isset($restriction_start_parsed) && isset($restriction_end_parsed))
                            {
                                if ($val == 1)
                                {
                                    if (Carbon::parse($delivery_date_n)->between($restriction_start_parsed, $restriction_end_parsed))
                                    {
                                        continue;
                                    }
                                }
                                else
                                {
                                    if (Carbon::parse($delivery_date_p)->between($restriction_start_parsed, $restriction_end_parsed))
                                    {
                                        break;
                                    }
                                }
                            }
                            //Reset resources
                            $transit_mixer = null;
                            $pouring_pump = null;

                            $pouring_pump_index = null;
                            $transit_mixer_index = null;

                            if ($this->sch_adj_from != 0)
                            {
                                $batching_plant = null;
                                $batching_plant_index = null;
                            }

                            $pump_ids = [];
                            $qty = $order->quantity;
                            $trip = 1;
                            $tl = 0;
                            $qtyCounter = 0;
                            //Trips Loop
                            while ($qty > 0 && $qtyCounter <= 210)
                            {
                                $tl++;
                                // dd('a');
                                $qtyCounter++;
                                // echo "qqqqq:" .$qtyCounter;
                                Log::info('QTY while: order-' . $order->order_no . '-qty-' . $qty);
                                //Truck Loop
                                $tc = 0;
                                dd($truck_capacities);
                                foreach ($truck_capacities as $truck_capacity)
                                {
                                    // dd('aaa');
                                    $transit_mixer = null;
                                    $transit_mixer_index = null;

                                    // $loading_time = ConstantHelper::LOADING_TIME;
                                    $newCurrentOrder = SelectedOrder::find($order->id); //Need to optimize
                                    if (isset($newCurrentOrder))
                                    {
                                        $newLoadingTime = $newCurrentOrder->customer_product ?->product ?->product_type ?->batching_creation_time ?? ConstantHelper::LOADING_TIME;
                                        // dd($newLoadingTime);
                                    }
                                    $loading_time = isset($newLoadingTime) ? $newLoadingTime : ConstantHelper::LOADING_TIME;
                                    //First Trip
                                    if ($trip == 1)
                                    {
                                        if ($sch_adj_time == 0)
                                        {
                                            $delivery_date = $delivery_time;
                                        }
                                        else
                                        {
                                            if ($val == 1)
                                            {
                                                $delivery_date = $delivery_date_n;
                                            }
                                            else
                                            {
                                                $delivery_date = $delivery_date_p;
                                            }
                                        }
                                        Log::info('TRIP 1 D_date :'.$delivery_date.'--val:'.$val);

                                        //Subsequent Trips
                                        
                                    }
                                    else
                                    {
                                        // $delivery_dateA = $pouring_interval > 0 ? $pouring_start_prev->copy()
                                        //     ->addMinutes($pouring_interval) : $pouring_end_prev->copy()
                                        //     ->subMinutes($pouring_interval)->addMinute();

                                        if($val == 1) {
                                            
                                            $delivery_date =  $pouring_interval > 0 ?  $pouring_start_prev->copy()
                                                ->addMinutes($pouring_interval) :   $pouring_end_prev->copy()
                                                ->addMinutes();
                                        } else {
                                            $delivery_date = $pouring_interval > 0 ? $pouring_start_prev->copy()
                                                ->subMinutes($pouring_interval)->addMinute() : $pouring_end_prev->copy()
                                                ->subMinutes();
                                        }

                                        Log::info('TRIP '.$trip.' D_date :'.$delivery_date.'--val:'.$val.'-PI-'.$pouring_interval.'-dda-'.$pouring_end_prev);
                                        
                                    }
                                    $delivery_date = Carbon::parse($delivery_date);

                                    //Restriction check
                                    if (isset($restriction_start_parsed) && isset($restriction_end_parsed) && ($schedule_preference == ConstantHelper::CUSTOMER_TIMELINE_PREF || $schedule_preference == ConstantHelper::LARGEST_JOB_FIRST_PREF))
                                    {
                                        if ($delivery_date->gte($restriction_start_parsed) && $delivery_date->lte($restriction_end_parsed))
                                        {
                                            if ($restriction_flag->notEqualTo($restriction_start_parsed))
                                            {
                                                $delivery_time = $restriction_end_parsed->copy()
                                                    ->addMinute();
                                                $sch_adj_time = - 1;
                                                $avl = 0;
                                                $restriction_flag = $restriction_start_parsed;
                                                // Log::info('truck: restriction flag-' . $order->order_no . '-qty-' . $qty . '--tc--' . $truck_capacity.'->tl--'.$tl++.'tc>>'.$tc++);
                                                break;
                                            }
                                        }
                                    }

                                    //Time calculation for activities
                                    $travel_time = $order->travel_to_site;
                                    $total_time = ((int)$loading_time) + $qc_time + ((int)$travel_time) + $insp_time;
                                    // dd($loading_time,$qc_time,$travel_time,$insp_time);
                                    $loading_start = $delivery_date->copy()
                                        ->subMinutes($total_time);
                                    $loading_end = $loading_start->copy()
                                        ->addMinutes($loading_time)->subMinute();

                                    $qc_start = $loading_end->copy()
                                        ->addMinute();
                                    $qc_end = $qc_start->copy()
                                        ->addMinutes($qc_time)->subMinute();
                                        // dd($order);
// dd([$total_time,$loading_start,$loading_end ,$qc_start,$qc_end]);

                                    if ($trip == 1)
                                    {
                                        $first_trip_qc_start = $qc_start;
                                    }

                                    $travel_start = $qc_end->copy()
                                        ->addMinute();
                                    $travel_end = $travel_start->copy()
                                        ->addMinutes($travel_time)->subMinute();
                                    $insp_start = $travel_end->copy()
                                        ->addMinute();
                                    $insp_end = $insp_start->copy()
                                        ->addMinutes($insp_time)->subMinute();


                                    $pouring_start = $insp_end->copy()
                                        ->addMinute();
                                    $pouring_end = $pouring_start->copy()
                                        ->addMinutes($pouring_time)->subMinute();
                                    $cleaning_start = $pouring_end->copy()
                                        ->addMinute();
                                    $cleaning_end = $cleaning_start->copy()
                                        ->addMinutes($cleaning_time)->subMinute();


                                    $return_time = $order->return_to_plant;
                                    $return_start = $cleaning_end->copy()
                                        ->addMinute();
                                    $return_end = $return_start->copy()
                                        ->addMinutes($return_time)->subMinute();

                                    $deviation = ($pouring_start->copy())
                                        ->diffInMinutes($order->delivery_date);

                                    $shift_end_exit = 0;
                                    if (Carbon::parse($loading_start)->gt(Carbon::Parse($location_end_time)) && $trip > 1)
                                    {
                                        $shift_end_exit = 1;
                                        // Log::info('truck: shift end exit-' . $order->order_no . '-qty-' . $qty . '--tc--' . $truck_capacity.'->tl--'.$tl++.'tc>>'.$tc++);
                                        break;
                                    }
                                    // dd($loading_start);
                                    if($avlCounter > 100){

                                        // dd($avlCounter,$batching_plant_availability_copy);
                                    }
                                    // Log::info("calling bps available helper here :::::::::::");
                                    $plant = BatchingPlantHelper::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $location_end_time, $batching_plant);
                                    // dd($plant);
                                    if (isset($plant))
                                    {
                                        // echo 'batch tl'.$tl;
                                        // dd('plant avl:',$plant );
                                        $batching_plant = $plant['data'];
                                        $batching_plant_index = $plant['index'];
                                        Log::info('truck: batching_plant FOUND-' . $order->order_no . '-qty-' . $qty.'avlCtr:'.$avlCounter.'loading_start:'.$loading_start.'loading_end:'.$loading_end);
                                        // Log::info('BATCHING AVL:'.json_encode($batching_plant_availability_copy));
                                    }
                                    else
                                    {
                                        $batching_plant = $plant;
                                        Log::info('truck: batching_plant NOT FOUND-' . $order->order_no . '-qty-' . $qty .'avlCtr:'.$avlCounter.'loading_start:'.$loading_start.'loading_end:'.$loading_end);
                                        // Log::info('BATCHING NOT AVL:'.json_encode($batching_plant_availability_copy));

                                        // dd($batching_plant_availability_copy,$loading_start,$loading_end);
                                        // Log::info('truck: batching_plant else- break' . $order->order_no . '-qty-' . $qty . '--tc--' . $truck_capacity.'->tl--'.$tl++.'tc>>'.$tc++);
                                        break;
                                    }

                                    // if (!isset($batching_plant)) {
                                    //     $bplant = null;
                                    //     $bplant = BatchingPlantHelper::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_start, $restriction_start, $location_end_time, $restriction_end);
                                    //     if (!isset($bplant)) {
                                    //         break;
                                    //     }
                                    // } else {
                                    //     $bplant = ['data' => $batching_plant, 'index' => $batching_plant_index];
                                    // }
                                    if (isset($order->pump))
                                    {
                                        Log::info(json_encode('if pump required: order-' . $order->order_no));
                                        $release_current_pump = false;
                                        $current_remaining_qty = $qty - $truck_capacity;
                                        $reamining_pump_trips = ceil($current_remaining_qty / $min_truck_cap);
                                        $reamining_pump_trips = $reamining_pump_trips / $order->pump_qty;
                                        if ($reamining_pump_trips < 1)
                                        {
                                            $release_current_pump = true;
                                        }
                                        $lastTripAll = $qty - min([$qty, $truck_capacity]) <= 0;
                                        // Get Pump Start and End Time
                                        $lastTrip = $qty - min([$qty, $truck_capacity]) <= 0;
                                        $pumpTrip = $trip;
                                        // if ($trip > 1) {
                                        //     $temp_pump_ids = $pump_ids;
                                        //     if (count($temp_pump_ids) < $order->pump_qty) {
                                        //         $pumpTrip = 1;
                                        //     }
                                        // }
                                        // dd($pump_availability_copy);
                                        $pump_timings = PumpHelper::getPumpStartAndEndTime($qc_start, $pouring_end, $pouring_start, $return_end, $cleaning_end, $release_current_pump, $pumpTrip);
                                        $pump_start_time = $pump_timings['pump_start'];
                                        $pump_end_time = $pump_timings['pump_end'];

                                        $pump = PumpHelper::get_available_pumps($pump_availability_copy, $order->id, $company, $pump_start_time, $pump_end_time, $order->pump, $trip, $selected_order_pump_schedules, $location_end_time, $order->pump_qty, $location);
                                        $pump = isset($pump) ? $pump : PumpHelper::get_available_pumps($pump_availability_copy, $order->id, $company, $pump_start_time, $pump_end_time, $order->pump, $trip, $selected_order_pump_schedules, $location_end_time, $order->pump_qty);

                                        $pump = isset($pump) ? $pump : PumpHelper::get_available_pumps($pump_availability_copy, $order->id, $company, $pump_start_time, $pump_end_time, $order->pump, null, $selected_order_pump_schedules, $location_end_time, $order->pump_qty);

                                        // dd($pump);
                                        if (isset($pump))
                                        {
                                            // echo 'pump tl'.$tl;
                                            Log::info(json_encode('pump Avl if: order-' . $order->order_no . '-pump-' . $pump['pump']['pump_name']));
                                            $pouring_pump = $pump['pump'];
                                            $pouring_pump_index = $pump['index'];
                                        }
                                        else
                                        {
                                            Log::info(json_encode('pump Avl else: order-' . $order->order_no . '-pump-' . $pump));
                                            $pouring_pump = $pump;
                                            // dd($pump_availability_copy);
                                            break ;
                                        }

                                    }

                                    //Assign current Truck capacity and check AVL
                                    $truck_cap = (int)$truck_capacity;

                                    $truck = TransitMixerHelper::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end, $location);
                                    $truck = isset($truck) ? $truck : TransitMixerHelper::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end);
                                    if (isset($truck))
                                    {
                                        // echo 'truck tl'.$tl;
                                        Log::info('truck avl');
                                        $transit_mixer = $truck['data'];
                                        $transit_mixer_index = $truck['index'];
                                    }
                                    else
                                    {
                                        Log::info('truck NOT avl');

                                        $transit_mixer = $truck;
                                    }
                                    if (!isset($transit_mixer))
                                    {
                                        continue;
                                    }
                                    //All resources assigned
                                    if (((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null)) && isset($transit_mixer) && isset($batching_plant))
                                    {
                                        // dd('inside');
                                        //Assign pouring end starting next trip
                                        Log::info('All resources Found order-' . $order->order_no);
                                        // dd('all');
                                        $actual_interval_deviation = isset($order->interval_deviation) ? $order->interval_deviation : $interval_deviation;
                                        $max_deviation = round($pouring_time * $actual_interval_deviation / 100, 0);

                                        // $pouring_end_prev = $pouring_end->copy()->addMinutes($pouring_time);
                                        $pouring_end_prev = $pouring_end->copy()
                                            ->addMinutes($max_deviation);
                                        // $pouring_end_prev = $pouring_end -> copy() -> addMinutes(1);
                                        // $pouring_end_prev = $pouring_end;
                                        $pouring_start_prev = $pouring_start;
                                        // $trip_reset_time = $pouring_end;
                                        // $trip_reset_time = $loading_start -> copy() -> addMinutes($total_time + $loading_time);
                                        $trip_reset_time = $loading_start->copy()
                                            ->addMinutes($total_time + $pouring_interval > 0 ? 0 : $loading_time);
                                        //Update Pump AVL
                                        if (isset($order->pump))
                                        {
                                            $pump_availability_copy[$pouring_pump_index]['free_upto'] = Carbon::parse($pump_start_time)->copy()
                                                ->subMinute();
                                            $pump_availability_copy[$pouring_pump_index]['location'] = $location;

                                            if($pump_availability_copy[$pouring_pump_index]['free_upto']  <= $pump_availability_copy[$pouring_pump_index]['free_from'] ) {
                                                unset($pump_availability_copy[$pouring_pump_index]);
                                            }

                                            $pump_availability_copy[] = array(
                                                'pump_name' => $pouring_pump['pump_name'],
                                                'pump_capacity' => $pouring_pump['pump_capacity'],
                                                'free_from' => Carbon::parse($pump_end_time)->copy()
                                                    ->addMinute() ,
                                                'free_upto' => $pouring_pump['free_upto'],
                                                'location' => $location,
                                                'order_id' => $release_current_pump ? null : $order->id . '-' . (($trip) + $order->pump_qty) ,
                                                'order_id_wo_trip' => $release_current_pump ? null : $order->id
                                            );
                                            if ($lastTripAll)
                                            {
                                                foreach ($pump_availability_copy as & $pAvl)
                                                {
                                                    if (Carbon::parse($pAvl['free_upto'])->gte(Carbon::parse($pAvl['free_from'])))
                                                    {
                                                        $innerOrder = explode('-', $pAvl['order_id']);
                                                        if (count($innerOrder) > 0)
                                                        {
                                                            $order_id = $innerOrder[0];
                                                            if ($order_id == $order->id)
                                                            {
                                                                $pAvl['order_id'] = null;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        //Update Transit Mixer AVL
                                        $transit_mixer_availability_copy[$transit_mixer_index]['free_upto'] = $loading_start->copy()
                                            ->subMinute();
                                        $transit_mixer_availability_copy[$transit_mixer_index]['location'] = $location;

                                        if($transit_mixer_availability_copy[$transit_mixer_index]['free_upto'] <= $transit_mixer_availability_copy[$transit_mixer_index]['free_from']) {
                                            unset($transit_mixer_availability_copy[$transit_mixer_index]);
                                        }
                                        $transit_mixer_availability_copy[] = array(
                                            'truck_name' => $transit_mixer['truck_name'],
                                            'truck_capacity' => $truck_capacity,
                                            'loading_time' => $loading_time,
                                            'free_from' => $return_end->copy()
                                                ->addMinute() ,
                                            'free_upto' => $transit_mixer['free_upto'],
                                            'location' => $location,
                                        );
                                        //Update Batching Plant AVL
                                        Log::info('updating batching plant availabilities');


                                        $batching_plant_availability_copy[$batching_plant_index]['free_upto'] = $loading_start->copy()
                                            ->subMinute();

                                        if($batching_plant_availability_copy[$batching_plant_index]['free_upto'] <= $batching_plant_availability_copy[$batching_plant_index]['free_from']) {
                                            unset($batching_plant_availability_copy[$batching_plant_index]);
                                        }

                                        $batching_plant_availability_copy[] = array(
                                            'location' => $location,
                                            'plant_name' => $batching_plant['plant_name'],
                                            'plant_capacity' => $batching_plant['plant_capacity'],
                                            'free_from' => $loading_end->copy()
                                                ->addMinute() ,
                                            'free_upto' => $batching_plant['free_upto'],
                                        );

                                        $batching_qty = min([$truck_capacity, $qty]);
                                        // dd($batching_qty);



                                        break ;
                                    }

                                } //End Truck Loop
                                // dd('loop truck end');
                                // dd('aa');

                                //Trip adjustiment
                                if (((!isset($transit_mixer)) || (!isset($batching_plant)) || (!isset($pouring_pump) && isset($order->pump))) && $shift_end_exit === 0)
                                {
                                    Log::info('trip adjustment condition if: order-' . $order->order_no);
                                    // dd('trip adjustment');
                                    if (isset($batching_plant) && $generateLog)
                                    {
                                        $reason = ConstantHelper::TRIP_GAP;
                                        if (!isset($transit_mixer) && (!isset($pouring_pump) && isset($order->pump)))
                                        {
                                            $reason = ConstantHelper::TM_AND_PUMP_NOT_AVL;
                                        }
                                        else if (!isset($transit_mixer))
                                        {
                                            $reason = ConstantHelper::TM_NOT_AVL;
                                        }
                                        else if ((!isset($pouring_pump) && isset($order->pump)))
                                        {
                                            $reason = ConstantHelper::PUMP_NOT_AVL;
                                        }
                                        $b_flag = false;
                                        foreach ($bpScheduleGap as $gap)
                                        {
                                            // Log::info('bpScheduleGap: order-' . $order->order_no . '--gap--' . $gap['free_from'] . 'loadingStart' . $loading_start);
                                            if (Carbon::Parse($loading_start)->eq(Carbon::parse($gap['free_from'])))
                                            {
                                                $b_flag = true;
                                                break;
                                            }
                                        }
                                        if ($b_flag == false && $reason !== ConstantHelper::TRIP_GAP)
                                        {

                                            BatchingPlantAvailability::create(['group_company_id' => $company, 'location' => $location, 'plant_name' => $batching_plant['plant_name'], 'plant_capacity' => 0, 'free_from' => $loading_start, 'free_upto' => $loading_start, 'user_id' => $user_id, 'reason' => $reason]);
                                            // // //         }
                                            
                                        }
                                    }

                                    if ($trip > 1)
                                    {

                                        // Log::info('trip condtion gr 1: order-' . $order->order_no);

                                        // if ((Carbon::parse($pouring_end_prev) -> diffInMinutes(Carbon::parse($trip_reset_time))) > $pouring_time )
                                        // if ((Carbon::parse($pouring_end_prev)->diffInMinutes(Carbon::parse($trip_reset_time))) <= 0) {
                                        if (Carbon::parse($pouring_end_prev)->lt(Carbon::parse($trip_reset_time)))
                                        {
                                            // Log::info('trip condtion continue if: order-' . $order->order_no);
                                            break;
                                        }
                                        else
                                        {
                                            // Log::info('trip condtion continue else: order-' . $order->order_no);
                                            // $pouring_end_prev = Carbon::parse($pouring_end_prev) -> copy() -> addMinute();
                                            $pouring_end_prev = Carbon::parse($pouring_end_prev)->copy()
                                                ->subMinute();
                                            $pouring_start_prev = Carbon::parse($pouring_start_prev)->copy()
                                                ->addMinute();
                                            continue;
                                        }
                                    }
                                    else
                                    {
                                        // Log::info('trip condtion else: order-' . $order->order_no);
                                        break;
                                    }
                                }
                                else
                                { //Trip fulfilled
                                    // dd('trip full');
                                    if ($trip == 1)
                                    {


                                        if ($shift_end_exit === 1)
                                        {
                                            // dd($shift_end_exit);
                                            break;
                                        }

                                        $schedules[] = array(
                                            "order_id" => $order->id,
                                            'group_company_id' => $company,
                                            'user_id' => $user_id,
                                            'schedule_date' => $schedule_date,
                                            'order_no' => $order->order_no,
                                            'pump' => isset($pouring_pump) ? $pouring_pump['pump_name'] : null,
                                            'location' => $location,
                                            'trip' => $trip,
                                            'mix_code' => $order->mix_code,
                                            'batching_plant' => $batching_plant['plant_name'],
                                            'transit_mixer' => $transit_mixer['truck_name'],
                                            'batching_qty' => $batching_qty,
                                            'loading_time' => $loading_time,
                                            'loading_start' => $loading_start,
                                            'loading_end' => $loading_end,
                                            'qc_time' => $qc_time,
                                            'qc_start' => $qc_start,
                                            'qc_end' => $qc_end,
                                            'travel_time' => $travel_time,
                                            'travel_start' => $travel_start,
                                            'travel_end' => $travel_end,
                                            'insp_time' => $insp_time,
                                            'insp_start' => $insp_start,
                                            'insp_end' => $insp_end,
                                            'pouring_time' => $pouring_time,
                                            'pouring_start' => $pouring_start,
                                            'pouring_end' => $pouring_end,
                                            'cleaning_time' => $cleaning_time,
                                            'cleaning_start' => $cleaning_start,
                                            'cleaning_end' => $cleaning_end,
                                            'return_time' => $return_time,
                                            'return_start' => $return_start,
                                            'return_end' => $return_end,
                                            'delivery_start' => $delivery_date,
                                            'deviation' => $deviation
                                        );
                                        // dd($schedules);
                                        if (isset($order->pump))
                                        {

                                            $pump_update = CommonHelper::searchAndUpdateArray($selected_order_pump_schedules, ['group_company_id' => $company, 'schedule_date' => $schedule_date, 'order_no' => $order->order_no, 'pump' => $pouring_pump['pump_name'], 'location' => $location], ['pouring_time' => ['value' => $pouring_end], 'pouring_end' => $pouring_end, 'cleaning_start' => $cleaning_start, 'cleaning_end' => $cleaning_end,
                                            // 'return_time' => $return_time,
                                            // 'return_start' => $return_start,
                                            // 'return_end' => $return_end
                                            ]);
                                            if ($pump_update['match'] === false)
                                            {
                                                $selected_order_pump_schedules[] = array(
                                                    'order_id' => $order->id,
                                                    'user_id' => $user_id,
                                                    'group_company_id' => $company,
                                                    'schedule_date' => $schedule_date,
                                                    'order_no' => $order->order_no,
                                                    'pump' => $pouring_pump['pump_name'],
                                                    'location' => $location,
                                                    'trip' => $trip,
                                                    'mix_code' => $order->mix_code,
                                                    'batching_qty' => $batching_qty,
                                                    'qc_time' => $qc_time,
                                                    'qc_start' => $qc_start,
                                                    'qc_end' => $qc_end,
                                                    'travel_time' => $travel_time,
                                                    'travel_start' => $travel_start,
                                                    'travel_end' => $travel_end,
                                                    'insp_time' => $insp_time,
                                                    'insp_start' => $insp_start,
                                                    'insp_end' => $insp_end,
                                                    'pouring_time' => $pouring_time,
                                                    'pouring_start' => $pouring_start,
                                                    'pouring_end' => $pouring_end,
                                                    'cleaning_time' => $cleaning_time,
                                                    'cleaning_start' => $cleaning_start,
                                                    'cleaning_end' => $cleaning_end,
                                                    'return_time' => 0,
                                                    'return_start' => null,
                                                    'return_end' => null,
                                                    'delivery_start' => $pouring_start
                                                );
                                            }
                                            else
                                            {
                                                $selected_order_pump_schedules = $pump_update['data'];
                                            }
                                            if (!in_array($pouring_pump['pump_name'], $pump_ids))
                                            {
                                                $pump_ids[] = $pouring_pump['pump_name'];
                                            }

                                        }

                                    }
                                    // echo "t:".$trip;
                                    // if($trip == 3){

                                    // // dd($selected_order_pump_schedules);
                                    // }

                                    //Next trip
                                    $qty = $qty - $batching_qty;

                                    $trip += 1;
                                    // Log::info('after schedule: order-' . $order->order_no . 'qty' . $qty . 'trip' . $trip . 'batching_qty' . $batching_qty);

                                }
                            } //End Loop Trips
                            // dd($schedules);
                            // if($selected_order_pump_schedules){
                            //     dd($selected_order_pump_schedules);
                            // }
                            $pump_ids = [];
                            //All resources fulfilled

                            if ((((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null)) && isset($transit_mixer) && isset($batching_plant)) || $shift_end_exit === 1)
                            // if ((count($schedules) && $order->pump && count($selected_order_pump_schedules)) || (!$order->pump  && count($schedules)))
                            {

                                Log::info('abcd order-' . $order->order_no);
                                // dd('all set');
                                
                                $current_order_deviation = 0;
                                $current_order_max_deviation = 0;
                                //Reset schedules
                                    $order_start_time = $loading_start;
                                if ($schedules && count($schedules) > 0)
                                {
                                    // Log::info('abcd scedule count-' . $order->order_no);
                                    $order_start_time = $schedules[0]['loading_start'];
                                    $current_order_deviation = abs(Carbon::parse($order->delivery_date)
                                        ->copy()
                                        ->diffInMinutes(Carbon::parse($schedules[0]['pouring_start']) , false));
                                    $current_order_max_deviation = self::getOrderMaxPossibleDeviation($current_order_deviation);
                                }
                                $orders_copy[$orderKey]['next_loading'] = $order_start_time;
                                $orders_copy[$orderKey]['order_start_time'] = $order_start_time;
                                $orders_copy[$orderKey]['is_scheduled'] = true;
                                $orders_copy[$orderKey]['current_deviation'] = $current_order_deviation;
                                $orders_copy[$orderKey]['current_max_deviation'] = $current_order_max_deviation;
                                $schedules = [];
                                $selected_order_pump_schedules = [];
                                //Update Deviation and AVL
                                $transit_mixer_availability = $transit_mixer_availability_copy;
                                $pump_availability = $pump_availability_copy;
                                $batching_plant_availability = $batching_plant_availability_copy;

                               
                                $avl = 1;
                                break;
                            }
                            else
                            { // All resources not fulfilled (ROLLBACK/ RESET)
                                // Log::info('abcd all resources not fullfilled-' . $order->order_no);
                                $schedules = [];
                                $selected_order_pump_schedules = [];
                                $transit_mixer_availability_copy = $transit_mixer_availability;
                                $pump_availability_copy = $pump_availability;
                                $batching_plant_availability_copy = $batching_plant_availability;
                                $pump_ids = [];

                               
                                if ($sch_adj_time <= 0)
                                {
                                    $avl = 0;
                                    break;
                                }
                            }
                        } //End Forward backward adjustment loop
                        //Order adjustment
                        $sch_adj_time += 1;
                        $delivery_date_n = Carbon::parse($delivery_time)->copy()
                            ->addMinutes($sch_adj_time);
                        $delivery_date_p = Carbon::parse($delivery_time)->copy()
                            ->subMinutes($sch_adj_time);
                            Log::info('DT: '.$delivery_time.'SCHADJ: '.$sch_adj_time.'DDN: '.$delivery_date_n.'DDP'.$delivery_date_p);

                        //Shift crossed or day crossed
                        if ($delivery_date_p->copy()
                            ->lt(Carbon::parse($delivery_time)->copy()
                            ->subMinutes($this->sch_adj_to)) && $delivery_date_n->copy()
                            ->gt(Carbon::parse($delivery_time)->copy()
                            ->addMinutes($this->sch_adj_to)))
                        {
                            $avl = 1;
                            // Log::info('abcd avl update 1-' . $order->order_no . '--avl--' . $avl);
                        }
                        if ($delivery_date_p->copy()
                            ->lt($location_start_time) && $delivery_date_n->copy()
                            ->gt($location_end_time))
                        {
                            // Log::info('abcd avl update 2-' . $order->order_no);
                            if ((($delivery_date_p->copy()
                                ->subMinutes($total_time))->lt($location_start_time)) && (($delivery_date_n->copy()
                                ->subMinutes($total_time))->gt($location_end_time)))
                            {

                                $avl = 1;
                                // Log::info('abcd avl update 2-' . $order->order_no . '--avl--' . $avl);

                            }
                        }
                        if ($avl == 1)
                        {
                            // Log::info('abcd break update 1-' . $order->order_no . '--avl--' . $avl);
                            break;
                        }
                    } //Schedule adjustment based on availability LOOP END
                    if ((isset($transit_mixer) && isset($batching_plant) && ((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null))) || $shift_end_exit === 1)
                    {

                        // Log::info('abcd break update 2-' . $order->order_no);
                        break;
                    }
                } //Location loops end
                Log::info('end ----------------------------------------------------------- '.$avlCounter.'--'.$qtyCounter);
            } //Orders Loop End
            // foreach (array_chunk($bpScheduleGap, 5000) as $gap) {
            //     DB::table('batching_plant_availability')->insert($gap);
            // }
            $orders_copy = array_filter($orders_copy, function ($ord)
            {
                if (isset($ord['is_scheduled']) && $ord['is_scheduled'] == true)
                {
                    return true;
                }
                else
                {
                    return false;
                };
            });
            // dd($orders_copy);
            return $orders_copy;
        }
        catch(\Exception $ex)
        {
            dd($ex);
        }
    }
	
	//initialize old
    public  function initializeSchedule()
    {
        try
        {           
            $this->resetOrders();
            $this->generateRestrictions();

            $this->pumps_availabilty = PumpHelper::getPumpsAvailability($this->company, $this->schedule_date, $this->pump_ids);

            $this->tms_availabilty = TransitMixerHelper::getTrucksAvailability($this->company, $this->schedule_date, $this->transit_mixer_ids);
            $this->min_order_start_time = BatchingPlantHelper::getMinOrderScheduleTimeCopy($this->company, $this->user_id, $this->shift_start, $this->shift_end, $this->schedule_date);
            
            $this->bps_availabilty = BatchingPlantHelper::getBatchingPlantAvailabilityCopy($this->company, $this->schedule_date, $this->batching_plant_ids, $this->min_order_start_time);

            // $schedule_loop = [180, 360, 720, 1440];
            $schedule_loop = [1440,1440,1440];

            $scheduled_orders = [];

            foreach ($schedule_loop as $loop_key => $loop_time)
            {

                $modified_orders = $this->generateSchedule();

                // dd($modified_orders);
                $scheduled_orders = array_merge($scheduled_orders, $modified_orders);

                $availabilities = self::initializeSchedule($user_id, $this->company, $this->schedule_date, $transit_mixer_ids, $pump_ids, $batching_plant_ids, $this->shift_start, $this->shift_end, $interval_deviation, $scheduled_orders, true);
                $pumps_availabilty = $availabilities['pumps_availability'];
                // dd($pumps);
                Log::info('PUMPAVL 1:: :: '.json_encode($pumps_availabilty));
                $tms_availabilty = $availabilities['tms_availability'];
                Log::info('--------TMS 1-----:: :: '.json_encode($tms_availabilty));
                $bps_availabilty = BatchingPlantHelper::generateOrUpdateAvailability($user_id, $this->schedule_date, $this->company, $min_order_start_time, $this->shift_end);
                Log::info('--------BPSAVL 1-----:: :: '.json_encode($bps_availabilty));

                $orders = SelectedOrder::where("group_company_id", $this->company)->where("user_id", $this->user_id)->whereBetween("delivery_date", [$this->shift_start, $this->shift_end])->whereNull("start_time")
                    ->where("selected", true)
                    ->get()
                    ->toArray();

                if ($orders && count($orders) == 0)
                {
                    break;
                }
            }
        }
        catch(\Exception $e)
        {
            dd($e);
        }
    }
}