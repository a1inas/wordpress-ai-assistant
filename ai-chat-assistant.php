<?php
/**
 * Plugin Name: AI Chat Assistant
 * Description: AI-ассистент для консультации посетителей сайта
 * Version: 1.0.0
 * Author: Alina Smolyakova
 */

if (!defined('ABSPATH')) {
    exit;
}

// Основные константы плагина
define('AI_CHAT_PATH', plugin_dir_path(__FILE__));
define('AI_CHAT_URL', plugin_dir_url(__FILE__));
define('AI_CHAT_VERSION', '1.0.0');

// Ядро плагина
require_once AI_CHAT_PATH . 'includes/database.php';
require_once AI_CHAT_PATH . 'includes/helpers.php';
require_once AI_CHAT_PATH . 'includes/groq-client.php';
require_once AI_CHAT_PATH . 'includes/ajax-handlers.php';

// Административная часть
if (is_admin()) {
    require_once AI_CHAT_PATH . 'admin/admin-menu.php';
    require_once AI_CHAT_PATH . 'admin/settings-page.php';
    require_once AI_CHAT_PATH . 'admin/history-page.php';
}

// Публичная часть
require_once AI_CHAT_PATH . 'public/enqueue.php';
require_once AI_CHAT_PATH . 'public/chat-render.php';

const AI_CHAT_CRON_HOOK = 'ai_chat_daily_cleanup';

register_activation_hook(__FILE__, 'ai_chat_activate');
function ai_chat_activate() {
    if (function_exists('ai_chat_install')) {
        ai_chat_install();
    }

    if (function_exists('ai_chat_create_leads_table')) {
        ai_chat_create_leads_table();
    }

    if (function_exists('ai_chat_schedule_cleanup_cron')) {
        ai_chat_schedule_cleanup_cron();
    }
}

// Деактивация плагина
register_deactivation_hook(__FILE__, 'ai_chat_deactivate');
function ai_chat_deactivate() {
    if (function_exists('ai_chat_unschedule_cleanup_cron')) {
        ai_chat_unschedule_cleanup_cron();
    }
}

function ai_chat_schedule_cleanup_cron() {
    if (!wp_next_scheduled(AI_CHAT_CRON_HOOK)) {
        wp_schedule_event(time() + 60, 'daily', AI_CHAT_CRON_HOOK);
    }
}

function ai_chat_unschedule_cleanup_cron() {
    $ts = wp_next_scheduled(AI_CHAT_CRON_HOOK);
    if ($ts) {
        wp_unschedule_event($ts, AI_CHAT_CRON_HOOK);
    }
}

add_action(AI_CHAT_CRON_HOOK, function () {
    if (function_exists('ai_chat_prune_messages')) {
        ai_chat_prune_messages(30);
    }
    if (function_exists('ai_chat_prune_leads')) {
        ai_chat_prune_leads(180);
    }
});

// Очистка истории (админка)
add_action('admin_post_ai_chat_clear_history', 'ai_chat_handle_clear_history');
function ai_chat_handle_clear_history() {
    if (!current_user_can('manage_options')) {
        wp_die('Недостаточно прав.');
    }

    check_admin_referer('ai_chat_clear_history');

    global $wpdb;
    $table = $wpdb->prefix . 'ai_chat_messages';

    $result = $wpdb->query("TRUNCATE TABLE {$table}");

    $back_url = wp_get_referer();
    if (!$back_url) {
        $back_url = admin_url('admin.php');
    }

    $back_url = add_query_arg(
        ['ai_chat_cleared' => ($result !== false ? '1' : '0')],
        $back_url
    );

    wp_safe_redirect($back_url);
    exit;
}
