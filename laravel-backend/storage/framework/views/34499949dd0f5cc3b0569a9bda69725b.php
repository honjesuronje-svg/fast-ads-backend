<?php $__env->startSection('page_title', 'API Keys'); ?>

<?php $__env->startSection('page_content'); ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">API Keys Management</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tenant Name</th>
                    <th>API Key</th>
                    <th>Channels</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $tenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($tenant->id); ?></td>
                    <td><?php echo e($tenant->name); ?></td>
                    <td>
                        <code><?php echo e($tenant->api_key ?? 'Not set'); ?></code>
                    </td>
                    <td><?php echo e($tenant->channels_count ?? 0); ?></td>
                    <td>
                        <span class="badge badge-<?php echo e($tenant->status === 'active' ? 'success' : 'danger'); ?>">
                            <?php echo e(ucfirst($tenant->status)); ?>

                        </span>
                    </td>
                    <td>
                        <a href="<?php echo e(route('api-keys.show', $tenant)); ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <form action="<?php echo e(route('api-keys.regenerate', $tenant)); ?>" method="POST" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure? This will invalidate the current API key.')">
                                <i class="fas fa-sync"></i> Regenerate
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="text-center">No tenants found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <?php echo e($tenants->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/lamkapro/fast-ads-backend/laravel-backend/resources/views/api-keys/index.blade.php ENDPATH**/ ?>