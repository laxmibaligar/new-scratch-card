<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScratchCardGift;
use App\Models\ScratchCard;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class ScratchCardController extends Controller
{
    public function showScratchCard($hash = null)
    {
        $employee = Employee::where('hash', $hash)->first();

       
        if (!empty($hash) && empty($employee)){
            return view('invalidUser');
        }

        $scratchCard = ScratchCard::where('employee_id', $employee->id)->first();

        $gift = null;

       if ($scratchCard && $scratchCard->gift_id) {
            $gift = ScratchCardGift::find($scratchCard->gift_id);
        }

     
        $data=[
            'employee' => $employee,
            'gift' => $gift,
            'scratchCard' => $scratchCard
        ];

                // dd($data);


        return view('scratchCard.scratchCard',$data); 
    }

    public function saveScratchCard(Request $request)
    {


        $employee_id =  $request->employee_id;
        $gift_id = $request->gift_id;

        $scratchCard = ScratchCard::where('employee_id', $employee_id)
        ->first();

        // dd($scratchCard);




        $scratchCard->is_done = 1;
        $scratchCard->save();

        return response()->json(['message' => 'Scratch card marked as done.']);
    }




}
