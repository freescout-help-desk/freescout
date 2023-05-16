@extends('layouts.app')
@section('content')
<div class="container">
  <div class="row vh-100">
    <div class="col-md-9"> <!-- 80% width for larger screens -->
        <div class="row">
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Total Tickets</p>
                        <h2 class="card-title text-danger" style="font-size: 80px;">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Unassigned Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">15</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Overdue Tickets</p>
                        <h2 class="card-title text-danger" style="font-size: 80px;">0</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Open Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">40</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Close Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">33</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Hold Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">40</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="dropdown float-end">
                                            <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <!-- item-->
                                                <a href="javascript:void(0);" class="dropdown-item">Action</a>
                                                <!-- item-->
                                                <a href="javascript:void(0);" class="dropdown-item">Another action</a>
                                                <!-- item-->
                                                <a href="javascript:void(0);" class="dropdown-item">Something else</a>
                                                <!-- item-->
                                                <a href="javascript:void(0);" class="dropdown-item">Separated link</a>
                                            </div>
                                        </div>

                                        <h4 class="header-title mt-0">Total no of Tickets</h4>

                                        <div class="widget-chart text-center">
                                            <div id="morris-donut-example" dir="ltr" style="height: 250px;" class="morris-chart"><svg height="245" version="1.1" width="301" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="overflow: hidden; position: relative; left: -0.609375px; top: -0.796875px;"><desc style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">Created with Raphaël 2.3.0</desc><defs style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></defs><path fill="none" stroke="	#7CFC00" d="M150.5,197.5A75,75,0,0,0,221.53058844420985,146.57603591269296" stroke-width="2" opacity="0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); opacity: 0;"></path><path fill="#ff8acc" stroke="#000000" d="M150.5,200.5A78,78,0,0,0,224.37181198197825,147.53907734920068L252.31051010336745,157.00898480819325A107.5,107.5,0,0,1,150.5,230Z" stroke-opacity="0" stroke-width="3" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><path fill="none" stroke="#5b69bc" d="M221.53058844420985,146.57603591269296A75,75,0,0,0,83.2429080941063,89.31139369659871" stroke-width="2" opacity="1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); opacity: 1;"></path><path fill="#5b69bc" stroke="#000000" d="M224.37181198197825,147.53907734920068A78,78,0,0,0,80.55262441787056,87.98384944446265L49.61436214115946,72.71709054489806A112.5,112.5,0,0,1,257.0458826663148,158.61405386903942Z" stroke-opacity="0" stroke-width="3" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><path fill="none" stroke="#35b8e0" d="M83.2429080941063,89.31139369659871A75,75,0,0,0,150.4764380554856,197.4999962988984" stroke-width="2" opacity="0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); opacity: 0;"></path><path fill="#35b8e0" stroke="#000000" d="M80.55262441787056,87.98384944446265A78,78,0,0,0,150.47549557770503,200.49999615085432L150.46622787952936,229.99999469508768A107.5,107.5,0,0,1,54.09816826821904,74.92966429845814Z" stroke-opacity="0" stroke-width="3" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><text x="150.5" y="112.5" text-anchor="middle" font-family="&quot;Arial&quot;" font-size="15px" stroke="none" fill="#000000" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); text-anchor: middle; font-family: Arial; font-size: 15px; font-weight: 800;" font-weight="800" transform="matrix(1.3504,0,0,1.3504,-52.7468,-42.5715)" stroke-width="0.7405309606481482"><tspan dy="5" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">Total Tickets</tspan></text><text x="150.5" y="132.5" text-anchor="middle" font-family="&quot;Arial&quot;" font-size="14px" stroke="none" fill="#000000" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); text-anchor: middle; font-family: Arial; font-size: 14px;" transform="matrix(1.4706,0,0,1.4706,-70.8235,-58.3529)" stroke-width="0.6799999999999999"><tspan dy="4.5" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">50</tspan></text></svg></div>
                                            <ul class="list-inline chart-detail-list mb-0">
                                                <li class="list-inline-item">
                                                    <h5 style="color: #ff8acc;"><i class="fa fa-circle me-1"></i>Series A</h5>
                                                </li>
                                                <li class="list-inline-item">
                                                    <h5 style="color: #5b69bc;"><i class="fa fa-circle me-1"></i>Series B</h5>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
            <div class="col-xl-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="dropdown float-end">
                                            <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <!-- item-->
                                                <a href="javascript:void(0);" class="dropdown-item">Action</a>
                                                <!-- item-->
                                                <a href="javascript:void(0);" class="dropdown-item">Another action</a>
                                                <!-- item-->
                                                <a href="javascript:void(0);" class="dropdown-item">Something else</a>
                                                <!-- item-->
                                                <a href="javascript:void(0);" class="dropdown-item">Separated link</a>
                                            </div>
                                        </div>
                                        <h4 class=" header-title mt-0text-align: left">Average time taken to close within SLA</h4>
                                        <div id="morris-line-example" dir="ltr" style="height: 280px; position: relative; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);" class="morris-chart"><svg height="280" version="1.1" width="1100%" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="overflow: hidden; position:relative; left: 250%; top: -0.796875px;"><desc style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">Created with Raphaël 2.3.0</desc><defs style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></defs><text x="30.71875" y="241" text-anchor="end" font-family="sans-serif" font-size="12px" stroke="none" fill="#888888" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); text-anchor: end; font-family: sans-serif; font-size: 12px; font-weight: normal;" font-weight="normal"><tspan dy="4" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">0</tspan></text><path fill="none" stroke="#adb5bd" d="M43.21875,241.5H276" stroke-opacity="0.1" stroke-width="0.5" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><text x="30.71875" y="187" text-anchor="end" font-family="sans-serif" font-size="12px" stroke="none" fill="#888888" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); text-anchor: end; font-family: sans-serif; font-size: 12px; font-weight: normal;" font-weight="normal"><tspan dy="4" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">25</tspan></text><path fill="none" stroke="#adb5bd" d="M43.21875,187.5H276" stroke-opacity="0.1" stroke-width="0.5" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><text x="30.71875" y="133" text-anchor="end" font-family="sans-serif" font-size="12px" stroke="none" fill="#888888" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); text-anchor: end; font-family: sans-serif; font-size: 12px; font-weight: normal;" font-weight="normal"><tspan dy="4" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">50</tspan></text><path fill="none" stroke="#adb5bd" d="M43.21875,133.5H276" stroke-opacity="0.1" stroke-width="0.5" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><text x="30.71875" y="79" text-anchor="end" font-family="sans-serif" font-size="12px" stroke="none" fill="#888888" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); text-anchor: end; font-family: sans-serif; font-size: 12px; font-weight: normal;" font-weight="normal"><tspan dy="4" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">75</tspan></text><path fill="none" stroke="#adb5bd" d="M43.21875,79.5H276" stroke-opacity="0.1" stroke-width="0.5" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><text x="30.71875" y="25" text-anchor="end" font-family="sans-serif" font-size="12px" stroke="none" fill="#888888" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); text-anchor: end; font-family: sans-serif; font-size: 12px; font-weight: normal;" font-weight="normal"><tspan dy="4" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">100</tspan></text><path fill="none" stroke="#adb5bd" d="M43.21875,25.5H276" stroke-opacity="0.1" stroke-width="0.5" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><text x="109.76669436840047" y="253.5" text-anchor="middle" font-family="sans-serif" font-size="12px" stroke="none" fill="#888888" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0); text-anchor: middle; font-family: sans-serif; font-size: 12px; font-weight: normal;" font-weight="normal" transform="matrix(1,0,0,1,0,7)"><tspan dy="4" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">2010</tspan></text><path fill="none" stroke="#188ae2" d="M43.21875,241C51.548622653500196,214,68.2083679605006,154.6295485636115,76.53824061400078,133C84.8453540526007,111.42954856361149,101.45958092980055,68.19999999999999,109.76669436840047,68.19999999999999C118.07380780700038,68.19999999999999,134.68803468420023,114.1,142.99514812280015,133C151.30226156140006,151.9,167.9164884385999,216.70369357045143,176.22360187719983,219.4C184.55347453070002,222.10369357045144,201.21321983770042,165.41477428180573,209.5430924912006,154.6C217.85020592980052,143.81477428180574,234.46443280700038,141.1,242.7715462456003,133C251.0786596842002,124.89999999999999,267.6928865614001,100.6,276,89.79999999999998" stroke-width="3" class="line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><path fill="none" stroke="#10c469" d="M43.21875,133C51.548622653500196,119.5,68.2083679605006,73.59261285909713,76.53824061400078,79C84.8453540526007,84.39261285909713,101.45958092980055,169.45,109.76669436840047,176.2C118.07380780700038,182.95,134.68803468420023,145.15,142.99514812280015,133C151.30226156140006,120.85,167.9164884385999,79,176.22360187719983,79C184.55347453070002,79,201.21321983770042,133,209.5430924912006,133C217.85020592980052,133,234.46443280700038,92.5,242.7715462456003,79C251.0786596842002,65.5,267.6928865614001,38.5,276,25" stroke-width="3" class="line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></path><circle cx="43.21875" cy="241" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="76.53824061400078" cy="133" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="109.76669436840047" cy="68.19999999999999" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="142.99514812280015" cy="133" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="176.22360187719983" cy="219.4" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="209.5430924912006" cy="154.6" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="242.7715462456003" cy="133" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="276" cy="89.79999999999998" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_1" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="43.21875" cy="133" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="76.53824061400078" cy="79" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="109.76669436840047" cy="176.2" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="142.99514812280015" cy="133" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="176.22360187719983" cy="79" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="209.5430924912006" cy="133" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="242.7715462456003" cy="79" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle><circle cx="276" cy="25" r="0" fill="#ffffff" stroke="#999999" stroke-width="1" class="circle_line_0" style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></circle></svg><div class="morris-hover morris-default-style" style="left: 165px; top: 35px; display: none;"><div class="morris-hover-row-label">2015</div><div class="morris-hover-point" style="color: #188ae2">
  Series B:
  70
</div><div class="morris-hover-point" style="color: #10c469">
  Series A:
  100
</div></div></div>
                                    </div>
                                </div>
                            </div>
    <!-- SIDEBAR -->

@endsection