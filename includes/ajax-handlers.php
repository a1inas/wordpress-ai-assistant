<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_ai_chat_send', 'ai_chat_send_message');
add_action('wp_ajax_nopriv_ai_chat_send', 'ai_chat_send_message');

add_action('wp_ajax_ai_chat_history', 'ai_chat_get_history');
add_action('wp_ajax_nopriv_ai_chat_history', 'ai_chat_get_history');

add_action('wp_ajax_ai_chat_submit_lead', 'ai_chat_submit_lead');
add_action('wp_ajax_nopriv_ai_chat_submit_lead', 'ai_chat_submit_lead');

add_action('wp_ajax_ai_chat_test_groq', 'ai_chat_test_groq');

function ai_chat_send_message() {
    check_ajax_referer('ai_chat_nonce', 'nonce');

    $text = sanitize_textarea_field($_POST['message'] ?? '');
    if ($text === '') {
        wp_send_json_error(['message' => 'Пустое сообщение']);
    }

    $session_id = ai_chat_get_session_id();

    ai_chat_db_save_message($session_id, 'user', $text);

    $history = ai_chat_db_load_messages($session_id, 10);
    $messages_for_ai = ai_chat_build_messages_for_ai($history);

    $answer = ai_chat_groq_chat($messages_for_ai);

    ai_chat_db_save_message($session_id, 'assistant', $answer);

    wp_send_json_success(['reply' => $answer]);
}

function ai_chat_get_history() {
    check_ajax_referer('ai_chat_nonce', 'nonce');

    $session_id = ai_chat_get_session_id();
    $history = ai_chat_db_load_messages($session_id, 20);

    wp_send_json_success(['history' => $history]);
}

function ai_chat_test_groq() {
    check_ajax_referer('ai_chat_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Недостаточно прав']);
    }

    $system = function_exists('ai_chat_get_system_prompt_full')
        ? ai_chat_get_system_prompt_full()
        : 'Ты тестовый ассистент. Ответь: Тест пройден.';

    $messages = [
        ['role' => 'system', 'content' => $system . "\n\nОтветь строго одной фразой: Тест пройден."],
        ['role' => 'user', 'content' => 'Тест'],
    ];

    $answer = ai_chat_groq_chat($messages);

    wp_send_json_success(['message' => 'Ответ получен: ' . $answer]);
}

function ai_chat_validate_name($name) {
    $name = trim((string) $name);

    if ($name === '') {
        return 'Введите имя.';
    }

    if (!preg_match('/^[\p{L}\s\'-]+$/u', $name)) {
        return 'Имя может содержать только буквы, пробел, апостроф и дефис.';
    }

    preg_match_all('/\p{L}/u', $name, $m);
    $letters = isset($m[0]) ? count($m[0]) : 0;

    if ($letters < 3) {
        return 'Имя: минимум 3 буквы.';
    }

    return true;
}

function ai_chat_validate_phone($phone) {
    $phone = preg_replace('/\s+/', '', (string) $phone);

    if ($phone === '') {
        return 'Введите телефон.';
    }

    if (!preg_match('/^\+375\d{9}$/', $phone)) {
        return 'Телефон должен быть в формате +375XXXXXXXXX (9 цифр после +375).';
    }

    return true;
}

function ai_chat_validate_contact_time($time) {
    $time = trim((string) $time);

    if ($time === '') {
        return 'Укажите удобное время для связи (например: «сегодня в 18:00»).';
    }

    if (mb_strlen($time, 'UTF-8') < 3) {
        return 'Напишите чуть подробнее (например: «сегодня в 18:00»).';
    }

    if (mb_strlen($time, 'UTF-8') > 80) {
        return 'Слишком длинно. Напишите короче (до 80 символов).';
    }

    if (preg_match('/[<>$%^*{}\[\]|\\\\]/u', $time)) {
        return 'Используйте обычный текст (без спецсимволов).';
    }

    if (!preg_match('/^[\p{L}\p{N}\s:.,()\-—]+$/u', $time)) {
        return 'Используйте обычный текст (например: «сегодня в 18:00»).';
    }

    if (!preg_match('/[\p{L}\p{N}]/u', $time)) {
        return 'Напишите словами или укажите часы (например: «сегодня в 18:00»).';
    }

    return true;
}

function ai_chat_submit_lead() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'ai_chat_nonce')) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте снова.',
            'field'   => 'nonce',
        ]);
    }

    $name         = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $email        = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone        = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $contact_time = isset($_POST['contact_time']) ? sanitize_text_field($_POST['contact_time']) : '';
    $message      = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    $page_url     = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '';

    $name_check = ai_chat_validate_name($name);
    if ($name_check !== true) {
        wp_send_json_error(['message' => $name_check, 'field' => 'name']);
    }

    if ($email === '' || !is_email($email)) {
        wp_send_json_error(['message' => 'Введите корректный email.', 'field' => 'email']);
    }

    $phone_check = ai_chat_validate_phone($phone);
    if ($phone_check !== true) {
        wp_send_json_error(['message' => $phone_check, 'field' => 'phone']);
    }

    $time_check = ai_chat_validate_contact_time($contact_time);
    if ($time_check !== true) {
        wp_send_json_error(['message' => $time_check, 'field' => 'contact_time']);
    }

    $session_id = function_exists('ai_chat_get_session_id')
        ? (string) ai_chat_get_session_id()
        : ('ai_chat_' . wp_generate_uuid4());

    if (!function_exists('ai_chat_insert_lead')) {
        wp_send_json_error(['message' => 'Ошибка: модуль базы данных не подключён.']);
    }

    $lead_id = ai_chat_insert_lead([
        'session_id'   => $session_id,
        'name'         => $name,
        'email'        => $email,
        'phone'        => preg_replace('/\s+/', '', $phone),
        'contact_time' => $contact_time,
        'message'      => $message,
        'page_url'     => $page_url,
        'user_ip'      => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
        'user_agent'   => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
    ]);

    if (!$lead_id) {
        wp_send_json_error(['message' => 'Не удалось сохранить заявку. Попробуйте позже.']);
    }

    $to = get_option('ai_chat_manager_email', get_option('admin_email'));
    if (!is_email($to)) {
        $to = get_option('admin_email');
    }

    $subject = 'Заявка из чата (AI Chat Assistant)';

    $body_lines = [
        'Новая заявка из чата:',
        '',
        'ID: ' . $lead_id,
        'Имя: ' . $name,
        'Email: ' . $email,
        'Телефон: ' . preg_replace('/\s+/', '', $phone),
        'Время для связи: ' . $contact_time,
        'Страница: ' . ($page_url ?: '—'),
        'Session ID: ' . $session_id,
        '',
        'Сообщение:',
        $message !== '' ? $message : '—',
    ];

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES) . ' <' . get_option('admin_email') . '>',
        'Reply-To: ' . $email,
    ];

    $sent = wp_mail($to, $subject, implode("\n", $body_lines), $headers);

    if (!$sent) {
        error_log('AI Chat Assistant: wp_mail() не отправил письмо. To=' . $to . ' LeadID=' . $lead_id);
        wp_send_json_error([
            'message' => 'Заявка сохранена, но письмо менеджеру не отправилось. Проверьте почту/SMTP в WordPress.'
        ]);
    }

    wp_send_json_success([
        'message'    => 'Спасибо! Заявка отправлена. Менеджер свяжется с вами в указанное время.',
        'lead_id'    => $lead_id,
        'session_id' => $session_id,
    ]);
}
