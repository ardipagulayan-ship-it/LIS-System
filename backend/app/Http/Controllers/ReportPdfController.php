<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReportTemplate;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request as HttpRequest;

class ReportPdfController extends Controller
{
    /**
     * Generate a PDF for the given template and sample/request id.
     */
    public function generatePdf(Request $request, $templateId, $sampleId)
    {
        // Use a temporary signed URL to render the report via the preview route,
        // then have Browsershot load that URL so the same renderer (client or server) is used.

        $template = ReportTemplate::findOrFail($templateId);

        // Create temporary signed URL valid for a short period
        $signedUrl = URL::temporarySignedRoute(
            'reports.preview',
            now()->addMinutes(5),
            ['templateId' => $templateId, 'sampleId' => $sampleId]
        );

        $fileName = 'reports/report_' . $sampleId . '_' . time() . '.pdf';
        $fullPath = storage_path('app/' . $fileName);
        @mkdir(dirname($fullPath), 0755, true);

        $bs = Browsershot::url($signedUrl)->showBackground();

        $pageSettings = $template->page_settings ?? [];
        $format = $pageSettings['format'] ?? 'A4';
        $margins = $pageSettings['margins'] ?? null;

        $bs->format($format);
        if ($margins && is_array($margins) && count($margins) === 4) {
            $bs->margins(...$margins);
        }

        $bs->save($fullPath);

        return response()->download($fullPath)->deleteFileAfterSend(true);
    }

    /**
     * Preview route used by Browsershot. This uses a signed URL for access control.
     */
    public function preview(HttpRequest $request, $templateId, $sampleId)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired signature');
        }

        $template = ReportTemplate::findOrFail($templateId);
        $reportService = app()->make('\App\\Services\\ReportService');
        $prepared = $reportService->prepareDataForTemplate($template, $sampleId);

        $renderedHtml = $prepared['renderedHtml'] ?? null;
        $schema = $prepared['schema'] ?? $template->schema_data;

        return view('reports.canvas_renderer', [
            'schema' => $schema,
            'renderedHtml' => $renderedHtml,
            'page_settings' => $template->page_settings ?? [],
        ]);
    }
}
