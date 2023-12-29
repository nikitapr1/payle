<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentsCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:payments-create';

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
        $sql = "select now, schedule.id as schedule_id, date, last_payment_date, schedule.period, debt as sum, debtor as member_id from (
                       select payment_schedules.id as id, date, period, debt, debtor, (SELECT payments.schedule_date
                                                                                       FROM payments
                                                                                       WHERE payment_schedules.id = payments.schedule_id
                                                                                       ORDER BY payments.schedule_date DESC limit 1) as last_payment_date,
                           (SELECT CASE
                               WHEN (SELECT DAYNAME(NOW())) = 'Friday'
                                   THEN ADDDATE(NOW(), INTERVAL 2 day)
                               ELSE NOW()
                            END AS now
                            ) as now
                       from payment_schedules
                   ) as schedule
where (timediff(now, schedule.date) >= 0 and schedule.period = 'once' and last_payment_date IS NULL)
   or (schedule.period = 'regular' and last_payment_date is null and timediff(now, schedule.date) >= 0)
   or (schedule.period = 'regular' and timediff(now, adddate(last_payment_date, interval 1 month )) >= 0)";

        $payments = DB::select($sql);

        foreach ($payments as $payment) {
            $payment = (array) $payment;
            $carbon = Carbon::create($payment['now'])->setDay(Carbon::create($payment['date'])->day);
            Payment::create([
                'schedule_id' => $payment['schedule_id'],
                'sum' => $payment['sum'],
                'member_id' => $payment['member_id'],
                'schedule_date' => $carbon
            ]);




        }

    }
}
