<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\BookingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        Log::info('🕐 [AutoDeletePendingBookings] Starting process at: ' . now());

        DB::beginTransaction();
        try {
            // 1️⃣ Find bookings still pending and older than 2 minutes
            $expiredBookings = Booking::where('status', 'pending')
                ->where('created_at', '<', $cutoff)
                ->get();

            Log::info('🔍 Found ' . $expiredBookings->count() . ' pending bookings older than 2 minutes.');

            foreach ($expiredBookings as $booking) {
                Log::info('🚮 Deleting booking ID: ' . $booking->id);

                // 2️⃣ Expire all related booking requests
                $updated = BookingRequest::where('booking_id', $booking->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);

                Log::info('📦 Expired ' . $updated . ' related booking requests for booking ID: ' . $booking->id);

                // 3️⃣ Delete or mark booking as cancelled
                $booking->delete(); // 🚀 delete it completely
            }

            DB::commit();

            Log::info('✅ Auto-delete completed. ' . $expiredBookings->count() . ' expired bookings handled.');
            $this->info('✅ Auto-delete completed. ' . $expiredBookings->count() . ' expired bookings handled.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [AutoDeletePendingBookings] Error: ' . $e->getMessage());
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
