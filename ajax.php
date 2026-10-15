<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$user = current_user();

try {
    switch ($action) {
        case 'like_post':
            $post_id = (int)($_POST['post_id'] ?? 0);
            $liked = toggle_like($post_id, $user['id']);
            $stmt = db()->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?');
            $stmt->execute([$post_id]);
            $count = (int)$stmt->fetchColumn();
            if ($liked) {
                $post = get_post($post_id);
                if ($post && $post['user_id'] != $user['id']) {
                    add_notification($post['user_id'], $user['id'], 'like', $post_id);
                }
            }
            echo json_encode(['ok' => true, 'liked' => $liked, 'like_count' => $count]);
            break;

        case 'add_comment':
            $post_id = (int)($_POST['post_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            if (!$content) throw new Exception('Empty comment');
            $comment = add_comment($post_id, $user['id'], $content);
            $post = get_post($post_id);
            if ($post && $post['user_id'] != $user['id']) {
                add_notification($post['user_id'], $user['id'], 'comment', $post_id, $comment['id']);
            }
            echo json_encode(['ok' => true, 'comment' => $comment]);
            break;

        case 'get_comments':
            $post_id = (int)($_POST['post_id'] ?? 0);
            $comments = get_comments($post_id);
            echo json_encode(['ok' => true, 'comments' => $comments]);
            break;

        case 'delete_post':
            $post_id = (int)($_POST['post_id'] ?? 0);
            delete_post($post_id, $user['id']);
            echo json_encode(['ok' => true]);
            break;

        case 'friend_request':
            $target_id = (int)($_POST['user_id'] ?? 0);
            $res = send_friend_request($user['id'], $target_id);
            $msg = $res === 'sent' ? 'Friend request sent' : ($res === 'pending' ? 'Request already pending' : 'Already friends');
            echo json_encode(['ok' => true, 'message' => $msg]);
            break;

        case 'respond_request':
            $request_id = (int)($_POST['request_id'] ?? 0);
            $accept = (int)($_POST['accept'] ?? 0);
            $stmt = db()->prepare('SELECT * FROM friendships WHERE id = ?');
            $stmt->execute([$request_id]);
            $rel = $stmt->fetch();
            if (!$rel) throw new Exception('Request not found');
            $from = (int)$rel['user_one_id'];
            $to = (int)$rel['user_two_id'];
            if ($accept) {
                accept_friend_request($from, $to);
                add_notification($from, $user['id'], 'friend_accept');
            } else {
                reject_friend_request($from, $to);
            }
            echo json_encode(['ok' => true]);
            break;

        case 'remove_friend':
            $target_id = (int)($_POST['user_id'] ?? 0);
            remove_friend($user['id'], $target_id);
            echo json_encode(['ok' => true]);
            break;

        case 'send_message':
            $conv_id = (int)($_POST['conversation_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $image = null;
            if (!empty($_FILES['image']['tmp_name'])) {
                $image = upload_file($_FILES['image'], 'messages');
            }
            if (!$content && !$image) throw new Exception('Empty message');
            $stmt = db()->prepare('SELECT * FROM conversation_participants WHERE conversation_id = ? AND user_id = ?');
            $stmt->execute([$conv_id, $user['id']]);
            if (!$stmt->fetch()) throw new Exception('Not a participant');
            send_message($conv_id, $user['id'], $content, $image);
            $stmt = db()->prepare('SELECT user_id FROM conversation_participants WHERE conversation_id = ? AND user_id != ?');
            $stmt->execute([$conv_id, $user['id']]);
            $recipient = $stmt->fetch();
            if ($recipient) {
                add_notification((int)$recipient['user_id'], $user['id'], 'message', null, null, null, $conv_id);
            }
            echo json_encode(['ok' => true]);
            break;

        case 'get_messages':
            $conv_id = (int)($_POST['conversation_id'] ?? 0);
            $messages = get_messages($conv_id, $user['id']);
            mark_messages_read($conv_id, $user['id']);
            echo json_encode(['ok' => true, 'messages' => $messages]);
            break;

        case 'notifications_read':
            mark_notifications_read($user['id']);
            echo json_encode(['ok' => true]);
            break;

        case 'search_users':
            $q = trim($_POST['q'] ?? '');
            $users = search_users($q, $user['id']);
            echo json_encode(['ok' => true, 'users' => $users]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
