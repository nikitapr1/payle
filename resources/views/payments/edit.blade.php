@extends('layout.default')

@section('title', 'Payment Create')

@section('content')
    <form action="{{route('payments.update', $payment->id)}}" method="POST">
        ID: {{$payment->id}}
        <label>
            Сумма
            <input name="sum" required type="text" value="{{$payment->sum}}">
        </label>

        <label>
            Member
            <select name="member_id" required>
                @foreach($members as $member)
                    <option value="{{$member->id}}">{{$member->name}} {{$member->surname}}</option>
                @endforeach
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


