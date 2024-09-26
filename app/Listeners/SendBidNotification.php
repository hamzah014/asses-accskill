<?php

namespace App\Listeners;

use App\Events\BidSaved;
use App\Models\Bid;
use App\Models\User;
use App\Notifications\BidNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendBidNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(BidSaved $event)
    {

        $user = $event->bid->user; // Get the user who made the bid

        // Get the latest bid price for the user
        $latestUserBidPrice = $user->bids()->latest()->value('price');
        $latestUserBidPrice = $latestUserBidPrice ? number_format($latestUserBidPrice, 2, '.', '') : "0.00";

        // Send notification with correctly formatted data
        $user->notify(new BidNotification($event->bid->price, $latestUserBidPrice));
    }
}