<?php

namespace App\Controllers\Healthcare;

use App\Core\Application;
use App\Core\Logger;
use App\Middleware\RoleMiddleware;

class AppointmentController
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
            
            if ($userRole === 'monk') {
                // Monks can only see their own appointments
                $monk = $this->db->fetch('SELECT id FROM healthcare_monks WHERE user_id = ?', [$userId]);
                if (!$monk) {
                    $this->addFlashMessage('error', 'Monk profile not found');
                    header('Location: /dashboard');
                    exit;
                }
                $whereClause = 'WHERE a.monk_id = ?';
                $params[] = $monk['id'];
            } elseif ($userRole === 'doctor') {
                // Doctors can see appointments assigned to them
                $provider = $this->db->fetch('SELECT id FROM healthcare_providers WHERE user_id = ?', [$userId]);
                if ($provider) {
                    $whereClause = 'WHERE a.provider_id = ?';
                    $params[] = $provider['id'];
                }
            }
            
            // Apply additional filters
            $status = $_GET['status'] ?? 'all';
            $date = $_GET['date'] ?? '';
            
            if ($status !== 'all') {
                $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
                $whereClause .= 'a.status = ?';
                $params[] = $status;
            }
            
            if (!empty($date)) {
                $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
                $whereClause .= 'a.appointment_date = ?';
                $params[] = $date;
            }
            
            // Get appointments
            $appointments = $this->db->fetchAll(
                "SELECT a.*,
                        CONCAT(mu.first_name, ' ', mu.last_name) as monk_name,
                        m.monk_id,
                        CONCAT(pu.first_name, ' ', pu.last_name) as provider_name,
                        hp.specialization,
                        f.name as facility_name
                 FROM healthcare_appointments a
                 JOIN healthcare_monks m ON a.monk_id = m.id
                 JOIN sys_users mu ON m.user_id = mu.id
                 JOIN healthcare_providers hp ON a.provider_id = hp.id
                 JOIN sys_users pu ON hp.user_id = pu.id
                 LEFT JOIN healthcare_facilities f ON a.facility_id = f.id
                 {$whereClause}
                 ORDER BY a.appointment_date DESC, a.appointment_time DESC",
                $params
            );
            
            $data = [
                'appointments' => $appointments,
                'status' => $status,
                'date' => $date,
                'userRole' => $userRole
            ];
            
            ob_start();
            include __DIR__ . '/../../templates/pages/healthcare/appointments/index.php';
            $content = ob_get_clean();
            
            $pageData = [
                'title' => 'Appointments - Healthcare',
                'page_title' => 'Appointments',
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../../templates/layouts/base.php';
            
        } catch (\Exception $e) {
            Logger::error('Error loading appointments: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error loading appointments');
            header('Location: /dashboard');
            exit;
        }
    }
    
    public function show($id)
    {
        try {
            $appointment = $this->db->fetch(
                "SELECT a.*,
                        CONCAT(mu.first_name, ' ', mu.last_name) as monk_name,
                        m.monk_id, m.phone as monk_phone, m.blood_group,
                        CONCAT(pu.first_name, ' ', pu.last_name) as provider_name,
                        hp.specialization, hp.license_number,
                        f.name as facility_name, f.location
                 FROM healthcare_appointments a
                 JOIN healthcare_monks m ON a.monk_id = m.id
                 JOIN sys_users mu ON m.user_id = mu.id
                 JOIN healthcare_providers hp ON a.provider_id = hp.id
                 JOIN sys_users pu ON hp.user_id = pu.id
                 LEFT JOIN healthcare_facilities f ON a.facility_id = f.id
                 WHERE a.id = ?",
                [$id]
            );
            
            if (!$appointment) {
                $this->addFlashMessage('error', 'Appointment not found');
                header('Location: /healthcare/appointments');
                exit;
            }
            
            // Check permission
            $userRole = RoleMiddleware::getUserRole();
            $userId = $this->session->get('user_id');
            
            if ($userRole === 'monk') {
                $monk = $this->db->fetch('SELECT id FROM healthcare_monks WHERE user_id = ?', [$userId]);
                if (!$monk || $monk['id'] !== $appointment['monk_id']) {
                    http_response_code(403);
                    exit;
                }
            } elseif ($userRole === 'doctor') {
                $provider = $this->db->fetch('SELECT id FROM healthcare_providers WHERE user_id = ?', [$userId]);
                if (!$provider || $provider['id'] !== $appointment['provider_id']) {
                    http_response_code(403);
                    exit;
                }
            }
            
            // Get related medical record if exists
            $medicalRecord = $this->db->fetch(
                "SELECT * FROM healthcare_medical_records 
                 WHERE appointment_id = ?",
                [$id]
            );
            
            $data = [
                'appointment' => $appointment,
                'medicalRecord' => $medicalRecord,
                'userRole' => $userRole
            ];
            
            ob_start();
            include __DIR__ . '/../../templates/pages/healthcare/appointments/show.php';
            $content = ob_get_clean();
            
            $pageData = [
                'title' => 'Appointment Details',
                'page_title' => 'Appointment #' . $appointment['appointment_number'],
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../../templates/layouts/base.php';
            
        } catch (\Exception $e) {
            Logger::error('Error loading appointment: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error loading appointment');
            header('Location: /healthcare/appointments');
            exit;
        }
    }
    
    public function create()
    {
        // Only admin, doctors, and helpers can create appointments
        if (!RoleMiddleware::hasRole(['admin', 'doctor', 'helper'])) {
            http_response_code(403);
            exit;
        }
        
        try {
            // Get monks
            $monks = $this->db->fetchAll(
                "SELECT m.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, m.monk_id
                 FROM healthcare_monks m
                 JOIN sys_users u ON m.user_id = u.id
                 WHERE m.is_active = 1
                 ORDER BY u.first_name, u.last_name"
            );
            
            // Get healthcare providers
            $providers = $this->db->fetchAll(
                "SELECT hp.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, hp.specialization
                 FROM healthcare_providers hp
                 JOIN sys_users u ON hp.user_id = u.id
                 WHERE hp.is_active = 1
                 ORDER BY u.first_name, u.last_name"
            );
            
            // Get facilities
            $facilities = $this->db->fetchAll(
                "SELECT * FROM healthcare_facilities 
                 WHERE status = 'available'
                 ORDER BY name"
            );
            
            $data = [
                'monks' => $monks,
                'providers' => $providers,
                'facilities' => $facilities
            ];
            
            ob_start();
            include __DIR__ . '/../../templates/pages/healthcare/appointments/create.php';
            $content = ob_get_clean();
            
            $pageData = [
                'title' => 'Schedule New Appointment',
                'page_title' => 'Schedule New Appointment',
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../../templates/layouts/base.php';
            
        } catch (\Exception $e) {
            Logger::error('Error loading appointment form: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error loading form');
            header('Location: /healthcare/appointments');
            exit;
        }
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