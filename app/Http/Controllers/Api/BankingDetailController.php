<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BankingDetail;

class BankingDetailController extends Controller
{
    public function index(Request $req) {
        $user = $req->user();
        $details = $user->bankingDetails()->get()->map(function($d){
            return [
                'id'=>$d->id,
                'bank_name'=>$d->bank_name,
                'account_holder_name'=>$d->account_holder_name,
                'account_number_masked'=>$d->masked_account(),
                'ifsc'=>$d->ifsc,
                'is_default'=>$d->is_default,
                'status'=>$d->status
            ];
        });
        return response()->json(['success'=>true,'data'=>$details]);
    }

    public function store(Request $req) {
        $user = $req->user();
        $data = $req->validate([
            'bank_name'=>'nullable|string|max:255',
            'account_holder_name'=>'required|string|max:255',
            'account_number'=>'required|string|max:64',
            'ifsc'=>'nullable|string|max:20',
            'branch'=>'nullable|string|max:255',
            'currency'=>'nullable|string|max:10',
        ]);

        $detail = $user->bankingDetails()->create($data);

        return response()->json(['success'=>true,'data'=>[
            'id'=>$detail->id,
            'bank_name'=>$detail->bank_name,
            'account_holder_name'=>$detail->account_holder_name,
            'account_number_masked'=>$detail->masked_account(),
            'ifsc'=>$detail->ifsc,
            'is_default'=>$detail->is_default
        ]], 201);
    }

    public function show(Request $req, $id) {
        $user = $req->user();
        $d = $user->bankingDetails()->findOrFail($id);
        return response()->json(['success'=>true,'data'=>[
            'id'=>$d->id,'bank_name'=>$d->bank_name,'account_holder_name'=>$d->account_holder_name,
            'account_number_masked'=>$d->masked_account(),'ifsc'=>$d->ifsc,'is_default'=>$d->is_default
        ]]);
    }

    public function update(Request $req, $id) {
        $user = $req->user();
        $d = $user->bankingDetails()->findOrFail($id);
        $data = $req->validate([
            'bank_name'=>'nullable|string|max:255',
            'account_holder_name'=>'required|string|max:255',
            'account_number'=>'required|string|max:64',
            'ifsc'=>'nullable|string|max:20',
            'branch'=>'nullable|string|max:255',
            'currency'=>'nullable|string|max:10',
        ]);
        $d->update($data);
        return response()->json(['success'=>true,'data'=>[
            'id'=>$d->id,'account_number_masked'=>$d->masked_account()
        ]]);
    }

    public function destroy(Request $req, $id) {
        $user = $req->user();
        $d = $user->bankingDetails()->findOrFail($id);
        // if default, you may want to prevent deletion or set another as default
        $d->delete();
        return response()->json(['success'=>true]);
    }

    public function setDefault(Request $req, $id) {
        $user = $req->user();
        $d = $user->bankingDetails()->findOrFail($id);

        DB::transaction(function() use($user,$d){
            $user->bankingDetails()->update(['is_default'=>false]);
            $d->update(['is_default'=>true]);
        });

        return response()->json(['success'=>true,'message'=>'Default updated']);
    }
}
