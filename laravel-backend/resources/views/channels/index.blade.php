@extends('layouts.app')

@section('page_title', 'Channels')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Channels List</h3>
        <div class="card-tools">
            <a href="{{ route('channels.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Channel
            </a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Tenant</th>
                    <th>Status</th>
                    <th>SSAI URL</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channels as $channel)
                <tr>
                    <td>{{ $channel->id }}</td>
                    <td>{{ $channel->name }}</td>
                    <td>{{ $channel->slug }}</td>
                    <td>{{ $channel->tenant->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-{{ $channel->status === 'active' ? 'success' : 'danger' }}">
                            {{ ucfirst($channel->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="input-group input-group-sm" style="min-width: 300px;">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   value="https://doubleclick.wkkworld.com/fast/{{ $channel->tenant->slug }}/{{ $channel->slug }}.m3u8" 
                                   readonly 
                                   style="font-size: 11px;">
                            <div class="input-group-append">
                                <button class="btn btn-info btn-sm" 
                                        type="button" 
                                        onclick="copySSAIUrl('https://doubleclick.wkkworld.com/fast/{{ $channel->tenant->slug }}/{{ $channel->slug }}.m3u8', this)"
                                        title="Copy SSAI URL">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('channels.show', $channel) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('channels.edit', $channel) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('channels.destroy', $channel) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No channels found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $channels->links() }}
    </div>
</div>

<script>
function copySSAIUrl(url, button) {
    // Create temporary input element
    const tempInput = document.createElement('input');
    tempInput.value = url;
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        // Show success feedback
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.remove('btn-info');
        button.classList.add('btn-success');
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add('btn-info');
        }, 2000);
    } catch (err) {
        document.body.removeChild(tempInput);
        alert('Failed to copy. URL: ' + url);
    }
}
</script>
@endsection

