<?php $__env->startSection('page_title', 'Campaigns'); ?>

<?php $__env->startSection('page_content'); ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Campaigns List</h3>
        <div class="card-tools">
            <a href="<?php echo e(route('campaigns.create')); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Campaign
            </a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Tenant</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Budget</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($campaign->id); ?></td>
                    <td><?php echo e($campaign->name); ?></td>
                    <td><?php echo e($campaign->tenant->name ?? 'N/A'); ?></td>
                    <td><?php echo e($campaign->start_date->format('Y-m-d')); ?></td>
                    <td><?php echo e($campaign->end_date->format('Y-m-d')); ?></td>
                    <td><?php echo e($campaign->budget ? '$' . number_format($campaign->budget, 2) : 'N/A'); ?></td>
                    <td>
                        <span class="badge badge-<?php echo e($campaign->status === 'active' ? 'success' : 'danger'); ?>">
                            <?php echo e(ucfirst($campaign->status)); ?>

                        </span>
                    </td>
                    <td>
                        <a href="<?php echo e(route('campaigns.show', $campaign)); ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo e(route('campaigns.edit', $campaign)); ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="<?php echo e(route('campaigns.destroy', $campaign)); ?>" method="POST" class="d-inline">
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
                    <td colspan="8" class="text-center">No campaigns found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <?php echo e($campaigns->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/lamkapro/fast-ads-backend/laravel-backend/resources/views/campaigns/index.blade.php ENDPATH**/ ?>