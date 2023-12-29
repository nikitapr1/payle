@extends('layout.default')

@section('title', 'Member List')

@section('content')
    <h1>Список участников</h1>
    <div class="member-list">
        <ol>
            @foreach($members as $member)

                <li class="member">
                    {{$member->name}} {{$member->surname}}
                    <a href="{{route('members.edit', ['id' => $member->id])}}">
                        <button class="redact">Редактировать</button>
                    <form action="{{route('members.destroy', ['id' => $member->id])}}" method="post">
                    <button type="submit">Удалить</button>
                    @method('delete')
                    @csrf
                    </form>
                    </a>
                    <a href="<?=$member->telegram_link?>">
                        Ссылка
                    </a>


                </li>
            @endforeach
        </ol>
    </div>
@endsection


