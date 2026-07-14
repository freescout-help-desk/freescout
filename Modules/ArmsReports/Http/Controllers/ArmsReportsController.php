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
     * ARMS KPIs — §5.3 reports catalogue.
     */
    public function kpis(Request $request)
    {
        $filters = ReportFilters::fromRequest($request);
        $data = (new KpiReportService($filters))->build();

        return $this->respond($request, $data, __('ARMS KPIs'), 'armsreports::kpis', $filters, 'arms-kpis');
    }

    /**
     * Agent Performance — §5.2 per-assignee medians.
     */
    public function agents(Request $request)
    {
        $filters = ReportFilters::fromRequest($request);
        $data = (new AgentPerformanceService($filters))->build();

        return $this->respond($request, $data, __('Agent Performance'), 'armsreports::agents', $filters, 'arms-agent-performance');
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
