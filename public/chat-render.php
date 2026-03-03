<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }
    ?>
    <div class="ai-chat-widget" id="ai-chat-widget">
        <button type="button" class="ai-chat-toggle" id="ai-chat-toggle" aria-label="Открыть чат">
            <span class="ai-chat-toggle-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M4 5.5C4 4.119 5.119 3 6.5 3H17.5C18.881 3 20 4.119 20 5.5V13.5C20 14.881 18.881 16 17.5 16H10L6 20V16H6.5C5.119 16 4 14.881 4 13.5V5.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </span>
        </button>

        <div class="ai-chat-overlay ai-chat-hidden" id="ai-chat-overlay"></div>

        <div class="ai-chat-panel ai-chat-hidden" id="ai-chat-panel" role="dialog" aria-label="Чат с ассистентом">
            <div class="ai-chat-header">
                <div>
                    <div class="ai-chat-title">AI Ассистент</div>
                    <div class="ai-chat-status">
                        <span class="ai-chat-dot" aria-hidden="true"></span>
                        Онлайн
                    </div>
                </div>
                <button type="button" class="ai-chat-close" id="ai-chat-close" aria-label="Закрыть">×</button>
            </div>

            <div class="ai-chat-messages" id="ai-chat-messages"></div>

			<div class="ai-lead-panel ai-chat-hidden" id="ai-lead-panel" role="dialog" aria-label="Заявка менеджеру">
				<div class="ai-lead-header">
					<div class="ai-lead-title">Связаться с менеджером</div>
					<button type="button" class="ai-lead-close" id="ai-lead-close" aria-label="Закрыть">×</button>
				</div>

				<div class="ai-lead-body">
					<div class="ai-lead-field">
						<label for="ai-lead-name">Ваше имя<span class="ai-req">*</span></label>
						<input type="text" id="ai-lead-name" autocomplete="name" />
					</div>

					<div class="ai-lead-field">
						<label for="ai-lead-email">Email<span class="ai-req">*</span></label>
						<input type="email" id="ai-lead-email" autocomplete="email" />
					</div>

					<div class="ai-lead-field">
						<label for="ai-lead-phone">Телефон<span class="ai-req">*</span></label>
						<input
						  type="tel"
						  id="ai-lead-phone"
						  autocomplete="tel"
						  inputmode="tel"
						  value="+375"
						  maxlength="13"
						  placeholder="+375XXXXXXXXX"
						/>
					</div>

					<div class="ai-lead-field">
						<label for="ai-lead-time">Время для связи с Вами<span class="ai-req">*</span></label>
						<input type="text" id="ai-lead-time" placeholder="Например: сегодня 15:00–18:00" />
					</div>

					<div class="ai-lead-field">
						<label for="ai-lead-message">Напишите нам</label>
						<textarea id="ai-lead-message" rows="3" placeholder="Коротко опишите задачу (необязательно)"></textarea>
					</div>

					<div class="ai-lead-error ai-chat-hidden" id="ai-lead-error"></div>
					<div class="ai-lead-success ai-chat-hidden" id="ai-lead-success"></div>

					<div class="ai-lead-actions">
						<button type="button" class="ai-lead-back" id="ai-lead-back">Назад</button>
						<button type="button" class="ai-lead-submit" id="ai-lead-submit">Отправить</button>
					</div>
				</div>
			</div>

            <div class="ai-chat-input">
                <textarea id="ai-chat-input" rows="1" placeholder="Напишите сообщение..."></textarea>
                <button type="button" id="ai-chat-send" aria-label="Отправить">→</button>
            </div>
			<div class="ai-chat-footer">
			  <button type="button" class="ai-chat-human-btn" id="ai-chat-human-operator">
				Связаться с менеджером
			  </button>
			</div>
        </div>
    </div>
    <?php
});
