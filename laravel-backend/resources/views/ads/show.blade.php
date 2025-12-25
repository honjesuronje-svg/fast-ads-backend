@extends('layouts.app')

@section('page_title', 'Ad Details')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ad Details</h3>
        <div class="card-tools">
            <a href="{{ route('ads.edit', $ad) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9">{{ $ad->id }}</dd>

            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $ad->name }}</dd>

            <dt class="col-sm-3">Tenant</dt>
            <dd class="col-sm-9">{{ $ad->tenant->name ?? 'N/A' }}</dd>

            <dt class="col-sm-3">Campaign</dt>
            <dd class="col-sm-9">{{ $ad->campaign->name ?? 'N/A' }}</dd>

            <dt class="col-sm-3">VAST URL</dt>
            <dd class="col-sm-9"><a href="{{ $ad->vast_url }}" target="_blank">{{ $ad->vast_url }}</a></dd>

            <dt class="col-sm-3">Duration</dt>
            <dd class="col-sm-9">{{ $ad->duration_seconds }} seconds</dd>

            <dt class="col-sm-3">Ad Type</dt>
            <dd class="col-sm-9">{{ ucfirst($ad->ad_type) }}</dd>

            <dt class="col-sm-3">Click Through URL</dt>
            <dd class="col-sm-9">{{ $ad->click_through_url ?? 'N/A' }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <span class="badge badge-{{ $ad->status === 'active' ? 'success' : 'danger' }}">
                    {{ ucfirst($ad->status) }}
                </span>
            </dd>

            <dt class="col-sm-3">Created At</dt>
            <dd class="col-sm-9">{{ $ad->created_at->format('Y-m-d H:i:s') }}</dd>
        </dl>
    </div>
</div>

<!-- Ad Rules Section -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Ad Rules (Channel Assignment)</h3>
    </div>
    <div class="card-body">
        @if($ad->rules->isEmpty())
            <p class="text-muted">No rules configured. This ad will appear in all channels.</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Operator</th>
                            <th>Value</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ad->rules as $rule)
                            <tr>
                                <td>{{ ucfirst($rule->rule_type) }}</td>
                                <td>{{ $rule->rule_operator }}</td>
                                <td>
                                    @if($rule->rule_type === 'channel' && $rule->rule_operator === 'in')
                                        @php
                                            $values = json_decode($rule->rule_value, true);
                                            if (is_array($values)) {
                                                echo implode(', ', $values);
                                            } else {
                                                echo $rule->rule_value;
                                            }
                                        @endphp
                                    @else
                                        {{ $rule->rule_value }}
                                    @endif
                                </td>
                                <td>{{ $rule->priority }}</td>
                                <td>
                                    <form action="{{ route('ads.rules.destroy', [$ad, $rule]) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Add Rule Form -->
        <div class="mt-4">
            <h5>Add Channel Rule</h5>
            <form action="{{ route('ads.rules.store', $ad) }}" method="POST" id="addRuleForm">
                @csrf
                <input type="hidden" name="rule_type" value="channel">
                <input type="hidden" name="rule_operator" id="rule_operator" value="equals">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Select Channel(s)</label>
                            <div class="border p-3" style="max-height: 200px; overflow-y: auto;">
                                @foreach($channels as $channel)
                                    <div class="form-check">
                                        <input class="form-check-input channel-checkbox" 
                                               type="checkbox" 
                                               name="channels[]" 
                                               value="{{ $channel->slug }}" 
                                               id="channel_{{ $channel->id }}">
                                        <label class="form-check-label" for="channel_{{ $channel->id }}">
                                            {{ $channel->name }} ({{ $channel->slug }})
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted">
                                Select one or more channels. If multiple selected, rule will use "In" operator.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <input type="number" class="form-control" id="priority" name="priority" value="0" min="0">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Rule</button>
            </form>
        </div>
    </div>
    <div class="card-footer">
        <a href="{{ route('ads.index') }}" class="btn btn-default">Back to List</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.channel-checkbox');
    const operatorInput = document.getElementById('rule_operator');
    const form = document.getElementById('addRuleForm');
    
    // Auto-update operator based on checkbox selection
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.channel-checkbox:checked').length;
            if (checkedCount > 1) {
                operatorInput.value = 'in';
            } else if (checkedCount === 1) {
                operatorInput.value = 'equals';
            }
        });
    });
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        const checkedBoxes = document.querySelectorAll('.channel-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one channel');
            return false;
        }
        
        // Get selected channel values
        const values = Array.from(checkedBoxes).map(cb => cb.value);
        
        // Update operator based on selection count
        if (values.length > 1) {
            operatorInput.value = 'in';
        } else {
            operatorInput.value = 'equals';
        }
        
        // Create hidden input with comma-separated values
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'rule_value';
        hidden.value = values.join(',');
        this.appendChild(hidden);
    });
});
</script>
@endsection

