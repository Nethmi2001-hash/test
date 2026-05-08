<?php
/**
 * COMPREHENSIVE RULE-BASED CHATBOT
 * Enhanced with system analysis and intelligent pattern matching
 * No API required - completely rule-based
 */

header('Content-Type: application/json');
require_once __DIR__ . '/includes/db_config.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$user_message = $input['message'] ?? '';
$language = $input['language'] ?? 'auto';

if (empty($user_message)) {
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

// Detect language
if ($language === 'auto') {
    $language = detectLanguage($user_message);
}

// Get context
$context = getSystemContext();

// Process query - enhanced rule-based logic
$response = processQuery($user_message, $language, $context);

echo json_encode([
    'success' => true,
    'response' => $response,
    'language' => $language,
    'mode' => 'rule-based-enhanced',
    'context' => $context
]);

/**
 * Main query processor - handles all types of queries
 */
function processQuery($message, $language, $context) {
    $message_lower = strtolower(trim($message));
    
    // Remove common filler words
    $clean_msg = preg_replace('/\b(the|a|an|is|are|can|could|would|please|can you|how|what|tell me|show me)\b/i', '', $message_lower);
    
    // ===== DONATION QUERIES =====
    if (matchesKeywords($message_lower, ['donate', 'donation', 'give', 'contribute', 'sponsor'])) {
        return handleDonationQuery($message_lower, $language, $context);
    }
    
    // ===== APPOINTMENT QUERIES =====
    if (matchesKeywords($message_lower, ['appointment', 'appointment', 'consult', 'doctor', 'medical', 'health'])) {
        return handleAppointmentQuery($message_lower, $language, $context);
    }
    
    // ===== STATISTICS QUERIES =====
    if (matchesKeywords($message_lower, ['statistics', 'stats', 'total', 'count', 'how many', 'number'])) {
        return handleStatisticsQuery($message_lower, $language, $context);
    }
    
    // ===== CATEGORY QUERIES =====
    if (matchesKeywords($message_lower, ['category', 'categories', 'type', 'kinds', 'purpose', 'where does money go'])) {
        return handleCategoryQuery($language, $context);
    }
    
    // ===== MONK QUERIES =====
    if (matchesKeywords($message_lower, ['monk', 'monks', 'bhikkhu', 'bhikkhus', 'venerable', 'thero'])) {
        return handleMonkQuery($message_lower, $language, $context);
    }
    
    // ===== DOCTOR QUERIES =====
    if (matchesKeywords($message_lower, ['doctor', 'doctors', 'physician', 'ayurvedic', 'western', 'specialization'])) {
        return handleDoctorQuery($message_lower, $language, $context);
    }
    
    // ===== PAYMENT METHOD QUERIES =====
    if (matchesKeywords($message_lower, ['payment', 'pay', 'bank', 'transfer', 'cash', 'card', 'payhere', 'how to pay'])) {
        return handlePaymentQuery($language, $context);
    }
    
    // ===== TRANSPARENCY & REPORTS =====
    if (matchesKeywords($message_lower, ['transparency', 'report', 'where', 'fund', 'used', 'spent'])) {
        return handleTransparencyQuery($language, $context);
    }
    
    // ===== GREETING & HELP =====
    if (matchesKeywords($message_lower, ['hello', 'hi', 'hey', 'greet', 'help', 'what can you do', 'capabilities'])) {
        return getWelcomeMessage($language, $context);
    }
    
    // Default response
    return getDefaultResponse($language, $context);
}

/**
 * Check if message matches any keywords
 */
function matchesKeywords($message, $keywords) {
    foreach ($keywords as $keyword) {
        if (strpos($message, strtolower($keyword)) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Handle donation-related queries
 */
function handleDonationQuery($message, $language, $context) {
    $conn = getDBConnection();
    
    // Get donation statistics
    $result = $conn->query("
        SELECT COUNT(*) as count, SUM(amount) as total 
        FROM donations 
        WHERE status IN ('verified', 'paid')
    ");
    $stats = $result->fetch_assoc();
    
    if ($language === 'si') {
        $response = "🙏 **පරිත්‍යාග ගැන**\n\n";
        $response .= "**මුළු පරිත්‍යාග:** " . number_format($stats['total'], 0) . " LKR\n";
        $response .= "**පරිත්‍යාගකරුවරු:** " . $stats['count'] . " දෙනෙක්\n";
        $response .= "**මෙම මාසය:** " . $context['this_month_donations'] . "\n\n";
        $response .= "**ගැයිතිය:**\n";
        $response .= "💵 **සෙවන්දි දෙයි** - ඉතිරිවිතරක\n";
        $response .= "🏦 **බැංකු ගිණුම** - ඉතිරිවිතරක ඉතුර ගන්න\n";
        $response .= "💳 **ඔන්ලයින්** - PayHere හරහා ආරක්ෂිතව ගෙවන්න\n";
        $response .= "📅 **දිනපත් දිනවලින්** - දිනපතින පරිත්‍යාග සඳහා සඳහන් කරන්න\n\n";
        $response .= "**පරිත්‍යාග වර්ගයන්:**\n";
        $response .= "🏛️ සාමාන්‍ය සුභසාධනය\n";
        $response .= "🏥 සෞඛ්‍ය සේවා\n";
        $response .= "🍚 ආහාර සහ සපයුම\n";
        $response .= "🏠 නිවස සහ කාමරා\n";
    } else {
        $response = "🙏 **About Donations**\n\n";
        $response .= "**Total Raised:** Rs. " . number_format($stats['total'], 0) . "\n";
        $response .= "**Number of Donors:** " . $stats['count'] . " people\n";
        $response .= "**This Month:** " . $context['this_month_donations'] . "\n\n";
        $response .= "**How to Donate:**\n";
        $response .= "💵 **Cash** - Direct donations\n";
        $response .= "🏦 **Bank Transfer** - Get account details below\n";
        $response .= "💳 **Online Payment** - Secure via PayHere\n";
        $response .= "📅 **Schedule Alms** - Reserve a date for daily alms donations\n\n";
        $response .= "**Donation Categories:**\n";
        $response .= "🏛️ General Welfare\n";
        $response .= "🏥 Healthcare\n";
        $response .= "🍚 Food & Supplies\n";
        $response .= "🏠 Housing & Rooms\n\n";
        $response .= "Every donation helps us serve the monastery community better. Thank you! 🙏\n";
    }
    
    $conn->close();
    return $response;
}

/**
 * Handle appointment queries
 */
function handleAppointmentQuery($message, $language, $context) {
    $conn = getDBConnection();
    
    // Get today's appointments
    $result = $conn->query("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE app_date = CURRENT_DATE() 
        AND status = 'scheduled'
    ");
    $today_apps = $result->fetch_assoc()['count'];
    
    if ($language === 'si') {
        $response = "🏥 **වෛද්‍ය හමුවීම්**\n\n";
        $response .= "**අද හමුවීම්:** " . $today_apps . " ක්‍රමලේඛන\n";
        $response .= "**ඉතිරිවිතරක දෙනු:** අධිකරණ නිල ස්ථානයෙහි\n\n";
        $response .= "**වෛද්‍ය ශිල්පයන්:**\n";
        $response .= "🌿 ආයුර්වේද වෛද්‍ය\n";
        $response .= "⚕️ බටහිර වෛද්‍ය\n";
        $response .= "🩺 සාමාන්‍ය වෛද්‍ය\n\n";
        $response .= "හමුවීම් වෙතින් ඉතිරිවිතරක ඉතුර ගැනීමට හෝ දිනය වෙනස් කිරීමට අධිකරණ කාර්ය කර්තෘ ඉතිරිවිතරක ඉතුර ගන්න.";
    } else {
        $response = "🏥 **Medical Appointments**\n\n";
        $response .= "**Today's Scheduled:** " . $today_apps . " appointments\n";
        $response .= "**Available Doctors:** " . $context['doctor_count'] . " active\n\n";
        $response .= "**Specializations:**\n";
        $response .= "🌿 Ayurvedic Medicine\n";
        $response .= "⚕️ Western Medicine\n";
        $response .= "🩺 General Practice\n\n";
        $response .= "To book an appointment, contact the administration office.";
    }
    
    $conn->close();
    return $response;
}

/**
 * Handle statistics queries
 */
function handleStatisticsQuery($message, $language, $context) {
    if ($language === 'si') {
        return "📊 **ඉතිරිවිතරක සංඛ්‍යා**\n\n" .
               "💰 **මුළු පරිත්‍යාග:** " . $context['total_donations'] . "\n" .
               "📦 **මෙම මාසය:** " . $context['this_month_donations'] . "\n" .
               "👥 **සක්‍රිය භික්ෂුවරු:** " . $context['monk_count'] . " දෙනෙක්\n" .
               "🩺 **සක්‍රිය වෛද්‍යවරු:** " . $context['doctor_count'] . " දෙනෙක්\n" .
               "📅 **අද හමුවීම්:** " . $context['todays_appointments'] . "\n";
    } else {
        return "📊 **System Statistics**\n\n" .
               "💰 **Total Donations:** " . $context['total_donations'] . "\n" .
               "📦 **This Month:** " . $context['this_month_donations'] . "\n" .
               "👥 **Active Monks:** " . $context['monk_count'] . "\n" .
               "🩺 **Active Doctors:** " . $context['doctor_count'] . "\n" .
               "📅 **Today's Appointments:** " . $context['todays_appointments'] . "\n";
    }
}

/**
 * Handle category queries
 */
function handleCategoryQuery($language, $context) {
    if ($language === 'si') {
        return "📂 **පරිත්‍යාග වර්ගයන්**\n\n" .
               "🏛️ **සාමාන්‍ය සුභසාධනය** - සාමාන්‍ය අවශ්‍යතා සඳහා\n" .
               "🏥 **සෞඛ්‍ය සේවා** - වෛද්‍ය සපයුම සහ ප්‍රතිකාර\n" .
               "🍚 **ආහාර සහ සපයුම** - දෛනික খाद්‍ය සඳහා\n" .
               "🏠 **නිවස සහ කාමරා** - ගොඩනැගිලි නඩත්තු\n\n" .
               "සෑම පරිත්‍යාගයකම 100% විශ්වාසිතව වාර්තා කරනු ලැබේ.";
    } else {
        return "📂 **Donation Categories**\n\n" .
               "🏛️ **General Welfare** - Basic needs and operations\n" .
               "🏥 **Healthcare** - Medical supplies and treatments\n" .
               "🍚 **Food & Supplies** - Daily meals and provisions\n" .
               "🏠 **Housing & Rooms** - Facility maintenance\n\n" .
               "100% of your donation is tracked and reported transparently.";
    }
}

/**
 * Handle monk queries
 */
function handleMonkQuery($message, $language, $context) {
    if ($language === 'si') {
        return "👨‍🔬 **භික්ෂුවරු**\n\n" .
               "**සක්‍රිය භික්ෂුවරු:** " . $context['monk_count'] . " දෙනෙක්\n\n" .
               "ෙනාමින අනුගමනය කරනු ලබන්නේ:\n" .
               "🌿 විනයඉතිරිවිතරක කෙසේ\n" .
               "📚 බෞද්ධ අධ්‍යයනය\n" .
               "🏥 සෞඛ්‍ය සේවා\n" .
               "🍚 ස්වභාවික ජීවිතයාපනය\n\n" .
               "ඔබේ පරිත්‍යාගයින් නිවාස ඉතිරිවිතරක ඉතුරු කරන්න.";
    } else {
        return "👨‍🔬 **Our Monks**\n\n" .
               "**Active Monks:** " . $context['monk_count'] . "\n\n" .
               "They dedicate their lives to:\n" .
               "🌿 Buddhist practice and discipline\n" .
               "📚 Spiritual studies\n" .
               "🏥 Healthcare service\n" .
               "🍚 Serving the community\n\n" .
               "Your donations support their wellbeing and mission.";
    }
}

/**
 * Handle doctor queries
 */
function handleDoctorQuery($message, $language, $context) {
    if ($language === 'si') {
        return "🩺 **වෛද්‍යවරු**\n\n" .
               "**සක්‍රිය වෛද්‍යවරු:** " . $context['doctor_count'] . " දෙනෙක්\n\n" .
               "**ශිල්පයන්:**\n" .
               "🌿 ආයුර්වේද වෛද්‍ය - තිරසර ප්‍රතිකාර\n" .
               "⚕️ බටහිර වෛද්‍ය - බටහිර වෛද්‍ය\n" .
               "🩺 සාමාන්‍ය වෛද්‍ය - සාමාන්‍ය සෞඛ්‍ය සේවා\n\n" .
               "භික්ෂුවරු සඳහා නිදහසිනට වෛද්‍ය සේවා ලබා දේ.";
    } else {
        return "🩺 **Our Doctors**\n\n" .
               "**Active Doctors:** " . $context['doctor_count'] . "\n\n" .
               "**Specializations:**\n" .
               "🌿 Ayurvedic Medicine - Traditional healing\n" .
               "⚕️ Western Medicine - Modern treatment\n" .
               "🩺 General Practice - Primary healthcare\n\n" .
               "Medical services provided to monks at no cost.";
    }
}

/**
 * Handle payment method queries
 */
function handlePaymentQuery($language, $context) {
    if ($language === 'si') {
        return "💳 **ගිණුම් ක්‍රම**\n\n" .
               "**1. සෙවන්දි දෙයි** 💵\n" .
               "   පිටපතින ඉතිරිවිතරක ඉතුර ගන්න\n\n" .
               "**2. බැංකු ගිණුම** 🏦\n" .
               "   බැංකුව: People's Bank\n" .
               "   ගිණුම: 123-456-789-0\n" .
               "   නම: Seela suwa herath Monastery Trust\n" .
               "   ඉතිරිවිතරක උපහාර ඉතුර ගන්න\n\n" .
               "**3. ඔන්ලයින්** 💻\n" .
               "   PayHere හරහා ආරක්ෂිතව ගෙවන්න\n" .
               "   🔒 සම්පූර්ණ සුරක්ෂිතකම\n";
    } else {
        return "💳 **Payment Methods**\n\n" .
               "**1. Cash Donation** 💵\n" .
               "   Visit administration office\n\n" .
               "**2. Bank Transfer** 🏦\n" .
               "   Bank: People's Bank\n" .
               "   Account: 123-456-789-0\n" .
               "   Name: Seela suwa herath Monastery Trust\n" .
               "   Upload slip for receipt\n\n" .
               "**3. Online Payment** 💻\n" .
               "   Secure via PayHere\n" .
               "   🔒 100% safe & encrypted\n";
    }
}

/**
 * Handle transparency queries
 */
function handleTransparencyQuery($language, $context) {
    if ($language === 'si') {
        return "🔍 **පරිවර්තනතාව**\n\n" .
               "අපි සම්පූර්ණ පරිවර්තනතාව සපයන්නෙමු:\n\n" .
               "📊 **සෙසු වාර්තා** - දෙපලින්\n" .
               "📈 **පරිත්‍යාග සංඛ්‍යා** - ප්‍රතිමාසිකව\n" .
               "💰 **වැයුපත** - ස්විකුර්ත\n" .
               "📋 **ක්‍රියාකලාප** - සක්‍රිয়\n\n" .
               "View Reports: ඉතිරිවිතරක ප්‍රතිසිද්ධිය බලන්න";
    } else {
        return "🔍 **Transparency & Reports**\n\n" .
               "We provide complete transparency:\n\n" .
               "📊 **Annual Reports** - Detailed financial statements\n" .
               "📈 **Donation Statistics** - Monthly updates\n" .
               "💰 **Budget Allocation** - Where money is used\n" .
               "📋 **Impact Report** - How we help the community\n\n" .
               "👉 View full transparency reports on our website.";
    }
}

/**
 * Get welcome message
 */
function getWelcomeMessage($language, $context) {
    if ($language === 'si') {
        return "🙏 **ස්වාගතයි!**\n\n" .
               "මම Seela Suwa Herath Monastery AI සහයක. ඔබට උදව් කිරීමට සූදානම්:\n\n" .
               "💰 **පරිත්‍යාග** - කරන්නේ කොහොමද\n" .
               "🏥 **වෛද්‍ය හමුවීම්** - ඉතිරිවිතරක\n" .
               "📊 **සංඛ්‍යා** - අපගේ බලපෑම\n" .
               "👥 **භික්ෂුවරු & වෛද්‍යවරු** - අපගේ කණ්ඩායම\n" .
               "📂 **වර්ගයන්** - කුමක් සඳහා\n" .
               "💳 **ගිණුම් ක්‍රම** - උපහාර ගන්නේ කොහොමද\n" .
               "🔍 **පරිවර්තනතාව** - වාර්තා බලන්න\n\n" .
               "ඕනෑම ගම්‍ය විමසන්න! 🙏";
    } else {
        return "🙏 **Welcome!**\n\n" .
               "I'm the AI Assistant for Seela Suwa Herath Monastery. I can help you with:\n\n" .
               "💰 **Donations** - How to donate\n" .
               "🏥 **Appointments** - Book a consultation\n" .
               "📊 **Statistics** - Our impact\n" .
               "👥 **Monks & Doctors** - Our team\n" .
               "📂 **Categories** - Where donations go\n" .
               "💳 **Payment Methods** - Ways to give\n" .
               "🔍 **Transparency** - View reports\n\n" .
               "Ask me anything! 🙏";
    }
}

/**
 * Get default response
 */
function getDefaultResponse($language, $context) {
    if ($language === 'si') {
        return "😊 ඔබගේ ප්‍රශ්නය සම්පූර්ණයෙන් තේරුම් නොගතහොත්, නැවතත් උත්සාහ කරන්න.\n\n" .
               "කිසිම ගිණුම් විමසිය හැක:\n" .
               "• පරිත්‍යාග ගැන\n" .
               "• වෛද්‍ය සේවා ගැන\n" .
               "• අපගේ ඉතිරිවිතරක ගැන\n" .
               "• ගිණුම් ක්‍රම ගැන\n\n" .
               "ස්පષ්ටවම විමසන්න! 🙏";
    } else {
        return "😊 I didn't quite understand that. Let me help you better.\n\n" .
               "You can ask me about:\n" .
               "• How to donate\n" .
               "• Medical services\n" .
               "• Our team\n" .
               "• Payment methods\n" .
               "• Transparency reports\n\n" .
               "Try again with more details! 🙏";
    }
}

/**
 * Get system context from database
 */
function getSystemContext() {
    $conn = getDBConnection();
    
    $context = [
        'monastery_name' => 'Seela Suwa Herath Monastery',
        'current_date' => date('Y-m-d'),
        'current_time' => date('H:i:s')
    ];
    
    // Donations
    $result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM donations WHERE status IN ('verified', 'paid')");
    if ($result && $row = $result->fetch_assoc()) {
        $context['donation_count'] = $row['count'];
        $context['total_donations'] = 'Rs. ' . number_format($row['total'], 2);
    }
    
    // This month
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status IN ('verified', 'paid') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    if ($result && $row = $result->fetch_assoc()) {
        $context['this_month_donations'] = 'Rs. ' . number_format($row['total'], 2);
    }
    
    // Monks
    $result = $conn->query("SELECT COUNT(*) as count FROM monks WHERE status='active'");
    if ($result) {
        $context['monk_count'] = $result->fetch_assoc()['count'];
    }
    
    // Doctors
    $result = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status='active'");
    if ($result) {
        $context['doctor_count'] = $result->fetch_assoc()['count'];
    }
    
    // Today's appointments
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE app_date = CURRENT_DATE() AND status='scheduled'");
    if ($result) {
        $context['todays_appointments'] = $result->fetch_assoc()['count'];
    }
    
    $conn->close();
    return $context;
}

/**
 * Detect language
 */
function detectLanguage($text) {
    if (preg_match('/[\x{0D80}-\x{0DFF}]/u', $text)) {
        return 'si';
    }
    return 'en';
}
?>
