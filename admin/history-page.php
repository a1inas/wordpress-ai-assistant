<?php
if (!defined('ABSPATH')) {
    exit;
}

function ai_chat_render_history_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ai_chat_messages';

    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $sessions = (int) $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table");

    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 50", ARRAY_A);
    ?>
    <div class="wrap">
        <h1>AI Chat Assistant — История</h1>

		<?php if (isset($_GET['ai_chat_cleared'])): ?>
			<?php if ($_GET['ai_chat_cleared'] === '1'): ?>
				<div class="notice notice-success is-dismissible">
					<p>История диалогов очищена.</p>
				</div>
			<?php else: ?>
				<div class="notice notice-error is-dismissible">
					<p>Не удалось очистить историю. Проверьте доступ к базе данных.</p>
				</div>
			<?php endif; ?>
		<?php endif; ?>

        <p><strong>Сообщений:</strong> <?php echo esc_html($total); ?> |
           <strong>Сессий:</strong> <?php echo esc_html($sessions); ?></p>

		<div style="margin: 16px 0; padding: 14px; background: #fff8e5; border-radius: 6px;">
			<strong>Опасная операция:</strong> очистка удалит все сообщения из базы данных без возможности восстановления.
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 10px;">
				<?php wp_nonce_field('ai_chat_clear_history'); ?>
				<input type="hidden" name="action" value="ai_chat_clear_history">
				<button type="submit" class="button button-secondary"
						onclick="return confirm('Точно очистить всю историю диалогов? Это действие нельзя отменить.');">
					Очистить историю
				</button>
			</form>
		</div>

        <?php if (!$rows): ?>
            <p>Пока нет сообщений.</p>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Время</th>
                        <th>Сессия</th>
                        <th>Роль</th>
                        <th>Сообщение</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo (int) $r['id']; ?></td>
                            <td><?php echo esc_html($r['created_at']); ?></td>
                            <td><code><?php echo esc_html(substr($r['session_id'], 0, 8)); ?>...</code></td>
                            <td><?php echo esc_html($r['role']); ?></td>
                            <td><?php echo esc_html($r['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
