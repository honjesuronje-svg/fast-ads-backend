<?php $__env->startSection('page_title', 'Tenants'); ?>

<?php $__env->startSection('page_content'); ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tenants List</h3>
        <div class="card-tools">
            <a href="<?php echo e(route('tenants.create')); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Tenant
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
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $tenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($tenant->id); ?></td>
                    <td><?php echo e($tenant->name); ?></td>
                    <td><?php echo e($tenant->slug); ?></td>
                    <td>
                        <span class="badge badge-<?php echo e($tenant->status === 'active' ? 'success' : 'danger'); ?>">
                            <?php echo e(ucfirst($tenant->status)); ?>

                        </span>
                    </td>
                    <td>
                        <a href="<?php echo e(route('tenants.show', $tenant)); ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo e(route('tenants.edit', $tenant)); ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="<?php echo e(route('tenants.destroy', $tenant)); ?>" method="POST" class="d-inline">
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
                    <td colspan="5" class="text-center">No tenants found.</td>
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


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/lamkapro/fast-ads-backend/laravel-backend/resources/views/tenants/index.blade.php ENDPATH**/ ?>