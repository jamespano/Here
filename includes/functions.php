<?php
require_once __DIR__ . '/../config.php';

function get_user($id) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_users() {
    $stmt = db()->query('SELECT * FROM users ORDER BY first_name, last_name');
    return $stmt->fetchAll();
}

function search_users($q, $exclude_id = null) {
    $sql = 'SELECT * FROM users WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
    $params = ["%$q%", "%$q%", "%$q%"];
    if ($exclude_id !== null) {
        $sql .= ' AND id != ?';
        $params[] = $exclude_id;
    }
    $sql .= ' ORDER BY first_name, last_name LIMIT 20';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_feed_posts($user_id, $limit = 20, $offset = 0) {
    $sql = '
        SELECT p.*, u.first_name, u.last_name, u.avatar,
        (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
        EXISTS(SELECT 1 FROM post_likes pl WHERE pl.post_id = p.id AND pl.user_id = ?) AS user_liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.user_id = ? OR p.user_id IN (
            SELECT CASE WHEN user_one_id = ? THEN user_two_id ELSE user_one_id END
            FROM friendships WHERE (user_one_id = ? OR user_two_id = ?) AND status = \'accepted\'
        )
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ';
    $stmt = db()->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(4, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(5, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(6, $limit, PDO::PARAM_INT);
    $stmt->bindValue(7, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_post($post_id) {
    $stmt = db()->prepare('SELECT p.*, u.first_name, u.last_name, u.avatar FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
    $stmt->execute([$post_id]);
    return $stmt->fetch();
}

function create_post($user_id, $content, $image = null, $privacy = 'public') {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO posts (user_id, content, image, privacy, created_at, updated_at) VALUES (?, ?, ?, ?, datetime(\'now\'), datetime(\'now\'))');
    $stmt->execute([$user_id, $content, $image, $privacy]);
    return $pdo->lastInsertId();
}

function delete_post($post_id, $user_id) {
    $stmt = db()->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    return $stmt->execute([$post_id, $user_id]);
}

function toggle_like($post_id, $user_id) {
    $pdo = db();
    $existing = $pdo->prepare('SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?');
    $existing->execute([$post_id, $user_id]);
    if ($existing->fetch()) {
        $pdo->prepare('DELETE FROM post_likes WHERE post_id = ? AND user_id = ?')->execute([$post_id, $user_id]);
        return false;
    }
    $pdo->prepare('INSERT INTO post_likes (post_id, user_id, created_at) VALUES (?, ?, datetime(\'now\'))')->execute([$post_id, $user_id]);
    return true;
}

function get_post_likes($post_id) {
    $stmt = db()->prepare('SELECT u.id, u.first_name, u.last_name, u.avatar FROM post_likes pl JOIN users u ON u.id = pl.user_id WHERE pl.post_id = ? ORDER BY pl.created_at DESC');
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

function add_comment($post_id, $user_id, $content) {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, datetime(\'now\'))');
    $stmt->execute([$post_id, $user_id, $content]);
    $comment_id = $pdo->lastInsertId();
    $stmt = db()->prepare('SELECT c.*, u.first_name, u.last_name, u.avatar FROM comments c JOIN users u ON u.id = c.user_id WHERE c.id = ?');
    $stmt->execute([$comment_id]);
    return $stmt->fetch();
}

function get_comments($post_id) {
    $stmt = db()->prepare('SELECT c.*, u.first_name, u.last_name, u.avatar FROM comments c JOIN users u ON u.id = c.user_id WHERE c.post_id = ? ORDER BY c.created_at ASC');
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

function friendship_status($user_one, $user_two) {
    $a = min($user_one, $user_two);
    $b = max($user_one, $user_two);
    $stmt = db()->prepare('SELECT * FROM friendships WHERE user_one_id = ? AND user_two_id = ?');
    $stmt->execute([$a, $b]);
    return $stmt->fetch();
}

function send_friend_request($from_id, $to_id) {
    $a = min($from_id, $to_id);
    $b = max($from_id, $to_id);
    $pdo = db();
    $existing = $pdo->prepare('SELECT id, status FROM friendships WHERE user_one_id = ? AND user_two_id = ?');
    $existing->execute([$a, $b]);
    $row = $existing->fetch();
    if ($row) {
        if ($row['status'] === 'pending') return 'pending';
        if ($row['status'] === 'accepted') return 'friends';
        return 'exists';
    }
    $pdo->prepare('INSERT INTO friendships (user_one_id, user_two_id, status, created_at, updated_at) VALUES (?, ?, \'pending\', datetime(\'now\'), datetime(\'now\'))')->execute([$a, $b]);
    return 'sent';
}

function accept_friend_request($from_id, $to_id) {
    $a = min($from_id, $to_id);
    $b = max($from_id, $to_id);
    $pdo = db();
    $pdo->prepare('UPDATE friendships SET status = \'accepted\', updated_at = datetime(\'now\') WHERE user_one_id = ? AND user_two_id = ? AND status = \'pending\'')->execute([$a, $b]);
    return $pdo->prepare('SELECT changes()')->fetchColumn() > 0;
}

function reject_friend_request($from_id, $to_id) {
    $a = min($from_id, $to_id);
    $b = max($from_id, $to_id);
    return db()->prepare('DELETE FROM friendships WHERE user_one_id = ? AND user_two_id = ? AND status = \'pending\'')->execute([$a, $b]);
}

function remove_friend($user_one, $user_two) {
    $a = min($user_one, $user_two);
    $b = max($user_one, $user_two);
    return db()->prepare('DELETE FROM friendships WHERE user_one_id = ? AND user_two_id = ? AND status = \'accepted\'')->execute([$a, $b]);
}

function get_friends($user_id) {
    $sql = '
        SELECT u.*,
        CASE WHEN f.user_one_id = ? THEN f.user_two_id ELSE f.user_one_id END AS friend_id
        FROM friendships f
        JOIN users u ON u.id = CASE WHEN f.user_one_id = ? THEN f.user_two_id ELSE f.user_one_id END
        WHERE (f.user_one_id = ? OR f.user_two_id = ?) AND f.status = \'accepted\'
    ';
    $stmt = db()->prepare($sql);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    return $stmt->fetchAll();
}

function get_pending_requests($user_id) {
    $sql = '
        SELECT u.*, f.id AS request_id,
        CASE WHEN f.user_one_id = ? THEN \'sent\' ELSE \'received\' END AS direction
        FROM friendships f
        JOIN users u ON u.id = CASE WHEN f.user_one_id = ? THEN f.user_two_id ELSE f.user_one_id END
        WHERE (f.user_one_id = ? OR f.user_two_id = ?) AND f.status = \'pending\'
    ';
    $stmt = db()->prepare($sql);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    return $stmt->fetchAll();
}

function get_received_requests($user_id) {
    $sql = '
        SELECT u.*, f.id AS request_id
        FROM friendships f
        JOIN users u ON u.id = f.user_one_id
        WHERE f.user_two_id = ? AND f.status = \'pending\'
    ';
    $stmt = db()->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_sent_requests($user_id) {
    $sql = '
        SELECT u.*, f.id AS request_id
        FROM friendships f
        JOIN users u ON u.id = f.user_two_id
        WHERE f.user_one_id = ? AND f.status = \'pending\'
    ';
    $stmt = db()->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function friends_count($user_id) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM friendships WHERE (user_one_id = ? OR user_two_id = ?) AND status = \'accepted\'');
    $stmt->execute([$user_id, $user_id]);
    return (int)$stmt->fetchColumn();
}

function get_conversations($user_id) {
    $sql = '
        SELECT c.*, cp.user_id AS other_user_id,
        (SELECT m.content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
        (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message_at,
        (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) AS unread_count
        FROM conversations c
        JOIN conversation_participants cp ON cp.conversation_id = c.id
        WHERE cp.user_id = ?
        ORDER BY c.updated_at DESC
    ';
    $stmt = db()->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll();
}

function get_or_create_conversation($user1, $user2) {
    $pdo = db();
    $existing = $pdo->prepare('
        SELECT c.id FROM conversations c
        JOIN conversation_participants cp1 ON cp1.conversation_id = c.id AND cp1.user_id = ?
        JOIN conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id = ?
        GROUP BY c.id HAVING COUNT(*) = 2
    ');
    $existing->execute([$user1, $user2]);
    $row = $existing->fetch();
    if ($row) return (int)$row['id'];
    $pdo->prepare('INSERT INTO conversations (created_at, updated_at) VALUES (datetime(\'now\'), datetime(\'now\'))')->execute();
    $cid = $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)')->execute([$cid, $user1]);
    $pdo->prepare('INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)')->execute([$cid, $user2]);
    return (int)$cid;
}

function get_messages($conversation_id, $user_id, $limit = 50) {
    $sql = '
        SELECT m.*, u.first_name, u.last_name, u.avatar
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at DESC
        LIMIT ?
    ';
    $stmt = db()->prepare($sql);
    $stmt->bindValue(1, $conversation_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_reverse($stmt->fetchAll());
}

function send_message($conversation_id, $sender_id, $content, $image = null) {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, content, image, is_read, created_at) VALUES (?, ?, ?, ?, 0, datetime(\'now\'))');
    $stmt->execute([$conversation_id, $sender_id, $content, $image]);
    $pdo->prepare('UPDATE conversations SET updated_at = datetime(\'now\') WHERE id = ?')->execute([$conversation_id]);
    return $pdo->lastInsertId();
}

function mark_messages_read($conversation_id, $user_id) {
    return db()->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0')->execute([$conversation_id, $user_id]);
}

function add_notification($user_id, $actor_id, $type, $post_id = null, $comment_id = null, $message_id = null, $conversation_id = null) {
    if ($user_id == $actor_id) return;
    return db()->prepare('INSERT INTO notifications (user_id, actor_id, type, post_id, comment_id, message_id, conversation_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, datetime(\'now\'))')->execute([$user_id, $actor_id, $type, $post_id, $comment_id, $message_id, $conversation_id]);
}

function get_notifications($user_id, $limit = 20) {
    $stmt = db()->prepare('
        SELECT n.*, u.first_name, u.last_name, u.avatar
        FROM notifications n
        JOIN users u ON u.id = n.actor_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function unread_notifications_count($user_id) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

function mark_notifications_read($user_id) {
    return db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$user_id]);
}

function update_user_profile($user_id, $fields) {
    $pdo = db();
    $sets = [];
    $values = [];
    foreach ($fields as $col => $val) {
        $sets[] = "$col = ?";
        $values[] = $val;
    }
    $sets[] = 'updated_at = datetime(\'now\')';
    $values[] = $user_id;
    $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
    return $pdo->prepare($sql)->execute($values);
}
