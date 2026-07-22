<?php
/**
 * d-more.jp AIチャットウィジェット
 * 子テーマ functions.php の末尾に追記してください。
 *
 * ※ VERCEL_ENDPOINT は Vercel デプロイ完了後に発行される URL に置き換えること
 *    例: https://dmore-chat.vercel.app/api/chat
 */
add_action( 'wp_footer', 'dmore_ai_chat_widget' );
function dmore_ai_chat_widget() {
  if ( is_admin() ) {
    return;
  }
  $endpoint = 'https://VERCEL_ENDPOINT/api/chat'; // ← Vercelデプロイ後に置き換え
  ?>
  <div id="dmore-chat-widget">
    <button id="dmore-chat-toggle" aria-label="チャットを開く">
      <span class="dmore-chat-icon">💬</span>
    </button>
    <div id="dmore-chat-panel" class="dmore-chat-hidden">
      <div id="dmore-chat-header">
        <span>お問い合わせチャット</span>
        <button id="dmore-chat-close" aria-label="閉じる">×</button>
      </div>
      <div id="dmore-chat-messages"></div>
      <form id="dmore-chat-form">
        <input type="text" id="dmore-chat-input" placeholder="ご質問をどうぞ" autocomplete="off" />
        <button type="submit">送信</button>
      </form>
    </div>
  </div>

  <style>
    #dmore-chat-widget { position: fixed; right: 20px; bottom: 20px; z-index: 9999; font-family: inherit; }
    #dmore-chat-toggle {
      width: 56px; height: 56px; border-radius: 50%;
      background: #1c2451; color: #fff; border: none;
      box-shadow: 0 4px 12px rgba(0,0,0,.25); cursor: pointer; font-size: 24px;
    }
    #dmore-chat-panel {
      position: absolute; right: 0; bottom: 70px;
      width: 320px; max-width: 90vw; height: 440px; max-height: 70vh;
      background: #fdfbf6; border: 1px solid #c9a35c;
      border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.25);
      display: flex; flex-direction: column; overflow: hidden;
    }
    #dmore-chat-panel.dmore-chat-hidden { display: none; }
    #dmore-chat-header {
      background: #1c2451; color: #fff; padding: 10px 14px;
      display: flex; justify-content: space-between; align-items: center; font-size: 14px;
    }
    #dmore-chat-header button { background: none; border: none; color: #fff; font-size: 18px; cursor: pointer; }
    #dmore-chat-messages {
      flex: 1; padding: 10px; overflow-y: auto; font-size: 13px; line-height: 1.6;
    }
    .dmore-msg { margin-bottom: 10px; padding: 8px 10px; border-radius: 8px; max-width: 85%; }
    .dmore-msg.user { background: #1c2451; color: #fff; margin-left: auto; }
    .dmore-msg.bot { background: #eee3cf; color: #1c2451; margin-right: auto; }
    #dmore-chat-form { display: flex; border-top: 1px solid #e0d5b8; }
    #dmore-chat-input { flex: 1; border: none; padding: 10px; font-size: 13px; }
    #dmore-chat-form button {
      border: none; background: #c9a35c; color: #1c2451; padding: 0 14px; cursor: pointer; font-weight: bold;
    }
  </style>

  <script>
  (function () {
    var endpoint = '<?php echo esc_js( $endpoint ); ?>';
    var toggle = document.getElementById('dmore-chat-toggle');
    var panel = document.getElementById('dmore-chat-panel');
    var closeBtn = document.getElementById('dmore-chat-close');
    var form = document.getElementById('dmore-chat-form');
    var input = document.getElementById('dmore-chat-input');
    var messagesEl = document.getElementById('dmore-chat-messages');
    var history = [];

    function appendMessage(role, text) {
      var div = document.createElement('div');
      div.className = 'dmore-msg ' + (role === 'user' ? 'user' : 'bot');
      div.textContent = text;
      messagesEl.appendChild(div);
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    toggle.addEventListener('click', function () {
      panel.classList.toggle('dmore-chat-hidden');
      if (!panel.classList.contains('dmore-chat-hidden') && messagesEl.children.length === 0) {
        appendMessage('bot', 'こんにちは。ダスキンサービスマスターモアー店です。ハウスクリーニングに関するご質問をどうぞ。');
      }
    });
    closeBtn.addEventListener('click', function () {
      panel.classList.add('dmore-chat-hidden');
    });

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var text = input.value.trim();
      if (!text) return;
      appendMessage('user', text);
      history.push({ role: 'user', content: text });
      input.value = '';
      input.disabled = true;

      try {
        var res = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ messages: history }),
        });
        var data = await res.json();
        var reply = data.reply || 'すみません、うまくお答えできませんでした。お手数ですがお電話またはお問い合わせフォームからご連絡ください。';
        appendMessage('bot', reply);
        history.push({ role: 'assistant', content: reply });
      } catch (err) {
        appendMessage('bot', '通信エラーが発生しました。時間をおいて再度お試しください。');
      } finally {
        input.disabled = false;
        input.focus();
      }
    });
  })();
  </script>
  <?php
}

