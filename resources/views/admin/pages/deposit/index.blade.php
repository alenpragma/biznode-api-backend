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
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($deposits as $index => $deposit)
                    <tr>
                        <td>{{ $index + $deposits->firstItem() }}</td>
                        <td>{{ $deposit->details }}</td>
                        <td>{{ $deposit->user->name ?? 'N/A' }}</td>
                        <td>${{$deposit->amount}}</td>

                        <td>
                            <span class="badge
                                @if($deposit->status == 'Pending') badge-warning
                                @elseif($deposit->status == 'Rejected') badge-danger
                                @else badge-success @endif">
                                {{ ucfirst($deposit->status) }}
                            </span>
                        </td>
                        <td>{{ $deposit->created_at?->format('Y-m-d H:i') }}</td>
                       @if($deposit->status == 'Pending')
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-primary"
                                        data-toggle="modal"
                                        data-target="#actionModal{{ $deposit->id }}">
                                    <i class="fas fa-edit"></i> Manage
                                </button>
                            </td>
                       @endif
                    </tr>

                    <!-- Modal -->
                    <div class="modal fade" id="actionModal{{ $deposit->id }}" tabindex="-1" role="dialog" aria-labelledby="actionModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form method="POST" action="{{ route('deposit.update', $deposit->id) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Status</h5>
                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="{{ $deposit->id }}">
                                        <div class="form-group">
                                            <label>Transaction ID</label>
                                            <input type="text" class="form-control" value="{{ $deposit->details }}" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="pending" {{ $deposit->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="rejected" {{ $deposit->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                <option value="completed" {{ $deposit->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success">Update</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                @empty
                    <tr>
                        <td colspan="8" class="text-center">No withdrawals found.</td>
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
