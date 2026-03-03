<?php
if (!defined('ABSPATH')) {
    exit;
}

function ai_chat_get_session_id() {
    if (!empty($_COOKIE['ai_chat_session'])) {
        return sanitize_text_field($_COOKIE['ai_chat_session']);
    }

    $id = wp_generate_uuid4();
    setcookie('ai_chat_session', $id, time() + DAY_IN_SECONDS * 7, COOKIEPATH, COOKIE_DOMAIN);

    return $id;
}

function ai_chat_get_system_prompt_full() {
    $style_prompt = trim((string) get_option('ai_chat_system_prompt', 'Ты — помощник сайта. Пиши кратко и по делу.'));
    $rules        = trim((string) get_option('ai_chat_rules', ''));
    $site_context = trim((string) get_option('ai_chat_site_context', ''));

    $contacts_url = trim((string) get_option('ai_chat_contacts_url', ''));
    $contacts_url = esc_url_raw($contacts_url);
    if ($contacts_url !== '' && !filter_var($contacts_url, FILTER_VALIDATE_URL)) {
        $contacts_url = '';
    }

    $contacts_email = sanitize_email((string) get_option('ai_chat_contacts_email', ''));
    if ($contacts_email === '' || !is_email($contacts_email)) {
        $contacts_email = sanitize_email((string) get_option('admin_email'));
    }

    $contacts_block = "Контакты (давать только по прямому запросу пользователя):\n"
        . "• Email: {$contacts_email}\n"
        . ($contacts_url !== '' ? "• Страница «Контакты»: {$contacts_url}\n" : '');

    $system = "Ты — AI ассистент на сайте компании IDS.\n\n";

    if ($style_prompt !== '') {
        $system .= $style_prompt . "\n\n";
    }

    if ($rules !== '') {
        $system .= "Правила ассистента:\n" . $rules . "\n\n";
    }

    $system .= $contacts_block . "\n\n";
    $system .= "Контекст сайта:\n" . ($site_context !== '' ? $site_context : 'Контекст пока не заполнен администратором.');

    return trim($system);
}

function ai_chat_build_messages_for_ai(array $history) {
    if (count($history) > 10) {
        $history = array_slice($history, -10);
    }

    $messages = [
        ['role' => 'system', 'content' => ai_chat_get_system_prompt_full()]
    ];

    foreach ($history as $row) {
        $role = ($row['role'] === 'user') ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => (string) $row['message']];
    }

    return $messages;
}

function ai_chat_sanitize_plain_text($text) {
    $text = (string) $text;
    $text = preg_replace("/\r\n|\r/u", "\n", $text);

    $text = preg_replace('/```[\s\S]*?```/u', '', $text);
    $text = preg_replace('/`[^`]*`/u', '', $text);

    $text = preg_replace('/^\s{0,3}#{1,6}\s+/mu', '', $text);

    $text = preg_replace('/\*\*(.*?)\*\*/u', '$1', $text);
    $text = preg_replace('/__(.*?)__/u', '$1', $text);
    $text = preg_replace('/\*(.*?)\*/u', '$1', $text);
    $text = preg_replace('/_(.*?)_/u', '$1', $text);

    $text = preg_replace('/^\s*>\s?/mu', '', $text);

    $lines = explode("\n", $text);
    $filtered = [];
    foreach ($lines as $line) {
        $trim = trim($line);

        $looks_like_table_sep = (bool) preg_match('/^\s*\|?(\s*:?-{2,}:?\s*\|)+\s*:?-{2,}:?\s*\|?\s*$/u', $trim);
        $looks_like_table_row = (strpos($trim, '|') !== false) && (substr_count($trim, '|') >= 2);

        if ($looks_like_table_sep || $looks_like_table_row) {
            continue;
        }
        $filtered[] = $line;
    }
    $text = implode("\n", $filtered);

    $text = preg_replace('/^\s*[-*•]\s+/mu', '• ', $text);
    $text = preg_replace('/^\s*\d+\)\s+/mu', '• ', $text);
    $text = preg_replace('/^\s*\d+\.\s+/mu', '• ', $text);

    $text = str_replace(['**', '__', '~~'], '', $text);

    $text = preg_replace('/[\x{1F300}-\x{1FAFF}]/u', '', $text);
    $text = preg_replace('/[\x{2600}-\x{27BF}]/u', '', $text);
    $text = preg_replace('/[\x{FE0F}\x{200D}]/u', '', $text);

    $text = preg_replace('/[ \t]+/u', ' ', $text);
    $text = preg_replace('/ *\n */u', "\n", $text);
    $text = preg_replace("/\n{3,}/u", "\n\n", $text);

    return trim($text);
}
