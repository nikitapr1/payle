<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PaymentSchedule;
use Illuminate\Http\Request;

class PaymentScheduleController extends Controller
{
    public function index()
    {
        $schedules = PaymentSchedule::all();
        $members = Member::all();
        return view('payment_schedules/list', compact('schedules', 'members'));
    }

    public function create()
    {
        $members = Member::all();

        return view('payment_schedules/create', compact('members'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'debtor' =>  'required|integer',
            'usurer' =>  'required|integer',
            'debt' =>  'required|integer',
            'date' =>  'required',
            'period' => 'required'
        ]);

        PaymentSchedule::create($data);

        return redirect(route('schedules.index'));
    }

    public function edit(int $id)
    {
        $schedule = PaymentSchedule::find($id);
        $members = Member::all();

        return view('payment_schedules/edit', compact('schedule', 'members'));

    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'debtor' =>  'required|integer',
            'usurer' =>  'required|integer',
            'debt' =>  'required|integer',
            'date' =>  'required',
            'period' => 'required'
        ]);

        $schedule = PaymentSchedule::find($id);

        $schedule->update($data);

        return redirect(route('schedules.index'));
    }

    public function destroy(int $id)
    {
        $schedule = PaymentSchedule::find($id);

        $schedule->delete();

        return redirect(route('schedules.index'));
    }

}
