<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

function get_download_links_file_path(): string {
    global $project_root;
    return $project_root . 'includes/download_links.json';
}

function get_default_download_links(): array {
    return [
        'external' => [
            [
                'id' => 'ext_default_gdrive',
                'title' => 'Google Drive',
                'description' => 'Fast & reliable cloud download',
                'url' => 'https://drive.google.com/file/d/1S_7ltXiOOmHDHsE0NKg338KWXWloAjZ7/view?usp=drive_link',
                'button_text' => 'Open Google Drive',
                'icon' => 'fab fa-google-drive',
            ],
        ],
        'addons' => [
            [
                'id' => 'addon_default_dbm',
                'title' => 'Deadly Boss Mods',
                'description' => 'Raid & dungeon alerts',
                'url' => 'https://curseforge.com/wow/addons/deadly-boss-mods-dbm',
                'button_text' => 'Get DBM',
                'icon' => 'fas fa-skull',
            ],
            [
                'id' => 'addon_default_wa',
                'title' => 'WeakAuras',
                'description' => 'Custom UI triggers',
                'url' => 'https://wago.io',
                'button_text' => 'Browse Auras',
                'icon' => 'fas fa-magic',
            ],
            [
                'id' => 'addon_default_details',
                'title' => 'Details!',
                'description' => 'DPS & combat analytics',
                'url' => 'https://curseforge.com/wow/addons/details',
                'button_text' => 'Install',
                'icon' => 'fas fa-chart-bar',
            ],
        ],
    ];
}

function load_download_links(): array {
    $file = get_download_links_file_path();
    if (!is_file($file)) {
        return get_default_download_links();
    }

    $raw = @file_get_contents($file);
    if ($raw === false || trim($raw) === '') {
        return get_default_download_links();
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return get_default_download_links();
    }

    return [
        'external' => array_values(is_array($decoded['external'] ?? null) ? $decoded['external'] : []),
        'addons' => array_values(is_array($decoded['addons'] ?? null) ? $decoded['addons'] : []),
    ];
}

function save_download_links(array $data): bool {
    $file = get_download_links_file_path();
    $payload = [
        'external' => array_values($data['external'] ?? []),
        'addons' => array_values($data['addons'] ?? []),
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    return @file_put_contents($file, $json . PHP_EOL, LOCK_EX) !== false;
}

function is_valid_download_url(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

function sanitize_download_icon(string $icon, string $fallback = 'fas fa-link'): string {
    $icon = trim($icon);
    if ($icon === '' || !preg_match('/^[a-zA-Z0-9\s-]+$/', $icon)) {
        return $fallback;
    }
    return $icon;
}
