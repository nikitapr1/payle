<?php

namespace App\Telegram;

use App\Models\Member;
use App\Models\MessagesSupportHandle;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Services\NextMessageService;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Stringable;
use function Pest\Laravel\json;

class Handler extends WebhookHandler
{

    protected function handleChatMessage(Stringable $text): void
    {
        parent::handleChatMessage($text);

        if ($nextMessageHandler = NextMessageService::getNextMessageHandler($this->chat->chat_id, $this->message->text())) {
            $nextMessageHandler->getHandler()($nextMessageHandler->getData());
        }
    }


    protected function checkAuth()
    {
        $member = Member::query()->where("chat_id", $this->chat->chat_id)->first();
        if (!$member) {
            $this->reply("Вы не авторизованы");
            die();
        }


    }

    public function confirmPayment()
    {
        $this->checkAuth();

        $payment_id = $this->data->get('payment');
        $payment = Payment::query()->find($payment_id);

        $payment->update(['status' => 'paid']);

        $this->deleteKeyboard();

        $this->chat->message('Платеж подтвержден!')->send();

        $usurer = Member::query()->find(PaymentSchedule::query()->find($payment->schedule_id)->usurer);
        $debtor = Member::query()->find(PaymentSchedule::query()->find($payment->schedule_id)->debtor);
        $chat = TelegraphChat::query()->where('chat_id', $usurer->chat_id)->first();

        /** @var TelegraphChat $chat */

        $chat->message("{$debtor->name} подтвердил перевод Вам")->keyboard(Keyboard::make()->buttons([
            Button::make("Поблагодарить за перевод")->action('thanks')->param('debtor', $debtor->id)
                ->param('usurer', $usurer->id)
        ]))->send();


    }


    public function denyPayment()
    {
        $this->checkAuth();

        $payment_id = $this->data->get('payment');
        $payment = Payment::query()->find($payment_id);

        $payment->update(['status' => 'denied']);

        $this->deleteKeyboard();

        $this->chat->message('Платеж отклонен')->send();

    }

    public function thanks()
    {
        $this->checkAuth();
        $this->deleteKeyboard();

        $usurerId = $this->data->get('usurer');
        $debtorId = $this->data->get('debtor');

        $usurer = Member::query()->find($usurerId);
        $debtor = Member::query()->find($debtorId);

        $chat = TelegraphChat::query()->where('chat_id', (int) $debtor->chat_id)->first();
        /** @var TelegraphChat $chat */

        $chat->message("{$usurer->name} поблагодарил Вас за перевод")->send();
    }

    public function selectDebtor()
    {
        $this->checkAuth();
        $this->deleteKeyboard();

        $usurerId = $this->data->get('usurer');

        $members = Member::query()->get();
        $buttons = [];

        foreach ($members as $member) {
            $buttons[] = Button::make("{$member->name} {$member->surname}")->action('selectPeriod')
                ->param('usurer', $usurerId)->param('debtor', $member->id);


        }
        $this->chat->message("Укажите получателя платежа")->keyboard(Keyboard::make()->buttons($buttons))->send();


    }

    public function selectPeriod()
    {
        $this->checkAuth();
        $this->deleteKeyboard();
        $data = $this->data->toArray();

        $this->chat->message("Укажите тип платежа")->keyboard(Keyboard::make()->buttons([
            Button::make("Ежемесячный")->action('selectSum')
                ->param('usurer', $data['usurer'])->param('debtor', $data['debtor'])->param('period', 'regular'),

            Button::make("Одноразовый")->action('selectSum')
                ->param('usurer', $data['usurer'])->param('debtor', $data['debtor'])->param('period', 'once')


        ]))->send();


    }

    public function selectSum()
    {
        $this->checkAuth();
        $this->deleteKeyboard();

        $data = serialize($this->data->toArray());

        $this->chat->message("Укажите сумму платежа")->send();

    }

    public function start(string $token = '')
    {
        $member = Member::query()->where("telegram_start_token", $token)->first();

        if (!$member) {
            $this->reply("Вы не авторизованы.");
        } else {
            $member->update(['chat_id' => $this->chat->chat_id]);
            $this->reply("Здравствуйте, {$member->name} {$member->surname}. Бот успешно запущен.");

        }


    }


    public function schedule()
    {
        $this->checkAuth();

        $memberID = Member::query()->where('chat_id', $this->chat->chat_id)->first()->id;
        $incomingPayments = PaymentSchedule::query()->where('usurer', $memberID)->get();
        $outcomingPayments = PaymentSchedule::query()->where('debtor', $memberID)->get();

        $incomingStr = '';
        $outcomingStr = '';
        $count = 0;
        foreach ($incomingPayments as $incomingPayment) {
            $count += 1;

            $incomingStr .= sprintf("%s. %s переводит Вам %sр %s числа\n", $count,
                Member::find($incomingPayment->debtor)->name, $incomingPayment->debt,
                ($incomingPayment->period == 'once') ? $incomingPayment->date->format("d.m.Y") : $incomingPayment->date->format("d"));
        }
        $count = 0;

        foreach ($outcomingPayments as $outcomingPayment) {
            $count++;

            $outcomingStr .= sprintf("%s. %s ждет от вас перевод на сумму %sр %s числа\n", $count,
                Member::find($outcomingPayment->usurer)->name,
                $outcomingPayment->debt,
                ($outcomingPayment->period == 'once') ? $outcomingPayment->date->format("d.m.Y") : $outcomingPayment->date->format("d"));
        }

        if (empty($incomingStr)) {
            $incomingStr = "У вас нет входящих платежей.";
        }

        if (empty($outcomingStr)) {
            $outcomingStr = "У вас нет исходящих платежей.";
        }

        $this->reply(sprintf("<b>Входящие переводы:</b>\n%s\n<b>Исходящие переводы:</b>\n%s\n", $incomingStr,
            $outcomingStr));

    }

    public function scheduleAll()
    {
        $this->checkAuth();

        $schedules = PaymentSchedule::query()->get();
        $msg = '';
        $count = 0;

        foreach ($schedules as $schedule) {
            $count++;
            $msg .= sprintf("%s. %s переводит участнику %s %sр.\n", $count,
                Member::query()->find($schedule->debtor)->name,
                Member::query()->find($schedule->usurer)->name, $schedule->debt);
        }

        if (empty($msg)) {
            $msg = "Графики платежей пусты.";
        }

        $this->reply($msg);

    }

    public function check()
    {
        $this->checkAuth();

        $this_member = Member::query()->where('chat_id', $this->chat->chat_id)->first();

        $waitingPayments = Payment::query()->where([
            ['status', '=', 'waiting'],
            ['member_id', '=', $this_member->id]
        ])->get();

        $paymentsStr = '';
        $count = 0;
        foreach ($waitingPayments as $payment) {
            $count += 1;

            $paymentsStr .= sprintf("\n%s. %s ждет от вас перевод на сумму %sр \n", $count,
                Member::find(PaymentSchedule::find($payment->schedule_id)->usurer)->name, $payment->sum);
        }

        if (empty($paymentsStr)) {
            $paymentsStr = 'У вас нет задолженностей.';
        }

        $this->reply($paymentsStr);
    }


    public function add()
    {


        $members = Member::query()->get();
        $buttons = [];
        foreach ($members as $member) {

            $buttons[] = Button::make("{$member->name} {$member->surname}")->action('selectDebtor')
                ->param('usurer', $member->id);

        }


        $this->chat->message("Укажите платильщика")->keyboard(Keyboard::make()->buttons($buttons))->send();
    }

    public function del()
    {
        $this->reply("Данная команда еще не реализована в боте, но скоро мы её добавим!");
    }

    public function edit()
    {
        $this->reply("Данная команда еще не реализована в боте, но скоро мы её добавим!");
    }


}
