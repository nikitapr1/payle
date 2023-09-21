<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::all();
        return view('members/list', compact('members'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => ['required'],
            'surname' => ['required'],
            'phone' => ['required', 'unique:members'],

        ]);


        Member::create($validated);

        return redirect(route('members.index'));

    }

    public function create()
    {

        return view('members/create');
    }

    public function edit(int $id)
    {
        $member = Member::find($id);
        return view('members/edit', compact('member'));
    }

    public function update(int $id, Request $request)
    {
        $validated = $request->validate([
            'name' => ['required'],
            'surname' => ['required'],
            'phone' => ['required', 'unique:members', 'integer'],

        ]);
        $member = Member::find($id);

        $member->update($validated);

        return redirect(route('members.index'));
    }

    public function destroy(int $id){
        $member = Member::query()->find($id);
        $member->delete();

        return redirect(route('members.index'));
    }
}
