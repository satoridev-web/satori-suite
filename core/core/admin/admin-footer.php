<?php
use Satori\Core\Includes\Logger;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Admin footer additions (Debug footer)
 * -------------------------------------------------*/
$opts = get_option('satori_core_settings', []);
if (empty($opts['debug'])) {
    return;
}

$log_path = Logger::getLatestLogPathStatic();
$has_log  = file_exists($log_path);
?>
<div class="satori-core-debug-footer">
    <?php if ($has_log): ?>
        <a href="<?php echo esc_url(admin_url('admin-post.php?action=satori_view_log')); ?>" class="log-link"><?php _e('View latest log','satori-core'); ?></a>
    <?php else: ?>
        <span><?php _e('No log yet','satori-core'); ?></span>
    <?php endif; ?>
    <span class="sep">|</span>
    <a href="<?php echo esc_url(admin_url('admin-post.php?action=satori_test_log')); ?>" class="log-link"><?php _e('Write test log entry','satori-core'); ?></a>
</div>
