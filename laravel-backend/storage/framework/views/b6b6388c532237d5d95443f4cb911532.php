<?php $__env->startSection('page_title', 'Dashboard'); ?>

<?php $__env->startSection('page_content'); ?>
<div class="row">
    <!-- Tenants Card -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo e($stats['tenants']); ?></h3>
                <p>Tenants</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <a href="<?php echo e(route('tenants.index')); ?>" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Channels Card -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo e($stats['channels']); ?></h3>
                <p>Channels</p>
            </div>
            <div class="icon">
                <i class="fas fa-tv"></i>
            </div>
            <a href="<?php echo e(route('channels.index')); ?>" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Ads Card -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo e($stats['ads']); ?></h3>
                <p>Total Ads</p>
            </div>
            <div class="icon">
                <i class="fas fa-ad"></i>
            </div>
            <a href="<?php echo e(route('ads.index')); ?>" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Campaigns Card -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo e($stats['campaigns']); ?></h3>
                <p>Campaigns</p>
            </div>
            <div class="icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <a href="<?php echo e(route('campaigns.index')); ?>" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Active Status</h3>
            </div>
            <div class="card-body">
                <p><strong>Active Ads:</strong> <?php echo e($stats['active_ads']); ?></p>
                <p><strong>Active Campaigns:</strong> <?php echo e($stats['active_campaigns']); ?></p>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/lamkapro/fast-ads-backend/laravel-backend/resources/views/dashboard/index.blade.php ENDPATH**/ ?>