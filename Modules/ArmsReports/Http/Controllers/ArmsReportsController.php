<?php

namespace Modules\ArmsReports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ArmsReports\Services\AgentPerformanceService;
use Modules\ArmsReports\Services\Exporter;
use Modules\ArmsReports\Services\KpiReportService;
use Modules\ArmsReports\Services\ReportFilters;

class ArmsReportsController extends Controller
{
    /**
     * ARMS KPIs — volume/lifecycle reports (ARMS-13).
     */
    public function kpis(Request $request)
    {
        $filters = ReportFilters::fromRequest($request);
        $data = (new KpiReportService($filters))->build();

        return $this->respond($request, $data, __('ARMS KPIs'), 'armsreports::kpis', $filters, 'arms-kpis');
    }

    /**
     * Agent Performance — per-assignee medians (ARMS-13).
     */
    public function agents(Request $request)
    {
        $filters = ReportFilters::fromRequest($request);
        $data = (new AgentPerformanceService($filters))->build();

        return $this->respond($request, $data, __('Agent Performance'), 'armsreports::agents', $filters, 'arms-agent-performance');
    }

    /**
     * PDF export for the native Reports pages (Conversations/Productivity/
     * Satisfaction), which have no export of their own (ARMS-40 follow-up).
     * Takes the metrics/tables HTML the button already found rendered on
     * screen rather than re-deriving that data through the Reports module's
     * own controller, so this has no dependency on its internal query logic.
     */
    public function nativeExportPdf(Request $request)
    {
        $request->validate([
            'html'  => 'required|string',
            'title' => 'nullable|string|max:200',
        ]);

        $title = $request->input('title') ?: __('Report');
        $filename = 'report-'.str_slug($title).'-'.now()->format('Ymd');

        return Exporter::pdfFromHtml($request->input('html'), $title, $filename);
    }

    protected function respond(Request $request, array $data, $title, $view, ReportFilters $filters, $filename)
    {
        $filename .= '-'.$filters->from->format('Ymd').'-'.$filters->to->format('Ymd');

        switch ($request->input('format')) {
            case 'csv':
                return Exporter::csv($data, $filename);
            case 'pdf':
                return Exporter::pdf($data, $title, $filters, $filename);
            default:
                return view($view, [
                    'data' => $data,
                    'title' => $title,
                    'filters' => $filters,
                    'mailboxes' => \App\Mailbox::orderBy('name')->get(),
                    'users' => \App\User::nonDeleted()->orderBy('first_name')->get(),
                ]);
        }
    }
}
