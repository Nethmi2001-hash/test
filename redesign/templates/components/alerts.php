<?php
// Display flash messages and alerts
if (isset($_SESSION['flash_messages'])) {
    foreach ($_SESSION['flash_messages'] as $type => $messages) {
        if (is_array($messages)) {
            foreach ($messages as $message) {
                echo '<div class="alert alert-' . $type . ' alert-dismissible" data-auto-hide="5000">';
                echo '<span>' . htmlspecialchars($message) . '</span>';
                echo '<button type="button" class="alert-close ml-4">&times;</button>';
                echo '</div>';
            }
        } else {
            echo '<div class="alert alert-' . $type . ' alert-dismissible" data-auto-hide="5000">';
            echo '<span>' . htmlspecialchars($messages) . '</span>';
            echo '<button type="button" class="alert-close ml-4">&times;</button>';
            echo '</div>';
        }
    }
    // Clear flash messages after displaying
    unset($_SESSION['flash_messages']);
}

// Display validation errors if they exist
if (isset($_SESSION['validation_errors'])) {
    echo '<div class="alert alert-danger">';
    echo '<h4 class="font-semibold mb-2">Please correct the following errors:</h4>';
    echo '<ul class="list-disc list-inside space-y-1">';
    foreach ($_SESSION['validation_errors'] as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
    
    // Clear validation errors
    unset($_SESSION['validation_errors']);
}
?>