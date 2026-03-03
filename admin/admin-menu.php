<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page(
        'AI Chat Assistant',
        'AI Chat Assistant',
        'manage_options',
        'ai-chat-assistant',
        'ai_chat_render_settings_page',
        'dashicons-format-chat',
        30
    );

    add_submenu_page(
        'ai-chat-assistant',
        'Настройки',
        'Настройки',
        'manage_options',
        'ai-chat-assistant',
        'ai_chat_render_settings_page'
    );

    add_submenu_page(
        'ai-chat-assistant',
        'История',
        'История',
        'manage_options',
        'ai-chat-assistant-history',
        'ai_chat_render_history_page'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'ai-chat-assistant') === false) {
        return;
    }

    wp_enqueue_style(
        'ai-chat-admin',
        AI_CHAT_URL . 'assets/css/admin.css',
        [],
        AI_CHAT_VERSION
    );
});
