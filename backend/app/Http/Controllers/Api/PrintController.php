<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaboratoryRequest;
use App\Services\PrintService;
use Barryvdh\DomPDF\Facade\Pdf;

class PrintController extends Controller
{
    protected $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
    }

    /**
     * Generate a PDF with results grouped by report_group_id.
     * Each group renders on a separate page using its layout.
     */
    public function generateGroupedPdf($laboratoryRequestId)
    {
        $request = LaboratoryRequest::with('patient')
            ->findOrFail($laboratoryRequestId);

        // Group results by report_group_id
        $groups = $this->printService->groupResultsByReportGroup($request);

        // Log groups for debugging
        \Log::info('Grouped results:', $groups);

        // If no groups, return error
        if (empty($groups)) {
            return response()->json(['error' => 'No results found'], 404);
        }

        // Build HTML for each group, add page breaks
        $htmlParts = [];
        foreach ($groups as $group) {
            $html = $this->printService->renderGroupHtml($group, $request);
            $htmlParts[] = $html;
            \Log::info('Group HTML:', ['group_id' => $group['group_id'], 'html_length' => strlen($html)]);
        }

        // Combine all pages with page breaks
        $finalHtml = implode('<div style="page-break-after: always;"></div>', $htmlParts);
        
        \Log::info('Final HTML length: ' . strlen($finalHtml));
        \Log::info('Final HTML snippet: ' . substr($finalHtml, 0, 500));

        try {
            // Determine paper size and orientation from the first group's layout (if present)
            $paperSize = 'A4';
            $orientation = 'portrait';
            if (!empty($groups) && isset($groups[0]['layout']) && $groups[0]['layout']) {
                $firstLayout = $groups[0]['layout'];
                $pageConfig = $firstLayout->page_config ?? null;
                if (is_string($pageConfig)) {
                    $decodedCfg = json_decode($pageConfig, true);
                    $pageConfig = $decodedCfg ?? $pageConfig;
                }

                if (is_array($pageConfig)) {
                    $paperSize = $pageConfig['page_size'] ?? ($firstLayout->page_size ?? $paperSize);
                    $orientation = $pageConfig['orientation'] ?? $orientation;
                } else {
                    $paperSize = $firstLayout->page_size ?? $paperSize;
                }
            }

            // Generate PDF using dompdf
            $pdf = Pdf::loadHTML($finalHtml)
                ->setPaper($paperSize, $orientation);

            $fileName = 'Results_' . $request->id . '_' . now()->format('Ymd_His') . '.pdf';

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Log::error('PDF generation error: ' . $e->getMessage());
            return response()->json(['error' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get grouped results data (for preview or frontend).
     */
    public function getGroupedResults($laboratoryRequestId)
    {
        $request = LaboratoryRequest::with('patient')
            ->findOrFail($laboratoryRequestId);

        $groups = $this->printService->groupResultsByReportGroup($request);

        return response()->json([
            'laboratory_request_id' => $request->id,
            'patient' => $request->patient,
            'groups' => $groups
        ]);
    }
}
