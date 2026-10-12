<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;

class Report extends Model {
    protected $table = 'reports';

    public function createReport($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO reports (property_id, user_id, reason, details, status, created_at) 
                VALUES (:property_id, :user_id, :reason, :details, 'pending', NOW())";
        $stmt = $db->prepare($sql);

        $details = $data['details'] ?? null;

        return $stmt->execute([
            ':property_id' => $data['property_id'],
            ':user_id'     => $data['user_id'],
            ':reason'      => $data['reason'],
            ':details'     => $details
        ]);
    }

    public function getAllPending() {
        $db = Database::getInstance();
        $sql = "SELECT r.*, p.title AS property_title, u.fullname AS reporter_name 
                FROM reports r
                JOIN properties p ON r.property_id = p.id
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'pending'
                ORDER BY r.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $db = Database::getInstance();
        $sql = "UPDATE reports SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id'     => $id
        ]);
    }
}