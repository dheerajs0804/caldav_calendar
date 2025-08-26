<?php

namespace CalDev\Calendar\Models;

use PDO;

class Calendar
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    public function create(array $data): int
    {
        $sql = "INSERT INTO calendars (name, color, url, read_only, sync_token, user_id, created_at, updated_at) 
                VALUES (:name, :color, :url, :read_only, :sync_token, :user_id, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'color' => $data['color'] ?? '#4285f4',
            'url' => $data['url'],
            'read_only' => $data['read_only'] ?? false,
            'sync_token' => $data['sync_token'] ?? null,
            'user_id' => $data['user_id']
        ]);
        
        return (int) $this->db->lastInsertId();
    }
    
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM calendars WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function findByUserId(int $userId): array
    {
        $sql = "SELECT * FROM calendars WHERE user_id = :user_id ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE calendars SET 
                name = :name, 
                color = :color, 
                url = :url, 
                read_only = :read_only, 
                sync_token = :sync_token, 
                updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'color' => $data['color'],
            'url' => $data['url'],
            'read_only' => $data['read_only'],
            'sync_token' => $data['sync_token']
        ]);
    }
    
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM calendars WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    public function updateSyncToken(int $id, string $syncToken): bool
    {
        $sql = "UPDATE calendars SET sync_token = :sync_token, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'sync_token' => $syncToken
        ]);
    }
    
    public function findByUrl(string $url, int $userId): ?array
    {
        $sql = "SELECT * FROM calendars WHERE url = :url AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'url' => $url,
            'user_id' => $userId
        ]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
