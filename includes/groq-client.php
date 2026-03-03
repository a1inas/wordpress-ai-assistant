<?php
if (!defined('ABSPATH')) {
    exit;
}

function ai_chat_groq_chat(array $messages) {
    $api_key = trim((string) get_option('ai_chat_groq_api_key', ''));

    if ($api_key === '') {
        return 'Сейчас я не могу ответить. Пожалуйста, напишите через контакты на сайте — менеджер поможет.';
    }

    $model = trim((string) get_option('ai_chat_model', 'llama3-8b-8192'));
    $temperature = (float) get_option('ai_chat_temperature', 0.7);
    $max_tokens = (int) get_option('ai_chat_max_tokens', 800);

    $body = [
        'model'       => $model,
        'messages'    => $messages,
        'temperature' => $temperature,
        'max_tokens'  => $max_tokens,
        'stream'      => false,
    ];

    $response = wp_remote_post(
        'https://api.groq.com/openai/v1/chat/completions',
        [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 30,
        ]
    );

    if (is_wp_error($response)) {
        return 'Ошибка подключения: ' . $response->get_error_message();
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw  = (string) wp_remote_retrieve_body($response);
    $json = json_decode($raw, true);

    if ($code !== 200) {
        $msg = $json['error']['message'] ?? $raw;
        error_log('AI Chat Groq error ' . $code . ': ' . (is_string($msg) ? $msg : wp_json_encode($msg)));
        return 'Сейчас я не могу ответить. Попробуйте чуть позже или свяжитесь с менеджером через контакты на сайте.';
    }

    $text = $json['choices'][0]['message']['content'] ?? '';
    $text = is_string($text) ? trim($text) : '';

    if ($text === '') {
        return 'Пустой ответ AI.';
    }

    // Ограничение и форматирование
    $text = ai_chat_trim_long_answer($text, 700);
    $text = ai_chat_format_answer($text);

    if (function_exists('ai_chat_sanitize_plain_text')) {
        $text = ai_chat_sanitize_plain_text($text);
    }

    $text = ai_chat_filter_contacts($text);

    return $text;
}

function ai_chat_trim_long_answer($text, $max_chars = 700) {
    $text = trim((string) $text);

    if (mb_strlen($text, 'UTF-8') <= $max_chars) {
        return $text;
    }

    $cut = mb_substr($text, 0, $max_chars, 'UTF-8');

    $posDot = mb_strrpos($cut, '.', 0, 'UTF-8');
    $posQ   = mb_strrpos($cut, '?', 0, 'UTF-8');
    $posE   = mb_strrpos($cut, '!', 0, 'UTF-8');

    $pos = max($posDot !== false ? $posDot : 0, $posQ !== false ? $posQ : 0, $posE !== false ? $posE : 0);

    if ($pos > 200) {
        $cut = mb_substr($cut, 0, $pos + 1, 'UTF-8');
    }

    return rtrim($cut) . "\n\nЕсли нужно — уточните детали, и я отвечу точнее.";
}

function ai_chat_format_answer($text) {
    $text = trim((string) $text);

    $text = preg_replace("/\r\n|\r/", "\n", $text);
    $text = preg_replace('/^\s*[\*\-]\s+/m', '• ', $text);
    $text = preg_replace('/:\s*•\s*/u', ":\n• ", $text);
    $text = preg_replace('/\s+•\s+/u', "\n• ", $text);
    $text = preg_replace('/;\s*(\n|$)/u', "\n", $text);
    $text = preg_replace('/[ \t]+/u', ' ', $text);
    $text = preg_replace('/ *\n */u', "\n", $text);
    $text = preg_replace("/\n{3,}/u", "\n\n", $text);

    return trim($text);
}

function ai_chat_filter_contacts($text) {
    $contacts_email = sanitize_email((string) get_option('ai_chat_contacts_email', ''));

    if ($contacts_email === '' || !is_email($contacts_email)) {
        $contacts_email = sanitize_email((string) get_option('admin_email'));
    }

    return preg_replace(
        '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/u',
        $contacts_email,
        (string) $text
    );
}
