
@extends('layout.default')

@section('title', 'Payment Schedule Create')

@section('content')
    <form action="{{route('schedules.store')}}" method="POST">


        <label>
        Debtor
            <select name="debtor" id="">
                @foreach($members as $member)
                    <option value="{{$member->id}}">
                        {{$member->name}} - {{$member->id}}
                     </option>
                @endforeach
            </select>
        </label>

        <label>
            Usurer
            <select name="usurer" id="">
                @foreach($members as $member)
                    <option value="{{$member->id}}">{{$member->name}} - {{$member->id}}</option>
                @endforeach
            </select>
        </label>

        <label>
            Debt
            <input type="text" name="debt">
        </label>

        <label>
            Date
            <input type="datetime-local" name="date">
        </label>

        <label>
            Period
            <select name="period">
                <option value="once">Once</option>
                <option value="regular">Regular</option>
            </select>
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


