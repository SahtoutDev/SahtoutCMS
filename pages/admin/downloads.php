<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';
require_once $project_root . 'includes/download_links_helper.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'moderator'], true)) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'downloads';
$page_title = translate('admin_downloads_title', 'Download Links & Addons');
$page_meta_description = translate('admin_downloads_meta', 'Manage external download links and addons');
$page_meta_robots = 'noindex';

$alert_danger = function ($msg) {
    return '<div class="bg-red-900/40 border border-red-500/50 text-red-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3 shadow-lg">'
        . '<i class="fas fa-exclamation-circle flex-shrink-0"></i>'
        . '<span>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</span>'
        . '</div>';
};

$alert_success = function ($msg) {
    return '<div class="bg-green-900/40 border border-green-500/50 text-green-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3 shadow-lg">'
        . '<i class="fas fa-check-circle flex-shrink-0"></i>'
        . '<span>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</span>'
        . '</div>';
};

$links_data = load_download_links();
$status_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $status_message = $alert_danger(translate('err_invalid_csrf', 'Invalid CSRF token.'));
    } else {
        $action = $_POST['action'];
        $category = ($_POST['category'] ?? '') === 'addons' ? 'addons' : 'external';
        $default_icon = $category === 'addons' ? 'fas fa-puzzle-piece' : 'fas fa-external-link-alt';
        $default_btn = $category === 'addons' ? 'Download Addon' : 'Open Link';

        if ($action === 'add') {
            $title = trim((string)($_POST['title'] ?? ''));
            $url = trim((string)($_POST['url'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $button_text = trim((string)($_POST['button_text'] ?? ''));
            $icon = sanitize_download_icon((string)($_POST['icon'] ?? ''), $default_icon);

            if ($title === '') {
                $status_message = $alert_danger(translate('admin_downloads_err_title', 'Please enter a title.'));
            } elseif (!is_valid_download_url($url)) {
                $status_message = $alert_danger(translate('admin_downloads_err_url', 'Please enter a valid http:// or https:// URL.'));
            } else {
                $prefix = $category === 'addons' ? 'addon_' : 'ext_';
                $links_data[$category][] = [
                    'id' => $prefix . bin2hex(random_bytes(5)),
                    'title' => $title,
                    'description' => $description,
                    'url' => $url,
                    'button_text' => $button_text !== '' ? $button_text : $default_btn,
                    'icon' => $icon,
                ];
                if (save_download_links($links_data)) {
                    $status_message = $alert_success(translate('admin_downloads_added', 'Download link added successfully.'));
                } else {
                    $status_message = $alert_danger(translate('admin_downloads_save_failed', 'Failed to save changes. Check file permissions on includes/.'));
                }
            }
        } elseif ($action === 'update') {
            $id = trim((string)($_POST['id'] ?? ''));
            $title = trim((string)($_POST['title'] ?? ''));
            $url = trim((string)($_POST['url'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $button_text = trim((string)($_POST['button_text'] ?? ''));
            $icon = sanitize_download_icon((string)($_POST['icon'] ?? ''), $default_icon);

            if ($title === '') {
                $status_message = $alert_danger(translate('admin_downloads_err_title', 'Please enter a title.'));
            } elseif (!is_valid_download_url($url)) {
                $status_message = $alert_danger(translate('admin_downloads_err_url', 'Please enter a valid http:// or https:// URL.'));
            } else {
                $updated = false;
                foreach (['external', 'addons'] as $cat) {
                    foreach ($links_data[$cat] as $idx => $item) {
                        if (($item['id'] ?? '') === $id) {
                            $new_item = [
                                'id' => $id,
                                'title' => $title,
                                'description' => $description,
                                'url' => $url,
                                'button_text' => $button_text !== '' ? $button_text : $default_btn,
                                'icon' => $icon,
                            ];
                            if ($cat === $category) {
                                $links_data[$cat][$idx] = $new_item;
                            } else {
                                array_splice($links_data[$cat], $idx, 1);
                                $links_data[$category][] = $new_item;
                            }
                            $updated = true;
                            break 2;
                        }
                    }
                }

                if ($updated) {
                    if (save_download_links($links_data)) {
                        $status_message = $alert_success(translate('admin_downloads_updated', 'Download link updated successfully.'));
                    } else {
                        $status_message = $alert_danger(translate('admin_downloads_save_failed', 'Failed to save changes. Check file permissions on includes/.'));
                    }
                } else {
                    $status_message = $alert_danger(translate('admin_downloads_not_found', 'Item not found.'));
                }
            }
        } elseif ($action === 'delete') {
            $id = trim((string)($_POST['id'] ?? ''));
            $deleted = false;
            foreach (['external', 'addons'] as $cat) {
                foreach ($links_data[$cat] as $idx => $item) {
                    if (($item['id'] ?? '') === $id) {
                        array_splice($links_data[$cat], $idx, 1);
                        $deleted = true;
                        break 2;
                    }
                }
            }

            if ($deleted) {
                if (save_download_links($links_data)) {
                    $status_message = $alert_success(translate('admin_downloads_deleted', 'Download link deleted successfully.'));
                } else {
                    $status_message = $alert_danger(translate('admin_downloads_save_failed', 'Failed to save changes. Check file permissions on includes/.'));
                }
            } else {
                $status_message = $alert_danger(translate('admin_downloads_not_found', 'Item not found.'));
            }
        }
    }
}

$external_items = $links_data['external'];
$addon_items = $links_data['addons'];

ob_start();
?>
<style>
    body {
        min-height: 100vh;
        color: #d8d8d8;
        background: #05070b;
        background-image:
            radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
            radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
            linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
        background-attachment: fixed;
    }

    .wow-panel {
        position: relative;
        background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
        border: 1px solid rgba(201,162,39,.22);
        box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
    }
    .wow-panel::before {
        content: '';
        position: absolute;
        inset: 5px;
        border: 1px solid rgba(201,162,39,.14);
        pointer-events: none;
    }
    .wow-panel::after {
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

    .wow-title {
        font-family: 'Cinzel', serif;
        font-weight: 900;
        background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
        letter-spacing: .02em;
    }

    .section-title {
        font-family: 'Cinzel', serif;
        font-weight: 700;
        color: #f2cf5b;
        text-shadow: 0 0 12px rgba(201,162,39,.35), 0 2px 4px rgba(0,0,0,.8);
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .btn-game {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: .65rem 1.25rem;
        font-weight: 800;
        font-size: .8rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
        transition: transform .2s ease, filter .2s ease;
        border: none;
        cursor: pointer;
    }
    .btn-game:hover { transform: translateY(-2px); }

    .btn-gold {
        background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
        color: #1a1200;
        text-shadow: 0 1px 0 rgba(255,255,255,.35);
    }

    .btn-iron {
        background: linear-gradient(180deg, #3a404d 0%, #232833 55%, #141821 100%);
        color: #cfe1ff;
        box-shadow: inset 0 0 0 1px rgba(120,160,255,.25);
    }

    .btn-danger {
        background: linear-gradient(180deg, #ff4d4d 0%, #cc0000 48%, #8a0000 100%);
        color: #fff;
    }

    .wow-input, .wow-select {
        width: 100%;
        background: rgba(10, 14, 22, 0.85);
        border: 1px solid rgba(201,162,39,.3);
        color: #e5e7eb;
        padding: 0.65rem 0.9rem;
        font-size: 0.9rem;
        transition: border-color 0.2s ease;
        outline: none;
    }
    .wow-input:focus, .wow-select:focus {
        border-color: #f2cf5b;
    }

    .main-content-area {
        transition: margin-left 0.3s ease;
        min-height: calc(100vh - 72px);
        width: 100%;
    }
    .content-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    @media (min-width: 1024px) {
        .content-wrapper { padding: 0 2rem; }
        .main-content-area.lg\:ml-\[280px\] { margin-left: 280px; }
    }
</style>
<?php $page_head = ob_get_clean(); ?>

<?php include $project_root . 'includes/header.php'; ?>

<div class="flex relative min-h-screen">
    <?php include $project_root . 'includes/admin_sidebar.php'; ?>

    <main class="main-content-area flex-1 lg:ml-[280px] py-8 px-2 sm:px-4">
        <div class="content-wrapper">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="wow-title text-2xl sm:text-3xl">
                        <i class="fas fa-download text-[#f2cf5b] mr-2"></i>
                        <?php echo translate('admin_downloads_heading', 'Download Links & Addons'); ?>
                    </h1>
                    <p class="text-sm text-gray-400 mt-1">
                        <?php echo translate('admin_downloads_subheading', 'Add, edit, or remove External Mirrors and Essential Addons shown on the Download page.'); ?>
                    </p>
                </div>
                <a href="<?php echo $base_path; ?>download" target="_blank" class="btn-game btn-iron self-start">
                    <i class="fas fa-external-link-alt"></i>
                    <?php echo translate('admin_downloads_view_page', 'View Download Page'); ?>
                </a>
            </div>

            <?php echo $status_message; ?>

            <!-- Add New Link Form -->
            <div class="wow-panel p-6 mb-8">
                <h2 class="section-title text-xl mb-5">
                    <i class="fas fa-plus-circle"></i>
                    <?php echo translate('admin_downloads_add_new', 'Add New Link'); ?>
                </h2>

                <form method="post" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="add">

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
                            <?php echo translate('admin_downloads_type', 'Section'); ?> *
                        </label>
                        <select name="category" class="wow-select" required>
                            <option value="external"><?php echo translate('external_mirrors', 'External Mirrors'); ?></option>
                            <option value="addons"><?php echo translate('essential_addons', 'Essential Addons'); ?></option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
                            <?php echo translate('admin_downloads_field_title', 'Title'); ?> *
                        </label>
                        <input type="text" name="title" class="wow-input" placeholder="e.g. Google Drive or Deadly Boss Mods" required maxlength="100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
                            <?php echo translate('admin_downloads_field_url', 'Link URL'); ?> *
                        </label>
                        <input type="url" name="url" class="wow-input" placeholder="https://..." required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
                            <?php echo translate('admin_downloads_field_desc', 'Details / Description'); ?>
                        </label>
                        <input type="text" name="description" class="wow-input" placeholder="e.g. Fast cloud mirror / Raid & dungeon alerts" maxlength="200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
                            <?php echo translate('admin_downloads_field_btn', 'Button Text (optional)'); ?>
                        </label>
                        <input type="text" name="button_text" class="wow-input" placeholder="e.g. Open Google Drive / Download" maxlength="60">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
                            <?php echo translate('admin_downloads_field_icon', 'Icon Class (optional)'); ?>
                        </label>
                        <input type="text" name="icon" class="wow-input" placeholder="e.g. fab fa-google-drive or fas fa-puzzle-piece" maxlength="60">
                    </div>

                    <div class="md:col-span-2 lg:col-span-3 flex justify-end pt-2">
                        <button type="submit" class="btn-game btn-gold">
                            <i class="fas fa-save"></i>
                            <?php echo translate('admin_downloads_add_btn', 'Add Link'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- External Mirrors List -->
            <div class="wow-panel p-6 mb-8">
                <h2 class="section-title text-xl mb-4">
                    <i class="fas fa-globe"></i>
                    <?php echo translate('external_mirrors', 'External Mirrors'); ?>
                    <span class="text-xs px-2 py-0.5 rounded bg-[#f2cf5b]/15 text-[#f2cf5b] border border-[#f2cf5b]/30 ml-2">
                        <?php echo count($external_items); ?>
                    </span>
                </h2>

                <?php if (empty($external_items)): ?>
                    <p class="text-gray-400 text-sm py-4"><?php echo translate('admin_downloads_empty_external', 'No external mirrors added yet.'); ?></p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-[#c9a227]/25 text-xs uppercase text-[#f2cf5b]">
                                    <th class="py-3 px-3">Title</th>
                                    <th class="py-3 px-3">Details</th>
                                    <th class="py-3 px-3">Link</th>
                                    <th class="py-3 px-3">Button</th>
                                    <th class="py-3 px-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($external_items as $item): ?>
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="py-3 px-3 font-semibold text-white">
                                            <i class="<?php echo htmlspecialchars($item['icon'] ?? 'fas fa-link', ENT_QUOTES, 'UTF-8'); ?> text-[#f2cf5b] mr-2"></i>
                                            <?php echo htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="py-3 px-3 text-gray-400">
                                            <?php echo htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="py-3 px-3 max-w-xs truncate">
                                            <a href="<?php echo htmlspecialchars($item['url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:underline">
                                                <?php echo htmlspecialchars($item['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-3 text-gray-300">
                                            <?php echo htmlspecialchars($item['button_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="py-3 px-3 text-right whitespace-nowrap">
                                            <button type="button"
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode(array_merge($item, ['category' => 'external'])), ENT_QUOTES, 'UTF-8'); ?>)"
                                                class="btn-game btn-iron text-xs py-1.5 px-3 mr-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="post" class="inline" onsubmit="return confirm('Delete this link?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="btn-game btn-danger text-xs py-1.5 px-3">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Addons List -->
            <div class="wow-panel p-6">
                <h2 class="section-title text-xl mb-4">
                    <i class="fas fa-puzzle-piece"></i>
                    <?php echo translate('essential_addons', 'Essential Addons'); ?>
                    <span class="text-xs px-2 py-0.5 rounded bg-[#f2cf5b]/15 text-[#f2cf5b] border border-[#f2cf5b]/30 ml-2">
                        <?php echo count($addon_items); ?>
                    </span>
                </h2>

                <?php if (empty($addon_items)): ?>
                    <p class="text-gray-400 text-sm py-4"><?php echo translate('admin_downloads_empty_addons', 'No addons added yet.'); ?></p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-[#c9a227]/25 text-xs uppercase text-[#f2cf5b]">
                                    <th class="py-3 px-3">Title</th>
                                    <th class="py-3 px-3">Details</th>
                                    <th class="py-3 px-3">Link</th>
                                    <th class="py-3 px-3">Button</th>
                                    <th class="py-3 px-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($addon_items as $item): ?>
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="py-3 px-3 font-semibold text-white">
                                            <i class="<?php echo htmlspecialchars($item['icon'] ?? 'fas fa-puzzle-piece', ENT_QUOTES, 'UTF-8'); ?> text-[#f2cf5b] mr-2"></i>
                                            <?php echo htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="py-3 px-3 text-gray-400">
                                            <?php echo htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="py-3 px-3 max-w-xs truncate">
                                            <a href="<?php echo htmlspecialchars($item['url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:underline">
                                                <?php echo htmlspecialchars($item['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-3 text-gray-300">
                                            <?php echo htmlspecialchars($item['button_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="py-3 px-3 text-right whitespace-nowrap">
                                            <button type="button"
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode(array_merge($item, ['category' => 'addons'])), ENT_QUOTES, 'UTF-8'); ?>)"
                                                class="btn-game btn-iron text-xs py-1.5 px-3 mr-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="post" class="inline" onsubmit="return confirm('Delete this addon?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="btn-game btn-danger text-xs py-1.5 px-3">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4">
    <div class="wow-panel w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="section-title text-lg">
                <i class="fas fa-edit"></i>
                <?php echo translate('admin_downloads_edit', 'Edit Link'); ?>
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-white text-xl">&times;</button>
        </div>

        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Section *</label>
                <select name="category" id="edit_category" class="wow-select" required>
                    <option value="external">External Mirrors</option>
                    <option value="addons">Essential Addons</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Title *</label>
                <input type="text" name="title" id="edit_title" class="wow-input" required maxlength="100">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Link URL *</label>
                <input type="url" name="url" id="edit_url" class="wow-input" required>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Details / Description</label>
                <input type="text" name="description" id="edit_description" class="wow-input" maxlength="200">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Button Text</label>
                    <input type="text" name="button_text" id="edit_button_text" class="wow-input" maxlength="60">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Icon Class</label>
                    <input type="text" name="icon" id="edit_icon" class="wow-input" maxlength="60">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeEditModal()" class="btn-game btn-iron">Cancel</button>
                <button type="submit" class="btn-game btn-gold"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(item) {
        document.getElementById('edit_id').value = item.id || '';
        document.getElementById('edit_category').value = item.category || 'external';
        document.getElementById('edit_title').value = item.title || '';
        document.getElementById('edit_url').value = item.url || '';
        document.getElementById('edit_description').value = item.description || '';
        document.getElementById('edit_button_text').value = item.button_text || '';
        document.getElementById('edit_icon').value = item.icon || '';
        const modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<?php include $project_root . 'includes/footer.php'; ?>
</body>
</html>
