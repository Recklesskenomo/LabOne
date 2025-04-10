<?php
// No direct access
if(!defined('INCLUDED')) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

/**
 * Role Manager Class
 * Handles all role-based authentication and permissions
 */
class RoleManager {
    private $conn;
    private $user_id;
    private $role_id;
    private $role_name;
    
    /**
     * Constructor
     * @param mysqli $conn Database connection
     * @param int $user_id User ID
     */
    public function __construct($conn, $user_id = null) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        
        if ($user_id) {
            $this->loadUserRole();
        }
    }
    
    /**
     * Load the user's role from database
     */
    private function loadUserRole() {
        $sql = "SELECT u.role_id, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ?";
                
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $this->user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $this->role_id, $this->role_name);
                    mysqli_stmt_fetch($stmt);
                }
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    /**
     * Check if user is an admin
     * @return bool True if admin, false otherwise
     */
    public function isAdmin() {
        return $this->role_name === 'admin';
    }
    
    /**
     * Check if user has a specific role
     * @param string $role_name Role name to check
     * @return bool True if user has the role, false otherwise
     */
    public function hasRole($role_name) {
        return $this->role_name === $role_name;
    }
    
    /**
     * Get all available roles
     * @return array Array of roles
     */
    public function getAllRoles() {
        $roles = [];
        
        $sql = "SELECT id, role_name, description FROM roles ORDER BY id";
        $result = mysqli_query($this->conn, $sql);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $roles[] = $row;
            }
            mysqli_free_result($result);
        }
        
        return $roles;
    }
    
    /**
     * Get the user's current role
     * @return string Role name
     */
    public function getUserRole() {
        return $this->role_name;
    }
    
    /**
     * Get the user's role ID
     * @return int Role ID
     */
    public function getUserRoleId() {
        return $this->role_id;
    }
    
    /**
     * Change a user's role
     * @param int $user_id User ID
     * @param int $role_id New role ID
     * @return bool True on success, false on failure
     */
    public function changeUserRole($user_id, $role_id) {
        // Only admins can change roles
        if (!$this->isAdmin()) {
            return false;
        }
        
        $sql = "UPDATE users SET role_id = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $role_id, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            
            mysqli_stmt_close($stmt);
        }
        
        return false;
    }
}
?> 