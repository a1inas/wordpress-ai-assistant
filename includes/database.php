<?php
if (!defined('ABSPATH')) {
    exit;
}

function ai_chat_install() {
    global $wpdb;

    $table   = $wpdb->prefix . 'ai_chat_messages';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(64) NOT NULL,
        role VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY created_at (created_at)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function ai_chat_db_save_message($session_id, $role, $message) {
    global $wpdb;

    $table = $wpdb->prefix . 'ai_chat_messages';

    $wpdb->insert(
        $table,
        [
            'session_id' => (string) $session_id,
            'role'       => (string) $role,
            'message'    => (string) $message,
        ],
        ['%s', '%s', '%s']
    );
}

function ai_chat_db_load_messages($session_id, $limit = 20) {
    global $wpdb;

    $table = $wpdb->prefix . 'ai_chat_messages';

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT role, message, created_at
             FROM {$table}
             WHERE session_id = %s
             ORDER BY id DESC
             LIMIT %d",
            (string) $session_id,
            (int) $limit
        ),
        ARRAY_A
    );

    return array_reverse($rows);
}

function ai_chat_create_leads_table() {
    global $wpdb;

    $table   = $wpdb->prefix . 'ai_chat_leads';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(100) NOT NULL,
        name VARCHAR(191) NOT NULL,
        email VARCHAR(191) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        contact_time VARCHAR(191) NOT NULL,
        message TEXT NULL,
        page_url TEXT NULL,
        user_ip VARCHAR(45) NULL,
        user_agent TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY status (status),
        KEY created_at (created_at)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Заявка в БД
function ai_chat_insert_lead(array $data) {
    global $wpdb;

    $table = $wpdb->prefix . 'ai_chat_leads';

    $inserted = $wpdb->insert(
        $table,
        [
            'session_id'    => (string) ($data['session_id'] ?? ''),
            'name'          => (string) ($data['name'] ?? ''),
            'email'         => (string) ($data['email'] ?? ''),
            'phone'         => (string) ($data['phone'] ?? ''),
            'contact_time'  => (string) ($data['contact_time'] ?? ''),
            'message'       => (string) ($data['message'] ?? ''),
            'page_url'      => (string) ($data['page_url'] ?? ''),
            'user_ip'       => (string) ($data['user_ip'] ?? ''),
            'user_agent'    => (string) ($data['user_agent'] ?? ''),
            'status'        => 'new',
        ],
        ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s']
    );

    if ($inserted === false) {
        return false;
    }

    return (int) $wpdb->insert_id;
}

function ai_chat_prune_messages($days = 30) {
    global $wpdb;

    $days = max(1, (int) $days);
    $table = $wpdb->prefix . 'ai_chat_messages';

    $cutoff = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);

    $wpdb->query(
        $wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff)
    );
}

function ai_chat_prune_leads($days = 180) {
    global $wpdb;

    $days = max(1, (int) $days);
    $table = $wpdb->prefix . 'ai_chat_leads';

    $cutoff = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);

    $wpdb->query(
        $wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff)
    );
}
