<?php
/**
 * PayHere Server Notification (IPN - Instant Payment Notification)
 * This file receives POST notifications from PayHere server when payment is completed
 * IMPORTANT: This must be accessible from internet (use ngrok for local testing)
 */

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "monastery_healthcare";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get PayHere notification data
$merchant_id = $_POST['merchant_id'] ?? '';
$order_id = $_POST['order_id'] ?? '';
$payhere_amount = $_POST['payhere_amount'] ?? 0;
$payhere_currency = $_POST['payhere_currency'] ?? '';
$status_code = $_POST['status_code'] ?? '';
$md5sig = $_POST['md5sig'] ?? '';
$method = $_POST['method'] ?? '';
$status_message = $_POST['status_message'] ?? '';
$card_holder_name = $_POST['card_holder_name'] ?? '';
$card_no = $_POST['card_no'] ?? '';
$custom_1 = $_POST['custom_1'] ?? '';  // category_id
$custom_2 = $_POST['custom_2'] ?? '';

// PayHere merchant secret (get from your PayHere account)
$merchant_secret = "YOUR_MERCHANT_SECRET";  // REPLACE WITH YOUR SECRET

// Verify signature (security check)
$local_md5sig = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $payhere_amount . 
        $payhere_currency . 
        $status_code . 
        strtoupper(md5($merchant_secret))
    )
);

// Log the notification (for debugging)
$log_file = __DIR__ . '/payhere_logs.txt';
$log_data = date('Y-m-d H:i:s') . " - Order: $order_id, Status: $status_code, Amount: $payhere_amount\n";
file_put_contents($log_file, $log_data, FILE_APPEND);

// Verify signature matches
if ($local_md5sig === $md5sig) {
    // Signature is valid
    
    if ($status_code == 2) {
        // Payment successful (status_code 2 = success)
        
        // Extract donor info from POST
        $donor_name = $_POST['first_name'] . ' ' . $_POST['last_name'];
        $donor_email = $_POST['email'] ?? '';
        $donor_phone = $_POST['phone'] ?? '';
        $items = $_POST['items'] ?? 'General Donation';
        
        // Default category (you can customize this)
        $category_id = !empty($custom_1) ? intval($custom_1) : 1;  // Default to first donation category
        
        // Save donation to database
        $stmt = $conn->prepare("INSERT INTO donations (donor_name, donor_email, donor_phone, amount, category_id, payment_method, reference_number, notes, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, 'payhere', ?, ?, 'verified', 1, NOW())");
        
        $notes = "PayHere Payment - Order ID: $order_id, Method: $method, Card: $card_no";
        $stmt->bind_param("sssdiss", $donor_name, $donor_email, $donor_phone, $payhere_amount, $category_id, $order_id, $notes);
        
        if ($stmt->execute()) {
            $donation_id = $stmt->insert_id;
            $log_data = date('Y-m-d H:i:s') . " - SUCCESS: Donation ID $donation_id saved for Order $order_id\n";
            file_put_contents($log_file, $log_data, FILE_APPEND);
        } else {
            $log_data = date('Y-m-d H:i:s') . " - ERROR: Failed to save donation - " . $stmt->error . "\n";
            file_put_contents($log_file, $log_data, FILE_APPEND);
        }
        $stmt->close();
        
    } else {
        // Payment failed or other status
        $log_data = date('Y-m-d H:i:s') . " - FAILED: Order $order_id - $status_message\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);
    }
    
} else {
    // Signature mismatch - possible fraud
    $log_data = date('Y-m-d H:i:s') . " - SECURITY WARNING: MD5 signature mismatch for Order $order_id\n";
    file_put_contents($log_file, $log_data, FILE_APPEND);
}

$conn->close();

// Return 200 OK to PayHere
http_response_code(200);
echo "OK";
?>
