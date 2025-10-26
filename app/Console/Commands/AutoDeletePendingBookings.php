<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\BookingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoDeletePendingBookings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:auto-delete';

    /**
     * The console command description.
     */
    protected $description = 'Delete or expire bookings and booking requests after 2 minutes if not accepted';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = Carbon::now()->subMinutes(2);

        DB::beginTransaction();
        try {
            // 1️⃣ Find bookings still pending and older than 2 minutes
            $expiredBookings = Booking::where('status', 'pending')
                ->where('created_at', '<', $cutoff)
                ->get();

            foreach ($expiredBookings as $booking) {
                // 2️⃣ Expire all related booking requests
                BookingRequest::where('booking_id', $booking->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);

                // 3️⃣ Delete or mark booking as cancelled
                $booking->delete(); // 🚀 delete it completely
                // OR use this instead if you want to mark instead of delete:
                // $booking->update(['status' => 'expired']);
            }

            DB::commit();

            $this->info('✅ Auto-delete completed. ' . count($expiredBookings) . ' expired bookings handled.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
