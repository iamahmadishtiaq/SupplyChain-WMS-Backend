<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\SalesOrder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateDispatchInvoiceJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public SalesOrder $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Real-world scenario: PDF invoice generation & S3 storage upload
        Log::info("Generating official tax invoice for dispatches Order #{$this->order->order_number} (Customer ID: {$this->order->customer_id}). Total: PKR {$this->order->total_amount}");
    }
}
