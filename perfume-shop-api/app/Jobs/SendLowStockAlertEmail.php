<?php

namespace App\Jobs;

use App\Mail\LowStockAlert;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLowStockAlertEmail implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Inventory $inventory
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send to all admin users
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new LowStockAlert($this->inventory));
        }
    }
}
