<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrderRefundJob;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRefundsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'orders:process-refunds';

    /**
     * The console command description.
     */
    protected $description = 'Process all orders requiring refunds due to concurrent stock depletion';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning for orders requiring refunds...');

        $orders = Order::where('status', 'refund_required')->get();

        $count = $orders->count();

        if ($count === 0) {
            $this->info('No orders requiring refunds found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} order(s) requiring refunds.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $dispatched = 0;

        foreach ($orders as $order) {
            try {
                ProcessOrderRefundJob::dispatch($order);
                $dispatched++;
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch refund job', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]);
                $this->error("Failed to dispatch refund for order #{$order->id}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Dispatched {$dispatched} refund job(s) successfully.");

        return Command::SUCCESS;
    }
}
