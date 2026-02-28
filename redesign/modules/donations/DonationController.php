<?php

namespace App\Controllers\Donations;

use App\Core\Application;
use App\Core\Logger;
use App\Middleware\RoleMiddleware;

class DonationController
{
    private $db;
    private $session;
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getDatabase();
        $this->session = $app->getSession();
    }
    
    public function index()
    {
        try {
            $userRole = RoleMiddleware::getUserRole();
            $userId = $this->session->get('user_id');
            
            // Build query based on user role
            $whereClause = '';
            $params = [];
            
            if ($userRole === 'donor') {
                // Donors can only see their own donations
                $whereClause = 'WHERE d.donor_user_id = ?';
                $params[] = $userId;
            }
            
            // Apply filters
            $status = $_GET['status'] ?? 'all';
            $verification = $_GET['verification'] ?? 'all';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            
            if ($status !== 'all') {
                $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
                $whereClause .= 'd.status = ?';
                $params[] = $status;
            }
            
            if ($verification !== 'all') {
                $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
                $whereClause .= 'd.verification_status = ?';
                $params[] = $verification;
            }
            
            if (!empty($dateFrom)) {
                $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
                $whereClause .= 'd.created_at >= ?';
                $params[] = $dateFrom . ' 00:00:00';
            }
            
            if (!empty($dateTo)) {
                $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
                $whereClause .= 'd.created_at <= ?';
                $params[] = $dateTo . ' 23:59:59';
            }
            
            // Get donations
            $donations = $this->db->fetchAll(
                "SELECT d.*,
                        dc.name as category_name,
                        dcam.title as campaign_title,
                        CONCAT(vu.first_name, ' ', vu.last_name) as verified_by_name
                 FROM donations_transactions d
                 LEFT JOIN donations_categories dc ON d.category_id = dc.id
                 LEFT JOIN donations_campaigns dcam ON d.campaign_id = dcam.id
                 LEFT JOIN sys_users vu ON d.verified_by = vu.id
                 {$whereClause}
                 ORDER BY d.created_at DESC",
                $params
            );
            
            // Get summary statistics
            $stats = $this->getStatistics($userRole, $userId);
            
            $data = [
                'donations' => $donations,
                'stats' => $stats,
                'status' => $status,
                'verification' => $verification,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'userRole' => $userRole
            ];
            
            ob_start();
            include __DIR__ . '/../../templates/pages/donations/index.php';
            $content = ob_get_clean();
            
            $pageData = [
                'title' => 'Donations Management',
                'page_title' => 'Donations',
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../../templates/layouts/base.php';
            
        } catch (\Exception $e) {
            Logger::error('Error loading donations: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error loading donations');
            header('Location: /dashboard');
            exit;
        }
    }
    
    public function show($id)
    {
        try {
            $donation = $this->db->fetch(
                "SELECT d.*,
                        dc.name as category_name,
                        dcam.title as campaign_title,
                        CONCAT(vu.first_name, ' ', vu.last_name) as verified_by_name,
                        CONCAT(pu.first_name, ' ', pu.last_name) as processed_by_name
                 FROM donations_transactions d
                 LEFT JOIN donations_categories dc ON d.category_id = dc.id
                 LEFT JOIN donations_campaigns dcam ON d.campaign_id = dcam.id
                 LEFT JOIN sys_users vu ON d.verified_by = vu.id
                 LEFT JOIN sys_users pu ON d.processed_by = pu.id
                 WHERE d.id = ?",
                [$id]
            );
            
            if (!$donation) {
                $this->addFlashMessage('error', 'Donation not found');
                header('Location: /donations');
                exit;
            }
            
            // Check permission
            $userRole = RoleMiddleware::getUserRole();
            $userId = $this->session->get('user_id');
            
            if ($userRole === 'donor' && $donation['donor_user_id'] !== $userId) {
                http_response_code(403);
                exit;
            }
            
            $data = [
                'donation' => $donation,
                'userRole' => $userRole
            ];
            
            ob_start();
            include __DIR__ . '/../../templates/pages/donations/show.php';
            $content = ob_get_clean();
            
            $pageData = [
                'title' => 'Donation Details - ' . $donation['transaction_number'],
                'page_title' => 'Donation #' . $donation['transaction_number'],
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../../templates/layouts/base.php';
            
        } catch (\Exception $e) {
            Logger::error('Error loading donation: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error loading donation');
            header('Location: /donations');
            exit;
        }
    }
    
    public function verify($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /donations/' . $id);
            exit;
        }
        
        // Only admin and helpers can verify donations
        if (!RoleMiddleware::hasRole(['admin', 'helper'])) {
            http_response_code(403);
            exit;
        }
        
        try {
            $action = $_POST['action'] ?? ''; // verify or reject
            $notes = trim($_POST['notes'] ?? '');
            
            if (!in_array($action, ['verify', 'reject'])) {
                $this->addFlashMessage('error', 'Invalid action');
                header('Location: /donations/' . $id);
                exit;
            }
            
            $verificationStatus = $action === 'verify' ? 'verified' : 'rejected';
            $userId = $this->session->get('user_id');
            
            $this->db->update(
                'donations_transactions',
                [
                    'verification_status' => $verificationStatus,
                    'verified_by' => $userId,
                    'verified_at' => date('Y-m-d H:i:s'),
                    'internal_notes' => $notes
                ],
                'id = ?',
                [$id]
            );
            
            Logger::info('Donation ' . $action . 'd', ['donation_id' => $id, 'verified_by' => $userId]);
            
            $this->addFlashMessage('success', 'Donation has been ' . $action . 'd successfully');
            header('Location: /donations/' . $id);
            exit;
            
        } catch (\Exception $e) {
            Logger::error('Error verifying donation: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error processing verification');
            header('Location: /donations/' . $id);
            exit;
        }
    }
    
    private function getStatistics($userRole, $userId)
    {
        $whereClause = '';
        $params = [];
        
        if ($userRole === 'donor') {
            $whereClause = 'WHERE donor_user_id = ?';
            $params[] = $userId;
        }
        
        return [
            'total_donations' => $this->db->fetch(
                "SELECT COUNT(*) as count FROM donations_transactions {$whereClause}",
                $params
            )['count'],
            'total_amount' => $this->db->fetch(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM donations_transactions 
                 {$whereClause} AND status = 'completed'",
                $params
            )['total'],
            'pending_verification' => $this->db->fetch(
                "SELECT COUNT(*) as count 
                 FROM donations_transactions 
                 {$whereClause} AND verification_status = 'unverified'",
                $params
            )['count'],
            'this_month_amount' => $this->db->fetch(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM donations_transactions 
                 {$whereClause} AND status = 'completed'
                 AND MONTH(created_at) = MONTH(NOW()) 
                 AND YEAR(created_at) = YEAR(NOW())",
                $params
            )['total']
        ];
    }
    
    private function addFlashMessage($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        if (!isset($_SESSION['flash_messages'][$type])) {
            $_SESSION['flash_messages'][$type] = [];
        }
        $_SESSION['flash_messages'][$type][] = $message;
    }
}