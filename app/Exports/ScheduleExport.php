<?php

namespace App\Exports;

use App\Helpers\GroupCompanyHelper;
use App\Models\SelectedOrder;
use App\Models\SelectedOrderSchedule;
use App\Models\SelectedOrderPumpSchedule;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// =============================================================================
//  ScheduleExport  —  all three sheet classes live in this ONE file so that
//  Laravel's autoloader can find them without extra config.
// =============================================================================

class ScheduleExport implements WithMultipleSheets
{
    protected array $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function sheets(): array
    {
        ['company_id' => $companyId, 'schedule_date' => $scheduleDate, 'user_id' => $userId] = $this->params;

        $shifts     = GroupCompanyHelper::getShiftTime($companyId, $scheduleDate);
        $shiftStart = $shifts['start_time'];
        $shiftEnd   = $shifts['end_time'];

        $orders = SelectedOrder::with(['schedule', 'pump_schedule'])
            ->where('group_company_id', $companyId)
            ->where('user_id', $userId)
            ->where('selected', true)
            ->whereBetween('delivery_date', [$shiftStart, $shiftEnd])
            ->orderBy('start_time', 'ASC')
            ->orderBy('priority', 'ASC')
            ->orderBy('quantity', 'DESC')
            ->get();

        $trips = SelectedOrderSchedule::join('transit_mixers', function ($q) {
            $q->on('transit_mixers.truck_name', '=', 'selected_order_schedules.transit_mixer');
        })
            ->select(
                'selected_order_schedules.order_no',
                'selected_order_schedules.location',
                'selected_order_schedules.trip',
                'selected_order_schedules.batching_plant',
                'selected_order_schedules.batching_qty',
                'transit_mixers.truck_capacity',
                'selected_order_schedules.transit_mixer',
                'selected_order_schedules.loading_time',
                'selected_order_schedules.loading_start',
                'selected_order_schedules.loading_end',
                'selected_order_schedules.qc_time',
                'selected_order_schedules.qc_start',
                'selected_order_schedules.qc_end',
                'selected_order_schedules.travel_time',
                'selected_order_schedules.travel_start',
                'selected_order_schedules.travel_end',
                'selected_order_schedules.insp_time',
                'selected_order_schedules.insp_start',
                'selected_order_schedules.insp_end',
                'selected_order_schedules.pouring_time',
                'selected_order_schedules.pouring_start',
                'selected_order_schedules.pouring_end',
                'selected_order_schedules.cleaning_time',
                'selected_order_schedules.cleaning_start',
                'selected_order_schedules.cleaning_end',
                'selected_order_schedules.return_time',
                'selected_order_schedules.return_start',
                'selected_order_schedules.return_end',
                'selected_order_schedules.waiting_time',
                'selected_order_schedules.waiting_start',
                'selected_order_schedules.waiting_end',
            )
            ->where('selected_order_schedules.group_company_id', $companyId)
            ->where('selected_order_schedules.user_id', $userId)
            ->whereBetween('selected_order_schedules.loading_start', [$shiftStart, $shiftEnd])
            ->orderBy('selected_order_schedules.loading_start')
            ->get();

        $pumpTrips = SelectedOrderPumpSchedule::join('pumps', function ($q) {
            $q->on('pumps.pump_name', '=', 'selected_order_pump_schedules.pump');
        })
            ->select(
                'selected_order_pump_schedules.order_no',
                'selected_order_pump_schedules.location',
                'selected_order_pump_schedules.pump',
                'pumps.type',
                'pumps.pump_capacity',
                'selected_order_pump_schedules.trip',
                'selected_order_pump_schedules.batching_qty',
                'selected_order_pump_schedules.qc_start',
                'selected_order_pump_schedules.qc_end',
                'selected_order_pump_schedules.travel_start',
                'selected_order_pump_schedules.travel_end',
                'selected_order_pump_schedules.insp_start',
                'selected_order_pump_schedules.insp_end',
                'selected_order_pump_schedules.install_start',
                'selected_order_pump_schedules.install_end',
                'selected_order_pump_schedules.pouring_start',
                'selected_order_pump_schedules.pouring_end',
                'selected_order_pump_schedules.cleaning_start',
                'selected_order_pump_schedules.cleaning_end',
                'selected_order_pump_schedules.return_start',
                'selected_order_pump_schedules.return_end',
            )
            ->where('selected_order_pump_schedules.group_company_id', $companyId)
            ->where('selected_order_pump_schedules.user_id', $userId)
            ->whereBetween('selected_order_pump_schedules.qc_start', [$shiftStart, $shiftEnd])
            ->orderBy('selected_order_pump_schedules.pouring_start')
            ->get();

        return [
            new ScheduleResourcesSheet($trips, $pumpTrips, $orders, $scheduleDate, $shiftStart, $shiftEnd),
            new ScheduleOrdersSheet($orders, $scheduleDate),
            new ScheduleTripsSheet($trips, $scheduleDate),
            new SchedulePumpsSheet($pumpTrips, $scheduleDate),
        ];
    }
}


// =============================================================================
//  Sheet 1 — Orders summary
// =============================================================================
class ScheduleOrdersSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private $orders, private string $scheduleDate) {}

    public function title(): string { return 'Orders'; }

    public function array(): array
    {
        $rows = [[
            'Schedule Date', 'Order No', 'Customer', 'Site', 'Location',
            'Quantity (m³)', 'Delivered (m³)', 'Status',
            'Start Time', 'End Time', 'Trips Scheduled',
            'CS Score', 'Mix Code', 'Pump Required', 'Failure Reason',
        ]];

        foreach ($this->orders as $order) {
            $delivered = (int) ($order->delivered_quantity ?? 0);
            $qty       = (int) ($order->quantity ?? 0);

            if ($delivered >= $qty && $qty > 0) {
                $status = 'Fully Scheduled';
            } elseif ($delivered > 0) {
                $status = "Partial ({$delivered}/{$qty} m³)";
            } else {
                $status = empty($order->failure_reason) ? 'Not Scheduled' : 'Failed';
            }

            $rows[] = [
                $this->scheduleDate,
                $order->order_no,
                $order->customer_name ?? $order->customer ?? '',
                $order->site_name     ?? $order->site      ?? '',
                $order->location      ?? '',
                $qty,
                $delivered,
                $status,
                $order->start_time ? Carbon::parse($order->start_time)->format('h:i A') : '',
                $order->end_time   ? Carbon::parse($order->end_time)->format('h:i A')   : '',
                $order->schedule   ? $order->schedule->count() : 0,
                $order->cs_score       ?? '',
                $order->mix_code       ?? '',
                ($order->pump_qty ?? 0) ? 'Yes' : 'No',
                $order->failure_reason ?? '',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->orders) + 1;

        $sheet->getStyle('A1:O1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        for ($i = 2; $i <= $lastRow; $i++) {
            $color = ($i % 2 === 0) ? 'EBF3FB' : 'FFFFFF';
            $sheet->getStyle("A{$i}:O{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }

        for ($i = 2; $i <= $lastRow; $i++) {
            $val = $sheet->getCell("H{$i}")->getValue();
            $bg  = match (true) {
                str_starts_with((string) $val, 'Fully')   => 'C6EFCE',
                str_starts_with((string) $val, 'Partial') => 'FFEB9C',
                default                                    => 'FFC7CE',
            };
            $sheet->getStyle("H{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'font' => ['bold' => true],
            ]);
        }

        $sheet->getStyle("A1:O{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => 'B0B7C3'],
            ]],
        ]);

        return [];
    }
}


// =============================================================================
//  Sheet 2 — Trip-level schedule detail
// =============================================================================
class ScheduleTripsSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    private static function fmt(?string $dt): string
    {
        return $dt ? Carbon::parse($dt)->format('h:i A') : '';
    }

    public function __construct(private $trips, private string $scheduleDate) {}

    public function title(): string { return 'Trip Schedule'; }

    public function array(): array
    {
        $rows = [[
            'Schedule Date', 'Order No', 'Trip', 'Location',
            'Truck', 'Truck Cap (m³)', 'Batching Plant', 'Qty (m³)',
            'Loading Start',  'Loading End',   'Loading (m)',
            'QC Start',       'QC End',        'QC (m)',
            'Travel Start',   'Travel End',    'Travel (m)',
            'Insp Start',     'Insp End',      'Insp (m)',
            'Pouring Start',  'Pouring End',   'Pouring (m)',
            'Cleaning Start', 'Cleaning End',  'Cleaning (m)',
            'Return Start',   'Return End',    'Return (m)',
            'Waiting Start',  'Waiting End',   'Waiting (m)',
        ]];

        foreach ($this->trips as $t) {
            $rows[] = [
                $this->scheduleDate,
                $t->order_no,   $t->trip,       $t->location,
                $t->transit_mixer, $t->truck_capacity, $t->batching_plant, $t->batching_qty,
                self::fmt($t->loading_start),  self::fmt($t->loading_end),  $t->loading_time,
                self::fmt($t->qc_start),       self::fmt($t->qc_end),       $t->qc_time,
                self::fmt($t->travel_start),   self::fmt($t->travel_end),   $t->travel_time,
                self::fmt($t->insp_start),     self::fmt($t->insp_end),     $t->insp_time,
                self::fmt($t->pouring_start),  self::fmt($t->pouring_end),  $t->pouring_time,
                self::fmt($t->cleaning_start), self::fmt($t->cleaning_end), $t->cleaning_time,
                self::fmt($t->return_start),   self::fmt($t->return_end),   $t->return_time,
                self::fmt($t->waiting_start),  self::fmt($t->waiting_end),  $t->waiting_time,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->trips->count() + 1;
        $lastCol = 'AF';

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        foreach ([
            'I1:K1' => 'BDD7EE', 'L1:N1' => 'FCE4D6', 'O1:Q1' => 'E2EFDA',
            'R1:T1' => 'FFF2CC', 'U1:W1' => 'D9E1F2', 'X1:Z1' => 'DDEBF7',
            'AA1:AC1' => 'E2EFDA', 'AD1:AF1' => 'F2F2F2',
        ] as $range => $color) {
            $sheet->getStyle($range)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            ]);
        }

        for ($i = 2; $i <= $lastRow; $i++) {
            $color = ($i % 2 === 0) ? 'F5F9FF' : 'FFFFFF';
            $sheet->getStyle("A{$i}:{$lastCol}{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }

        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => 'B0B7C3'],
            ]],
        ]);

        return [];
    }
}


// =============================================================================
//  Sheet 3 — Pump schedule detail
// =============================================================================
class SchedulePumpsSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    private static function fmt(?string $dt): string
    {
        return $dt ? Carbon::parse($dt)->format('h:i A') : '';
    }

    public function __construct(private $pumpTrips, private string $scheduleDate) {}

    public function title(): string { return 'Pump Schedule'; }

    public function array(): array
    {
        $rows = [[
            'Schedule Date', 'Order No', 'Trip', 'Location',
            'Pump', 'Type', 'Capacity (m³)', 'Qty (m³)',
            'QC Start',       'QC End',
            'Travel Start',   'Travel End',
            'Insp Start',     'Insp End',
            'Install Start',  'Install End',
            'Pouring Start',  'Pouring End',
            'Cleaning Start', 'Cleaning End',
            'Return Start',   'Return End',
        ]];

        foreach ($this->pumpTrips as $p) {
            $rows[] = [
                $this->scheduleDate,
                $p->order_no, $p->trip, $p->location,
                $p->pump, $p->type, $p->pump_capacity, $p->batching_qty,
                self::fmt($p->qc_start),       self::fmt($p->qc_end),
                self::fmt($p->travel_start),   self::fmt($p->travel_end),
                self::fmt($p->insp_start),     self::fmt($p->insp_end),
                self::fmt($p->install_start),  self::fmt($p->install_end),
                self::fmt($p->pouring_start),  self::fmt($p->pouring_end),
                self::fmt($p->cleaning_start), self::fmt($p->cleaning_end),
                self::fmt($p->return_start),   self::fmt($p->return_end),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->pumpTrips->count() + 1;

        $sheet->getStyle('A1:V1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7030A0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        for ($i = 2; $i <= $lastRow; $i++) {
            $color = ($i % 2 === 0) ? 'F5EEF8' : 'FFFFFF';
            $sheet->getStyle("A{$i}:V{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }

        $sheet->getStyle("A1:V{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => 'B0B7C3'],
            ]],
        ]);

        return [];
    }
}


// =============================================================================
//  Sheet 0 — Resource Usage Summary
//  Shows how heavily each batching plant, truck, and pump was used today.
//  All numbers are derived from the already-fetched trips / pumpTrips collections
//  — no extra DB queries.
// =============================================================================
class ScheduleResourcesSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private $trips,
        private $pumpTrips,
        private $orders,
        private string $scheduleDate,
        private string $shiftStart,
        private string $shiftEnd,
    ) {}

    public function title(): string { return 'Resource Usage'; }

    public function array(): array
    {
        $rows = [];

        // ── Shift header ──────────────────────────────────────────────────────
        $rows[] = ['SCHEDULE DATE', $this->scheduleDate, '', '', '', '', '', ''];
        $rows[] = [
            'SHIFT',
            Carbon::parse($this->shiftStart)->format('h:i A') . '  →  ' . Carbon::parse($this->shiftEnd)->format('h:i A'),
            '', '', '', '', '', ''
        ];
        $rows[] = ['', '', '', '', '', '', '', ''];   // blank spacer

        // ── Day totals banner ─────────────────────────────────────────────────
        $totalOrders    = $this->orders->count();
        $scheduledCount = $this->orders->filter(fn($o) => ($o->delivered_quantity ?? 0) > 0)->count();
        $failedCount    = $totalOrders - $scheduledCount;
        $totalQty       = $this->orders->sum('quantity');
        $deliveredQty   = $this->orders->sum('delivered_quantity');
        $totalTrips     = $this->trips->count();

        $rows[] = ['OVERVIEW',           '',               '', '', '', '', '', ''];
        $rows[] = ['Total Orders',        $totalOrders,     '', '', '', '', '', ''];
        $rows[] = ['Scheduled Orders',    $scheduledCount,  '', '', '', '', '', ''];
        $rows[] = ['Failed / Unscheduled',$failedCount,     '', '', '', '', '', ''];
        $rows[] = ['Total Quantity (m³)', $totalQty,        '', '', '', '', '', ''];
        $rows[] = ['Delivered Qty (m³)',  $deliveredQty,    '', '', '', '', '', ''];
        $rows[] = ['Total Trips',         $totalTrips,      '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', ''];   // spacer

        // ── Batching Plant usage ──────────────────────────────────────────────
        $rows[] = ['BATCHING PLANT USAGE', '', '', '', '', '', '', ''];
        $rows[] = [
            'Plant', 'Trips', 'Total Qty Loaded (m³)',
            'First Load', 'Last Load End',
            'Busy Time (mins)', 'Orders Served', '',
        ];

        $byPlant = $this->trips->groupBy('batching_plant');
        foreach ($byPlant as $plant => $plantTrips) {
            $firstLoad  = $plantTrips->min('loading_start');
            $lastEnd    = $plantTrips->max('loading_end');
            $busyMins   = $plantTrips->sum('loading_time');
            $orders     = $plantTrips->pluck('order_no')->unique()->count();

            $rows[] = [
                $plant,
                $plantTrips->count(),
                $plantTrips->sum('batching_qty'),
                $firstLoad ? Carbon::parse($firstLoad)->format('h:i A') : '',
                $lastEnd   ? Carbon::parse($lastEnd)->format('h:i A')   : '',
                $busyMins,
                $orders,
                '',
            ];
        }
        $rows[] = ['', '', '', '', '', '', '', ''];   // spacer

        // ── Transit Mixer usage ───────────────────────────────────────────────
        $rows[] = ['TRANSIT MIXER (TRUCK) USAGE', '', '', '', '', '', '', ''];
        $rows[] = [
            'Truck', 'Capacity (m³)', 'Trips Made',
            'Total Qty Carried (m³)', 'First Departure', 'Last Return',
            'Total On-Road Time (mins)', 'Orders Served',
        ];

        $byTruck = $this->trips->groupBy('transit_mixer');
        foreach ($byTruck as $truck => $truckTrips) {
            $firstDep  = $truckTrips->min('loading_start');
            $lastRet   = $truckTrips->max('return_end');
            $onRoad    = $truckTrips->sum(function ($t) {
                $ls = Carbon::parse($t->loading_start);
                $re = $t->return_end ? Carbon::parse($t->return_end) : null;
                return $re ? $ls->diffInMinutes($re) : 0;
            });
            $cap    = $truckTrips->first()->truck_capacity ?? '';
            $orders = $truckTrips->pluck('order_no')->unique()->count();

            $rows[] = [
                $truck,
                $cap,
                $truckTrips->count(),
                $truckTrips->sum('batching_qty'),
                $firstDep ? Carbon::parse($firstDep)->format('h:i A') : '',
                $lastRet  ? Carbon::parse($lastRet)->format('h:i A')  : '',
                $onRoad,
                $orders,
            ];
        }
        $rows[] = ['', '', '', '', '', '', '', ''];   // spacer

        // ── Pump usage ────────────────────────────────────────────────────────
        if ($this->pumpTrips->count() > 0) {
            $rows[] = ['PUMP USAGE', '', '', '', '', '', '', ''];
            $rows[] = [
                'Pump', 'Type', 'Capacity (m³)', 'Trips',
                'First Deployment', 'Last Return',
                'Total Active Time (mins)', 'Orders Served',
            ];

            $byPump = $this->pumpTrips->groupBy('pump');
            foreach ($byPump as $pump => $pumpRows) {
                $firstDep = $pumpRows->min('qc_start');
                $lastRet  = $pumpRows->max('return_end');
                $active   = $pumpRows->sum(function ($p) {
                    $start = $p->qc_start  ? Carbon::parse($p->qc_start)  : null;
                    $end   = $p->return_end ? Carbon::parse($p->return_end) : null;
                    return ($start && $end) ? $start->diffInMinutes($end) : 0;
                });
                $first  = $pumpRows->first();
                $orders = $pumpRows->pluck('order_no')->unique()->count();

                $rows[] = [
                    $pump,
                    $first->type          ?? '',
                    $first->pump_capacity ?? '',
                    $pumpRows->count(),
                    $firstDep ? Carbon::parse($firstDep)->format('h:i A') : '',
                    $lastRet  ? Carbon::parse($lastRet)->format('h:i A')  : '',
                    $active,
                    $orders,
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sectionHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
        ];
        $colHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $overviewKeyStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E2F3']],
        ];

        // Rows 1-2: shift info
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F4E78']],
        ]);
        $sheet->getStyle('A2:B2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '595959']],
        ]);

        // Walk every row and style section headers + column headers
        $maxRow = $sheet->getHighestRow();
        $sectionKeywords = ['OVERVIEW', 'BATCHING PLANT USAGE', 'TRANSIT MIXER', 'PUMP USAGE'];
        $colHeaderPrev   = ['Plant', 'Truck', 'Pump'];

        for ($r = 1; $r <= $maxRow; $r++) {
            $cellA = (string) $sheet->getCell("A{$r}")->getValue();

            // Section headers (navy background)
            foreach ($sectionKeywords as $kw) {
                if (str_starts_with($cellA, $kw)) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray($sectionHeaderStyle);
                    break;
                }
            }

            // Column headers (blue background) — rows that start with resource-name columns
            foreach ($colHeaderPrev as $kw) {
                if ($cellA === $kw) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray($colHeaderStyle);
                    break;
                }
            }

            // Overview key-value rows
            if (in_array($cellA, [
                'Total Orders', 'Scheduled Orders', 'Failed / Unscheduled',
                'Total Quantity (m³)', 'Delivered Qty (m³)', 'Total Trips',
            ])) {
                $sheet->getStyle("A{$r}:B{$r}")->applyFromArray($overviewKeyStyle);
                $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        }

        // Light border around whole used range
        $lastCol = 'H';
        $sheet->getStyle("A1:{$lastCol}{$maxRow}")->applyFromArray([
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => 'D0D7E3'],
            ]],
        ]);

        return [];
    }
}