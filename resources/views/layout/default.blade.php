<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>

    <style>

        label
        {
            display: block;
            padding-bottom: 10px;
        }

        .member-list{
            margin-bottom: 15px;
        }

        a {
            text-decoration: none;
        }

        form{
            display: inline-block;
        }

        .hidden {
            display: none;

        }
    </style>
</head>
<body>

<ul>
    <li><a href="{{route('members.index')}}">Members List</a></li>
    <li><a href="{{route('members.create')}}">Members Create</a></li>
    <li><a href="{{route('payments.index')}}">Payments List</a></li>
    <li class="hidden"><a href="{{route('payments.create')}}">Payments Create</a></li>
    <li><a href="{{route('schedules.index')}}">Payments Schedule List</a></li>
    <li><a href="{{route('schedules.create')}}">Payments Schedule Create</a></li>

</ul>
<br>
@yield('content')
</body>
</html>
