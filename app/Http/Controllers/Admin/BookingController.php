<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * عرض قائمة الحجوزات مع إمكانية الفلترة بالحالة
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = Booking::query()->latest();

        if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        $bookings = $query->get();
        // $bookings = $query->paginate(15)->withQueryString();

        $stats = [
            'pending'   => Booking::where('status', 'pending')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
        ];

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'stats'    => $stats,
            'status'   => $status,
        ]);
    }

    /**
     * عرض تفاصيل حجز معيّن + سجل تغيّر الحالة
     */
    public function show(Booking $booking)
    {
        $booking->load([
            'student',
            'teacherProfile.user',
            'city',
            'deliveryMode',
            'statusHistories',
            // ✅ Batch 1
            'meeting',
        ]);

        $changes = $booking->statusHistories()
            ->latest()
            ->get();

        return view('admin.bookings.show', [
            'booking' => $booking,
            'changes' => $changes,
        ]);
    }

    /**
     * صفحة تعديل بيانات الحجز
     */
    public function edit(Booking $booking)
    {
        $cities = [
            'Abu Dhabi',
            'Dubai',
            'Sharjah',
            'Ajman',
            'UAQ',
            'RAK',
            'Fujairah',
            'Al Ain',
        ];

        $statusOptions = [
            'pending'   => 'قيد الانتظار (pending)',
            'confirmed' => 'مؤكّد (confirmed)',
            'cancelled' => 'ملغى (cancelled)',
        ];

        return view('admin.bookings.edit', [
            'booking'       => $booking,
            'cities'        => $cities,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * حفظ تعديل بيانات الحجز (المبلغ – المدينة – الحالة)
     * ✅ Batch 1: لو الحالة اتحولت confirmed -> ننشئ/نحدّث Meeting
     * ✅ Batch 1: لو الحالة اتحولت cancelled -> نقفل Meeting
     */
    public function update(Request $request, Booking $booking, MeetingService $meetingService)
    {
        $validated = $request->validate([
            'total_amount' => ['required', 'numeric', 'min:0'],
            'city'         => ['nullable', 'string', 'max:255'],
            'status'       => ['required', 'in:pending,confirmed,cancelled'],
        ]);

        $original = $booking->getOriginal(['total_amount', 'city', 'status']);

        // ✅ لو الحالة اتغيّرت من edit، نسجّل مين/إمتى/المصدر
        $statusChanged = isset($validated['status']) && ($original['status'] ?? null) !== $validated['status'];
        if ($statusChanged) {
            $validated['status_updated_by']     = Auth::id();
            $validated['status_updated_at']     = now();
            $validated['status_updated_source'] = 'admin';
        }

        $booking->update($validated);

        // ✅ Batch 1: مزامنة Meeting حسب الحالة الجديدة (فقط لو status اتغيّر)
        if ($statusChanged) {
            $this->syncMeetingOnStatusChange($booking, $validated['status'], $meetingService);
        }

        $this->logChanges($booking, $original, $validated);

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('success', 'تم حفظ التعديلات بنجاح.');
    }

    /**
     * تحديث حالة الحجز فقط (من الأزرار السريعة)
     * ✅ Batch 1: لو confirmed -> ensureMeeting
     * ✅ Batch 1: لو cancelled -> close meeting
     */
    public function updateStatus(Request $request, Booking $booking, MeetingService $meetingService)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled'],
        ]);

        $originalStatus = $booking->getOriginal('status');

        if ($originalStatus === $validated['status']) {
            return back()->with('info', 'لم يتم تغيير حالة الحجز.');
        }

        $booking->update([
            'status'                => $validated['status'],
            'status_updated_by'     => Auth::id(),
            'status_updated_at'     => now(),
            'status_updated_source' => 'admin',
        ]);

        // ✅ Batch 1: مزامنة Meeting حسب الحالة
        $this->syncMeetingOnStatusChange($booking, $validated['status'], $meetingService);

        $this->logChanges(
            $booking,
            ['status' => $originalStatus],
            ['status' => $validated['status']]
        );

        return back()->with('success', 'تم تحديث حالة الحجز بنجاح.');
    }

    /**
     * ✅ Batch 1: Helper لمزامنة Meeting على حسب status
     */
    protected function syncMeetingOnStatusChange(Booking $booking, string $newStatus, MeetingService $meetingService): void
    {
        try {
            // confirmed => create/refresh meeting window
            if ($newStatus === 'confirmed') {
                $meetingService->ensureMeetingForBooking($booking);
                return;
            }

            // cancelled => close meeting immediately if exists
            if ($newStatus === 'cancelled') {
                $booking->loadMissing(['meeting']);
                if ($booking->meeting) {
                    $booking->meeting->status = 'cancelled';
                    $booking->meeting->actual_ended_at = now();
                    $booking->meeting->allow_join_until = now(); // قفل فوري
                    $booking->meeting->save();
                }
            }
        } catch (\Throwable $e) {
            // ما نكسرش تحديث الحالة بسبب meeting
        }
    }

    /**
     * دالة مساعدة لتسجيل التغييرات (مُعطَّلة حاليًا)
     */
    protected function logChanges(Booking $booking, array $original, array $updates): void
    {
        return;
    }
}
