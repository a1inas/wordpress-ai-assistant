document.addEventListener("DOMContentLoaded", function () {
  if (window.__aiChatInitialized) return;
  window.__aiChatInitialized = true;

  var cfg = window.aiChat || window.aiChatData || null;
  if (!cfg) return;

  var ajaxUrl = cfg.ajax || cfg.ajax_url || "";
  var nonce = cfg.nonce || "";
  if (!ajaxUrl || !nonce) return;

  var widget = document.getElementById("ai-chat-widget");
  var toggle = document.getElementById("ai-chat-toggle");
  var panel = document.getElementById("ai-chat-panel");
  var overlay = document.getElementById("ai-chat-overlay");
  var closeBtn = document.getElementById("ai-chat-close");

  var box = document.getElementById("ai-chat-messages");
  var input = document.getElementById("ai-chat-input");
  var sendBtn = document.getElementById("ai-chat-send");

  var humanBtn = document.getElementById("ai-chat-human-operator");

  // Форма заявки
  var leadPanel = document.getElementById("ai-lead-panel");
  var leadClose = document.getElementById("ai-lead-close");
  var leadBack = document.getElementById("ai-lead-back");
  var leadSubmit = document.getElementById("ai-lead-submit");

  var leadName = document.getElementById("ai-lead-name");
  var leadEmail = document.getElementById("ai-lead-email");
  var leadPhone = document.getElementById("ai-lead-phone");
  var leadTime = document.getElementById("ai-lead-time");
  var leadMessage = document.getElementById("ai-lead-message");

  var leadError = document.getElementById("ai-lead-error");
  var leadSuccess = document.getElementById("ai-lead-success");

  if (!widget || !toggle || !panel || !box || !input || !sendBtn || !closeBtn) return;

  if (cfg.primary) widget.style.setProperty("--ai-primary", cfg.primary);

  var sending = false;
  var leadSent = false;

  function setSendingState(isSending) {
    sending = !!isSending;
    if (sendBtn) sendBtn.disabled = sending;
    if (input) input.disabled = sending;
  }

  function escapeHtml(text) {
    var div = document.createElement("div");
    div.textContent = String(text || "");
    return div.innerHTML;
  }

  function addMessage(role, text) {
    var row = document.createElement("div");
    row.className = "ai-msg " + (role === "user" ? "ai-user" : "ai-assistant");

    var bubble = document.createElement("div");
    bubble.className = "ai-bubble";
    bubble.innerHTML = escapeHtml(text);

    row.appendChild(bubble);
    box.appendChild(row);
    box.scrollTop = box.scrollHeight;
  }

  function addAssistantOnce(text) {
    var last = box && box.lastElementChild ? box.lastElementChild : null;
    if (last && last.classList && last.classList.contains("ai-assistant")) {
      var bubble = last.querySelector(".ai-bubble");
      var lastText = bubble ? (bubble.textContent || "").trim() : "";
      if (lastText === String(text || "").trim()) return;
    }
    addMessage("assistant", text);
  }

  function addTyping() {
    var row = document.createElement("div");
    row.id = "ai-typing";
    row.className = "ai-msg ai-assistant ai-typing";

    var bubble = document.createElement("div");
    bubble.className = "ai-bubble";
    bubble.innerHTML =
      '<span class="ai-dot"></span><span class="ai-dot"></span><span class="ai-dot"></span>';

    row.appendChild(bubble);
    box.appendChild(row);
    box.scrollTop = box.scrollHeight;
  }

  function removeTyping() {
    var el = document.getElementById("ai-typing");
    if (el) el.remove();
  }

  function getWelcomeText() {
    return cfg.welcome ? cfg.welcome : "Здравствуйте! Чем могу помочь?";
  }

  function normalizeText(s) {
    return String(s || "").toLowerCase().replace(/ё/g, "е").trim();
  }

  function detectIntent(userText) {
    var t = normalizeText(userText);

    var contactWords = [
      "контакты", "контакт", "связаться", "связь", "менеджер", "оператор",
      "позвонить", "звонок", "перезвоните", "перезвон", "телефон", "оставить заявку", "заявка"
    ];

    var priceWords = ["цена", "стоимость", "сколько стоит", "сколько будет", "прайс", "тариф", "расценки"];

    var timeWords = ["срок", "сроки", "когда будет", "когда будет готово", "дедлайн", "время выполнения", "за сколько", "в течение"];

    var isContact = contactWords.some(function (w) { return t.indexOf(w) !== -1; });
    var isPrice = priceWords.some(function (w) { return t.indexOf(w) !== -1; });
    var isTime = timeWords.some(function (w) { return t.indexOf(w) !== -1; });

    return { isContact: isContact, isPrice: isPrice, isTime: isTime };
  }

  function setHumanButtonState() {
    if (!humanBtn) return;

    if (leadSent) {
      humanBtn.textContent = "Заявка отправлена";
      humanBtn.disabled = true;
      humanBtn.classList.add("ai-human-disabled");
      return;
    }

    humanBtn.textContent = "Связаться с менеджером";
    humanBtn.disabled = false;
    humanBtn.classList.remove("ai-human-disabled");
  }

  function hideLeadAlerts() {
    if (leadError) {
      leadError.textContent = "";
      leadError.classList.add("ai-chat-hidden");
    }
    if (leadSuccess) {
      leadSuccess.textContent = "";
      leadSuccess.classList.add("ai-chat-hidden");
    }
  }

  function clearFieldError(el) {
    if (!el) return;
    var wrap = el.closest(".ai-lead-field");
    if (!wrap) return;

    wrap.classList.remove("is-error", "ai-shake");

    var hint = wrap.querySelector(".ai-lead-hint");
    if (hint) hint.remove();
  }

  function setFieldError(el, message) {
    if (!el) return;

    var wrap = el.closest(".ai-lead-field");
    if (!wrap) return;

    clearFieldError(el);

    wrap.classList.add("is-error", "ai-shake");

    var hint = document.createElement("div");
    hint.className = "ai-lead-hint";
    hint.textContent = message;

    wrap.appendChild(hint);

    setTimeout(function () {
      wrap.classList.remove("ai-shake");
    }, 300);
  }

  function wireClearOnInput(el) {
    if (!el) return;
    el.addEventListener("input", function () {
      clearFieldError(el);
      if (leadError) {
        leadError.textContent = "";
        leadError.classList.add("ai-chat-hidden");
      }
    });
  }

  wireClearOnInput(leadName);
  wireClearOnInput(leadEmail);
  wireClearOnInput(leadPhone);
  wireClearOnInput(leadTime);
  wireClearOnInput(leadMessage);

  function setLeadSentUI() {
    if (leadName) leadName.disabled = true;
    if (leadEmail) leadEmail.disabled = true;
    if (leadPhone) leadPhone.disabled = true;
    if (leadTime) leadTime.disabled = true;
    if (leadMessage) leadMessage.disabled = true;

    if (leadSubmit) {
      leadSubmit.disabled = true;
      leadSubmit.textContent = "Заявка отправлена";
    }
  }

  function openLeadForm() {
    if (!leadPanel) return;

    hideLeadAlerts();

    widget.classList.add("ai-lead-open");
    leadPanel.classList.remove("ai-chat-hidden");

    clearFieldError(leadName);
    clearFieldError(leadEmail);
    clearFieldError(leadPhone);
    clearFieldError(leadTime);
    clearFieldError(leadMessage);

    if (leadSent) {
      setLeadSentUI();
      return;
    }

    if (leadName) leadName.disabled = false;
    if (leadEmail) leadEmail.disabled = false;
    if (leadPhone) leadPhone.disabled = false;
    if (leadTime) leadTime.disabled = false;
    if (leadMessage) leadMessage.disabled = false;

    if (leadSubmit) {
      leadSubmit.disabled = false;
      leadSubmit.textContent = "Отправить";
    }

    if (leadPhone && !leadPhone.value) leadPhone.value = "+375";
    if (leadName) leadName.focus();
  }

  function closeLeadForm() {
    if (!leadPanel) return;
    leadPanel.classList.add("ai-chat-hidden");
    widget.classList.remove("ai-lead-open");
  }

  function normalizeBYPhone(raw) {
    var v = String(raw || "").replace(/\s+/g, "");
    v = v.replace(/[^\d+]/g, "");

    if (!v.startsWith("+375")) {
      var digits = v.replace(/[^\d]/g, "");
      if (digits.startsWith("375")) digits = digits.slice(3);
      v = "+375" + digits;
    }

    var after = v.slice(4).replace(/[^\d]/g, "").slice(0, 9);
    return "+375" + after;
  }

  if (leadPhone) {
    if (!leadPhone.value) leadPhone.value = "+375";

    leadPhone.addEventListener("input", function () {
      leadPhone.value = normalizeBYPhone(leadPhone.value);
    });

    leadPhone.addEventListener("paste", function () {
      setTimeout(function () {
        leadPhone.value = normalizeBYPhone(leadPhone.value);
      }, 0);
    });

    leadPhone.addEventListener("keydown", function (e) {
      var pos = leadPhone.selectionStart || 0;
      if ((e.key === "Backspace" || e.key === "Delete") && pos <= 4) e.preventDefault();
    });

    leadPhone.addEventListener("focus", function () {
      if (!leadPhone.value) leadPhone.value = "+375";
      leadPhone.value = normalizeBYPhone(leadPhone.value);
    });

    leadPhone.addEventListener("blur", function () {
      leadPhone.value = normalizeBYPhone(leadPhone.value);
    });
  }

  function loadHistory() {
    fetch(ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ action: "ai_chat_history", nonce: nonce })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.success) return;

        box.innerHTML = "";
        var history = data.data && data.data.history ? data.data.history : [];

        history.forEach(function (row) { addMessage(row.role, row.message); });

        if (history.length === 0) addMessage("assistant", getWelcomeText());
      })
      .catch(function () {});
  }

  function sendMessage() {
    var text = input.value.trim();
    if (!text || sending) return;

    setSendingState(true);
    addMessage("user", text);

    input.value = "";
    input.style.height = "auto";

    var intent = detectIntent(text);

    if ((intent.isContact || intent.isPrice || intent.isTime) && !leadSent) {
      addAssistantOnce(
        (intent.isPrice || intent.isTime)
          ? "По стоимости/срокам точнее ответит менеджер. Нажмите кнопку «Связаться с менеджером» ниже и заполните форму."
          : "Нажмите кнопку «Связаться с менеджером» ниже и заполните форму."
      );
      setSendingState(false);
      return;
    }

    addTyping();

    fetch(ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ action: "ai_chat_send", nonce: nonce, message: text })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        removeTyping();
        if (data && data.success && data.data && data.data.reply) addMessage("assistant", data.data.reply);
        else addMessage("assistant", "Ошибка отправки сообщения.");
        setSendingState(false);
      })
      .catch(function () {
        removeTyping();
        addMessage("assistant", "Ошибка соединения с сервером.");
        setSendingState(false);
      });
  }

  function validateName(value) {
    var s = String(value || "").trim();

    if (!/^[A-Za-zА-Яа-яЁё\s'\-]+$/.test(s)) {
      return { ok: false, msg: "Используйте только буквы (можно пробел или дефис)." };
    }

    var letters = (s.match(/[A-Za-zА-Яа-яЁё]/g) || []).length;
    if (letters < 3) return { ok: false, msg: "Минимум 3 буквы." };

    return { ok: true, value: s };
  }

  function validateEmail(value) {
    var s = String(value || "").trim();
    if (!s) return { ok: false, msg: "Введите email." };
    if (!/^\S+@\S+\.\S+$/.test(s)) return { ok: false, msg: "Введите корректный email." };
    return { ok: true, value: s };
  }

  function validatePhone(value) {
    var s = normalizeBYPhone(value);
    if (!/^\+375\d{9}$/.test(s)) return { ok: false, msg: "+375 и 9 цифр (например +375291234567)." };
    return { ok: true, value: s };
  }

  function validateTime(value) {
    var s = String(value || "").trim();

    if (!s) return { ok: false, msg: "Укажите удобное время для связи (например: «сегодня в 18:00»)." };
    if (s.length < 3) return { ok: false, msg: "Напишите чуть подробнее (например: «сегодня в 18:00»)." };
    if (s.length > 80) return { ok: false, msg: "Слишком длинно. Напишите короче (до 80 символов)." };

    var hasLetterOrDigit = /[A-Za-zА-Яа-яЁё0-9]/.test(s);
    if (!hasLetterOrDigit) return { ok: false, msg: "Напишите словами или укажите часы (например: «сегодня в 18:00»)." };

    if (/[<>$%^*{}[\]|\\]/.test(s)) return { ok: false, msg: "Используйте обычный текст (без спецсимволов)." };

    return { ok: true, value: s };
  }

  function submitLead() {
    if (leadSent) return;

    hideLeadAlerts();

    clearFieldError(leadName);
    clearFieldError(leadEmail);
    clearFieldError(leadPhone);
    clearFieldError(leadTime);
    clearFieldError(leadMessage);

    var nameRaw = leadName ? leadName.value : "";
    var emailRaw = leadEmail ? leadEmail.value : "";
    var phoneRaw = leadPhone ? leadPhone.value : "";
    var timeRaw = leadTime ? leadTime.value : "";
    var message = (leadMessage ? leadMessage.value : "").trim();

    var vName = validateName(nameRaw);
    if (!vName.ok) { setFieldError(leadName, vName.msg); leadName && leadName.focus(); return; }

    var vEmail = validateEmail(emailRaw);
    if (!vEmail.ok) { setFieldError(leadEmail, vEmail.msg); leadEmail && leadEmail.focus(); return; }

    var vPhone = validatePhone(phoneRaw);
    if (!vPhone.ok) { setFieldError(leadPhone, vPhone.msg); leadPhone && leadPhone.focus(); return; }

    var vTime = validateTime(timeRaw);
    if (!vTime.ok) { setFieldError(leadTime, vTime.msg); leadTime && leadTime.focus(); return; }

    if (leadSubmit) {
      leadSubmit.disabled = true;
      leadSubmit.textContent = "Отправляем...";
    }

    fetch(ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "ai_chat_submit_lead",
        nonce: nonce,
        name: vName.value,
        email: vEmail.value,
        phone: vPhone.value,
        contact_time: vTime.value,
        message: message,
        page_url: window.location.href
      })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.success) {
          if (leadSuccess) {
            leadSuccess.textContent =
              data.data && data.data.message ? data.data.message : "Спасибо! Заявка отправлена.";
            leadSuccess.classList.remove("ai-chat-hidden");
          }

          leadSent = true;
          setHumanButtonState();
          addAssistantOnce("Спасибо! Менеджер свяжется с вами в указанное время.");

          setLeadSentUI();
          return;
        }

        var serverMsg =
          (data && data.data && data.data.message)
            ? data.data.message
            : "Ошибка отправки заявки.";

        if (leadError) {
          leadError.textContent = serverMsg;
          leadError.classList.remove("ai-chat-hidden");
        }

        if (leadSubmit) {
          leadSubmit.disabled = false;
          leadSubmit.textContent = "Отправить";
        }
      })
      .catch(function () {
        if (leadError) {
          leadError.textContent = "Ошибка соединения. Попробуйте позже.";
          leadError.classList.remove("ai-chat-hidden");
        }
        if (leadSubmit) {
          leadSubmit.disabled = false;
          leadSubmit.textContent = "Отправить";
        }
      });
  }

  function openChat() {
    panel.classList.remove("ai-chat-hidden");
    if (overlay) overlay.classList.remove("ai-chat-hidden");
    widget.classList.add("ai-chat-open");
    loadHistory();
    setHumanButtonState();
    setTimeout(function () { input.focus(); }, 0);
  }

  function closeChat() {
    widget.classList.remove("ai-chat-open");
    if (overlay) overlay.classList.add("ai-chat-hidden");
    closeLeadForm();
    setTimeout(function () { panel.classList.add("ai-chat-hidden"); }, 200);
  }

  toggle.addEventListener("click", function () {
    if (widget.classList.contains("ai-chat-open")) closeChat();
    else openChat();
  });

  closeBtn.addEventListener("click", closeChat);
  if (overlay) overlay.addEventListener("click", closeChat);

  sendBtn.addEventListener("click", sendMessage);

  input.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  input.addEventListener("input", function () {
    this.style.height = "auto";
    this.style.height = Math.min(this.scrollHeight, 110) + "px";
  });

  if (humanBtn) {
    humanBtn.addEventListener("click", function () {
      if (leadSent) return;
      openLeadForm();
    });
  }

  if (leadClose) leadClose.addEventListener("click", closeLeadForm);
  if (leadBack) leadBack.addEventListener("click", closeLeadForm);
  if (leadSubmit) leadSubmit.addEventListener("click", submitLead);

  setHumanButtonState();
});
