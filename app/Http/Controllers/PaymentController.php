<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {

        $payments = Payment::all();
        $members = Member::all();
        return view('payments/list', compact('payments', 'members'));

    }

    public function create()
    {
        $members = Member::all();
        return view('payments/create', compact('members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
                'sum' => ['required', 'integer'],
                'member_id' => ['required', 'integer']
            ]
        );
        Payment::create($validated);

        return redirect(route('payments.index'));

    }

    public function edit(int $id)
    {
        $payment = Payment::find($id);
        $members = Member::all();
        return view('payments/edit', compact('payment', 'members'));

    }

    public function update(int $id, Request $request)
    {
        $validated = $request->validate([
                'sum' => ['required', 'integer'],
                'member_id' => ['required', 'integer']
            ]
        );

        $payment = Payment::find($id);

        $payment->update($validated);

        return redirect(route('payments.index'));
    }

    public function destroy(int $id)
    {
        $payment = Payment::find($id);

        $payment->delete();

        return redirect(route('payments.index'));

    }
}
