<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    $css_rel = 'assets/css/chat.css';
    $js_rel  = 'assets/js/chat.js';

    $css_path = AI_CHAT_PATH . $css_rel;
    $js_path  = AI_CHAT_PATH . $js_rel;

    $css_ver = file_exists($css_path) ? filemtime($css_path) : AI_CHAT_VERSION;
    $js_ver  = file_exists($js_path)  ? filemtime($js_path)  : AI_CHAT_VERSION;

    wp_enqueue_style(
        'ai-chat-css',
        AI_CHAT_URL . $css_rel,
        [],
        $css_ver
    );

    wp_enqueue_script(
        'ai-chat-js',
        AI_CHAT_URL . $js_rel,
        [],
        $js_ver,
        true
    );

    wp_localize_script('ai-chat-js', 'aiChat', [
        'ajax'    => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ai_chat_nonce'),
        'welcome' => get_option('ai_chat_welcome_message', 'Здравствуйте! Чем могу помочь?'),
        'primary' => get_option('ai_chat_primary_color', '#2271b1'),
    ]);
});
