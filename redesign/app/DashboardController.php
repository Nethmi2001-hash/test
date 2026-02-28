<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Logger;
use App\Middleware\RoleMiddleware;

class DashboardController
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
            
            // Get role-specific dashboard data
            switch ($userRole) {
                case 'admin':
                    $data = $this->getAdminDashboardData();
                    $template = 'admin';
                    break;
                case 'doctor':
                    $data = $this->getDoctorDashboardData($userId);
                    $template = 'doctor';
                    break;
                case 'monk':
                    $data = $this->getMonkDashboardData($userId);
                    $template = 'monk';
                    break;
                case 'donor':
                    $data = $this->getDonorDashboardData($userId);
                    $template = 'donor';
                    break;
                case 'helper':
                    $data = $this->getHelperDashboardData();
                    $template = 'helper';
                    break;
                default:
                    $data = [];
                    $template = 'default';
            }
            
            $data['userRole'] = $userRole;
            $data['userName'] = $this->session->get('user_name');
            
            ob_start();
            include __DIR__ . '/../templates/pages/dashboards/' . $template . '.php';
            $content = ob_get_clean();
            
            $pageData = [
                'title' => 'Dashboard - ' . ucfirst($userRole),
                'page_title' => 'Dashboard',
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../templates/layouts/base.php';
            
        } catch (\Exception $e) {
            Logger::error('Error loading dashboard: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error loading dashboard');
            
            // Fallback content
            $content = '<div class="alert alert-danger">Error loading dashboard data. Please try again.</div>';
            $pageData = [
                'title' => 'Dashboard Error',
                'page_title' => 'Dashboard',
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../templates/layouts/base.php';
        }
    }
    
    private function getAdminDashboardData()
    {
        return [
            'stats' => [
                'total_monks' => $this->db->fetch('SELECT COUNT(*) as count FROM healthcare_monks WHERE is_active = 1')['count'],
                'total_doctors' => $this->db->fetch('SELECT COUNT(*) as count FROM healthcare_providers WHERE is_active = 1')['count'],
                'total_users' => $this->db->fetch('SELECT COUNT(*) as count FROM sys_users WHERE status = "active"')['count'],
                'pending_appointments' => $this->db->fetch('SELECT COUNT(*) as count FROM healthcare_appointments WHERE status = "scheduled"')['count'],
                'total_donations_amount' => $this->db->fetch('SELECT COALESCE(SUM(amount), 0) as total FROM donations_transactions WHERE status = "completed"')['total'],
                'pending_verifications' => $this->db->fetch('SELECT COUNT(*) as count FROM donations_transactions WHERE verification_status = "unverified"')['count'],
                'monthly_donations' => $this->db->fetch('SELECT COALESCE(SUM(amount), 0) as total FROM donations_transactions WHERE status = "completed" AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())')['total'],
                'active_campaigns' => $this->db->fetch('SELECT COUNT(*) as count FROM donations_campaigns WHERE status = "active"')['count']
            ],
            'recent_appointments' => $this->db->fetchAll(
                "SELECT a.*, 
                        CONCAT(mu.first_name, ' ', mu.last_name) as monk_name,
                        CONCAT(pu.first_name, ' ', pu.last_name) as provider_name
                 FROM healthcare_appointments a
                 JOIN healthcare_monks m ON a.monk_id = m.id
                 JOIN sys_users mu ON m.user_id = mu.id
                 JOIN healthcare_providers hp ON a.provider_id = hp.id
                 JOIN sys_users pu ON hp.user_id = pu.id
                 ORDER BY a.created_at DESC
                 LIMIT 5"
            ),
            'recent_donations' => $this->db->fetchAll(
                "SELECT d.*, dc.name as category_name
                 FROM donations_transactions d
                 LEFT JOIN donations_categories dc ON d.category_id = dc.id
                 ORDER BY d.created_at DESC
                 LIMIT 5"
            )
        ];
    }
    
    private function getDoctorDashboardData($userId)
    {
        $provider = $this->db->fetch('SELECT id FROM healthcare_providers WHERE user_id = ?', [$userId]);
        $providerId = $provider ? $provider['id'] : 0;
        
        return [
            'stats' => [
                'today_appointments' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM healthcare_appointments WHERE provider_id = ? AND appointment_date = CURDATE()',
                    [$providerId]
                )['count'],
                'week_appointments' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM healthcare_appointments WHERE provider_id = ? AND appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)',
                    [$providerId]
                )['count'],
                'total_patients' => $this->db->fetch(
                    'SELECT COUNT(DISTINCT monk_id) as count FROM healthcare_appointments WHERE provider_id = ?',
                    [$providerId]
                )['count'],
                'completed_appointments' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM healthcare_appointments WHERE provider_id = ? AND status = "completed"',
                    [$providerId]
                )['count']
            ],
            'todays_appointments' => $this->db->fetchAll(
                "SELECT a.*, 
                        CONCAT(mu.first_name, ' ', mu.last_name) as monk_name,
                        m.monk_id, m.blood_group
                 FROM healthcare_appointments a
                 JOIN healthcare_monks m ON a.monk_id = m.id
                 JOIN sys_users mu ON m.user_id = mu.id
                 WHERE a.provider_id = ? AND a.appointment_date = CURDATE()
                 ORDER BY a.appointment_time",
                [$providerId]
            ),
            'recent_records' => $this->db->fetchAll(
                "SELECT mr.*,
                        CONCAT(mu.first_name, ' ', mu.last_name) as monk_name
                 FROM healthcare_medical_records mr
                 JOIN healthcare_monks m ON mr.monk_id = m.id
                 JOIN sys_users mu ON m.user_id = mu.id
                 WHERE mr.provider_id = ?
                 ORDER BY mr.visit_date DESC
                 LIMIT 5",
                [$providerId]
            )
        ];
    }
    
    private function getMonkDashboardData($userId)
    {
        $monk = $this->db->fetch('SELECT id FROM healthcare_monks WHERE user_id = ?', [$userId]);
        $monkId = $monk ? $monk['id'] : 0;
        
        return [
            'stats' => [
                'upcoming_appointments' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM healthcare_appointments WHERE monk_id = ? AND appointment_date >= CURDATE() AND status = "scheduled"',
                    [$monkId]
                )['count'],
                'completed_appointments' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM healthcare_appointments WHERE monk_id = ? AND status = "completed"',
                    [$monkId]
                )['count'],
                'medical_records' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM healthcare_medical_records WHERE monk_id = ?',
                    [$monkId]
                )['count'],
                'health_conditions' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM healthcare_conditions WHERE monk_id = ? AND is_active = 1',
                    [$monkId]
                )['count']
            ],
            'upcoming_appointments' => $this->db->fetchAll(
                "SELECT a.*,
                        CONCAT(pu.first_name, ' ', pu.last_name) as provider_name,
                        hp.specialization,
                        f.name as facility_name
                 FROM healthcare_appointments a
                 JOIN healthcare_providers hp ON a.provider_id = hp.id
                 JOIN sys_users pu ON hp.user_id = pu.id
                 LEFT JOIN healthcare_facilities f ON a.facility_id = f.id
                 WHERE a.monk_id = ? AND a.appointment_date >= CURDATE()
                 ORDER BY a.appointment_date, a.appointment_time
                 LIMIT 5",
                [$monkId]
            ),
            'recent_records' => $this->db->fetchAll(
                "SELECT mr.*,
                        CONCAT(pu.first_name, ' ', pu.last_name) as provider_name
                 FROM healthcare_medical_records mr
                 JOIN healthcare_providers hp ON mr.provider_id = hp.id
                 JOIN sys_users pu ON hp.user_id = pu.id
                 WHERE mr.monk_id = ?
                 ORDER BY mr.visit_date DESC
                 LIMIT 3",
                [$monkId]
            )
        ];
    }
    
    private function getDonorDashboardData($userId)
    {
        return [
            'stats' => [
                'total_donations' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM donations_transactions WHERE donor_user_id = ?',
                    [$userId]
                )['count'],
                'total_amount' => $this->db->fetch(
                    'SELECT COALESCE(SUM(amount), 0) as total FROM donations_transactions WHERE donor_user_id = ? AND status = "completed"',
                    [$userId]
                )['total'],
                'this_year_amount' => $this->db->fetch(
                    'SELECT COALESCE(SUM(amount), 0) as total FROM donations_transactions WHERE donor_user_id = ? AND status = "completed" AND YEAR(created_at) = YEAR(NOW())',
                    [$userId]
                )['total'],
                'pending_donations' => $this->db->fetch(
                    'SELECT COUNT(*) as count FROM donations_transactions WHERE donor_user_id = ? AND verification_status = "unverified"',
                    [$userId]
                )['count']
            ],
            'recent_donations' => $this->db->fetchAll(
                "SELECT d.*, dc.name as category_name
                 FROM donations_transactions d
                 LEFT JOIN donations_categories dc ON d.category_id = dc.id
                 WHERE d.donor_user_id = ?
                 ORDER BY d.created_at DESC
                 LIMIT 5",
                [$userId]
            ),
            'active_campaigns' => $this->db->fetchAll(
                "SELECT * FROM donations_campaigns 
                 WHERE status = 'active' AND end_date >= CURDATE()
                 ORDER BY end_date
                 LIMIT 3"
            )
        ];
    }
    
    private function getHelperDashboardData()
    {
        return [
            'stats' => [
                'pending_verifications' => $this->db->fetch('SELECT COUNT(*) as count FROM donations_transactions WHERE verification_status = "unverified"')['count'],
                'todays_appointments' => $this->db->fetch('SELECT COUNT(*) as count FROM healthcare_appointments WHERE appointment_date = CURDATE()')['count'],
                'active_monks' => $this->db->fetch('SELECT COUNT(*) as count FROM healthcare_monks WHERE is_active = 1')['count'],
                'monthly_donations' => $this->db->fetch('SELECT COALESCE(SUM(amount), 0) as total FROM donations_transactions WHERE status = "completed" AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())')['total']
            ],
            'pending_donations' => $this->db->fetchAll(
                "SELECT d.*, dc.name as category_name
                 FROM donations_transactions d
                 LEFT JOIN donations_categories dc ON d.category_id = dc.id
                 WHERE d.verification_status = 'unverified'
                 ORDER BY d.created_at DESC
                 LIMIT 5"
            ),
            'todays_appointments' => $this->db->fetchAll(
                "SELECT a.*, 
                        CONCAT(mu.first_name, ' ', mu.last_name) as monk_name,
                        CONCAT(pu.first_name, ' ', pu.last_name) as provider_name
                 FROM healthcare_appointments a
                 JOIN healthcare_monks m ON a.monk_id = m.id
                 JOIN sys_users mu ON m.user_id = mu.id
                 JOIN healthcare_providers hp ON a.provider_id = hp.id
                 JOIN sys_users pu ON hp.user_id = pu.id
                 WHERE a.appointment_date = CURDATE()
                 ORDER BY a.appointment_time
                 LIMIT 5"
            )
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