<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $pdo = db();

    // ------------------------------------------------------------
    // Basic totals
    // ------------------------------------------------------------
    $totalStudents = (int)$pdo->query("SELECT COUNT(*) as cnt FROM students")->fetch()['cnt'];
    $totalCollection = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) as total FROM payments")->fetch()['total'];

    $todayStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE payment_date = ?");
    $todayStmt->execute([date('Y-m-d')]);
    $todayCollection = (float)$todayStmt->fetch()['total'];

    // ------------------------------------------------------------
    // This month vs last month (for trend arrow/percent)
    // ------------------------------------------------------------
    $thisMonthKey = date('Y-m');
    $lastMonthKey = date('Y-m', strtotime('first day of last month'));

    $monthStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE strftime('%Y-%m', payment_date) = ?");
    $monthStmt->execute([$thisMonthKey]);
    $monthCollection = (float)$monthStmt->fetch()['total'];

    $monthStmt->execute([$lastMonthKey]);
    $lastMonthCollection = (float)$monthStmt->fetch()['total'];

    $monthTrendPercent = null;
    if ($lastMonthCollection > 0) {
        $monthTrendPercent = round((($monthCollection - $lastMonthCollection) / $lastMonthCollection) * 100, 1);
    } elseif ($monthCollection > 0) {
        $monthTrendPercent = 100.0; // went from 0 to something
    }

    // New admissions this month
    $admissionStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM students WHERE strftime('%Y-%m', admission_date) = ?");
    $admissionStmt->execute([$thisMonthKey]);
    $newAdmissionsMonth = (int)$admissionStmt->fetch()['cnt'];

    // ------------------------------------------------------------
    // Pending balances — single aggregate query (no N+1 loop).
    // Only need the total + count here; the full list already
    // lives on the Reports > Pending Fee tab.
    // ------------------------------------------------------------
    $balanceStmt = $pdo->query("
        SELECT
            CASE
                WHEN s.fee_mode != 'default' AND s.final_fee IS NOT NULL THEN s.final_fee
                ELSE c.fee
            END as total_fee,
            COALESCE(SUM(p.amount), 0) as total_paid
        FROM students s
        JOIN courses c ON c.id = s.course_id
        LEFT JOIN payments p ON p.student_id = s.id
        GROUP BY s.id
    ");
    $allBalances = $balanceStmt->fetchAll();

    $totalPending = 0.0;
    $pendingCount = 0;
    foreach ($allBalances as $row) {
        $balance = (float)$row['total_fee'] - (float)$row['total_paid'];
        if ($balance > 0) {
            $totalPending += $balance;
            $pendingCount++;
        }
    }

    // ------------------------------------------------------------
    // Collection trend — last 14 days (for the chart), zero-filled
    // ------------------------------------------------------------
    $trendStmt = $pdo->prepare("
        SELECT payment_date, SUM(amount) as total
        FROM payments
        WHERE payment_date >= ?
        GROUP BY payment_date
    ");
    $startDate = date('Y-m-d', strtotime('-13 days'));
    $trendStmt->execute([$startDate]);
    $trendRows = $trendStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $collectionTrend = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $collectionTrend[] = [
            'date'  => $d,
            'total' => isset($trendRows[$d]) ? (float)$trendRows[$d] : 0.0,
        ];
    }

    // ------------------------------------------------------------
    // Course-wise collection breakdown (all-time, top 6) - for the doughnut
    // ------------------------------------------------------------
    $courseStmt = $pdo->query("
        SELECT c.name, COALESCE(SUM(p.amount), 0) as total
        FROM courses c
        LEFT JOIN students s ON s.course_id = c.id
        LEFT JOIN payments p ON p.student_id = s.id
        GROUP BY c.id
        HAVING total > 0
        ORDER BY total DESC
        LIMIT 6
    ");
    $courseBreakdown = $courseStmt->fetchAll();

    // ------------------------------------------------------------
    // Course-wise overview table — students, collected, pending,
    // per course. Not shown anywhere else in the app.
    // ------------------------------------------------------------
    $overviewStmt = $pdo->query("
        SELECT
            c.id, c.name,
            COUNT(s.id) as student_count,
            COALESCE(SUM(
                CASE
                    WHEN s.id IS NULL THEN 0
                    WHEN s.fee_mode != 'default' AND s.final_fee IS NOT NULL THEN s.final_fee
                    ELSE c.fee
                END
            ), 0) as total_billable,
            COALESCE((
                SELECT SUM(p.amount) FROM payments p
                JOIN students s2 ON s2.id = p.student_id
                WHERE s2.course_id = c.id
            ), 0) as total_collected
        FROM courses c
        LEFT JOIN students s ON s.course_id = c.id
        GROUP BY c.id
        ORDER BY student_count DESC
    ");
    $courseOverview = [];
    foreach ($overviewStmt->fetchAll() as $row) {
        $billable = (float)$row['total_billable'];
        $collected = (float)$row['total_collected'];
        $courseOverview[] = [
            'name'            => $row['name'],
            'student_count'   => (int)$row['student_count'],
            'total_collected' => $collected,
            'total_pending'   => max(0, $billable - $collected),
            'percent'         => $billable > 0 ? round(min(100, ($collected / $billable) * 100)) : 0,
        ];
    }

    // ------------------------------------------------------------
    // Payment mode split — this month
    // ------------------------------------------------------------
    $modeStmt = $pdo->prepare("
        SELECT payment_mode, COALESCE(SUM(amount),0) as total, COUNT(*) as cnt
        FROM payments
        WHERE strftime('%Y-%m', payment_date) = ?
        GROUP BY payment_mode
        ORDER BY total DESC
    ");
    $modeStmt->execute([$thisMonthKey]);
    $paymentModeBreakdown = $modeStmt->fetchAll();

    jsonResponse(true, '', [
        'total_students'        => $totalStudents,
        'total_collection'      => $totalCollection,
        'today_collection'      => $todayCollection,
        'total_pending'         => $totalPending,
        'pending_count'         => $pendingCount,
        'month_collection'      => $monthCollection,
        'last_month_collection' => $lastMonthCollection,
        'month_trend_percent'   => $monthTrendPercent,
        'new_admissions_month'  => $newAdmissionsMonth,
        'collection_trend'      => $collectionTrend,
        'course_breakdown'      => $courseBreakdown,
        'course_overview'       => $courseOverview,
        'payment_mode_breakdown'=> $paymentModeBreakdown,
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load dashboard data: ' . $e->getMessage(), [], 500);
}