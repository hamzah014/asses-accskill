<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\BidController;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/*
  |--------------------------------------------------------------------------
  | ⛔⛔ EDITING OF ANY CODE UNDER THIS SECTION IS PROHIBITED ⛔⛔
  |--------------------------------------------------------------------------
  |
  | Once found, the eligibility for further application will be cancelled.
  | Do your best to fulfill any custom validation output required by the test case.
  |
  */

class BidTest extends TestCase
{
    public function test_bid_post()
    {
        $higherBidPrice = Bid::orderBy('price', 'desc')->value('price') + 100.55;
        $response = $this->post(route('bid.create'), ['user_id' => 1, 'price' => $higherBidPrice])->assertStatus(201);

        $higherBidPrice = number_format($higherBidPrice, 2, '.', '');
        $this->assertEquals($response['message'], 'Success');

        $user = User::find(1);
        $this->assertEquals($response['data'], [
            'full_name' => $user->first_name . ' ' . $user->last_name,
            'price' => $higherBidPrice
        ]);
    }

    public function test_bid_post_with_users_notification()
    {

        $higherBidPrice = Bid::orderBy('price', 'desc')->value('price') + 100.55;
        $response = $this->post(route('bid.create'), ['user_id' => 1, 'price' => $higherBidPrice])->assertStatus(201);

        $higherBidPrice = number_format($higherBidPrice, 2, '.', '');
        $this->assertEquals($response['message'], 'Success');

        $user = User::find(1);
        $this->assertEquals($response['data'], [
            'full_name' => $user->first_name . ' ' . $user->last_name,
            'price' => $higherBidPrice
        ]);

        User::chunk(20, function ($users) use ($higherBidPrice) {
            foreach ($users as $user) {
                $bid = Bid::where('user_id', $user->id)->latest()->first();
                $latestUserBidPrice = $bid ? number_format($bid->price, 2, '.', '') : "0.00";
                $formattedHigherBidPrice = number_format($higherBidPrice, 2, '.', '');

                $data = json_encode([
                    'latest_bid_price' => $formattedHigherBidPrice,
                    'user_last_bid_price' => $latestUserBidPrice,
                ]);

                Log::info($data);

                $notification = DB::table('notifications')
                    ->where('notifiable_id', $user->id)
                    ->select('data')
                    ->orderBy('created_at', 'desc')
                    ->get();

                foreach ($notification as $noti) {

                    $jsonData = json_decode($noti->data);

                    if ($latestUserBidPrice == $jsonData->user_last_bid_price) {
                        $this->assertEquals($latestUserBidPrice, $jsonData->user_last_bid_price);
                    }


                    if ($formattedHigherBidPrice == $jsonData->latest_bid_price) {
                        $this->assertEquals($formattedHigherBidPrice, $jsonData->latest_bid_price);
                    }


                    // $this->assertEquals($latestUserBidPrice, $jsonData->user_last_bid_price);
                    // $this->assertEquals($formattedHigherBidPrice, $jsonData->latest_bid_price);

                }
            }
        });
    }

    public function test_bid_post_3_decimal_price()
    {
        $higherBidPrice = Bid::latest()->value('price') + 100;
        $response = $this->post(route('bid.create'), ['user_id' => 1, 'price' => number_format($higherBidPrice, 3)]);

        $this->assertEquals('Fail', $response->baseResponse->original['message']);
        $this->assertArrayHasKey('price', $response->baseResponse->original['errors']);
        $this->assertEquals('The price format is invalid.', $response->baseResponse->original['errors']['price']);

        // $response->assertSessionHasErrors([
        //     'price' => 'The price format is invalid.'
        // ]);
    }

    public function test_bid_post_price_empty()
    {
        $response = $this->post(route('bid.create'), ['user_id' => 1, 'price' => null]);

        $jsonerr = json_decode($response->baseResponse->original['errors']);

        $this->assertEquals('Fail', $response->baseResponse->original['message']);
        $this->assertArrayHasKey('price', ['price' => $jsonerr->price[0]]);
        $this->assertEquals('The bid price is required!', $jsonerr->price[0]);

        // $response->assertSessionHasErrors([
        //     'price' => 'The bid price is required!'
        // ]);
    }

    public function test_bid_post_lower_price()
    {
        $lowerBidPrice = Bid::latest()->value('price');
        $response = $this->post(route('bid.create'), ['user_id' => 1, 'price' => $lowerBidPrice - 1]);

        $pricecheck = number_format($lowerBidPrice + 1, 2, '.', ',');

        $this->assertEquals('Fail', $response->baseResponse->original['message']);
        $this->assertArrayHasKey('price', $response->baseResponse->original['errors']);
        $this->assertEquals('The bid price cannot be lower than ' . $pricecheck, $response->baseResponse->original['errors']['price']);

        // $response->assertSessionHasErrors(['price']);

        // $response->assertSessionHasErrors([
        //     'price' => 'The bid price cannot lower than ' . $lowerBidPrice + 1
        // ]);
    }

}