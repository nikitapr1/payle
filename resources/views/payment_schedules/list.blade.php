@extends('layout.default')

@section('title', 'Payment Schedules List')

@section('content')
    <h1>List of schedules</h1>
    <ol>
        <div>
            @foreach($schedules as $schedule)
                <li>
                    <span>
                    {{($members->find($schedule->debtor))->name}}
                    {{($members->find($schedule->debtor))->surname}}

                        @if($schedule->period == 'once')
                            должен перевести
                            {{($members->find($schedule->usurer))->name}}
                            {{($members->find($schedule->usurer)->surname)}}
                            {{$schedule->debt}}р
                            до
                            {{$schedule->date->format('d M Y')}}
                            года
                        @endif
                        @if($schedule->period == 'regular')
                            должен переводить
                            {{($members->find($schedule->usurer))->name}}
                            {{($members->find($schedule->usurer)->surname)}}
                            {{$schedule->debt}}р
                            {{$schedule->date->format('d')}}
                            числа каждый месяц
                        @endif
                    </span>


                    <a href="{{route('schedules.edit', ['schedule' => $schedule->id])}}">
                        <button>Редактировать</button>
                    </a>

                    <form action="{{route('schedules.destroy', ['schedule' => $schedule->id])}}" method="post">
                        <button type="submit">Удалить</button>
                        @method('delete')
                        @csrf
                    </form>

                </li>
            @endforeach
        </div>
    </ol>
@endsection


