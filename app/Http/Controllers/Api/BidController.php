<?php

namespace App\Http\Controllers\Api;

use App\Events\BidSaved;
use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\User;
use App\Notifications\BidNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Notification;

class BidController extends Controller
{
    protected $bidModel;

    public function __construct()
    {
        $this->bidModel = new Bid();
    }

    public function create(Request $request)
    {
        $messages = [
            'price.required' => 'The bid price is required!',
            'user_id.required' => 'User ID is required.',
            'user_id.exists' => 'The selected user ID does not exist.'
        ];

        $validation = [
            'price' => ['required', 'between:0,999999.99'],
            'user_id' => 'required|exists:users,id'
        ];

        Log::info('Bid request', $request->all());

        // Validate the request
        $validator = Validator::make($request->all(), $validation, $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Fail',
                'errors' => $validator->messages()
            ], 422); // 422 for validation errors
        }

        // Find user by ID
        $user = User::find($request->user_id);

        // Get the highest existing bid price
        $getHighestBid = Bid::max('price');

        $amount = explode('.', $request->price);
        if (isset($amount[1]) && strlen($amount[1]) > 2) {

            return response()->json([
                'message' => 'Fail',
                'errors' => [
                    'price' => 'The price format is invalid.'
                ]
            ], 400);
        }

        // Check if the new bid price is lower than or equal to the highest bid
        if ($getHighestBid !== null && $request->price <= $getHighestBid) {
            return response()->json([
                'message' => 'Fail',
                'errors' => [
                    'price' => 'The bid price cannot be lower than ' . number_format($getHighestBid + 1, 2)
                ]
            ], 400);
        }

        try {

            // Create and save the new bid
            $bid = new Bid();
            $bid->price = $request->price;
            $bid->user_id = $request->user_id;
            $bid->save();

            // Prepare response data
            $fullname = $user->first_name . " " . $user->last_name;
            $formattedPrice = number_format($request->price, 2, '.', '');

            event(new BidSaved($bid));

            // Log successful response details
            Log::info('Bid created successfully', [
                'user_id' => $user->id,
                'price' => $formattedPrice
            ]);

            return response()->json([
                'message' => 'Success',
                'data' => [
                    'full_name' => $fullname,
                    'price' => $formattedPrice
                ]
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Bid creation error', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Fail',
                'errors' => 'Error! ' . $e->getMessage()
            ], 500);
        }
    }

}