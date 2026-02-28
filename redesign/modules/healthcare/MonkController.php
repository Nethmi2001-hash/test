<?php

namespace App\Controllers\Healthcare;

use App\Core\Application;
use App\Core\Logger;
use App\Middleware\RoleMiddleware;

class MonkController
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
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? 'active';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            
            // Build search and filter query
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR m.monk_id LIKE ? OR u.email LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($status !== 'all') {
                $whereConditions[] = "m.is_active = ?";
                $params[] = $status === 'active' ? 1 : 0;
            }
            
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Get monks with user details
            $monks = $this->db->fetchAll(
                "SELECT m.*, 
                        CONCAT(u.first_name, ' ', u.last_name) as full_name,
                        u.email, u.phone, u.status as user_status,
                        TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
                        (SELECT COUNT(*) FROM healthcare_appointments WHERE monk_id = m.id) as total_appointments
                 FROM healthcare_monks m
                 JOIN sys_users u ON m.user_id = u.id
                 {$whereClause}
                 ORDER BY u.first_name, u.last_name
                 LIMIT {$perPage} OFFSET {$offset}",
                $params
            );
            
            // Get total count for pagination
            $totalCount = $this->db->fetch(
                "SELECT COUNT(*) as count 
                 FROM healthcare_monks m
                 JOIN sys_users u ON m.user_id = u.id
                 {$whereClause}",
                $params
            )['count'];
            
            $totalPages = ceil($totalCount / $perPage);
            
            // Page data
            $data = [
                'monks' => $monks,
                'search' => $search,
                'status' => $status,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalCount' => $totalCount
            ];
            
            ob_start();
            include __DIR__ . '/../../templates/pages/healthcare/monks/index.php';
            $content = ob_get_clean();
            
            $pageData = [
                'title' => 'Monk Management - Healthcare',
                'page_title' => 'Monk Management',
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../../templates/layouts/base.php';
            
        } catch (\Exception $e) {
            Logger::error('Error loading monks: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error loading monks data');
            header('Location: /dashboard');
            exit;
        }
    }
    
    public function show($id)
    {
        try {
            $monk = $this->db->fetch(
                "SELECT m.*, 
                        CONCAT(u.first_name, ' ', u.last_name) as full_name,
                        u.email, u.phone, u.status as user_status,
                        TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age
                 FROM healthcare_monks m
                 JOIN sys_users u ON m.user_id = u.id
                 WHERE m.id = ?",
                [$id]
            );
            
            if (!$monk) {
                $this->addFlashMessage('error', 'Monk not found');
                header('Location: /healthcare/monks');
                exit;
            }
            
            // Check permission - monks can only view their own records
            if (RoleMiddleware::getUserRole() === 'monk') {
                $userMonkId = $this->getCurrentUserMonkId();
                if ($userMonkId !== (int)$id) {
                    http_response_code(403);
                    $this->addFlashMessage('error', 'Access denied');
                    header('Location: /dashboard');
                    exit;
                }
            }
            
            // Get medical conditions
            $conditions = $this->db->fetchAll(
                "SELECT * FROM healthcare_conditions 
                 WHERE monk_id = ? AND is_active = 1
                 ORDER BY type, condition_name",
                [$id]
            );
            
            // Get recent appointments
            $recentAppointments = $this->db->fetchAll(
                "SELECT a.*, 
                        CONCAT(pu.first_name, ' ', pu.last_name) as provider_name,
                        hp.specialization
                 FROM healthcare_appointments a
                 JOIN healthcare_providers hp ON a.provider_id = hp.id
                 JOIN sys_users pu ON hp.user_id = pu.id
                 WHERE a.monk_id = ?
                 ORDER BY a.appointment_date DESC, a.appointment_time DESC
                 LIMIT 10",
                [$id]
            );
            
            // Get recent medical records
            $recentRecords = $this->db->fetchAll(
                "SELECT mr.*,
                        CONCAT(pu.first_name, ' ', pu.last_name) as provider_name
                 FROM healthcare_medical_records mr
                 JOIN healthcare_providers hp ON mr.provider_id = hp.id
                 JOIN sys_users pu ON hp.user_id = pu.id
                 WHERE mr.monk_id = ?
                 ORDER BY mr.visit_date DESC
                 LIMIT 5",
                [$id]
            );
            
            $data = [
                'monk' => $monk,
                'conditions' => $conditions,
                'recentAppointments' => $recentAppointments,
                'recentRecords' => $recentRecords
            ];
            
            ob_start();
            include __DIR__ . '/../../templates/pages/healthcare/monks/show.php';
            $content = ob_get_clean();
            
            $pageData = [
                'title' => $monk['full_name'] . ' - Monk Profile',
                'page_title' => 'Monk Profile: ' . $monk['full_name'],
                'content' => $content,
                'show_navigation' => true
            ];
            
            extract($pageData);
            include __DIR__ . '/../../templates/layouts/base.php';
            
        } catch (\Exception $e) {
            Logger::error('Error loading monk profile: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error loading monk profile');
            header('Location: /healthcare/monks');
            exit;
        }
    }
    
    public function create()
    {
        // Only admin and helpers can create monk profiles
        if (!RoleMiddleware::hasRole(['admin', 'helper'])) {
            http_response_code(403);
            exit;
        }
        
        // Get available users without monk profiles
        $availableUsers = $this->db->fetchAll(
            "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.email
             FROM sys_users u
             WHERE u.role_id = (SELECT id FROM sys_roles WHERE slug = 'monk')
             AND u.id NOT IN (SELECT user_id FROM healthcare_monks WHERE user_id IS NOT NULL)
             AND u.status = 'active'
             ORDER BY u.first_name, u.last_name"
        );
        
        $data = [
            'availableUsers' => $availableUsers
        ];
        
        ob_start();
        include __DIR__ . '/../../templates/pages/healthcare/monks/create.php';
        $content = ob_get_clean();
        
        $pageData = [
            'title' => 'Add New Monk Profile',
            'page_title' => 'Add New Monk Profile',
            'content' => $content,
            'show_navigation' => true
        ];
        
        extract($pageData);
        include __DIR__ . '/../../templates/layouts/base.php';
    }
    
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /healthcare/monks/create');
            exit;
        }
        
        // Only admin and helpers can create monk profiles
        if (!RoleMiddleware::hasRole(['admin', 'helper'])) {
            http_response_code(403);
            exit;
        }
        
        try {
            $data = [
                'user_id' => (int)($_POST['user_id'] ?? 0),
                'monk_id' => trim($_POST['monk_id'] ?? ''),
                'title_prefix' => trim($_POST['title_prefix'] ?? ''),
                'ordination_date' => $_POST['ordination_date'] ?: null,
                'temple_name' => trim($_POST['temple_name'] ?? ''),
                'date_of_birth' => $_POST['date_of_birth'] ?: null,
                'place_of_birth' => trim($_POST['place_of_birth'] ?? ''),
                'blood_group' => $_POST['blood_group'] ?: null,
                'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
                'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? ''),
                'emergency_contact_relationship' => trim($_POST['emergency_contact_relationship'] ?? ''),
                'dietary_restrictions' => trim($_POST['dietary_restrictions'] ?? ''),
                'is_active' => 1
            ];
            
            // Validation
            $errors = [];
            if (empty($data['user_id'])) $errors[] = 'User selection is required';
            if (empty($data['monk_id'])) $errors[] = 'Monk ID is required';
            
            if (!empty($errors)) {
                $_SESSION['validation_errors'] = $errors;
                header('Location: /healthcare/monks/create');
                exit;
            }
            
            // Check if monk_id already exists
            $existing = $this->db->fetch('SELECT id FROM healthcare_monks WHERE monk_id = ?', [$data['monk_id']]);
            if ($existing) {
                $this->addFlashMessage('error', 'A monk with this ID already exists');
                header('Location: /healthcare/monks/create');
                exit;
            }
            
            $monkId = $this->db->insert('healthcare_monks', $data);
            
            Logger::info('New monk profile created', ['monk_id' => $monkId, 'created_by' => $this->session->get('user_id')]);
            
            $this->addFlashMessage('success', 'Monk profile created successfully');
            header('Location: /healthcare/monks/' . $monkId);
            exit;
            
        } catch (\Exception $e) {
            Logger::error('Error creating monk profile: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Error creating monk profile');
            header('Location: /healthcare/monks/create');
            exit;
        }
    }
    
    private function getCurrentUserMonkId()
    {
        $userId = $this->session->get('user_id');
        $monk = $this->db->fetch('SELECT id FROM healthcare_monks WHERE user_id = ?', [$userId]);
        return $monk ? (int)$monk['id'] : null;
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