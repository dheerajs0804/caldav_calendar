<?php

namespace CalDev\Calendar\Models;

use PDO;

class Event
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    public function create(array $data): int
    {
        $sql = "INSERT INTO events (
                    calendar_id, title, description, start_time, end_time, 
                    all_day, location, recurrence_rule, etag, uid, 
                    created_at, updated_at
                ) VALUES (
                    :calendar_id, :title, :description, :start_time, :end_time,
                    :all_day, :location, :recurrence_rule, :etag, :uid,
                    NOW(), NOW()
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'calendar_id' => $data['calendar_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'all_day' => $data['all_day'] ?? false,
            'location' => $data['location'] ?? null,
            'recurrence_rule' => $data['recurrence_rule'] ?? null,
            'etag' => $data['etag'] ?? null,
            'uid' => $data['uid']
        ]);
        
        return (int) $this->db->lastInsertId();
    }
    
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM events WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function findByCalendarId(int $calendarId, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT * FROM events WHERE calendar_id = :calendar_id";
        $params = ['calendar_id' => $calendarId];
        
        if ($startDate && $endDate) {
            $sql .= " AND (
                (start_time BETWEEN :start_date AND :end_date) OR
                (end_time BETWEEN :start_date AND :end_date) OR
                (start_time <= :start_date AND end_time >= :end_date)
            )";
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }
        
        $sql .= " ORDER BY start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function findByUserId(int $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT e.* FROM events e 
                JOIN calendars c ON e.calendar_id = c.id 
                WHERE c.user_id = :user_id";
        $params = ['user_id' => $userId];
        
        if ($startDate && $endDate) {
            $sql .= " AND (
                (e.start_time BETWEEN :start_date AND :end_date) OR
                (e.end_time BETWEEN :start_date AND :end_date) OR
                (e.start_time <= :start_date AND e.end_time >= :end_date)
            )";
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }
        
        $sql .= " ORDER BY e.start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE events SET 
                title = :title, 
                description = :description, 
                start_time = :start_time, 
                end_time = :end_time,
                all_day = :all_day, 
                location = :location, 
                recurrence_rule = :recurrence_rule, 
                etag = :etag,
                updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'all_day' => $data['all_day'],
            'location' => $data['location'],
            'recurrence_rule' => $data['recurrence_rule'],
            'etag' => $data['etag']
        ]);
    }
    
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM events WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    public function findByUid(string $uid, int $calendarId): ?array
    {
        $sql = "SELECT * FROM events WHERE uid = :uid AND calendar_id = :calendar_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uid' => $uid,
            'calendar_id' => $calendarId
        ]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function updateEtag(int $id, string $etag): bool
    {
        $sql = "UPDATE events SET etag = :etag, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'etag' => $etag
        ]);
    }
    
    public function search(int $userId, string $query): array
    {
        $sql = "SELECT e.* FROM events e 
                JOIN calendars c ON e.calendar_id = c.id 
                WHERE c.user_id = :user_id 
                AND (e.title LIKE :query OR e.description LIKE :query OR e.location LIKE :query)
                ORDER BY e.start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'query' => "%{$query}%"
        ]);
        
        return $stmt->fetchAll();
    }
}
