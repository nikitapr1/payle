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
    <li><a href="{{route('members.index')}}">Список участников</a></li>
    <li><a href="{{route('members.create')}}">Создать участника</a></li>
    <li><a href="{{route('schedules.index')}}">Список графиков</a></li>
    <li><a href="{{route('schedules.create')}}">Создать график</a></li>

</ul>
<br>
@yield('content')
</body>
</html>
