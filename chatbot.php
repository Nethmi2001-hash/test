<?php
session_start();
include 'navbar.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "monastery_healthcare";

$conn = new mysqli($servername, $username, $password, $dbname);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - Monastery System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
            --monastery-light: #ffa726;
            --monastery-dark: #e65100;
            --monastery-pale: #fff3e0;
        }
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .chat-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .chat-header {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .chat-box {
            height: 500px;
            overflow-y: auto;
            padding: 20px;
            background: white;
            border: 2px solid var(--monastery-orange);
            border-top: none;
        }
        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        .message.user {
            flex-direction: row-reverse;
        }
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin: 0 10px;
        }
        .message.bot .message-avatar {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
        }
        .message.user .message-avatar {
            background: #6c757d;
            color: white;
        }
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 15px;
            word-wrap: break-word;
        }
        .message.bot .message-content {
            background: var(--monastery-pale);
            border: 1px solid var(--monastery-light);
        }
        .message.user .message-content {
            background: #e9ecef;
            border: 1px solid #dee2e6;
        }
        .chat-input {
            padding: 20px;
            background: white;
            border: 2px solid var(--monastery-orange);
            border-top: 1px solid var(--monastery-light);
            border-radius: 0 0 10px 10px;
        }
        .typing-indicator {
            display: none;
        }
        .typing-indicator.active {
            display: flex;
        }
        .typing-indicator span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--monastery-saffron);
            margin: 0 2px;
            animation: typing 1.4s infinite;
        }
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        .quick-questions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .quick-question-btn {
            background: var(--monastery-pale);
            border: 1px solid var(--monastery-orange);
            color: var(--monastery-dark);
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .quick-question-btn:hover {
            background: var(--monastery-orange);
            color: white;
        }
        .language-selector {
            margin-bottom: 15px;
        }
        .code-block {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid var(--monastery-orange);
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container chat-container">
        <div class="card shadow-sm">
            <div class="chat-header">
                <h3 class="mb-0">🤖 AI Assistant - සමුහ බුද්ධි සහායක</h3>
                <p class="mb-0"><small>Powered by GPT-4 | Bilingual Support (English & Sinhala)</small></p>
            </div>

            <div class="chat-box" id="chatBox">
                <div class="message bot">
                    <div class="message-avatar">🪷</div>
                    <div class="message-content">
                        <strong>Welcome! ආයුබෝවන්!</strong><br><br>
                        I'm your AI assistant for Seela Suwa Herath Bikshu Gilan Arana Healthcare & Donation System.<br><br>
                        I can help you with:<br>
                        • Donation information and processes<br>
                        • Payment methods and procedures<br>
                        • Healthcare services available<br>
                        • Appointment booking guidance<br>
                        • General monastery information<br><br>
                        You can ask questions in <strong>English</strong> or <strong>Sinhala</strong> (සිංහල)!
                    </div>
                </div>
            </div>

            <div class="chat-input">
                <div class="language-selector">
                    <label class="form-label"><i class="bi bi-translate"></i> Language:</label>
                    <select id="languageSelect" class="form-select form-select-sm" style="width: 200px;">
                        <option value="auto">🌐 Auto-detect</option>
                        <option value="en">🇬🇧 English</option>
                        <option value="si">🇱🇰 සිංහල (Sinhala)</option>
                    </select>
                </div>

                <div class="quick-questions">
                    <span class="quick-question-btn" onclick="sendQuickQuestion('How can I make a donation?')">
                        💰 How to donate?
                    </span>
                    <span class="quick-question-btn" onclick="sendQuickQuestion('What are the payment methods?')">
                        💳 Payment methods
                    </span>
                    <span class="quick-question-btn" onclick="sendQuickQuestion('What will my donation be used for?')">
                        📊 Donation usage
                    </span>
                    <span class="quick-question-btn" onclick="sendQuickQuestion('පරිත්‍යාග කරන්නේ කෙසේද?')">
                        🇱🇰 පරිත්‍යාග කරන්නේ කෙසේද?
                    </span>
                </div>

                <div class="input-group">
                    <input type="text" id="userInput" class="form-control" placeholder="Type your question here... / ඔබගේ ප්‍රශ්නය මෙහි ටයිප් කරන්න..." onkeypress="handleKeyPress(event)">
                    <button class="btn btn-primary" onclick="sendMessage()" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); border: none;">
                        <i class="bi bi-send"></i> Send
                    </button>
                </div>

                <div class="typing-indicator mt-2" id="typingIndicator">
                    <span></span><span></span><span></span>
                    <small class="ms-2 text-muted">AI is thinking...</small>
                </div>

                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Powered by OpenAI GPT-4 | 
                        <a href="#" data-bs-toggle="modal" data-bs-target="#setupModal">Setup API Key</a>
                    </small>
                </div>
            </div>
        </div>

        <!-- System Stats (for AI context) -->
        <?php
        $stats = [];
        $stats['total_donations'] = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status='verified'")->fetch_assoc()['total'];
        $stats['donation_count'] = $conn->query("SELECT COUNT(*) as count FROM donations WHERE status='verified'")->fetch_assoc()['count'];
        $stats['monk_count'] = $conn->query("SELECT COUNT(*) as count FROM monks WHERE status='active'")->fetch_assoc()['count'];
        $stats['doctor_count'] = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status='active'")->fetch_assoc()['count'];
        
        $categories_result = $conn->query("SELECT name FROM categories WHERE type='donation'");
        $donation_categories = [];
        while ($row = $categories_result->fetch_assoc()) {
            $donation_categories[] = $row['name'];
        }
        ?>
        <script>
            const systemContext = {
                monastery_name: "Seela Suwa Herath Bikshu Gilan Arana",
                total_donations: "Rs. <?= number_format($stats['total_donations'], 2) ?>",
                donation_count: <?= $stats['donation_count'] ?>,
                monk_count: <?= $stats['monk_count'] ?>,
                doctor_count: <?= $stats['doctor_count'] ?>,
                donation_categories: <?= json_encode($donation_categories) ?>,
                payment_methods: ["Cash", "Bank Transfer", "Card", "PayHere Online Payment"],
                website: "http://localhost/test/"
            };
        </script>
    </div>

    <!-- API Key Setup Modal -->
    <div class="modal fade" id="setupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                    <h5 class="modal-title"><i class="bi bi-key"></i> OpenAI API Setup</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>🔑 Get Your OpenAI API Key</h6>
                    <ol>
                        <li>Visit <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API Keys</a></li>
                        <li>Sign up or log in to your account</li>
                        <li>Click "Create new secret key"</li>
                        <li>Copy the API key (starts with "sk-")</li>
                        <li>Paste it in the file: <code>includes/openai_config.php</code></li>
                    </ol>

                    <h6 class="mt-3">💰 Pricing (Pay-as-you-go)</h6>
                    <ul>
                        <li><strong>GPT-4:</strong> ~$0.03 per 1K tokens (~750 words)</li>
                        <li><strong>GPT-3.5:</strong> ~$0.002 per 1K tokens (cheaper alternative)</li>
                        <li>Free tier: $5 credit for new accounts</li>
                    </ul>

                    <h6 class="mt-3">⚙️ Configuration File</h6>
                    <div class="code-block">
                        <pre><code>&lt;?php
define('OPENAI_API_KEY', 'sk-your-api-key-here');
define('OPENAI_MODEL', 'gpt-4'); // or 'gpt-3.5-turbo'
define('OPENAI_MAX_TOKENS', 500);
define('OPENAI_TEMPERATURE', 0.7);
?&gt;</code></pre>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Note:</strong> If API key is not configured, the chatbot will use fallback responses based on predefined rules.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="https://platform.openai.com/api-keys" target="_blank" class="btn btn-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Get API Key
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="chatbot_script.js"></script>
</body>
</html>
