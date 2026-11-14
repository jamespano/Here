<?php
// index.php - ChatGPT-like Chat Interface
session_start();

// Initialize chat history in session
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Handle new message via POST (AJAX fallback)
$response = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $userMessage = trim($_POST['message']);
    if (!empty($userMessage)) {
        // Store user message
        $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $userMessage, 'time' => date('H:i')];

        // Generate AI response (simulated - replace with real AI API later)
        $aiResponse = generateAIResponse($userMessage);
        $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $aiResponse, 'time' => date('H:i')];

        $response = $aiResponse;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'response' => $response]);
    exit;
}

// Handle clear chat
if (isset($_GET['clear'])) {
    $_SESSION['chat_history'] = [];
    header('Location: index.php');
    exit;
}

function generateAIResponse($message) {
    $lower = strtolower($message);
    
    $responses = [
        'hello' => "Hello! How can I help you today?",
        'hi' => "Hi there! What's on your mind?",
        'help' => "I'm here to help. You can ask me questions, request code, or just chat!",
        'who are you' => "I'm your AI assistant, powered by PHP. I'm designed to be helpful, friendly, and smart.",
        'php' => "PHP is a popular server-side scripting language. I can help you write PHP code, debug scripts, or explain concepts!",
        'default' => "That's interesting! Tell me more about that. I'm here to help with whatever you need.",
    ];

    foreach ($responses as $keyword => $reply) {
        if (strpos($lower, $keyword) !== false) {
            return $reply;
        }
    }

    return $responses['default'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatAI - Your AI Assistant</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="chat-app">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fa-solid fa-robot"></i>
                    <span>ChatAI</span>
                </div>
            <button class="btn-new-chat" onclick="newChat()">
                <i class="fa-solid fa-plus"></i> New chat
            </div>

            <button id="sidebar-toggle" onclick="toggleSidebar()">☰ Toggle</button>

            <div class="sidebar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search chats" />
            </div>

            <div class="chat-list">
                <div class="chat-item active" onclick="selectChat(this)">
                    <div class="chat-item-info">
                        <div class="chat-item-title">General Assistant</div>
                        <div class="chat-item-preview">Ask me anything...</div>
                    </div>
                    <div class="chat-item-time">Just now</div>
                </div>
            </div>

            <div class="sidebar-footer">
                <a href="?clear=1" class="btn-clear" onclick="return confirm('Clear all chats?')">
                    <i class="fa-solid fa-trash"></i> Clear chats
                </a>
                <div class="version">v1.0.0</div>
            </div>
        </aside>

        <!-- Main Chat Area -->
        <main class="main-content">
            <!-- Empty State -->
            <div class="empty-state" id="emptyState">
                <div class="empty-icon">
                    <i class="fa-solid fa-message"></i>
                </div>
                <h2>Welcome to ChatAI</h2>
                <p>Ask me anything — from writing code to answering questions. I'm here to help.</p>
                <div class="suggestions">
                    <div class="suggestion-chip" onclick="sendSuggestion('Write a PHP login system')">
                        <i class="fa-solid fa-code"></i> Write a PHP login system
                    </div>
                    <div class="suggestion-chip" onclick="sendSuggestion('Explain quantum computing')">
                        <i class="fa-solid fa-lightbulb"></i> Explain quantum computing
                    </div>
                    <div class="suggestion-chip" onclick="sendSuggestion('Plan a trip to Japan')">
                        <i class="fa-solid fa-plane"></i> Plan a trip to Japan
                    </div>
                    <div class="suggestion-chip" onclick="sendSuggestion('Write a poem about coding')">
                        <i class="fa-solid fa-book"></i> Write a poem about coding
                    </div>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="messages-container" id="messagesContainer">
                <?php if (!empty($_SESSION['chat_history'])): ?>
                    <?php foreach ($_SESSION['chat_history'] as $msg): ?>
                        <div class="message <?= $msg['role'] === 'user' ? 'message-user' : 'message-assistant' ?>">
                            <div class="message-avatar <?= $msg['role'] === 'user' ? 'avatar-user' : 'avatar-ai' ?>">
                                <?= $msg['role'] === 'user' ? '<i class="fa-solid fa-user"></i>' : '<i class="fa-solid fa-robot"></i>' ?>
                            </div>
                            <div class="message-content">
                                <div class="message-bubble"><?= nl2br(htmlspecialchars($msg['content'])) ?></div>
                                <div class="message-time"><?= $msg['time'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Typing indicator -->
            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                <div class="typing-bubble">
                    <span></span><span></span><span></span>
                </div>
            </div>

            <!-- Input Area -->
            <div class="input-area">
                <form id="chatForm">
                    <div class="input-wrapper">
                        <textarea id="messageInput" rows="1" placeholder="Message ChatAI..."></textarea>
                        <input type="hidden" name="message" id="hiddenMessage">
                    </div>
                    <button type="submit" class="btn-send" id="btnSend">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>