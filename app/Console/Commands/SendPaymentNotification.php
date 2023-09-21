<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Console\Command;
use DefStudio\Telegraph\Models\TelegraphChat;
use Ramsey\Uuid\Nonstandard\Uuid;

class SendPaymentNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-payment-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $payments = Payment::query()->where('status', 'created')->get();

        foreach ($payments as $payment){
            $memberChatId = Member::where('id', $payment->member_id)->first()->chat_id;
            if (!$memberChatId){
                continue;
            }
            $chat = TelegraphChat::where('chat_id', $memberChatId)->first();
            if (!$chat){
                continue;
            }
            /**@var TelegraphChat $chat*/
            $UsurerId = PaymentSchedule::find($payment->schedule_id)->usurer;
            $usurerName = Member::find($UsurerId)->name;
            $usurerSurname = Member::find($UsurerId)->surname;
            $chat->message("Здравствуйте! {$usurerName} {$usurerSurname} ждет от Вас перевод на сумму {$payment->sum}р. После перевода, пожалуйста, нажмите кнопку")->keyboard(Keyboard::make()->buttons([
                    Button::make("Перевод выполнен")->action('confirmPayment')->param('payment', $payment->id),
                    Button::make("Отклонить платеж")->action('denyPayment')->param('payment', $payment->id),
                ]))->send();
            $payment->update(['status' => 'waiting']);

        }


    }

    public function delete()
    {

    }


}
