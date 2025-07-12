<div class="form-group">
    <label>Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name ?? '') }}" required>
</div>

<div class="form-group">
    <label>Amount</label>
    <input type="number" name="min_amount" class="form-control" value="{{ old('min_amount', $plan->min_amount ?? 0) }}" required>
</div>


<div class="form-group">
    <label>Interest Rate (%)</label>
    <input type="number" name="interest_rate" class="form-control" step="0.01" value="{{ old('interest_rate', $plan->interest_rate ?? 0) }}" required>
</div>

<div class="form-group">
    <label>Duration</label>
    <input type="number" name="duration" class="form-control" value="{{ old('duration', $plan->duration ?? 0) }}">
</div>

<div class="form-group">
    <label>Return Type</label>
    <select name="return_type" class="form-control" required>
        <option value="daily" {{ old('return_type', $plan->return_type ?? '') == 'daily' ? 'selected' : '' }}>Daily</option>
        <option value="weekly" {{ old('return_type', $plan->return_type ?? '') == 'weekly' ? 'selected' : '' }}>Weekly</option>
        <option value="monthly" {{ old('return_type', $plan->return_type ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
    </select>
</div>

<div class="form-group">
    <label>Status</label>
    <select name="active" class="form-control">
        <option value="1" {{ old('active', $plan->active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
        <option value="0" {{ old('active', $plan->active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
    </select>
</div>
