<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class BidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $latestBidPrice;
    protected $userLastBidPrice;

    /**
     * Create a new notification instance.
     *
     * @param array $data
     */
    public function __construct($latestBidPrice, $userLastBidPrice)
    {
        $this->latestBidPrice = $latestBidPrice;
        $this->userLastBidPrice = $userLastBidPrice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database']; // Adjust based on your needs
    }

    public function toDatabase($notifiable)
    {
        $data = [
            'latest_bid_price' => (float) round($this->latestBidPrice, 2),
            'user_last_bid_price' => $this->userLastBidPrice
        ];
        return $data;
    }

}