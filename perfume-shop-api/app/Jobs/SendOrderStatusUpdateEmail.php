<?php

namespace App\Jobs;

use App\Mail\OrderStatusUpdate;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusUpdateEmail implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order,
        public string $status
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->order->user->email)->send(new OrderStatusUpdate($this->order, $this->status));
    }
}
