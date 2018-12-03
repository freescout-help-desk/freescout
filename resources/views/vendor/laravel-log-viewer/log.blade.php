@extends('layouts.app')

@section('title', __('Logs'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="sidebar-title">
        {{ __('Logs') }}
    </div>
    @php
      $names = App\ActivityLog::select('log_name')->distinct()->pluck('log_name')->toArray();
      array_unshift($names, App\ActivityLog::NAME_OUT_EMAILS);
      array_push($names, App\ActivityLog::NAME_APP_LOGS);
      $current_name = 'app';
    @endphp
    <ul class="sidebar-menu">
        @foreach ($names as $name)
            <li @if ($current_name == $name)class="active"@endif><i class="glyphicon glyphicon-list-alt"></i> <a href="{{ route('logs', ['name'=>$name]) }}">{{ App\ActivityLog::getLogTitle($name) }}</a></li>
        @endforeach
    </ul>
@endsection

@section('content')

  <style>
    

    #table-log {
        font-size: inherit;
    }

    #laravel-logs {
        padding: 0 15px 15px;
    }
    #laravel-logs .sidebar {
        /*font-size: 0.85rem;*/
        line-height: 1;
    }

    #laravel-logs .btn {
        /*font-size: 0.7rem;*/
    }

    #laravel-logs .stack {
      /*font-size: 0.85em;*/
    }

    #laravel-logs .date {
      min-width: 75px;
    }

    #laravel-logs .text {
      word-break: break-all;
    }

    #laravel-logs a.llv-active {
      z-index: 2;
      background-color: #f5f5f5;
      border-color: #777;
    }

    #laravel-logs .list-group-item {
      word-wrap: break-word;
    }

    #laravel-logs .folder {
      padding-top: 15px;
    }

    #laravel-logs .div-scroll {
      /*height: 80vh;
      overflow: hidden auto;*/
    }
    #laravel-logs .nowrap {
      white-space: nowrap;
    }

  </style>

  <div class="section-heading margin-bottom">
    {{ __('Log Records') }}
    <div class="small text-help pull-right">{{ App\User::dateFormat(new Illuminate\Support\Carbon()) }}</div>
  </div>

  <div class="container-fluid1" id="laravel-logs">
    <div class="row1">
      <div class="col sidebar mb-3">

        <div class="list-group div-scroll">
          @foreach($folders as $folder)
            <div class="list-group-item">
              <a href="?f={{ \Illuminate\Support\Facades\Crypt::encrypt($folder) }}">
                <span class="fa fa-folder"></span> {{$folder}}
              </a>
              @if ($current_folder == $folder)
                <div class="list-group folder">
                  @foreach($folder_files as $file)
                    <a href="?l={{ \Illuminate\Support\Facades\Crypt::encrypt($file) }}&f={{ \Illuminate\Support\Facades\Crypt::encrypt($folder) }}"
                      class="list-group-item @if ($current_file == $file) llv-active @endif">
                      {{$file}}
                    </a>
                  @endforeach
                </div>
              @endif
            </div>
          @endforeach
          @foreach($files as $file)
            <a href="?l={{ \Illuminate\Support\Facades\Crypt::encrypt($file) }}"
               class="list-group-item @if ($current_file == $file) llv-active @endif">
              {{$file}}
            </a>
          @endforeach
        </div>
      </div>
      <div class="col-10 table-container">
        @if ($logs === null)
          <div>
            Log file >50M, please download it.
          </div>
        @else
          <table id="table-log" class="table table-striped" data-ordering-index="{{ $standardFormat ? 2 : 0 }}">
            <thead>
            <tr>
              @if ($standardFormat)
                <th>Level</th>
                <th>Context</th>
                <th>Date</th>
              @else
                <th>Line number</th>
              @endif
              <th>Content</th>
            </tr>
            </thead>
            <tbody>

            @foreach($logs as $key => $log)
              <tr data-display="stack{{{$key}}}">
                @if ($standardFormat)
                  <td class="nowrap text-{{{$log['level_class']}}}">
                    <span class="fa fa-{{{$log['level_img']}}}" aria-hidden="true"></span>&nbsp;&nbsp;{{$log['level']}}
                  </td>
                  <td class="text">{{$log['context']}}</td>
                @endif
                <td class="date">{{{$log['date']}}}</td>
                <td class="text">
                  @if ($log['stack'])
                    <button type="button"
                            class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
                            data-display="stack{{{$key}}}">
                      <span class="fa fa-search"></span>
                    </button>
                  @endif
                  {{{$log['text']}}}
                  @if (isset($log['in_file']))
                    <br/>{{{$log['in_file']}}}
                  @endif
                  @if ($log['stack'])
                    <div class="stack" id="stack{{{$key}}}"
                         style="display: none; white-space: pre-wrap;">{{{ trim($log['stack']) }}}
                    </div>
                  @endif
                </td>
              </tr>
            @endforeach

            </tbody>
          </table>
        @endif
        <div class="p-3">
          @if($current_file)
            <a href="?dl={{ \Illuminate\Support\Facades\Crypt::encrypt($current_file) }}{{ ($current_folder) ? '&f=' . \Illuminate\Support\Facades\Crypt::encrypt($current_folder) : '' }}">
              <span class="fa fa-download"></span> Download file
            </a>
            {{---
            <a id="clean-log" href="?clean={{ \Illuminate\Support\Facades\Crypt::encrypt($current_file) }}{{ ($current_folder) ? '&f=' . \Illuminate\Support\Facades\Crypt::encrypt($current_folder) : '' }}">
              <span class="fa fa-sync"></span> Clean file
            </a>--}}
            -
            <a id="delete-log" href="?del={{ \Illuminate\Support\Facades\Crypt::encrypt($current_file) }}{{ ($current_folder) ? '&f=' . \Illuminate\Support\Facades\Crypt::encrypt($current_folder) : '' }}">
              <span class="fa fa-trash"></span> Delete file
            </a>
            @if(count($files) > 1)
              -
              <a id="delete-all-log" href="?delall=true{{ ($current_folder) ? '&f=' . \Illuminate\Support\Facades\Crypt::encrypt($current_folder) : '' }}">
                <span class="fa fa-trash-alt"></span> Delete all files
              </a>
            @endif
          @endif
        </div>
      </div>
    </div>
  </div>
  <!-- jQuery for Bootstrap -->
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
          integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
          crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
          integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
          crossorigin="anonymous"></script>
  <!-- FontAwesome -->
  <script defer src="https://use.fontawesome.com/releases/v5.0.6/js/all.js"></script>
  <!-- Datatables -->
  <script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js"></script>
  <script>
    $(document).ready(function () {
      $('.table-container tr').on('click', function () {
        $('#' + $(this).data('display')).toggle();
      });
      $('#table-log').DataTable({
        "order": [$('#table-log').data('orderingIndex'), 'desc'],
        "stateSave": true,
        "stateSaveCallback": function (settings, data) {
          window.localStorage.setItem("datatable", JSON.stringify(data));
        },
        "stateLoadCallback": function (settings) {
          var data = JSON.parse(window.localStorage.getItem("datatable"));
          if (data) data.start = 0;
          return data;
        }
      });
      $('#delete-log, #clean-log, #delete-all-log').click(function () {
        return confirm('Are you sure?');
      });
    });
  </script>
@endsection

@section('stylesheets')
    <link href="{{ asset('js/datatables/datatables.min.css') }}" rel="stylesheet">
@endsection

@section('javascripts')
    <script src="{{ asset('js/datatables/datatables.min.js') }}"></script>
@endsection