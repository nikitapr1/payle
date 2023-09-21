
@extends('layout.default')

@section('title', 'Member Create')

@section('content')
    <form action="{{route('members.store')}}" method="POST">

        <label>
            Имя
            <input name="name" required type="text">
        </label>

        <label>
            Отчество
            <input name="surname" required type="text">
        </label>

        <label>
            Телефон
            <input name="phone" required type="text">
        </label>



        <button type="submit">Отправить</button>

        @if($errors->any())

            @foreach($errors->all() as $error)
                <div>
                    {{$error}}
                </div>
            @endforeach
        @endif

        @csrf
    </form>

@endsection


