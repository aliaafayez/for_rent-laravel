<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Payment;
use App\Models\Paymentmethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Charge;
use Stripe\Stripe;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function payment(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        Charge::create ([
            "amount" => $request->price * 100,
            "currency" => "usd",
            "source" => $request->token,
            "description" => "Test payment for booking "
        ]);
        if (Paymentmethod::where([['advertisement_id',$request->adver_id],['user_id',Auth::user()->id]])->exists()){
            $result=Paymentmethod::where([['advertisement_id',$request->adver_id],['user_id',Auth::user()->id]])->delete();
        }
    
        $payment=Paymentmethod::create([
            'user_id'=>Auth::user()->id,
            'owner_id'=>$request->owner_id,
            'advertisement_id'=>$request->adver_id,
            'amount'=>$request->price
        ]);

        $advertisement=Advertisement::where([['id',$payment->advertisement_id],['control','accepted'],['user_id',$payment->owner_id]])->first();
        $advertisement->status='rented';
        $advertisement->save();
        
        //start payment notification
        $user_id = Auth::user()->id;
        $owner_id = $request->owner_id;
        $user = User::find($user_id);
        $renter_name = $user->name;
        $payment_notf_data = [

            "message" => "تم الدفع من خلال الموقع من المستاجر" . ': '. $renter_name . "  ". " للعقار : " . $advertisement->title ,
            
            "owner_id"=> $owner_id,
           
        ];
        event(new PaymentNotification( $payment_notf_data ));

        return  response()->json(['success'=>true,'advertisement'=>$advertisement,'payment'=>$payment]);
    }



    public function renterPayment(){
        $data=Paymentmethod::where('user_id',Auth::user()->id)->with('user','owner')->get();

        if($data->isEmpty()) {
            return response()->json(['message'=> 'لا توجد اعلانات مؤجره','count'=>count($data)]);

        }else{
            $advertisements=[];
            foreach ($data as $value){
                $advertisements[]=Advertisement::where('id',$value->advertisement_id)->withCount('ratings')->withAvg("ratings", "count")->with('favourit',"advertisement_image")->get();

            }
            return response()->json(["allAdvertisements" => $advertisements,'count'=>count($advertisements)]);

        }
    }

    public function ownerPayment(){
        $data=Paymentmethod::where('owner_id',Auth::user()->id)->with('advertisement.advertisement_image','user','owner')->get();
        if($data->isEmpty()) {
            return response()->json(['message'=> 'لا توجد اعلانات مؤجره','count'=>count($data)]);

        }else{
            return response()->json(['success'=>true,'data'=>$data,'count'=>count($data)]);

        }
    }

    public function paymentAdmin(){
        $data=Paymentmethod::with('advertisement.advertisement_image','user','owner')->get();
        if($data->isEmpty()) {
            return response()->json(['message'=> 'لا توجد اعلانات مؤجره','count'=>count($data)]);
        }else{
            return response()->json(['success'=>true,'data'=>$data,'count'=>count($data)]);

        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
