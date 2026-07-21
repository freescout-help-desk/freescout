<?php

namespace Modules\ArmsReports\Services;

/**
 * CSV + PDF export of a report's data (the same arrays the Blade views
 * render — exports always match what the current filters show).
 */
class Exporter
{
    /**
     * Stream all sections (and stat cards, if any) as a single CSV download.
     * Uses response()->stream() — streamDownload() does not exist in Laravel 5.5.
     */
    public static function csv(array $data, $filename)
    {
        return response()->stream(function () use ($data) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens it correctly.
            fwrite($out, "\xEF\xBB\xBF");

            if (!empty($data['cards'])) {
                fputcsv($out, [__('Metric'), __('Value')]);
                foreach ($data['cards'] as $card) {
                    fputcsv($out, [$card['label'], $card['value']]);
                }
                fputcsv($out, []);
            }

            foreach ($data['sections'] as $section) {
                fputcsv($out, [$section['title']]);
                fputcsv($out, $section['headers']);
                foreach ($section['rows'] as $row) {
                    // Bar sections carry a trailing percentage column for CSS — drop it.
                    if (!empty($section['bar'])) {
                        $row = array_slice($row, 0, count($section['headers']));
                    }
                    fputcsv($out, $row);
                }
                fputcsv($out, []);
            }

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
        ]);
    }

    /**
     * Render the shared PDF template through dompdf and stream it.
     */
    public static function pdf(array $data, $title, ReportFilters $filters, $filename)
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            abort(500, 'dompdf is not installed — run composer install.');
        }

        $html = \View::make('armsreports::pdf', [
            'data' => $data,
            'title' => $title,
            'filters' => $filters,
        ])->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
        ]);
    }

    /**
     * PDF export of an already-rendered report fragment (ARMS-40 follow-up):
     * the native Reports module has no export of its own, so its "Export to
     * PDF" button posts back the metrics/tables HTML it already rendered on
     * screen rather than us re-deriving that data through its controller.
     */
    public static function pdfFromHtml($html, $title, $filename)
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            abort(500, 'dompdf is not installed — run composer install.');
        }

        // Defense in depth: this HTML arrives via a POST body, which
        // nothing server-side can guarantee is really an unmodified capture
        // of the page (a user could tamper with it via devtools). Script
        // tags have no legitimate reason to be in a metrics/tables capture,
        // and dompdf doesn't execute them anyway.
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string) $html);

        $rendered = \View::make('armsreports::native_pdf', [
            'html' => $html,
            'title' => $title,
        ])->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($rendered);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
        ]);
    }
}
