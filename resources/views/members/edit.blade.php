
@extends('layout.default')

@section('title', 'Member Edit')

@section('content')
    <form action="{{route('members.update', $member->id)}}" method="POST">
        <p>
            ID: {{$member->id}}
        </p>
        <label>
            Имя
            <input name="name" type="text" required value="{{$member->name}}">
        </label>

        <label>
            Отчество
            <input name="surname" type="text" required value="{{$member->surname}}">
        </label>

        <label>
            Телефон
            <input name="phone" type="text" required value="{{$member->phone}}">
        </label>


        <button type="submit">Отправить</button>

        @if($errors->any())

            @foreach($errors->all() as $error)
                <div>
                    {{$error}}
                </div>
            @endforeach
        @endif
        @method('put')
        @csrf
    </form>

@endsection


