<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ─────────────────────────────────────────────────────────────────────────────
//  Sheet 1 — Orders summary
// ─────────────────────────────────────────────────────────────────────────────
class ScheduleOrdersSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private $orders, private string $scheduleDate) {}

    public function title(): string { return 'Orders'; }

    public function array(): array
    {
        $rows = [[
            'Schedule Date', 'Order No', 'Customer', 'Site', 'Location',
            'Quantity (m³)', 'Delivered (m³)', 'Status',
            'Start Time', 'End Time',
            'Trips Scheduled', 'CS Score',
            'Mix Code', 'Pump Required', 'Failure Reason',
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
                $order->customer_name  ?? $order->customer  ?? '',
                $order->site_name      ?? $order->site       ?? '',
                $order->location       ?? '',
                $qty,
                $delivered,
                $status,
                $order->start_time ? Carbon::parse($order->start_time)->format('h:i A') : '',
                $order->end_time   ? Carbon::parse($order->end_time)->format('h:i A')   : '',
                $order->schedule ? $order->schedule->count() : 0,
                $order->cs_score ?? '',
                $order->mix_code ?? '',
                $order->pump_qty ?? 0 ? 'Yes' : 'No',
                $order->failure_reason ?? '',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->orders) + 1;

        // Header row
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Zebra rows
        for ($i = 2; $i <= $lastRow; $i++) {
            $color = ($i % 2 === 0) ? 'EBF3FB' : 'FFFFFF';
            $sheet->getStyle("A{$i}:O{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }

        // Colour-code status column (H)
        for ($i = 2; $i <= $lastRow; $i++) {
            $val = $sheet->getCell("H{$i}")->getValue();
            $bg  = match (true) {
                str_starts_with((string)$val, 'Fully')   => 'C6EFCE',
                str_starts_with((string)$val, 'Partial') => 'FFEB9C',
                default                                   => 'FFC7CE',
            };
            $sheet->getStyle("H{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'font' => ['bold' => true],
            ]);
        }

        // All-border
        $sheet->getStyle("A1:O{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B7C3']]],
        ]);

        return [];
    }
}


// ─────────────────────────────────────────────────────────────────────────────
//  Sheet 2 — Trip-level schedule detail
// ─────────────────────────────────────────────────────────────────────────────
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
            'Truck', 'Truck Capacity (m³)', 'Batching Plant', 'Qty (m³)',
            'Loading Start',  'Loading End',   'Loading (mins)',
            'QC Start',       'QC End',         'QC (mins)',
            'Travel Start',   'Travel End',     'Travel (mins)',
            'Insp Start',     'Insp End',       'Insp (mins)',
            'Pouring Start',  'Pouring End',    'Pouring (mins)',
            'Cleaning Start', 'Cleaning End',   'Cleaning (mins)',
            'Return Start',   'Return End',     'Return (mins)',
            'Waiting Start',  'Waiting End',    'Waiting (mins)',
        ]];

        foreach ($this->trips as $t) {
            $rows[] = [
                $this->scheduleDate,
                $t->order_no,
                $t->trip,
                $t->location,
                $t->transit_mixer,
                $t->truck_capacity,
                $t->batching_plant,
                $t->batching_qty,
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
        $lastRow  = $this->trips->count() + 1;
        $lastCol  = 'AF';

        // Header
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Phase group colours on header (light tints)
        $groups = [
            'I1:K1' => 'BDD7EE',  // Loading  – light blue
            'L1:N1' => 'FCE4D6',  // QC       – light orange
            'O1:Q1' => 'E2EFDA',  // Travel   – light green
            'R1:T1' => 'FFF2CC',  // Insp     – light yellow
            'U1:W1' => 'D9E1F2',  // Pouring  – light purple
            'X1:Z1' => 'DDEBF7',  // Cleaning – light teal
            'AA1:AC1' => 'E2EFDA', // Return
            'AD1:AF1' => 'F2F2F2', // Waiting
        ];
        foreach ($groups as $range => $color) {
            $sheet->getStyle($range)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            ]);
        }

        // Zebra rows
        for ($i = 2; $i <= $lastRow; $i++) {
            $color = ($i % 2 === 0) ? 'F5F9FF' : 'FFFFFF';
            $sheet->getStyle("A{$i}:{$lastCol}{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }

        // Borders
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B7C3']]],
        ]);

        return [];
    }
}


// ─────────────────────────────────────────────────────────────────────────────
//  Sheet 3 — Pump schedule detail
// ─────────────────────────────────────────────────────────────────────────────
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
                $p->order_no,
                $p->trip,
                $p->location,
                $p->pump,
                $p->type,
                $p->pump_capacity,
                $p->batching_qty,
                self::fmt($p->qc_start),      self::fmt($p->qc_end),
                self::fmt($p->travel_start),  self::fmt($p->travel_end),
                self::fmt($p->insp_start),    self::fmt($p->insp_end),
                self::fmt($p->install_start), self::fmt($p->install_end),
                self::fmt($p->pouring_start), self::fmt($p->pouring_end),
                self::fmt($p->cleaning_start),self::fmt($p->cleaning_end),
                self::fmt($p->return_start),  self::fmt($p->return_end),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->pumpTrips->count() + 1;

        $sheet->getStyle('A1:V1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7030A0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        for ($i = 2; $i <= $lastRow; $i++) {
            $color = ($i % 2 === 0) ? 'F5EEF8' : 'FFFFFF';
            $sheet->getStyle("A{$i}:V{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }

        $sheet->getStyle("A1:V{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B7C3']]],
        ]);

        return [];
    }
}