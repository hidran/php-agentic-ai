<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Enterprise FAQ Chatbot</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'/>
    <!-- Bootstrap 5 CSS CDN -->
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body {
            background: #f6f7fb;
        }

        .chat-window {
            max-width: 500px;
            min-height: 70vh;
            margin: 40px auto;
            border-radius: 1.5rem;
            box-shadow: 0 4px 24px 0 rgba(60, 72, 100, .15);
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-messages {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .chat-bubble {
            max-width: 75%;
            padding: 1rem 1.2rem;
            border-radius: 1.25rem;
            margin-bottom: .5rem;
        }

        .chat-user {
            background: #e9f3fe;
            align-self: end;
        }

        .chat-bot {
            background: #e6e6e6;
            align-self: start;
        }

        .chat-footer {
            background: #f8fafc;
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .typing {
            font-style: italic;
            color: #888;
        }

        .chat-title {
            max-width: 500px;
            margin: 32px auto 0 auto;
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: #244674;
            letter-spacing: .02em;
            text-shadow: 0 2px 8px rgba(60, 72, 100, .10);
        }

        @media (max-width: 600px) {
            .chat-title, .chat-window {
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>
<div class='chat-title'>Enterprise FAQ Chatbot</div>
<div class='chat-window d-flex flex-column'>
    <div id='messages' class='chat-messages d-flex flex-column'></div>
    <form id='chat-form' class='chat-footer d-flex gap-2' autocomplete='off'>
        <input id='chat-input' type='text' class='form-control' placeholder='Type your question…' autocomplete='off'/>
        <button class='btn btn-primary px-4' type='submit'>Ask</button>
    </form>
</div>

<!-- Bootstrap JS (optional for features) -->
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>
<script>
    const messagesDiv = document.getElementById('messages');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');

    function appendMessage(text, sender, isTyping = false) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-bubble chat-${sender} ${isTyping ? 'typing' : ''}`;
        msgDiv.textContent = text;
        messagesDiv.appendChild(msgDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
        return msgDiv;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const question = input.value.trim();
        if (!question) return;
        appendMessage(question, 'user');
        input.value = '';
        input.focus();

        const typingMsg = appendMessage('Bot is typing…', 'bot', true);

        try {
            const res = await fetch(`/api/faq_rag.php?q=${encodeURIComponent(question)}`);
            if (!res.ok) throw new Error('Network error');
            const data = await res.json();
            typingMsg.remove();
            appendMessage((data?.answer ) || 'No answer available.', 'bot');
        } catch (err) {
            typingMsg.remove();
            appendMessage('Failed to fetch answer. Please try again.', 'bot');
        }
    });
</script>
</body>
</html>
