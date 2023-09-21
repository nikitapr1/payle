@extends('layout.default')

@section('title', 'Payment List')

@section('content')
    <h1>List of Payments</h1>

    <div class="payment-list">
        <ol>

            @foreach($payments as $payment)
                <li class="payment">
                    Сумма {{$payment->sum}} кому {{($members->find($payment->member_id))->name}}

                    <form class="hidden" action="{{route('payments.destroy', ['id' => $payment->id])}}" method="post">
                        <button type="submit">Удалить</button>
                        @method('delete')
                        @csrf
                    </form>
                </li>
            @endforeach
        </ol>
    </div>
@endsection


