<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;

class Appointment extends Model {
    protected $table = 'viewing_appointments';

    public function createAppointment($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO viewing_appointments (property_id, user_id, appointment_date, note, status, created_at) 
                VALUES (:property_id, :user_id, :appointment_date, :note, 'pending', NOW())";
        $stmt = $db->prepare($sql);

        $note = $data['note'] ?? null;

        return $stmt->execute([
            ':property_id'      => $data['property_id'],
            ':user_id'          => $data['user_id'],
            ':appointment_date' => $data['appointment_date'],
            ':note'             => $note
        ]);
    }

    public function getByAgent($agent_id) {
        $db = Database::getInstance();
        $sql = "SELECT va.*, p.title AS property_title, u.fullname AS client_name, u.phone AS client_phone 
                FROM viewing_appointments va
                JOIN properties p ON va.property_id = p.id
                JOIN users u ON va.user_id = u.id
                WHERE p.user_id = :agent_id
                ORDER BY va.appointment_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':agent_id' => $agent_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $db = Database::getInstance();
        $sql = "UPDATE viewing_appointments SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id'     => $id
        ]);
    }
}