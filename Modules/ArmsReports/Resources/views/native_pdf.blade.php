<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .meta { color: #777; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f2f2f2; }

        /* Approximations of the native report's own classes - its module.css
           isn't loaded here, so these are re-implemented rather than reused. */
        .rpt-metrics { margin-bottom: 14px; }
        .rpt-metric { display: inline-block; width: 30%; margin: 0 1.5% 10px 0; vertical-align: top; }
        .rpt-metric-title { font-weight: bold; font-size: 10px; color: #555; }
        .rpt-metric-value { font-size: 16px; }
        .row { display: table; width: 100%; table-layout: fixed; }
        [class*="col-md-"] { display: table-cell; padding: 0 6px; vertical-align: top; }

        /* The chart is a canvas.toDataURL() snapshot taken at export time -
           constrained to the page width since the canvas's natural pixel
           size can be wider than an A4 page. */
        .rpt-chart-image { margin-bottom: 14px; text-align: center; }
        .rpt-chart-image img { max-width: 100%; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">{{ __('Generated') }}: {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}</div>
    {!! $html !!}
</body>
</html>
