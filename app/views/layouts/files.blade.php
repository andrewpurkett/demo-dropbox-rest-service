<html>
    <head>
        <title>
        @section('title')
            Demo | Dropbox Testing
        @show
        </title>
        <meta charset="utf-8">
        <style>
            @import url(//fonts.googleapis.com/css?family=Lato:700);

            body {
                font-family:'Lato', sans-serif;
                margin: 0;
                background-color: #C6FFFD;
            }

            .flash-message {
                border: 1px solid #666;
                background: #eee;
                color: #000;
                padding: 5px 15px 5px 15px;
                margin: 0px 0px 15px 0px;
            }

            a:link, a:visited, a:active, a:hover, a:focus {
                color: #019894;
            }

            div.page {
                position: absolute;
                width: 980px;
                max-width: 980px;
                min-width: 980px;
                left: 50%;
                margin-left: -490px;
                min-height: 100%;
                background-color: #FFFFFF;
                padding: 15px;
            }
            h1, h2, h3, h4, h5 {
                text-align: center;
            }
            h1 {
                text-overflow: ellipsis;
                overflow: hidden;
                white-space: nowrap;
            }

            div.nav {
                margin-top: 15px;
                padding-top: 15px;
                border-top: #C6FFFD 2px solid;
                text-align: right;
            }
        </style>
    </head>
    <body>
        <div class="page">
            @if (Session::has('flash_message'))
            <div class="flash-message">
                <p>{{ Session::get('flash_message') }}</p>
            </div>
            @endif

            @section('heading')
                <h1>Demo Dropbox Testing</h1>
            @show

            @yield('content')
        </div>
    </body>
</html>