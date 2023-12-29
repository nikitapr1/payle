<?php

namespace App\Telegram;

use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Services\NextMessageService;
use DefStudio\Telegraph\Facades\Telegraph as TelegraphFacade;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;
use DateTime;
use Illuminate\Support\Facades\Log;

class Handler extends WebhookHandler
{


    protected function handleChatMessage(Stringable $text): void
    {
        parent::handleChatMessage($text);

        if ($nextMessageHandler = NextMessageService::getNextStepHandler($this->chat->chat_id)) {
            call_user_func([$this, $nextMessageHandler->getAction()], $nextMessageHandler->getData(), $text);
            NextMessageService::deleteNextStepHandler($this->chat->chat_id, $nextMessageHandler->getAction());
        }
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        $this->chat->html("Неизвестная команда.")->send();
    }


    protected function checkAuth()
    {
        $member = Member::query()->where("chat_id", $this->chat->chat_id)->first();
        if (!$member) {
            $this->reply("Вы не авторизованы");
            die();
        }


    }

    /*public function test(){
        $this->chat->message('Bla')->keyboard(Keyboard::make()->buttons([
            Button::make('Вернуться в меню')->action('menu')->param('pMsg', $this->messageId + 1)
        ]))->send();
    }*/

   /* public function menu(){
        $this->checkAuth();
        $data = $this->data->toArray();

        $keyboard = Keyboard::make()->buttons([
            Button::make("Посмотреть график платежей")->action('schedule')->param('pMsg', $this->messageId + 1),
            Button::make("Посмотреть задолженности")->action('check')->param('pMsg', $this->messageId + 1),
        ]);

        if (!array_key_exists('action', $data)){
            $this->chat->message("Чем могу Вам помочь?")->keyboard($keyboard)->send();
            die();
        }
        $this->chat->edit($data['pMsg'])->message("Чем могу Вам помочь?")->keyboard($keyboard)->send();

    }*/




    protected function deleteSchedule($data, $id)
    {
        if (!($schedule = PaymentSchedule::query()->find($id))) {
            $this->reply("Такого графика не существует");
            NextMessageService::deleteNextStepHandler($this->chat->chat_id, 'deleteSchedule');
            die();
        }
        $schedule->delete();
        $this->reply("График удален.");
    }





    public function members()
    {
        $this->checkAuth();
        $answer = '';
        $members = Member::all();
        $count = 0;
        foreach ($members as $member) {
            $count++;
            $answer .= sprintf("%s\\. %s %s\\. ChatID: %s \n", $count, $member->name, $member->surname,
                is_null($member->chat_id) ? "Отсутствует\\. [Ссылка]({$member->telegram_link}) " : $member->chat_id);
        }

        $this->chat->message($answer)->withData('parse_mode', 'MarkdownV2')->send();
    }

    public function addMember()
    {
        $this->checkAuth();

        $this->reply("Укажите имя нового участника:");
        NextMessageService::addNextStepHandler($this->chat->chat_id, serialize([]), 'handleMemberFirstName');

    }

    protected function handleMemberFirstName(array $data, $text)
    {
        if (!is_string($text)) {
            $this->reply("Ошибка: неверный ввод.");
            NextMessageService::deleteNextStepHandler($this->chat->chat_id, 'handleMemberFirstName');
            die();
        }

        $data['name'] = $text;


        $this->reply("Укажите отчество или фамилию нового участника:");
        NextMessageService::addNextStepHandler($this->chat->chat_id, serialize($data), 'handleMemberSurname');
    }

    protected function handleMemberSurname(array $data, $text)
    {
        if (!is_string($text)){
            $this->reply("Ошибка: неверный ввод.");
            NextMessageService::deleteNextStepHandler($this->chat->chat_id, 'handleMemberSurname');
            die();
        }

        $data['surname'] = $text;

        $this->createMember($data);

    }

    protected function createMember(array $data){

    }




    public function confirmPayment()
    {
        $this->checkAuth();

        $payment_id = $this->data->get('payment');
        $payment = Payment::query()->find($payment_id);

        $payment->update(['status' => 'paid']);

        $this->deleteKeyboard();

        $this->chat->message('Платеж подтвержден!')->send();
        $thisMember = Member::query()->where('chat_id', $this->chat->chat_id)->first();
        Log::debug(sprintf("%s (%s) подтвердил оплату платежа", $thisMember->name, $thisMember->id));

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

        $data = $this->data->toArray();


        $usurerId = $this->data->get('usurer');
        $debtorId = $this->data->get('debtor');

        $usurer = Member::query()->find($usurerId);
        $debtor = Member::query()->find($debtorId);

        $chat = TelegraphChat::query()->where('chat_id', (int) $debtor->chat_id)->first();
        /** @var TelegraphChat $chat */

        $chat->message("{$usurer->name} поблагодарил Вас за перевод")->send();
    }

    public function add()
    {
        $this->checkAuth();

        $members = Member::query()->get();
        $action = 'selectUsurer';
        $buttons = [];
        foreach ($members as $member) {

            $buttons[] = Button::make("{$member->name} {$member->surname}")->action($action)
                ->param('debtor', $member->id)
                ->param('previousMessage', (int) $this->messageId + 1);
        }



        $this->chat->message("Укажите платильщика")->keyboard(Keyboard::make()->buttons($buttons))->send();

    }


    public function selectUsurer()
    {
        $this->checkAuth();
        $data = $this->data->toArray();
        $action = 'selectPeriod';
        $members = Member::query()->get();
        $buttons = [];

        foreach ($members as $member) {
            $buttons[] = Button::make("{$member->name} {$member->surname}")->action($action)
                ->param('previousMessage', (int) $this->messageId)
                ->param('usurer', $member->id)
                ->param('debtor', $data['debtor']);
        }

        $this->chat->edit($this->messageId)->message("Укажите получателя платежа")
            ->keyboard(Keyboard::make()->buttons($buttons))->send();

    }

    public function selectPeriod()
    {
        $this->checkAuth();
        $data = $this->data->toArray();
        $action = 'selectSum';
        $previousMessageId = (int)$data['previousMessage'];
        $buttons = [
            Button::make("Ежемесячный")->action($action)
            ->param('usurer', $data['usurer'])
            ->param('debtor', $data['debtor'])
            ->param('period', 'regular')
            ->param('pMsg', $previousMessageId),

            Button::make("Одноразовый")->action($action)
            ->param('usurer', $data['usurer'])
            ->param('debtor', $data['debtor'])
            ->param('period', 'once')
            ->param('pMsg', $previousMessageId),

        ];
       $this->chat->edit($previousMessageId)->message("Укажите тип платежа")->keyboard(Keyboard::make()->buttons($buttons))->send();


    }

    public function selectSum()
    {


        $this->checkAuth();
        $this->deleteKeyboard();
        $data = $this->data->toArray();
        $text = "Укажите сумму платежа\n(Для отмены напишите /cancel)";
        $this->chat->edit($data['pMsg'])->message($text)->send();
        NextMessageService::addNextStepHandler($this->chat->chat_id, serialize($data), 'handleSum');

    }
    protected function handleSum(array $data, string $text): void
    {
        $this->chat->deleteMessage($data['pMsg']);

        if (!is_numeric($text)) {
            $this->chat->message("Ошибка: вы ввели не целое число.")->send();
            NextMessageService::deleteNextStepHandler($this->chat->chat_id, 'handleSum');
            die();
        }
        $data['sum'] = $text;

        $this->chat->message("Укажите дату (формат: гггг мм дд чч мм сс). Например 2024 12 30 12 00 00\n(Для отмены напишите /cancel)")
        ->send();
        $data['pMsg'] = $this->message->id();
        NextMessageService::addNextStepHandler($this->chat->chat_id, serialize($data), 'handleDate');

    }

    protected function handleDate(array $data, string $text)
    {


        $format = 'Y m d H i s';
        $date = DateTime::createFromFormat($format, $text);
        if (!$text === $date) {
            $this->chat->edit($data['pMsg'])->message("Ошибка: вы ввели не целое число. Попробуйте еще раз.")->send();
            NextMessageService::deleteNextStepHandler($this->chat->chat_id, 'handleDate');
            die();
        }

        $data = (array) $data;

        $data['date'] = $text;
        $this->createSchedule($data);

    }

    private function createSchedule(array $data)
    {
        $this->checkAuth();

        PaymentSchedule::query()->create([
            'usurer' => $data['usurer'],
            'debtor' => $data['debtor'],
            'debt' => $data['sum'],
            'date' => $data['date'],
            'period' => $data['period']
        ]);

        $this->chat->message('График создан.')->send();
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
            $incomingStr = "У вас нет входящих платежей.\n\n";
        }

        if (empty($outcomingStr)) {
            $outcomingStr = "У вас нет исходящих платежей.";
        }

        if (array_key_exists('action', $data = $this->data->toArray())){
            $this->chat->edit($data['pMsg'])->message(sprintf("<b>Входящие переводы:</b>\n%s\n<b>Исходящие переводы:</b>\n%s\n", $incomingStr,
                $outcomingStr))->keyboard(Keyboard::make()->buttons([
                    Button::make('Вернуться в меню')->action('menu')->param('pMsg', $this->messageId)
            ]))->send();
            die();
        }

        $this->chat->message(sprintf("<b>Входящие переводы:</b>\n%s\n<b>Исходящие переводы:</b>\n%s\n", $incomingStr,
            $outcomingStr))->send();

    }

    public function scheduleAll()
    {
        $this->checkAuth();

        $oneTimePaymentSchedules = PaymentSchedule::query()->where('period', 'once')->get();
        $monthlyPaymentSchedules = PaymentSchedule::query()->where('period', 'regular')->get();
        $oneTimeStr = '';
        $monthlyStr = '';
        foreach ($oneTimePaymentSchedules as $schedule) {
            $oneTimeStr .= sprintf("ID:%s %s переводит участнику %s %sр %s числа.\n\n", $schedule->id,
                Member::query()->find($schedule->debtor)->name, Member::query()->find($schedule->usurer)->name,
                $schedule->debt, $schedule->date
            );
        }
        foreach ($monthlyPaymentSchedules as $schedule) {
            $monthlyStr .= sprintf("ID:%s %s переводит участнику %s %sр %s числа каждый месяц.\n\n", $schedule->id,
                Member::query()->find($schedule->debtor)->name, Member::query()->find($schedule->usurer)->name,
                $schedule->debt, $schedule->date->format('d'));


        }

        if (empty($oneTimeStr)) {
            $oneTimeStr = "Одноразовые платежи отсутствуют\n\n";
        }
        if (empty($monthlyStr)) {
            $oneTimeStr = "Ежемесячные платежи отсутствуют\n\n";
        }

        $this->chat->message("<b>Одноразовые платежи:</b>\n{$oneTimeStr}<b>Ежемесячные платежи:</b>\n{$monthlyStr}")->send();


    }

    public function check()
    {
        die();

        $this->checkAuth();

        $data = $this->data->toArray();

        $thisMember = Member::query()->where('chat_id', $this->chat->chat_id)->first();

        $waitingPayments = Payment::query()->where([
            ['status', '=', 'waiting'],
            ['member_id', '=', $thisMember->id]
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

        if (array_key_exists('action', $data)){
            $this->chat->edit($data['pMsg'])->message($paymentsStr)->keyboard(
                Keyboard::make()->buttons([
                    Button::make('Вернуться в меню')->action('menu')->param('pMsg', $data['pMsg'])
                ])
            )->send();

            die();
        }


        $this->reply($paymentsStr);
    }



    public function del()
    {
        $this->checkAuth();

        $this->scheduleAll();

        $this->reply("Укажите ID графика, который вы хотите удалить.\n(Для отмены напишите в чат /cancel)");

        NextMessageService::addNextStepHandler($this->chat->chat_id, serialize([]), 'deleteSchedule');
    }

    public function cancel(){

        $this->checkAuth();

        NextMessageService::deleteAllHandlersForChat($this->chat->chat_id);
        $this->reply("Действие прервано. Чем еще могу Вам помочь?");
        die();

    }



}
