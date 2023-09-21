@extends('layout.default')

@section('title', 'Payment Create')

@section('content')
    <form action="{{route('schedules.update', $schedule->id)}}" method="POST">

        ID: {{$schedule->id}}


        <label>
            Debtor
            <select name="debtor" id="">
                @foreach($members as $member)
                    <option value="{{$member->id}}" @if($member->id == $schedule->debtor) selected="selected" @endif>
                        {{$member->name}} - {{$member->id}}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            Usurer
            <select name="usurer" id="">
                @foreach($members as $member)
                    <option value="{{$member->id}}" @if($member->id == $schedule->usurer) selected="selected" @endif>
                        {{$member->name}} - {{$member->id}}
                    </option>
                @endforeach
            </select>
        </label>


        <label>
            Debt
            <input type="text" name="debt" value="{{$schedule->debt}}">
        </label>

        <label>
            Date
            <input type="datetime-local" name="date" value="{{$schedule->date->format('Y-m-d')}}{{'T'}}{{$schedule->date->format('H:i')}}">
        </label>

        <label>
            Period
            <select name="period" id="">
                <option value="once" @if($schedule->period = 'once') selected="selected" @endif>Once</option>
                <option value="regular" @if($schedule->period = 'regular') selected="selected" @endif>Regular</option>
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

        @method('put')
        @csrf
    </form>

@endsection


