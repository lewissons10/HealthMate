<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
	$pdo = getDBConnection();

	$period = $_GET['period'] ?? 'all';
	$category = $_GET['category'] ?? 'points';
	$limit = (int)($_GET['limit'] ?? 50);
	if ($limit <= 0 || $limit > 200) {
		$limit = 50;
	}

	$dateFilter = '';
	$params = [];
	if ($period === 'today') {
		$dateFilter = " AND DATE(w.created_at) = CURDATE()";
	} elseif ($period === 'week') {
		$dateFilter = " AND YEARWEEK(w.created_at, 1) = YEARWEEK(CURDATE(), 1)";
	} elseif ($period === 'month') {
		$dateFilter = " AND YEAR(w.created_at) = YEAR(CURDATE()) AND MONTH(w.created_at) = MONTH(CURDATE())";
	}

	$orderBy = 'u.points DESC';
	$selectAgg = 'u.points AS score';
	if ($category === 'calories') {
		$selectAgg = 'COALESCE(SUM(w.calories_burned), 0) AS score';
		$orderBy = 'score DESC';
	} elseif ($category === 'workouts') {
		$selectAgg = 'COALESCE(COUNT(w.id), 0) AS score';
		$orderBy = 'score DESC';
	} elseif ($category === 'points' && $period !== 'all') {
		// Use recent activity to approximate points within the selected period
		$selectAgg = 'COALESCE(SUM(w.calories_burned), 0) AS score';
		$orderBy = 'score DESC';
	}

	$sql = "
		SELECT 
			u.id,
			u.username,
			u.first_name,
			u.last_name,
			u.points,
			$selectAgg
		FROM users u
		LEFT JOIN workout_logs w ON w.user_id = u.id" . ($dateFilter ? $dateFilter : '') . "
		GROUP BY u.id, u.username, u.first_name, u.last_name, u.points
		ORDER BY $orderBy
		LIMIT ?
	";

	$stmt = $pdo->prepare($sql);
	$stmt->bindValue(1, $limit, PDO::PARAM_INT);
	$stmt->execute();
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$ranked = [];
	$rank = 1;
	foreach ($rows as $row) {
		$ranked[] = [
			'rank' => $rank++,
			'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ($row['username'] ?? 'Unknown'),
			'points' => (int)($row['points'] ?? 0),
			'score' => (int)($row['score'] ?? 0),
			'badge' => $rank === 2 ? '🏆' : ($rank === 3 ? '🥈' : ($rank === 4 ? '🥉' : '💪'))
		];
	}

	echo json_encode(['success' => true, 'period' => $period, 'category' => $category, 'limit' => $limit, 'leaders' => $ranked]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


