<?php

namespace App\Http\Controllers;

use App\Models\BatchingPlant;
use App\Models\OrderPumpSchedule;
use App\Models\OrderSchedule;
use App\Models\Pump;
use App\Models\TransitMixer;
use Carbon\Carbon;
use Flasher\Laravel\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {

        $batchingDetails = OrderSchedule::select('location', DB::raw('COUNT(*) as total_orders'))
            ->groupBy('location')
            ->get();

        foreach ($batchingDetails as  &$unit) {
            $batchingPlants = BatchingPlant::whereHas('company_location', function ($query) use ($unit) {
                $query->where('location', $unit->location);
            })->get();
            $unit->plants = $batchingPlants;

            $cuttrentUnitUtilization = 0;
            foreach ($unit->plants as $unitPlant) {
                $start_time = Carbon::parse(
                    $unitPlant->Batching_plant_occupancy?->start_time,
                );
                $end_time = Carbon::parse(
                    $unitPlant->Batching_plant_occupancy?->end_time,
                );
                $diffInMinutes =
                    $start_time && $end_time
                    ? $start_time->diffInMinutes($end_time)
                    : 0;

                $occupancy =
                    $diffInMinutes > 0
                    ? ($unitPlant->Batching_plant_occupancy
                        ?->occupied /
                        $diffInMinutes) *
                    100
                    : 0;
                $cuttrentUnitUtilization += $occupancy;
            }
            $cuttrentUnitUtilization = $cuttrentUnitUtilization / (count($batchingPlants) ? count($batchingPlants) : 2);

            $unit->totalOcupancy = number_format($cuttrentUnitUtilization, 2);
        }

        $transitMixerDetails = OrderSchedule::select(
            'transit_mixer_id',
            'loading_start',
            'loading_time',
            'qc_start',
            'qc_time',
            'insp_start',
            'insp_time',
            'pouring_start',
            'cleaning_start',
            'return_start',
            'deviation',
            DB::raw('COUNT(*) as total_orders')
        )
            ->whereNotNull('transit_mixer_id')
            ->groupBy(
                'transit_mixer_id',
                'loading_start',
                'loading_time',
                'qc_start',
                'qc_time',
                'insp_start',
                'insp_time',
                'pouring_start',
                'cleaning_start',
                'return_start',
                'deviation'
            )->get();
        // dd($transitMixerDetails);

        foreach ($transitMixerDetails as  &$tranit) {
            $transitMixeres = TransitMixer::where('id', $tranit->transit_mixer_id)->get();
            $tranit->transit_mixers = $transitMixeres;
        }


        $pumpDetails = OrderPumpSchedule::select(
            'pump_id',
            'travel_start',
            'travel_time',
            'insp_start',
            'insp_time',
            DB::raw('COUNT(*) as total_orders')
        )
            ->whereNotNull('pump_id')
            ->groupBy(
                'pump_id',

                'travel_start',
                'travel_time',
                'insp_start',
                'insp_time',
            )->get();

        foreach ($pumpDetails as  &$pump) {
            $pumpItems = Pump::where('id', $pump->pump_id)->get();
            // dd($pump->pump_mixers );
            $pump->pump_mixers = $pumpItems;
        }

        return view('components.settings.report.resource_index', [
            'batchingDetails' => $batchingDetails,
            'transitMixerDetails' => $transitMixerDetails,
            'pumpDetails' => $pumpDetails,
        ]);
    }


    public function batchingDetail()
    {

        return view(
            'components.settings.report.resources-batching-plantdetails'
        );
    }
}
