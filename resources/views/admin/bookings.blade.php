@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h2 class="mb-4">Bookings List</h2>

    {{-- ✅ فورم الفلترة --}}
    <div class="card mb-3">
        <div class="card-header">
            Filter Bookings
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.bookings.index') }}" class="row g-3">

                {{-- فلترة بالحالة --}}
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending"   @if(request('status') === 'pending') selected @endif>Pending</option>
                        <option value="confirmed" @if(request('status') === 'confirmed') selected @endif>Confirmed</option>
                        <option value="cancelled" @if(request('status') === 'cancelled') selected @endif>Cancelled</option>
                    </select>
                </div>

                {{-- من تاريخ --}}
                <div class="col-md-3">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" name="from_date" id="from_date"
                        class="form-control"
                        value="{{ request('from_date') }}">
                </div>

                {{-- إلى تاريخ --}}
                <div class="col-md-3">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" name="to_date" id="to_date"
                        class="form-control"
                        value="{{ request('to_date') }}">
                </div>

                {{-- الأزرار --}}
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Apply Filters
                    </button>

                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- جدول الحجوزات --}}
    <div class="card">
        <div class="card-header">
            All Bookings
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Teacher</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Booking Date</th>
                            <th>Created At</th>
                            <th style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>{{ optional($booking->student)->name ?? '-' }}</td>
                                <td>{{ optional($booking->teacher)->name ?? '-' }}</td>
                                <td>{{ optional($booking->city)->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ $booking->status }}
                                    </span>
                                </td>
                                <td>{{ $booking->booking_date ?? '-' }}</td>
                                <td>{{ $booking->created_at }}</td>
                                <td class="d-flex gap-1">
                                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-primary">
                                        View
                                    </a>
                                    <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-sm btn-outline-secondary">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-3">
                                    لا توجد حجوزات حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($bookings instanceof \Illuminate\Pagination\AbstractPaginator)
            <div class="card-footer">
                {{-- ✅ نحافظ على الفلتر مع الـ pagination --}}
                {{ $bookings->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
