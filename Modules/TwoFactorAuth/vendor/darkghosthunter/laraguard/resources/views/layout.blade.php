<!doctype html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <title>Two Factor Authentication</title>
    <style>
        #box-container {
            min-height: 100vh;
        }
        #box {
            margin-bottom: 6rem;
        }
        .cool-shadow {
            box-shadow: 0 2.8px 2.2px rgba(0, 0, 0, 0.1),
            0 6.7px 5.3px rgba(0, 0, 0, 0.072),
            0 12.5px 10px rgba(0, 0, 0, 0.06),
            0 22.3px 17.9px rgba(0, 0, 0, 0.05),
            0 41.8px 33.4px rgba(0, 0, 0, 0.04),
            0 100px 80px rgba(0, 0, 0, 0.028);
        }
    </style>
</head>
<body class="bg-light">
<div class="container">
    <div id="box-container" class="row justify-content-center align-items-center">
        <div id="form-container" class="col-lg-6 col-md-8 col-sm-10 col-12">
            <div id="box" class="card border-0 cool-shadow">
                <section class="card-body">
                    <h2 class="card-title h5 text-center">{{ trans('laraguard::messages.required') }}</h2>
                    <hr>
                    @yield('card-body')
                </section>
            </div>
            <div class="text-black-50 small text-center">
                <a href="javascript:history.back()" class="btn btn-sm text-secondary btn-link">
                    &laquo; Go back
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
