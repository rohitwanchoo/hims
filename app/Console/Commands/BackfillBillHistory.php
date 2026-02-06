<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillHistory;
use Illuminate\Console\Command;

class BackfillBillHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:backfill-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create initial history records for existing bills that have no history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to backfill bill history...');

        // Get all bills that don't have any history records
        $bills = Bill::whereDoesntHave('history')->get();

        if ($bills->isEmpty()) {
            $this->info('No bills need backfilling. All bills have history records.');
            return 0;
        }

        $this->info("Found {$bills->count()} bills without history. Creating records...");

        $bar = $this->output->createProgressBar($bills->count());
        $bar->start();

        $created = 0;
        foreach ($bills as $bill) {
            try {
                BillHistory::create([
                    'bill_id' => $bill->bill_id,
                    'action' => 'created',
                    'subtotal' => $bill->subtotal,
                    'discount_amount' => $bill->discount_amount,
                    'discount_percent' => $bill->discount_percent,
                    'tax_amount' => $bill->tax_amount,
                    'adjustment' => $bill->adjustment,
                    'total_amount' => $bill->total_amount,
                    'paid_amount' => $bill->paid_amount,
                    'due_amount' => $bill->due_amount,
                    'payment_mode' => $bill->payment_mode,
                    'payment_status' => $bill->payment_status,
                    'changed_by' => $bill->created_by,
                    'changes' => [],
                    'created_at' => $bill->created_at,
                    'updated_at' => $bill->created_at,
                ]);
                $created++;
            } catch (\Exception $e) {
                $this->error("Failed to create history for bill {$bill->bill_id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully created {$created} bill history records!");

        return 0;
    }
}
