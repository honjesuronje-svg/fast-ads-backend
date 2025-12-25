<?php $__env->startSection('page_title', 'Channels'); ?>

<?php $__env->startSection('page_content'); ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Channels List</h3>
        <div class="card-tools">
            <a href="<?php echo e(route('channels.create')); ?>" class="btn btn-primary btn-sm">
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
                <?php $__empty_1 = true; $__currentLoopData = $channels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $channel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($channel->id); ?></td>
                    <td><?php echo e($channel->name); ?></td>
                    <td><?php echo e($channel->slug); ?></td>
                    <td><?php echo e($channel->tenant->name ?? 'N/A'); ?></td>
                    <td>
                        <span class="badge badge-<?php echo e($channel->status === 'active' ? 'success' : 'danger'); ?>">
                            <?php echo e(ucfirst($channel->status)); ?>

                        </span>
                    </td>
                    <td>
                        <div class="input-group input-group-sm" style="min-width: 300px;">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   value="https://doubleclick.wkkworld.com/fast/<?php echo e($channel->tenant->slug); ?>/<?php echo e($channel->slug); ?>.m3u8" 
                                   readonly 
                                   style="font-size: 11px;">
                            <div class="input-group-append">
                                <button class="btn btn-info btn-sm" 
                                        type="button" 
                                        onclick="copySSAIUrl('https://doubleclick.wkkworld.com/fast/<?php echo e($channel->tenant->slug); ?>/<?php echo e($channel->slug); ?>.m3u8', this)"
                                        title="Copy SSAI URL">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="<?php echo e(route('channels.show', $channel)); ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo e(route('channels.edit', $channel)); ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="<?php echo e(route('channels.destroy', $channel)); ?>" method="POST" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="text-center">No channels found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <?php echo e($channels->links()); ?>

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
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/lamkapro/fast-ads-backend/laravel-backend/resources/views/channels/index.blade.php ENDPATH**/ ?>