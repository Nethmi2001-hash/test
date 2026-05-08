<?php
/**
 * COMPREHENSIVE RULE-BASED CHATBOT ENGINE
 * Monastery Donation & Healthcare Assistant
 * Bilingual: English & Sinhala
 * 
 * FEATURES:
 * - Pattern-based query matching
 * - Database integration for real-time stats
 * - Multi-language support
 * - No external API dependencies
 * - Conversation logging
 */

header('Content-Type: application/json');
require_once __DIR__ . '/includes/db_config.php';

// Start session for logging
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get POST input
$input = json_decode(file_get_contents('php://input'), true);
$user_message = trim($input['message'] ?? '');
$language = $input['language'] ?? 'auto';

// Validate input
if (empty($user_message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message required']);
    exit;
}

// Auto-detect language
if ($language === 'auto') {
    $language = detectLanguage($user_message);
}

// Get live system context
$context = getSystemContext();

// Process the query with rules engine
$response = processUserQuery($user_message, $language, $context);

// Log conversation for analytics
logConversation($user_message, $response, $language);

// Return response
echo json_encode([
    'success' => true,
    'response' => $response,
    'language' => $language,
    'mode' => 'rule-based-intelligent-v1',
    'system' => [
        'donations' => $context['total_donations'],
        'monks' => $context['monk_count'],
        'doctors' => $context['doctor_count']
    ]
]);

/**
 * MAIN QUERY PROCESSOR - Routes messages to appropriate handlers
 */
function processUserQuery($message, $language, $context) {
    $msg_clean = strtolower(preg_replace('/[^\w\s]/', '', $message));
    
    // DONATION QUERIES
    if (matchesPatterns($msg_clean, ['donate', 'donation', 'give', 'sponsor', 'alms', 'offer', 'contribute'])) {
        return handleDonationQuery($language, $context);
    }
    
    // APPOINTMENT & MEDICAL QUERIES
    if (matchesPatterns($msg_clean, ['appointment', 'doctor', 'medical', 'health', 'consult', 'book', 'schedule'])) {
        return handleMedicalQuery($language, $context);
    }
    
    // STATISTICS QUERIES
    if (matchesPatterns($msg_clean, ['statistics', 'stats', 'total', 'count', 'how many', 'number', 'info', 'information'])) {
        return handleStatisticsQuery($language, $context);
    }
    
    // CATEGORY & PURPOSE QUERIES
    if (matchesPatterns($msg_clean, ['category', 'categories', 'where', 'money', 'used', 'spent', 'purpose'])) {
        return handleCategoryQuery($language, $context);
    }
    
    // MONK QUERIES
    if (matchesPatterns($msg_clean, ['monk', 'monks', 'bhikkhu', 'bhikkhus', 'venerable', 'thero', 'community'])) {
        return handleMonkQuery($language, $context);
    }
    
    // DOCTOR QUERIES
    if (matchesPatterns($msg_clean, ['doctor', 'doctors', 'physician', 'specialist', 'ayurvedic', 'western', 'medical'])) {
        return handleDoctorQuery($language, $context);
    }
    
    // PAYMENT QUERIES
    if (matchesPatterns($msg_clean, ['payment', 'pay', 'bank', 'transfer', 'cash', 'card', 'payhere', 'method'])) {
        return handlePaymentQuery($language, $context);
    }
    
    // TRANSPARENCY QUERIES
    if (matchesPatterns($msg_clean, ['transparency', 'report', 'audit', 'fund', 'spending', 'trust', 'account'])) {
        return handleTransparencyQuery($language, $context);
    }
    
    // GREETINGS & HELP
    if (matchesPatterns($msg_clean, ['hello', 'hi', 'hey', 'greet', 'help', 'what can', 'how can you', 'about you'])) {
        return getWelcomeMessage($language, $context);
    }
    
    // DEFAULT FALLBACK
    return getDefaultResponse($language, $context);
}

/**
 * PATTERN MATCHING ENGINE
 */
function matchesPatterns($text, $patterns) {
    foreach ($patterns as $pattern) {
        if (strpos($text, strtolower($pattern)) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * HANDLER: Donation Queries
 */
function handleDonationQuery($language, $context) {
    if ($language === 'si') {
        return "🙏 **පරිත්‍යාග කරන ආකාරයන්**\n\n" .
               "✅ **සෙවන්දි දෙයි** - පරිපාලන කාර්යාලයට\n" .
               "✅ **බැංකු ගිණුම** - People's Bank 123-456-789-0\n" .
               "✅ **ඔන්ලයින්** - PayHere ගිණුම\n" .
               "✅ **දිනපතින අලුත්** - 📅 දිනපතින දිනවලින්\n\n" .
               "**ඔබේ බලපෑම:**\n" .
               "💰 මුළු: " . $context['total_donations'] . "\n" .
               "📦 මාසිකව: " . $context['this_month_donations'] . "\n" .
               "👥 දායකයින්: " . $context['donation_count'] . " දෙනෙක්\n\n" .
               "🙏 ස්වර්ගීය ස්තූතියි! ඔබේ පරිත්‍යාගයින් ජීවිත වෙනස් කරනු ලබයි.";
    } else {
        return "🙏 **How to Donate**\n\n" .
               "✅ **Cash** - Administration office\n" .
               "✅ **Bank Transfer** - People's Bank 123-456-789-0\n" .
               "✅ **Online** - Secure PayHere payment\n" .
               "✅ **Sponsor Daily Alms** - 📅 Schedule giving\n\n" .
               "**Your Impact:**\n" .
               "💰 Total Raised: " . $context['total_donations'] . "\n" .
               "📦 This Month: " . $context['this_month_donations'] . "\n" .
               "👥 Donors: " . $context['donation_count'] . " people\n\n" .
               "🙏 Thank you! Your gift changes lives.";
    }
}

/**
 * HANDLER: Medical & Appointment Queries
 */
function handleMedicalQuery($language, $context) {
    if ($language === 'si') {
        return "🏥 **වෛද්‍ය සේවා**\n\n" .
               "**ශිල්පයන්:**\n" .
               "🌿 **ආයුර්වේද** - තිරසර ප්‍රතිකාර\n" .
               "⚕️ **බටහිර** - අධුනික වෛද්‍ය\n" .
               "🩺 **සාමාන්‍ය** - ප්‍රාථමික සෞඛ්‍ය\n\n" .
               "**සංඛ්‍යා:**\n" .
               "👨‍⚕️ සක්‍රිය වෛද්‍යවරු: " . $context['doctor_count'] . "\n" .
               "📅 අද පෝස්ටු: " . $context['todays_appointments'] . "\n" .
               "🆓 සම්පූර්ණ නිදහසින්\n\n" .
               "පරිපාලන කාර්යාලයට එක්වීමට සඳහන්කරන්න.";
    } else {
        return "🏥 **Medical Services**\n\n" .
               "**Specializations:**\n" .
               "🌿 **Ayurvedic** - Traditional healing\n" .
               "⚕️ **Western** - Modern medicine\n" .
               "🩺 **General** - Primary healthcare\n\n" .
               "**Statistics:**\n" .
               "👨‍⚕️ Active Doctors: " . $context['doctor_count'] . "\n" .
               "📅 Today's Appointments: " . $context['todays_appointments'] . "\n" .
               "🆓 Completely Free\n\n" .
               "Contact administration to schedule an appointment.";
    }
}

/**
 * HANDLER: Statistics Queries
 */
function handleStatisticsQuery($language, $context) {
    if ($language === 'si') {
        return "📊 **සිස්ටම සංඛ්‍යා**\n\n" .
               "💰 **මුළු පරිත්‍යාග:** " . $context['total_donations'] . "\n" .
               "📦 **මෙම මාසය:** " . $context['this_month_donations'] . "\n" .
               "👥 **සක්‍රිය භික්ෂුවරු:** " . $context['monk_count'] . "\n" .
               "🩺 **සක්‍රිය වෛද්‍යවරු:** " . $context['doctor_count'] . "\n" .
               "📅 **අද හමුවීම්:** " . $context['todays_appointments'] . "\n" .
               "🆓 **සියල්ල නිදහසින්**";
    } else {
        return "📊 **System Overview**\n\n" .
               "💰 **Total Donations:** " . $context['total_donations'] . "\n" .
               "📦 **This Month:** " . $context['this_month_donations'] . "\n" .
               "👥 **Active Monks:** " . $context['monk_count'] . "\n" .
               "🩺 **Active Doctors:** " . $context['doctor_count'] . "\n" .
               "📅 **Today's Appointments:** " . $context['todays_appointments'] . "\n" .
               "🆓 **All Services Free**";
    }
}

/**
 * HANDLER: Category & Purpose Queries
 */
function handleCategoryQuery($language, $context) {
    if ($language === 'si') {
        return "📂 **පරිත්‍යාග වර්ගයන්**\n\n" .
               "🏛️ **සාමාන්‍ය සුභසාධනය**\n" .
               "   ගෘහ, බලශක්තිය, ජලය\n\n" .
               "🏥 **සෞඛ්‍ය සේවා**\n" .
               "   වෛද්‍ය සපයුම, ප්‍රතිකාර\n\n" .
               "🍚 **ආහාර සහ සපයුම**\n" .
               "   දෛනික ඛාදෙ, පෙත්‍ය\n\n" .
               "🏠 **නිවස සහ කාමරා**\n" .
               "   ගොඩනැගිලි නඩත්තු\n\n" .
               "✅ **100% පරිවර්තනතාව සහ විශ්වාසය** 🔒";
    } else {
        return "📂 **Where Your Donation Goes**\n\n" .
               "🏛️ **General Welfare**\n" .
               "   Buildings, electricity, water\n\n" .
               "🏥 **Healthcare**\n" .
               "   Medical supplies & treatments\n\n" .
               "🍚 **Food & Supplies**\n" .
               "   Daily meals & provisions\n\n" .
               "🏠 **Housing & Maintenance**\n" .
               "   Facility upkeep & repairs\n\n" .
               "✅ **100% Transparent & Tracked** 🔒";
    }
}

/**
 * HANDLER: Monk Queries
 */
function handleMonkQuery($language, $context) {
    if ($language === 'si') {
        return "👨‍🔬 **භික්ෂුවරු ප්‍රජාව**\n\n" .
               "**සංගම:** " . $context['monk_count'] . " සක්‍රිය\n\n" .
               "**ඔවුන් කරන්නේ:**\n" .
               "🧘 බෞද්ධ භාවනා සහ පුහුණුව\n" .
               "📚 ධර්ම අධ්‍යයනය\n" .
               "🏥 සමාජ සෞඛ්‍ය සේවාව\n" .
               "🍚 ප්‍රජා සේවාව\n\n" .
               "ඔබේ පරිත්‍යාගයින් ඔවුන් සහාය කරන්න! 🙏";
    } else {
        return "👨‍🔬 **Our Monastic Community**\n\n" .
               "**Members:** " . $context['monk_count'] . " active monks\n\n" .
               "**They Dedicate Themselves To:**\n" .
               "🧘 Buddhist meditation & practice\n" .
               "📚 Dharma study & teaching\n" .
               "🏥 Community healthcare\n" .
               "🍚 Social welfare\n\n" .
               "Support them with your donation today! 🙏";
    }
}

/**
 * HANDLER: Doctor Queries
 */
function handleDoctorQuery($language, $context) {
    if ($language === 'si') {
        return "🩺 **වෛද්‍යවරු කණ්ඩායම**\n\n" .
               "**ශිල්පයන්:**\n" .
               "🌿 **ආයුර්වේද** - සතර්ක ප්‍රතිකාර\n" .
               "⚕️ **බටහිර** - අධුනික වෛද්‍ය\n" .
               "🩺 **සාමාන්‍ය** - ප්‍රාථමික සෞඛ්‍ය\n\n" .
               "**සේවා:**\n" .
               "✅ සෞඛ්‍ය ප්‍රකිරණ\n" .
               "✅ ප්‍රතිකාර සහ ඖෂධ\n" .
               "✅ ස්වාස්థ්ය උපදෙස්\n" .
               "✅ හදිසි සහාය\n\n" .
               "**සක්‍රිය වෛද්‍යවරු:** " . $context['doctor_count'];
    } else {
        return "🩺 **Our Medical Team**\n\n" .
               "**Specializations:**\n" .
               "🌿 **Ayurvedic** - Traditional medicine\n" .
               "⚕️ **Western** - Modern healthcare\n" .
               "🩺 **General** - Primary care\n\n" .
               "**Services:**\n" .
               "✅ Health consultations\n" .
               "✅ Treatment & medication\n" .
               "✅ Health counseling\n" .
               "✅ Emergency support\n\n" .
               "**Active Doctors:** " . $context['doctor_count'];
    }
}

/**
 * HANDLER: Payment Queries
 */
function handlePaymentQuery($language, $context) {
    if ($language === 'si') {
        return "💳 **ගිණුම් ක්‍රම**\n\n" .
               "**💵 සෙවන්දි**\n" .
               "   පරිපාලන කාර්යාලයට එක්වන්න\n\n" .
               "**🏦 බැංකු ගිණුම**\n" .
               "   බැංකුව: People's Bank\n" .
               "   ගිණුම: 123-456-789-0\n" .
               "   නම: Seela Suwa Herath Monastery\n" .
               "   උපහාර තිත්පතක් පිටපත් කරන්න\n\n" .
               "**💻 ඔන්ලයින්**\n" .
               "   PayHere ගිණුම\n" .
               "   🔒 සම්පූර්ණ සුරක්ෂිතකම\n\n" .
               "✅ **100% සුරක්ෂිතයි**";
    } else {
        return "💳 **Payment Methods**\n\n" .
               "**💵 Cash**\n" .
               "   Visit administration office\n\n" .
               "**🏦 Bank Transfer**\n" .
               "   Bank: People's Bank\n" .
               "   Account: 123-456-789-0\n" .
               "   Name: Seela Suwa Herath Monastery\n" .
               "   Upload slip for receipt\n\n" .
               "**💻 Online Payment**\n" .
               "   Secure PayHere gateway\n" .
               "   🔒 Encrypted transactions\n\n" .
               "✅ **100% Safe**";
    }
}

/**
 * HANDLER: Transparency Queries
 */
function handleTransparencyQuery($language, $context) {
    if ($language === 'si') {
        return "🔍 **පරිවර්තනතාව සහ ගිණුම්කරණය**\n\n" .
               "අපි සම්පූර්ණ ගිණුම ප්‍රකාශ කරන්න:\n\n" .
               "📊 **වාර්තා** - ප්‍රතිමාසිකව\n" .
               "💰 **වැයුපත** - සම්පූර්ණ විස්තරයන්\n" .
               "👥 **බලපෑම** - ප්‍රජා සේවාව\n" .
               "📈 **ප්‍රතිසිද්ධිය** - වෙබ් අඩවිය\n\n" .
               "✅ ස්වාධීන අඩුසිටුවීම\n" .
               "✅ නියාමකීයතා අනුකූලතා\n" .
               "✅ ජනතා විශ්වාසය";
    } else {
        return "🔍 **Transparency & Accountability**\n\n" .
               "We provide complete financial transparency:\n\n" .
               "📊 **Reports** - Monthly updates\n" .
               "💰 **Budget** - Detailed allocation\n" .
               "👥 **Impact** - Community benefit\n" .
               "📈 **Results** - On our website\n\n" .
               "✅ Independent audit\n" .
               "✅ Regulatory compliance\n" .
               "✅ Public trust";
    }
}

/**
 * WELCOME MESSAGE
 */
function getWelcomeMessage($language, $context) {
    if ($language === 'si') {
        return "🙏 **ස්වාගතයි!**\n\n" .
               "මම Seela Suwa Herath Monastery AI සහයක.\n" .
               "ඔබට උදව් කිරීමට සූදානම්:\n\n" .
               "💰 පරිත්‍යාග - කරන්නේ කොහොමද\n" .
               "🏥 වෛද්‍ය - සේවා ගැන\n" .
               "📊 සංඛ්‍යා - අපගේ බලපෑම\n" .
               "👥 කණ්ඩායම - භික්ෂුවරු & වෛද්‍යවරු\n" .
               "📂 වර්ගයන් - මුදල් භාවිතය\n" .
               "💳 ගිණුම් - ගිණුම් ක්‍රම\n" .
               "🔍 පරිවර්තනතාව - වාර්තා බලන්න\n\n" .
               "ඕනෑම ගිණුම් විමසන්න! 🙏";
    } else {
        return "🙏 **Welcome!**\n\n" .
               "I'm Seela Suwa Herath's AI Assistant.\n" .
               "I can help you with:\n\n" .
               "💰 Donations - How to give\n" .
               "🏥 Healthcare - Medical services\n" .
               "📊 Statistics - Our impact\n" .
               "👥 Team - Monks & doctors\n" .
               "📂 Categories - Where funds go\n" .
               "💳 Payment - Ways to donate\n" .
               "🔍 Transparency - View reports\n\n" .
               "Ask me anything! 🙏";
    }
}

/**
 * DEFAULT FALLBACK RESPONSE
 */
function getDefaultResponse($language, $context) {
    if ($language === 'si') {
        return "😊 ඔබගේ ප්‍රශ්නය සම්පූර්ණයෙන් තේරුම් නොගතුන්.\n\n" .
               "කිසිම ගිණුම් විමසිය හැක:\n" .
               "• පරිත්‍යාග කරන්නේ කොහොමද\n" .
               "• වෛද්‍ය සේවා ගැන\n" .
               "• අපගේ කණ්ඩායම ගැන\n" .
               "• ගිණුම් ක්‍රම ගැන\n" .
               "• පරිවර්තනතාව ගැන\n\n" .
               "ස්පෂ්ටවම නැවතත් විමසන්න! 🙏";
    } else {
        return "😊 I didn't quite understand. Let me help!\n\n" .
               "Try asking about:\n" .
               "• How to donate\n" .
               "• Medical services\n" .
               "• Our team\n" .
               "• Payment methods\n" .
               "• Transparency reports\n\n" .
               "Ask again with more details! 🙏";
    }
}

/**
 * UTILITIES
 */

function detectLanguage($text) {
    // Sinhala Unicode range: U+0D80-U+0DFF
    if (preg_match('/[\x{0D80}-\x{0DFF}]/u', $text)) {
        return 'si';
    }
    return 'en';
}

function getSystemContext() {
    $context = [
        'monastery_name' => 'Seela Suwa Herath Monastery',
        'total_donations' => 'Rs. 0',
        'this_month_donations' => 'Rs. 0',
        'donation_count' => 0,
        'monk_count' => 0,
        'doctor_count' => 0,
        'todays_appointments' => 0
    ];
    
    try {
        $conn = getDBConnection();
        
        // Total donations
        $result = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total FROM donations WHERE status IN ('verified', 'paid')");
        if ($result && $row = $result->fetch_assoc()) {
            $context['donation_count'] = $row['cnt'];
            $context['total_donations'] = 'Rs. ' . number_format($row['total'], 2);
        }
        
        // This month
        $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status IN ('verified', 'paid') AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        if ($result && $row = $result->fetch_assoc()) {
            $context['this_month_donations'] = 'Rs. ' . number_format($row['total'], 2);
        }
        
        // Monks
        $result = $conn->query("SELECT COUNT(*) as cnt FROM monks WHERE status='active'");
        if ($result) {
            $context['monk_count'] = $result->fetch_assoc()['cnt'];
        }
        
        // Doctors
        $result = $conn->query("SELECT COUNT(*) as cnt FROM doctors WHERE status='active'");
        if ($result) {
            $context['doctor_count'] = $result->fetch_assoc()['cnt'];
        }
        
        // Today's appointments
        $result = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE app_date = CURDATE() AND status='scheduled'");
        if ($result) {
            $context['todays_appointments'] = $result->fetch_assoc()['cnt'];
        }
        
        $conn->close();
    } catch (Exception $e) {
        error_log("Context error: " . $e->getMessage());
    }
    
    return $context;
}

function logConversation($user_msg, $bot_response, $language) {
    try {
        $conn = getDBConnection();
        $session = session_id() ?: 'anonymous';
        $mode = 'rule-based-v1';
        $stmt = $conn->prepare("INSERT INTO chat_logs (session_id, user_message, bot_response, language, mode, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $session, $user_msg, $bot_response, $language, $mode);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Log error: " . $e->getMessage());
    }
}
?>
