<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Test;
use App\Models\TestCategory;
use App\Models\LaboratoryRequest;

class CensusReportController extends Controller
{
        public function getCensusData(Request $request)
    {
        $startDate = new Carbon($request->input('start_date', Carbon::now()->subMonth()->startOfDay()));
        $endDate = new Carbon($request->input('end_date', Carbon::now()->endOfDay()));

        // 1. Total N
        $totalAccessionsQuery = LaboratoryRequest::whereBetween('created_at', [$startDate, $endDate]);
        $totalAccessions = (clone $totalAccessionsQuery)->count();
        $previousPeriodStartDate = (new Carbon($startDate))->subMonth();
        $previousPeriodEndDate = (new Carbon($endDate))->subMonth();
        $previousTotalAccessions = LaboratoryRequest::whereBetween('created_at', [$previousPeriodStartDate, $previousPeriodEndDate])->count();
        $percentageChange = $previousTotalAccessions > 0 ? (($totalAccessions - $previousTotalAccessions) / $previousTotalAccessions) * 100 : 0;

        $totalAccessionsData = [
            'value' => $totalAccessions,
            'percentage_change' => round($percentageChange, 2),
        ];

        // 2. Send-Out Percentage
        $sendOutQuery = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->whereNotNull('lrt.sendout_at')
            ->whereBetween('lr.created_at', [$startDate, $endDate]);
        $sendOutCount = (clone $sendOutQuery)->count();
        $sendOutRate = $totalAccessions > 0 ? ($sendOutCount / $totalAccessions) * 100 : 0;

        $sendOutRateData = [
            'value' => round($sendOutRate, 2),
            'samples' => $sendOutCount,
        ];

        // 3. Processed By Lab
        $processedByLabQuery = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->whereBetween('lr.created_at', [$startDate, $endDate])
            ->whereNull('lrt.sendout_at');
        $processedByLabCount = (clone $processedByLabQuery)->count();

        $previousProcessedByLabCount = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->whereBetween('lr.created_at', [$previousPeriodStartDate, $previousPeriodEndDate])
            ->whereNull('lrt.sendout_at')
            ->count();

        $processedByLabPercentageChange = $previousProcessedByLabCount > 0 ? (($processedByLabCount - $previousProcessedByLabCount) / $previousProcessedByLabCount) * 100 : 0;

        $processedByLabData = [
            'value' => $processedByLabCount,
            'percentage_change' => round($processedByLabPercentageChange, 2),
        ];

        $rerunsCount = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->whereBetween('lr.created_at', [$startDate, $endDate])
            ->where('lrt.remarks', 'RERUN')
            ->count();

        $totalSamplesProcessed = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->whereBetween('lr.created_at', [$startDate, $endDate])
            ->count();

        $rerunPercentage = $totalSamplesProcessed > 0 ? ($rerunsCount / $totalSamplesProcessed) * 100 : 0;

        $rerunsData = [
            'value' => $rerunsCount,
            'percentage' => round($rerunPercentage, 2),
        ];

        // 4. Monthly Test Trends
        $monthlyTestTrends = $this->getMonthlyTestTrends($startDate, $endDate, $request->input('test_category'));

        // 5. Detailed Table
        $detailedTable = $this->getDetailedTableData($request, $startDate, $endDate);

        // 6. Comparison Data
        $comparisonData = $this->getComparisonData($startDate, $endDate);

        return response()->json([
            'total_accessions' => $totalAccessionsData,
            'send_out_rate' => $sendOutRateData,
            'processed_by_lab' => $processedByLabData,
            'reruns' => $rerunsData,
            'monthly_test_trends' => $monthlyTestTrends,
            'detailed_table' => $detailedTable,
            'test_categories' => TestCategory::select('name', 'color')->get(),
            'comparison_data' => $comparisonData,
        ]);
    }

        private function generateSeries($query, $startDate, $endDate, $aggregateType = 'count', $dateColumn = 'created_at')
    {
        $series = [];
        $current = new Carbon($startDate);
        $end = new Carbon($endDate);

        while ($current <= $end) {
            $date = $current->toDateString();
            $value = (clone $query)->whereDate($dateColumn, $date)->$aggregateType('*');
            $series[] = ['x' => $date, 'y' => $value];
            $current->addDay();
        }

        return $series;
    }

    private function getMonthlyTestTrends($startDate, $endDate, $testCategory = null)
    {
        $query = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->join('tests as t', 'lrt.test_id', '=', 't.id')
            ->join('test_categories as tc', 't.test_category_id', '=', 'tc.id')
            ->select('tc.name as category', 'tc.color', DB::raw('count(lrt.test_id) as test_count'))
            ->whereBetween('lr.created_at', [$startDate, $endDate])
            ->groupBy('tc.name', 'tc.color')
            ->orderByDesc('test_count');

        if ($testCategory) {
            $query->where('tc.name', $testCategory);
        }

        $topCategories = $query->limit(5)->get();

        $series = [];
        $categories = [];

        $current = new Carbon($startDate);
        while ($current <= $endDate) {
            $categories[] = $current->format('M d');
            $current->addDay();
        }

        foreach ($topCategories as $category) {
            $data = [];
            $current = new Carbon($startDate);
            while ($current <= $endDate) {
                $count = DB::table('laboratory_request_test as lrt')
                    ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
                    ->join('tests as t', 'lrt.test_id', '=', 't.id')
                    ->join('test_categories as tc', 't.test_category_id', '=', 'tc.id')
                    ->where('tc.name', $category->category)
                    ->whereDate('lr.created_at', $current->toDateString())
                    ->count();
                $data[] = $count;
                $current->addDay();
            }
            $series[] = [
                'name' => $category->category, 
                'data' => $data,
                'color' => $category->color ?? '#909399'
            ];
        }

        return ['series' => $series, 'categories' => $categories];
    }

    private function getDetailedTableData(Request $request, $startDate, $endDate)
    {
        $testCategory = $request->input('test_category');

        $daysInMonth = $startDate->daysInMonth;

        $query = Test::query();

        if ($testCategory) {
            $query->whereHas('category', function ($q) use ($testCategory) {
                $q->where('name', $testCategory);
            });
        }

        $tests = $query->get();

        $tableData = [];
        foreach ($tests as $test) {
            $rowData = ['test_name' => $test->name];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $startDate->copy()->addDays($day - 1)->toDateString();
                $count = DB::table('laboratory_request_test as lrt')
                    ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
                    ->where('lrt.test_id', $test->id)
                    ->whereDate('lr.created_at', $date)
                    ->count();
                $rowData["day_{$day}"] = $count;
            }
            $tableData[] = $rowData;
        }

        $test_ids = $tests->pluck('id');

        $processed_by_lab = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->whereIn('lrt.test_id', $test_ids)
            ->whereBetween('lr.created_at', [$startDate, $endDate])
            ->whereNull('lrt.sendout_at')
            ->count();

        $send_out = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->whereIn('lrt.test_id', $test_ids)
            ->whereBetween('lr.created_at', [$startDate, $endDate])
            ->whereNotNull('lrt.sendout_at')
            ->count();

        $reruns_in_table = DB::table('laboratory_request_test as lrt')
            ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
            ->whereIn('lrt.test_id', $test_ids)
            ->whereBetween('lr.created_at', [$startDate, $endDate])
            ->where('lrt.remarks', 'RERUN')
            ->count();

        $summary = [
            'processed_by_lab' => $processed_by_lab,
            'send_out' => $send_out,
            'wastage' => 0, // Placeholder
            'reruns' => $reruns_in_table,
        ];

        return [
            'headers' => array_map(function ($day) {
                return ['prop' => "day_{$day}", 'label' => $day];
            }, range(1, $daysInMonth)),
            'data' => $tableData,
            'summary' => $summary,
            'month' => $startDate->format('F Y'),
        ];
    }

    private function getComparisonData($startDate, $endDate)
    {
        $labels = [];
        $processed = [];
        $sendOut = [];

        $current = new Carbon($startDate);
        while ($current <= $endDate) {
            $date = $current->toDateString();
            $labels[] = $current->format('M d');

            $processedCount = DB::table('laboratory_request_test as lrt')
                ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
                ->whereNull('lrt.sendout_at')
                ->whereDate('lr.created_at', $date)
                ->count();
            $processed[] = $processedCount;

            $sendOutCount = DB::table('laboratory_request_test as lrt')
                ->join('laboratory_requests as lr', 'lrt.laboratory_request_id', '=', 'lr.id')
                ->whereNotNull('lrt.sendout_at')
                ->whereDate('lr.created_at', $date)
                ->count();
            $sendOut[] = $sendOutCount;

            $current->addDay();
        }

        return [
            'labels' => $labels,
            'processed' => $processed,
            'sendOut' => $sendOut,
        ];
    }
}