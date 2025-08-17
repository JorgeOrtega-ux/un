<?php
$userRole = $_SESSION['role'] ?? 'user';
$headerRankDetails = getRankDetails($userRole);
?>
<div class="header">
    <div class="header-left">
        <div class="header-item">
            <div class="header-button" data-action="toggleModuleSurface">
                <span class="material-symbols-rounded">menu</span>
            </div>
        </div>
    </div>
    <div class="header-right">
        <div class="header-item">
            <div class="profile-container <?php echo htmlspecialchars($headerRankDetails['class']); ?>" data-action="toggleModuleOptions">
                <div class="profile-content">
                    <span class="material-symbols-rounded"><?php echo htmlspecialchars($headerRankDetails['icon']); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/modules/module-options.php'; ?>
</div>