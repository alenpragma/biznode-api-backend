@extends('admin.layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">All Deposit</h4>
        </div>

        <div class="card-body table-responsive">
            <form method="GET" action="{{ route('deposit.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <select name="filter" class="form-control">
                            <option value="">-- Filter Status --</option>
                            <option value="pending" {{ request('filter') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="rejected" {{ request('filter') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="completed" {{ request('filter') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('deposit.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <table class="table table-striped table-hover mt-3">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>TrxID:</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Amount</th>
                        {{-- <th>Status</th> --}}
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deposits as $index => $deposit)
                        <tr>
                            <td>{{ $index + $deposits->firstItem() }}</td>
                            <td>{{ $deposit->transaction_id }}</td>
                            <td>{{ $deposit->user->name ?? 'N/A' }}</td>
                            <td>{{ $deposit->user->email ?? 'N/A' }}</td>
                            <td>${{ $deposit->amount }}</td>
                            {{-- <td>
                                <span class="badge
                                    @if($deposit->status == '0') badge-warning
                                    @elseif($deposit->status == '1') badge-danger
                                    @else badge-success @endif">
                                    {{ ucfirst($deposit->status) }}
                                </span>
                            </td> --}}
                            <td>{{ $deposit->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No Deposits found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $deposits->appends(request()->query())->links('admin.layouts.partials.__pagination') }}
            </div>
        </div>

        @if(session('success'))
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '{{ session('success') }}',
                    confirmButtonColor: '#3085d6',
                    timer: 3000,
                    timerProgressBar: true
                });
            </script>
        @endif

        @if(session('error'))
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    confirmButtonColor: '#d33',
                    timer: 3000,
                    timerProgressBar: true
                });
            </script>
        @endif
    </div>
@endsection
