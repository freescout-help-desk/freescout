<!DOCTYPE html>
<html lang="en">
    <head>
        <title>@yield('title')</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        {!! Minify::stylesheet(\Eventy::filter('stylesheets', array('/css/fonts.css', '/css/bootstrap.css', '/css/style.css'))) !!}
        <style type="text/css">
            #wrapper {
                background: #fff;
                border: 1px solid #e1e1e1;
                font-size: 16.4px;
                border-radius: 6px;
                -webkit-box-sizing: border-box;
                box-sizing: border-box;
                margin: 35px auto 20px auto;
                padding: 45px 85px;
                text-align: center;
                max-width: 650px;
            }
            .thanks #wrapper {
                padding-top: 80px;
                padding-bottom: 80px;
            }
            p {
                color: #898989;
            }
            h1 {
                font-size: 30px;
                font-weight: normal;
            }
            .comment-area {
                border: 1px solid #d7d7d7;
                border-radius: 3px;
                color: #3a3a3a;
                display: inline-block;
                font-size: 12px;
                height: 156px;
                line-height: 18px;
                outline: 0;
                overflow: auto;
                padding: 5px;
                resize: none;
                max-width: 420px;
            }
            .btn-submit-feedback {
                padding: 15px 45px;
                margin-top: 5px;
            }
            .comment-counter {
                color: #cfcfcf;
                width: 100%;
                max-width: 420px;
                display: inline-block;
            }
            .level-buttons {
                margin: 10px 0 35px;
                width: 100%;
            }
            .level-great,
            .level-great:hover,
            .level-great.active,
            .level-great.active:hover,
            .level-great.active.focus {
                color: #00b66d;
            }
            .level-okay,
            .level-okay:hover,
            .level-okay.active,
            .level-okay.active:hover,
            .level-okay.active.focus {
                color: #727272;
            }
            .level-bad,
            .level-bad:hover,
            .level-bad.active,
            .level-bad.active:hover,
            .level-bad.active.focus {
                color: #b05151;
            }
            .level-icon {
                background: url('/modules/satratings/img/saticons.png') 0 0 no-repeat transparent;
                display: inline-block;
                width: 40px;
                height: 40px;
            }
            .level-okay .level-icon {
                background-position: 0 -40px;
            }
            .level-bad .level-icon {
                background-position: 0 -80px;
            }
            .level-buttons .btn,
            .level-buttons .btn:focus,
            .level-buttons .btn.focus {
                border: 1px solid #d7d7d7;
                width: 33%;
                padding: 20px 0;
                font-size: 16.4px;
                font-weight: bold;
                outline: none;
            }
            .level-buttons .btn:hover {
                border-color: #b5b5b5;
                /*background-color: #edeff0;*/
                background-color: transparent;
            }
            .level-buttons .level-great.active {
                background-color: #dfffdf;
                /*border-color: #62b35f;*/
            }
            .level-buttons .level-okay.active {
                background-color: #e0e2e3;
            }
            .level-buttons .level-bad.active {
                background-color: #f2dede;
            }
            .satr-thanks-icon {
                font-size: 70px;
            }
            #satr-first-name {
                display: none;
            }
            @media (max-width:680px) {
                #wrapper {
                    padding: 35px 15px;
                    margin: 0 0 20px 0;
                    border: 0;
                    border-radius: 0;
                }
            }
        </style>
    </head>
    <body class="@yield('body_class')">
        <div id="wrapper">
            @yield('content')
        </div>
        <p class="text-center margin-bottom-40">
            @if (\Option::get('email_branding'))
                Powered by <a href="{{ config('app.freescout_url') }}" target="blank">{{ \Config::get('app.name') }}</a>
            @endif
        </p>
        {!! Minify::javascript(array('/js/jquery.js', '/js/bootstrap.js')) !!}
        <script type="text/javascript">
            $(document).ready(function($) {
                $('.comment-area').on('change keyup', function(e) {
                    var remaining = 500 - $(this).val().length;
                    jQuery('.comment-counter').text(remaining);
                });
            });
        </script>
    </body>
</html>