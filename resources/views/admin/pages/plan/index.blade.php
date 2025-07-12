@extends('admin.layouts.app')

@section('content')
    {{-- SweetAlert success message --}}
    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    @endif

    {{-- Import/Export Modal --}}
    <div class="modal fade" id="importExportModal" tabindex="-1" aria-labelledby="importExportLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import / Export Plans</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Import Form --}}
                    <form action="{{ route('all-plan.import') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <div class="input-group">
                            <input type="file" name="file" class="form-control form-control-sm" required>
                            <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" type="submit">
                                <i class="bi bi-upload"></i> Import
                            </button>
                        </div>
                    </form>

                    {{-- Export Button --}}
                    <a href="{{ route('all-plan.export') }}" class="btn btn-sm btn-success d-flex align-items-center gap-1">
                        <i class="bi bi-download"></i> Export
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">All Plans</h4>
            <div class="d-flex gap-2 align-items-center">
                <a href="{{ route('all-plan.create') }}" class="btn btn-success btn-sm">+ Add New Plan</a>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importExportModal">
                    <i class="bi bi-box-arrow-down">Import/Export</i>
                </button>
            </div>
        </div>

        <div class="card-body table-responsive">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <select name="filter" class="form-control">
                            <option value="">-- Filter Plans --</option>
                            <option value="active" {{ request('filter') == 'active' ? 'selected' : '' }}>Active Plans</option>
                            <option value="inactive" {{ request('filter') == 'inactive' ? 'selected' : '' }}>Inactive Plans</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('all-plan.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <table class="table table-striped table-hover mt-3">
                <thead class="thead-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Min Amount</th>
                    <th>Max Amount</th>
                    <th>Interest</th>
                    <th>Duration</th>
                    <th>Return Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($plans as $index => $plan)
                    <tr>
                        <td>{{ $index + $plans->firstItem() }}</td>
                        <td>{{ $plan->name }}</td>
                        <td>${{ number_format($plan->min_amount, 2) }}</td>
                        <td>${{ number_format($plan->max_amount, 2) }}</td>
                        <td>{{ $plan->interest_rate }}%</td>
                        <td>{{ $plan->duration == 0 ? 'Unlimited' : $plan->duration . ' days' }}</td>
                        <td>{{ ucfirst($plan->return_type) }}</td>
                        <td>
                            <span class="badge {{ $plan->active ? 'bg-success' : 'bg-danger' }}">
                                {{ $plan->active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('all-plan.edit', $plan->id) }}" class="btn btn-sm btn-info">Edit</a>
                            {{--                            <form action="{{ route('all-plan.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">--}}
                            {{--                                @csrf--}}
                            {{--                                @method('DELETE')--}}
                            {{--                                <button class="btn btn-sm btn-danger">Delete</button>--}}
                            {{--                            </form>--}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No plans found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $plans->links('admin.layouts.partials.__pagination') }}
            </div>
        </div>
    </div>

    {{-- Ensure Bootstrap Icons are loaded in your main layout --}}
@endsection
