<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function () {

    register_setting('ai_chat_options', 'ai_chat_groq_api_key', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);

    register_setting('ai_chat_options', 'ai_chat_model', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'llama3-8b-8192'
    ]);

    register_setting('ai_chat_options', 'ai_chat_temperature', [
        'type' => 'number',
        'sanitize_callback' => function ($v) { return (float) $v; },
        'default' => 0.7
    ]);

    register_setting('ai_chat_options', 'ai_chat_max_tokens', [
        'type' => 'integer',
        'sanitize_callback' => function ($v) { return (int) $v; },
        'default' => 800
    ]);

    register_setting('ai_chat_options', 'ai_chat_system_prompt', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default' => 'Ты — помощник сайта. Отвечай кратко, по делу. Если информации нет — скажи честно.'
    ]);

    register_setting('ai_chat_options', 'ai_chat_rules', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default' => ''
    ]);

    register_setting('ai_chat_options', 'ai_chat_welcome_message', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default' => 'Здравствуйте! Чем могу помочь?'
    ]);

    register_setting('ai_chat_options', 'ai_chat_primary_color', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#2271b1'
    ]);

    register_setting('ai_chat_options', 'ai_chat_site_context', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default' => ''
    ]);

    register_setting('ai_chat_options', 'ai_chat_contacts_email', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => get_option('admin_email')
    ]);

    register_setting('ai_chat_options', 'ai_chat_contacts_url', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => ''
    ]);

    register_setting('ai_chat_options', 'ai_chat_manager_email', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => ''
    ]);
});

function ai_chat_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $test_nonce = wp_create_nonce('ai_chat_nonce');

    $api_key = (string) get_option('ai_chat_groq_api_key', '');
    $admin_email = (string) get_option('admin_email');

    $manager_email = (string) get_option('ai_chat_manager_email', '');
    if ($manager_email === '') {
        $manager_email = $admin_email;
    }

    $rules = (string) get_option('ai_chat_rules', '');
    $site_context = (string) get_option('ai_chat_site_context', '');
    ?>
    <div class="wrap">
        <h1>AI Chat Assistant — Настройки</h1>

        <form method="post" action="options.php">
            <?php settings_fields('ai_chat_options'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ai_chat_groq_api_key">Groq API Key</label></th>
                    <td>
                        <input
                            type="password"
                            id="ai_chat_groq_api_key"
                            name="ai_chat_groq_api_key"
                            value="<?php echo esc_attr($api_key); ?>"
                            class="regular-text"
                            autocomplete="off"
                        />
                        <p class="description">Ключ вида <code>gsk_...</code></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_model">Модель</label></th>
                    <td>
                        <input
                            type="text"
                            id="ai_chat_model"
                            name="ai_chat_model"
                            value="<?php echo esc_attr(get_option('ai_chat_model', 'llama3-8b-8192')); ?>"
                            class="regular-text"
                        />
                        <p class="description">Пример: <code>llama3-8b-8192</code></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_temperature">Температура</label></th>
                    <td>
                        <input
                            type="number"
                            step="0.1"
                            min="0"
                            max="1.5"
                            id="ai_chat_temperature"
                            name="ai_chat_temperature"
                            value="<?php echo esc_attr(get_option('ai_chat_temperature', 0.7)); ?>"
                            class="small-text"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_max_tokens">Макс. токенов</label></th>
                    <td>
                        <input
                            type="number"
                            step="1"
                            min="50"
                            max="4000"
                            id="ai_chat_max_tokens"
                            name="ai_chat_max_tokens"
                            value="<?php echo esc_attr(get_option('ai_chat_max_tokens', 800)); ?>"
                            class="small-text"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_system_prompt">Системный промпт (стиль)</label></th>
                    <td>
                        <textarea
                            id="ai_chat_system_prompt"
                            name="ai_chat_system_prompt"
                            rows="4"
                            class="large-text ai-auto-resize"
                        ><?php echo esc_textarea(get_option('ai_chat_system_prompt')); ?></textarea>
                        <p class="description">Коротко: тон и поведение ассистента.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_rules"> Правила ассистента </label></th>
                    <td>
                        <textarea
                            id="ai_chat_rules"
                            name="ai_chat_rules"
                            rows="18"
                            class="large-text ai-auto-resize"
                        ><?php echo esc_textarea($rules); ?></textarea>
                        
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_site_context">Контекст сайта</label></th>
                    <td>
                        <textarea
                            id="ai_chat_site_context"
                            name="ai_chat_site_context"
                            rows="14"
                            class="large-text ai-auto-resize"
                        ><?php echo esc_textarea($site_context); ?></textarea>
                        <p class="description">Факты о компании/услугах/контакты. Это “источник правды”.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_primary_color">Основной цвет</label></th>
                    <td>
                        <input
                            type="text"
                            id="ai_chat_primary_color"
                            name="ai_chat_primary_color"
                            value="<?php echo esc_attr(get_option('ai_chat_primary_color', '#2271b1')); ?>"
                            class="regular-text"
                        />
                        <p class="description">HEX, например <code>#2271b1</code></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_welcome_message">Приветственное сообщение</label></th>
                    <td>
                        <input
							type="text"
							id="ai_chat_welcome_message"
							name="ai_chat_welcome_message"
							value="<?php echo esc_attr(get_option('ai_chat_welcome_message', 'Здравствуйте! Чем могу помочь?')); ?>"
							class="regular-text"
						/>
                        <p class="description">Показывается, если история чата пустая.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_contacts_email">Email для контактов</label></th>
                    <td>
                        <input
                            type="email"
                            id="ai_chat_contacts_email"
                            name="ai_chat_contacts_email"
                            value="<?php echo esc_attr(get_option('ai_chat_contacts_email', get_option('admin_email'))); ?>"
                            class="regular-text"
                            placeholder="info@company.com"
                        />
                        <p class="description">Ассистент будет подставлять этот email в своем ответе.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_contacts_url">Ссылка на страницу «Контакты»</label></th>
                    <td>
                        <input
                            type="url"
                            id="ai_chat_contacts_url"
                            name="ai_chat_contacts_url"
                            value="<?php echo esc_attr(get_option('ai_chat_contacts_url', '')); ?>"
                            class="regular-text"
                            placeholder="https://ваш-сайт/contacts/"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ai_chat_manager_email">Email для уведомлений менеджеру</label></th>
                    <td>
                        <input
                            type="email"
                            id="ai_chat_manager_email"
                            name="ai_chat_manager_email"
                            value="<?php echo esc_attr($manager_email); ?>"
                            class="regular-text"
                        />
                        <p class="description">На этот email приходят заявки из формы.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Сохранить'); ?>
        </form>

        <hr />

        <h2>Тест Groq</h2>
        <p>Проверка ключа и ответ модели.</p>
        <button class="button button-secondary" id="ai-chat-test-groq" type="button">Проверить</button>
        <span id="ai-chat-test-result" style="margin-left:10px;"></span>

        <script>
        (function () {
            const btn = document.getElementById('ai-chat-test-groq');
            const out = document.getElementById('ai-chat-test-result');
            if (!btn) return;

            btn.addEventListener('click', async function () {
                out.textContent = 'Проверяем...';

                try {
                    const res = await fetch(ajaxurl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            action: 'ai_chat_test_groq',
                            nonce: '<?php echo esc_js($test_nonce); ?>'
                        })
                    });

                    const data = await res.json();
                    out.textContent = data.success ? data.data.message : (data.data?.message || 'Ошибка теста');
                } catch (e) {
                    out.textContent = 'Ошибка запроса';
                }
            });
        })();
        </script>
		
       <script>
		(function () {
			const areas = document.querySelectorAll('textarea.ai-auto-resize');
			if (!areas.length) return;

			function getMax(area) {
				const mh = window.getComputedStyle(area).maxHeight;
				const n = parseInt(mh, 10);
				return Number.isFinite(n) ? n : 260;
			}

			function resize(area) {
				const MAX = getMax(area);

				area.style.setProperty('min-height', '0', 'important');
				area.style.setProperty('box-sizing', 'border-box', 'important');

				area.style.setProperty('height', 'auto', 'important');
				area.style.setProperty('overflow-y', 'hidden', 'important');

				const h = area.scrollHeight;

				if (h > MAX) {
					area.style.setProperty('height', MAX + 'px', 'important');
					area.style.setProperty('overflow-y', 'auto', 'important');
				} else {
					area.style.setProperty('height', h + 'px', 'important');
					area.style.setProperty('overflow-y', 'hidden', 'important');
				}
			}

			function bind(area) {
				['input', 'change', 'keyup'].forEach(evt => {
					area.addEventListener(evt, () => resize(area));
				});
				resize(area);
			}

			document.addEventListener('DOMContentLoaded', () => areas.forEach(bind));
			window.addEventListener('load', () => areas.forEach(area => resize(area)));
		})();
		</script>
    </div>
    <?php
}
