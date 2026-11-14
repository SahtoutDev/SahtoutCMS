<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';
require_once $project_root . 'includes/armory_playerbots.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'moderator'], true)) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'armory-settings';
$page_title = translate('page_title_armory_settings', 'Armory Settings');
$page_meta_description = translate('page_description_armory_settings', 'Configure Armory Leaderboards and Display Settings');
$page_meta_robots = 'noindex';
$page_body_class = 'min-h-screen text-[#d8d8d8] bg-[#05070b] bg-fixed';

$errors = $_SESSION['armory_settings_errors'] ?? [];
$success = $_SESSION['armory_settings_success'] ?? false;
unset($_SESSION['armory_settings_errors'], $_SESSION['armory_settings_success']);

$armoryConfigFile = $project_root . 'includes/armory_config.php';
$armoryConfig = [];

if (file_exists($armoryConfigFile)) {
    require_once $armoryConfigFile;
}

$allowedModes = ['all', 'humans_only'];

// Current values with fallbacks
$armoryDisplaySoloPvP  = in_array($armoryConfig['solo_pvp_display'] ?? '', $allowedModes, true) ? $armoryConfig['solo_pvp_display'] : 'all';
$armoryDisplayArena2v2 = in_array($armoryConfig['arena_2v2_display'] ?? '', $allowedModes, true) ? $armoryConfig['arena_2v2_display'] : 'all';
$armoryDisplayArena3v3 = in_array($armoryConfig['arena_3v3_display'] ?? '', $allowedModes, true) ? $armoryConfig['arena_3v3_display'] : 'all';
$armoryDisplayArena5v5 = in_array($armoryConfig['arena_5v5_display'] ?? '', $allowedModes, true) ? $armoryConfig['arena_5v5_display'] : 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    $validCsrf = !empty($csrfToken) && hash_equals($csrfToken, (string)($_SESSION['csrf_token'] ?? ''));

    $postErrors = [];

    if (!$validCsrf) {
        $postErrors[] = translate('err_invalid_csrf', 'Invalid CSRF token.');
    } else {
        // Strict validation: each field must be exactly 'all' or 'humans_only'.
        // Invalid or missing values are rejected with an error; nothing is saved.
        $displayFields = [
            'solo_pvp_display'  => translate('label_solo_pvp', 'Solo PvP Leaderboard'),
            'arena_2v2_display' => translate('label_arena_2v2', 'Arena 2v2 Leaderboard'),
            'arena_3v3_display' => translate('label_arena_3v3', 'Arena 3v3 Leaderboard'),
            'arena_5v5_display' => translate('label_arena_5v5', 'Arena 5v5 Leaderboard'),
        ];

        $newConfig = [];

        foreach ($displayFields as $fieldName => $fieldLabel) {
            $submitted = $_POST[$fieldName] ?? null;

            if (!is_string($submitted) || !in_array($submitted, $allowedModes, true)) {
                $postErrors[] = sprintf(
                    translate('err_invalid_display_setting', 'Invalid value submitted for "%s". It must be either "all" or "humans_only".'),
                    htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8')
                );
            } else {
                $newConfig[$fieldName] = $submitted;
            }
        }

        // Only write the configuration if every submitted value was valid.
       if (empty($postErrors)) {
    $configPhp  = "<?php\n";
    $configPhp .= "if (!defined('ALLOWED_ACCESS')) { exit('Forbidden'); }\n\n";
    $configPhp .= '$armoryConfig = ' . var_export($newConfig, true) . ";\n";

    $configDir = dirname($armoryConfigFile);

    if (!is_writable($configDir)) {
        $postErrors[] = sprintf(translate('err_config_dir_not_writable', 'Config directory is not writable: %s'), htmlspecialchars($configDir, ENT_QUOTES, 'UTF-8'));
    } else {
        $tmpConfigFile = $configDir . '/.armory_config_' . bin2hex(random_bytes(8)) . '.tmp';

        if (file_put_contents($tmpConfigFile, $configPhp, LOCK_EX) === false) {
            $postErrors[] = translate('err_write_armory_config', 'Cannot write armory configuration file.');
        } else {
            $renamed = false;
            for ($attempt = 0; $attempt < 3; $attempt++) {
                // Windows-safe rename fallback
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && file_exists($armoryConfigFile)) {
                    @unlink($armoryConfigFile);
                }
                
                if (@rename($tmpConfigFile, $armoryConfigFile)) {
                    $renamed = true;
                    break;
                }
                usleep(50000); // 50ms wait
            }

            // Cleanup residual tmp file if rename failed
            if (file_exists($tmpConfigFile)) {
                @unlink($tmpConfigFile);
            }

            if (!$renamed) {
                $postErrors[] = translate('err_write_armory_config', 'Cannot write armory configuration file.');
            } else {
                // Invalidate OPCache handle
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($armoryConfigFile, true);
                }

                $_SESSION['armory_settings_success'] = true;
                header("Location: {$base_path}admin/settings/armory");
                exit;
            }
        }
    }
}
    }

    if (!empty($postErrors)) {
        $_SESSION['armory_settings_errors'] = $postErrors;
        header("Location: {$base_path}admin/settings/armory");
        exit;
    }
}

ob_start();
?>
    <style>
        body {
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
                linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
        }

        * { font-family: 'Inter', sans-serif; }
        .wow-title, .section-title, .form-label { font-family: 'Cinzel', serif; }
        
        .panel-gold-corners { position: relative; }
        
        .panel-gold-corners::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(#e8c552,#e8c552) left top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
            background-repeat: no-repeat;
        }
        
        .panel-gold-corners::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        .btn-clip {
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        }
    </style>
<?php
$page_head = ob_get_clean();

include $project_root . 'includes/header.php';

// Playerbots advisory (warning only — never blocks saving):
// relevant only when at least one leaderboard is set to humans_only.
$playerbotsWarning = false;
if (in_array('humans_only', [$armoryDisplaySoloPvP, $armoryDisplayArena2v2, $armoryDisplayArena3v3, $armoryDisplayArena5v5], true)
    && isset($char_db)
    && !armory_playerbots_table_exists($char_db)) {
    $playerbotsWarning = true;
}
?>

    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="max-w-[1400px] mx-auto px-1 sm:px-4 md:px-6 lg:px-8 xl:px-10">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl font-black 
                               bg-gradient-to-b from-[#fff7d6] via-[#f2cf5b] via-[#c9a227] to-[#8a6a14] 
                               bg-clip-text text-transparent drop-shadow-[0_3px_6px_rgba(0,0,0,.85)]">
                        <?php echo translate('page_title_armory_settings', 'Armory Settings'); ?>
                    </h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="bg-[#e74c3c]/15 border border-[#e74c3c]/40 text-[#e74c3c] p-4 rounded-sm flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                            <div>
                                <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                                <?php foreach ($errors as $err): ?>
                                    <div class="text-sm mt-1">• <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-[#2ecc71]/15 border border-[#2ecc71]/40 text-[#2ecc71] p-4 rounded-sm flex items-center gap-3">
                            <i class="fas fa-check-circle text-xl"></i>
                            <span><?php echo translate('msg_armory_saved', 'Armory configuration saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($playerbotsWarning): ?>
                        <div class="bg-[#f39c12]/15 border border-[#f39c12]/40 text-[#f39c12] p-4 rounded-sm flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-xl mt-0.5"></i>
                            <div class="text-sm">
                                <?php echo translate('warn_playerbots_missing', 'Playerbots database/table not detected. The <code>humans_only</code> filter cannot exclude Playerbots until <code>acore_playerbots.playerbots_account_type</code> is available.'); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Settings Form -->
                    <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                                border border-[#c9a227]/[0.22] 
                                shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                                p-4 md:p-6 lg:p-8 panel-gold-corners">
                        
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3 
                                   text-[#f2cf5b] font-bold drop-shadow-[0_0_12px_rgba(201,162,39,.35),0_2px_4px_rgba(0,0,0,.8)]">
                            <i class="fas fa-shield-alt text-[#f2cf5b]"></i>
                            <?php echo translate('section_armory_config', 'Leaderboard Player Filter'); ?>
                        </h2>

                        <form method="POST" class="space-y-6 max-w-3xl mx-auto">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                            <!-- Solo PvP -->
                            <div class="p-4 bg-[#0a0e16]/60 border border-[rgba(201,162,39,.15)] rounded-sm space-y-3">
                                <div class="flex items-center gap-2 text-[#f2cf5b] font-bold text-sm tracking-wider">
                                    <i class="fas fa-user-ninja"></i>
                                    <span><?php echo translate('label_solo_pvp', 'Solo PvP Leaderboard'); ?></span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <label class="flex items-start gap-3 p-3 bg-[#080a0e]/80 border border-[rgba(201,162,39,.1)] rounded-sm cursor-pointer hover:border-[#c9a227]/40 transition-all">
                                        <input type="radio" name="solo_pvp_display" value="all" class="mt-1 accent-[#c9a227]" <?php echo ($armoryDisplaySoloPvP === 'all') ? 'checked' : ''; ?>>
                                        <div>
                                            <div class="text-[#e5e7eb] font-semibold text-xs"><?php echo translate('opt_show_all', 'Show Humans & Bots'); ?></div>
                                            <div class="text-[#6a7a8a] text-[11px] mt-0.5"><?php echo translate('opt_show_all_desc', 'Include playerbots on the Solo PvP leaderboard.'); ?></div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 bg-[#080a0e]/80 border border-[rgba(201,162,39,.1)] rounded-sm cursor-pointer hover:border-[#c9a227]/40 transition-all">
                                        <input type="radio" name="solo_pvp_display" value="humans_only" class="mt-1 accent-[#c9a227]" <?php echo ($armoryDisplaySoloPvP === 'humans_only') ? 'checked' : ''; ?>>
                                        <div>
                                            <div class="text-[#e5e7eb] font-semibold text-xs"><?php echo translate('opt_humans_only', 'Human Players Only'); ?></div>
                                            <div class="text-[#6a7a8a] text-[11px] mt-0.5"><?php echo translate('opt_humans_only_desc', 'Filter out playerbots from the Solo PvP leaderboard.'); ?></div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Arena 2v2 -->
                            <div class="p-4 bg-[#0a0e16]/60 border border-[rgba(201,162,39,.15)] rounded-sm space-y-3">
                                <div class="flex items-center gap-2 text-[#f2cf5b] font-bold text-sm tracking-wider">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo translate('label_arena_2v2', 'Arena 2v2 Leaderboard'); ?></span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <label class="flex items-start gap-3 p-3 bg-[#080a0e]/80 border border-[rgba(201,162,39,.1)] rounded-sm cursor-pointer hover:border-[#c9a227]/40 transition-all">
                                        <input type="radio" name="arena_2v2_display" value="all" class="mt-1 accent-[#c9a227]" <?php echo ($armoryDisplayArena2v2 === 'all') ? 'checked' : ''; ?>>
                                        <div>
                                            <div class="text-[#e5e7eb] font-semibold text-xs"><?php echo translate('opt_show_all', 'Show Humans & Bots'); ?></div>
                                            <div class="text-[#6a7a8a] text-[11px] mt-0.5"><?php echo translate('opt_show_all_arena_2v2_desc', 'Include teams with playerbots in 2v2 standings.'); ?></div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 bg-[#080a0e]/80 border border-[rgba(201,162,39,.1)] rounded-sm cursor-pointer hover:border-[#c9a227]/40 transition-all">
                                        <input type="radio" name="arena_2v2_display" value="humans_only" class="mt-1 accent-[#c9a227]" <?php echo ($armoryDisplayArena2v2 === 'humans_only') ? 'checked' : ''; ?>>
                                        <div>
                                            <div class="text-[#e5e7eb] font-semibold text-xs"><?php echo translate('opt_humans_only', 'Human Players Only'); ?></div>
                                            <div class="text-[#6a7a8a] text-[11px] mt-0.5"><?php echo translate('opt_humans_only_arena_2v2_desc', 'Hide teams whose captain is a playerbot from 2v2 standings.'); ?></div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Arena 3v3 -->
                            <div class="p-4 bg-[#0a0e16]/60 border border-[rgba(201,162,39,.15)] rounded-sm space-y-3">
                                <div class="flex items-center gap-2 text-[#f2cf5b] font-bold text-sm tracking-wider">
                                    <i class="fas fa-shield-alt"></i>
                                    <span><?php echo translate('label_arena_3v3', 'Arena 3v3 Leaderboard'); ?></span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <label class="flex items-start gap-3 p-3 bg-[#080a0e]/80 border border-[rgba(201,162,39,.1)] rounded-sm cursor-pointer hover:border-[#c9a227]/40 transition-all">
                                        <input type="radio" name="arena_3v3_display" value="all" class="mt-1 accent-[#c9a227]" <?php echo ($armoryDisplayArena3v3 === 'all') ? 'checked' : ''; ?>>
                                        <div>
                                            <div class="text-[#e5e7eb] font-semibold text-xs"><?php echo translate('opt_show_all', 'Show Humans & Bots'); ?></div>
                                            <div class="text-[#6a7a8a] text-[11px] mt-0.5"><?php echo translate('opt_show_all_arena_3v3_desc', 'Include teams with playerbots in 3v3 standings.'); ?></div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 bg-[#080a0e]/80 border border-[rgba(201,162,39,.1)] rounded-sm cursor-pointer hover:border-[#c9a227]/40 transition-all">
                                        <input type="radio" name="arena_3v3_display" value="humans_only" class="mt-1 accent-[#c9a227]" <?php echo ($armoryDisplayArena3v3 === 'humans_only') ? 'checked' : ''; ?>>
                                        <div>
                                            <div class="text-[#e5e7eb] font-semibold text-xs"><?php echo translate('opt_humans_only', 'Human Players Only'); ?></div>
                                            <div class="text-[#6a7a8a] text-[11px] mt-0.5"><?php echo translate('opt_humans_only_arena_3v3_desc', 'Hide teams whose captain is a playerbot from 3v3 standings.'); ?></div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Arena 5v5 -->
                            <div class="p-4 bg-[#0a0e16]/60 border border-[rgba(201,162,39,.15)] rounded-sm space-y-3">
                                <div class="flex items-center gap-2 text-[#f2cf5b] font-bold text-sm tracking-wider">
                                    <i class="fas fa-trophy"></i>
                                    <span><?php echo translate('label_arena_5v5', 'Arena 5v5 Leaderboard'); ?></span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <label class="flex items-start gap-3 p-3 bg-[#080a0e]/80 border border-[rgba(201,162,39,.1)] rounded-sm cursor-pointer hover:border-[#c9a227]/40 transition-all">
                                        <input type="radio" name="arena_5v5_display" value="all" class="mt-1 accent-[#c9a227]" <?php echo ($armoryDisplayArena5v5 === 'all') ? 'checked' : ''; ?>>
                                        <div>
                                            <div class="text-[#e5e7eb] font-semibold text-xs"><?php echo translate('opt_show_all', 'Show Humans & Bots'); ?></div>
                                            <div class="text-[#6a7a8a] text-[11px] mt-0.5"><?php echo translate('opt_show_all_arena_5v5_desc', 'Include teams with playerbots in 5v5 standings.'); ?></div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 bg-[#080a0e]/80 border border-[rgba(201,162,39,.1)] rounded-sm cursor-pointer hover:border-[#c9a227]/40 transition-all">
                                        <input type="radio" name="arena_5v5_display" value="humans_only" class="mt-1 accent-[#c9a227]" <?php echo ($armoryDisplayArena5v5 === 'humans_only') ? 'checked' : ''; ?>>
                                        <div>
                                            <div class="text-[#e5e7eb] font-semibold text-xs"><?php echo translate('opt_humans_only', 'Human Players Only'); ?></div>
                                            <div class="text-[#6a7a8a] text-[11px] mt-0.5"><?php echo translate('opt_humans_only_arena_5v5_desc', 'Hide teams whose captain is a playerbot from 5v5 standings.'); ?></div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Info Note -->
                            <div class="text-[#6a7a8a] text-sm p-3 bg-[#0a0e16]/50 border border-[rgba(201,162,39,0.1)] rounded-sm flex items-center gap-2">
                                <i class="fas fa-info-circle text-[#f2cf5b]"></i>
                                <span><?php echo translate('note_armory_config', 'These settings control whether Playerbots are included in the public PvP and Arena leaderboards.'); ?></span>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex justify-end">
                                <button type="submit" class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                                            font-extrabold text-xs uppercase tracking-wider
                                                            bg-gradient-to-b from-[#f6d478] via-[#c9a227] to-[#8a6a14] 
                                                            text-[#1a1200] shadow-[inset_0_0_0_1px_rgba(255,255,255,.28),inset_0_-8px_14px_rgba(0,0,0,.25)]
                                                            hover:scale-105 transition-transform duration-200">
                                    <i class="fas fa-save"></i>
                                    <?php echo translate('btn_save_armory_settings', 'Save Armory Settings'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>