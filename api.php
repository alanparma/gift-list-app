<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config.php';
header('Content-Type: application/json');

try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? '';

function calculateAgeDisplay($birthdate, $hasYear) {
    if (!$birthdate) return null;
    $parts = explode('-', $birthdate);
    $monthName = date('M j', strtotime($birthdate));

    if (!$hasYear) {
        return "Bday: $monthName";
    }

    try {
        $bday = new DateTime($birthdate);
        $diff = $bday->diff(new DateTime());
        $months = ($diff->y * 12) + $diff->m;
        $ageStr = ($months < 24) ? "{$months} mos old" : "{$diff->y} yrs old";
        return "$monthName ($ageStr)";
    } catch (Exception $e) {
        return null;
    }
}

// 1. DASHBOARD DATA WITH CONTEXTUAL READINESS
if ($action === 'get_dashboard') {
    $year = (int)($_GET['year'] ?? 2026);

    // Auto-seed default annual events
    $recurring = [
        'Christmas' => "$year-12-25",
        "Mother's Day" => "$year-05-10",
        "Father's Day" => "$year-06-21",
        "Anniversary" => null,
        "Birthdays" => null
    ];
    foreach ($recurring as $title => $date) {
        $stmt = $pdo->prepare("SELECT id FROM events WHERE title = ? AND event_year = ?");
        $stmt->execute([$title, $year]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO events (title, event_date, event_year, is_recurring) VALUES (?, ?, ?, 1)")->execute([$title, $date, $year]);
        }
    }

    // Fetch all events for selected year
    // In the missing_gifts calculation query inside get_dashboard:
    // Change WHERE p.is_abroad = 0 so they do not flag as "missing gift"
    $eventsStmt = $pdo->prepare("
        SELECT e.*,
               COUNT(DISTINCT CASE WHEN p.is_abroad = 0 THEN p.id END) as total_target,
               SUM(CASE WHEN p.is_abroad = 0 AND (g.id IS NULL OR g.status = 'Idea') THEN 1 ELSE 0 END) as missing_gifts
        FROM events e
        CROSS JOIN people p
        LEFT JOIN gifts g ON g.person_id = p.id AND g.event_id = e.id
        WHERE e.event_year = ?
        GROUP BY e.id
        ORDER BY e.event_date ASC
    ");
    $eventsStmt->execute([$year]);
    $eventsRaw = $eventsStmt->fetchAll() ?: [];

    // Fetch People and Gifts
    $peopleStmt = $pdo->query("
        SELECT p.*, GROUP_CONCAT(g.name SEPARATOR ', ') as group_names
        FROM people p
        LEFT JOIN person_groups pg ON p.id = pg.person_id
        LEFT JOIN groups g ON pg.group_id = g.id
        GROUP BY p.id
        ORDER BY p.full_name ASC
    ");
    $peopleRaw = $peopleStmt ? $peopleStmt->fetchAll() : [];

    $people = array_map(function($p) use ($pdo, $year) {
        $p['age_display'] = calculateAgeDisplay($p['birthdate'], $p['birthdate_has_year'] ?? 1);
        $stmt = $pdo->prepare("
            SELECT g.*, e.title as event_title
            FROM gifts g
            LEFT JOIN events e ON g.event_id = e.id
            WHERE g.person_id = ? AND g.year_given = ?
            ORDER BY g.id DESC
        ");
        $stmt->execute([$p['id'], $year]);
        $p['gifts'] = $stmt->fetchAll() ?: [];
        return $p;
    }, $peopleRaw);

    // Contextual Event Readiness Calculation
    $events = array_map(function($evt) use ($people, $year) {
        $title = strtolower($evt['title']);
        $targetPeople = [];

        if (strpos($title, 'christmas') !== false) {
            $targetPeople = $people;
        } elseif (strpos($title, "mother") !== false) {
            $targetPeople = array_filter($people, function($p) {
                return in_array(strtolower($p['relationship'] ?? ''), ['mother', 'mother-in-law', 'mom', 'stepmother', 'grandmother']);
            });
        } elseif (strpos($title, "father") !== false) {
            $targetPeople = array_filter($people, function($p) {
                return in_array(strtolower($p['relationship'] ?? ''), ['father', 'father-in-law', 'dad', 'stepfather', 'grandfather']);
            });
        } elseif (strpos($title, 'anniversary') !== false) {
            $targetPeople = array_filter($people, function($p) {
                return in_array(strtolower($p['relationship'] ?? ''), ['spouse', 'partner', 'husband', 'wife']);
            });
        } elseif (strpos($title, 'birthday') !== false) {
            $targetPeople = array_filter($people, function($p) {
                return !empty($p['birthdate']);
            });
        } else {
            // Custom event: targets people who have gifts assigned to this event
            $targetPeople = array_filter($people, function($p) use ($evt) {
                foreach ($p['gifts'] as $g) {
                    if ($g['event_id'] == $evt['id']) return true;
                }
                return false;
            });
        }

        $missingGifts = 0;
        foreach ($targetPeople as $p) {
            $hasValidGift = false;
            foreach ($p['gifts'] as $g) {
                if (($g['event_id'] == $evt['id'] || (strpos($title, 'birthday') !== false && empty($g['event_id']))) && $g['status'] !== 'Idea') {
                    $hasValidGift = true;
                    break;
                }
            }
            if (!$hasValidGift) $missingGifts++;
        }

        $evt['total_target'] = count($targetPeople);
        $evt['missing_gifts'] = $missingGifts;
        return $evt;
    }, $eventsRaw);

    // Stash & Groups
    $stash = $pdo->query("SELECT * FROM gifts WHERE person_id IS NULL ORDER BY id DESC")->fetchAll() ?: [];
    $groups = $pdo->query("SELECT id, name FROM groups ORDER BY name ASC")->fetchAll() ?: [];

    echo json_encode(['year' => $year, 'events' => $events, 'people' => $people, 'stash' => $stash, 'groups' => $groups]);
    exit;
}

    // 2. SAVE PERSON (With Relationship & Birth Year Option)
    if ($action === 'save_person') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['full_name'] ?? '');
        $relationship = trim($_POST['relationship'] ?? 'Friend');
        $dob = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
        $hasYear = isset($_POST['birthdate_has_year']) ? (int)$_POST['birthdate_has_year'] : 1;
        $isAbroad = isset($_POST['is_abroad']) ? (int)$_POST['is_abroad'] : 0;
        $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
        $groupList = json_decode($_POST['groups'] ?? '[]', true) ?: ['General'];
    
        if (empty($name)) {
            echo json_encode(['error' => 'Name is required']);
            exit;
        }
    
        if ($id) {
            $stmt = $pdo->prepare("UPDATE people SET full_name = ?, relationship = ?, birthdate = ?, birthdate_has_year = ?, is_abroad = ?, notes = ? WHERE id = ?");
            $stmt->execute([$name, $relationship, $dob, $hasYear, $isAbroad, $notes, $id]);
            $personId = $id;
            $pdo->prepare("DELETE FROM person_groups WHERE person_id = ?")->execute([$personId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO people (full_name, relationship, birthdate, birthdate_has_year, is_abroad, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $relationship, $dob, $hasYear, $isAbroad, $notes]);
            $personId = $pdo->lastInsertId();
        }
    
        foreach ($groupList as $gName) {
            $gName = trim($gName);
            if (empty($gName)) continue;
            $pdo->prepare("INSERT IGNORE INTO groups (name) VALUES (?)")->execute([$gName]);
            $getG = $pdo->prepare("SELECT id FROM groups WHERE name = ?");
            $getG->execute([$gName]);
            $groupId = $getG->fetchColumn();
            if ($groupId) {
                $pdo->prepare("INSERT IGNORE INTO person_groups (person_id, group_id) VALUES (?, ?)")->execute([$personId, $groupId]);
            }
        }
    
        echo json_encode(['success' => true]);
        exit;
    }

// 3. EVENT ENDPOINTS
if ($action === 'add_event') {
    $title = trim($_POST['title'] ?? '');
    $date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
    $year = (int)($_POST['event_year'] ?? 2026);
    if (!empty($title)) {
        $pdo->prepare("INSERT INTO events (title, event_date, event_year, is_recurring) VALUES (?, ?, ?, 0)")->execute([$title, $date, $year]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_event') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// 4. DELETE PERSON
if ($action === 'delete_person') {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM people WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// 5. SAVE GIFT
// SAVE / UPDATE / ADD GIFT
if ($action === 'save_gift' || $action === 'add_gift') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $person_id = !empty($_POST['person_id']) ? (int)$_POST['person_id'] : null;
    $event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    $item_name = trim($_POST['item_name'] ?? '');
    $category = !empty($_POST['category']) ? trim($_POST['category']) : 'General';
    $status = !empty($_POST['status']) ? trim($_POST['status']) : 'Idea';
    $location = !empty($_POST['storage_location']) ? trim($_POST['storage_location']) : '';
    $price = (isset($_POST['price']) && $_POST['price'] !== '') ? (float)$_POST['price'] : 0.00;
    $year = !empty($_POST['year_given']) ? (int)$_POST['year_given'] : (int)date('Y');

    if (empty($item_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Gift name is required']);
        exit;
    }

    try {
        if ($id) {
            // Update existing gift
            $stmt = $pdo->prepare("
                UPDATE gifts 
                SET person_id = ?, event_id = ?, item_name = ?, category = ?, status = ?, storage_location = ?, price = ?, year_given = ? 
                WHERE id = ?
            ");
            $stmt->execute([$person_id, $event_id, $item_name, $category, $status, $location, $price, $year, $id]);
        } else {
            // Insert new gift
            $stmt = $pdo->prepare("
                INSERT INTO gifts (person_id, event_id, item_name, category, status, storage_location, price, year_given) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$person_id, $event_id, $item_name, $category, $status, $location, $price, $year]);
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// 6. DELETE GIFT
if ($action === 'delete_gift') {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM gifts WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// 7. IMPORT CSV SPREADSHEET (8-Column Support)
if ($action === 'import_csv') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'No file uploaded or upload error.']);
        exit;
    }

    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$file) {
        echo json_encode(['error' => 'Unable to read CSV file.']);
        exit;
    }

    // Sanitize headers
    $headers = fgetcsv($file);
    if (!$headers) {
        fclose($file);
        echo json_encode(['error' => 'CSV file is empty.']);
        exit;
    }

    $map = [];
    foreach ($headers as $idx => $header) {
        $clean = strtolower(trim($header));
        $clean = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', $clean));
        $map[$clean] = $idx;
    }

    $importedCount = 0;

    // Allowed ENUM statuses in gifts table
    $validStatuses = ['Idea', 'Purchased', 'Wrapped', 'Given'];

    while (($row = fgetcsv($file)) !== false) {
        if (array_filter($row) === []) continue;

        // 1. Parse Year
        $yearVal = trim($row[$map['year'] ?? -1] ?? '');
        $year = (!empty($yearVal) && is_numeric($yearVal)) ? (int)$yearVal : 2026;

        // 2. Parse Person & Relationship
        $name = trim($row[$map['full_name'] ?? ($map['name'] ?? -1)] ?? '');
        $relationship = trim($row[$map['relationship'] ?? -1] ?? 'Friend');
        if (empty($relationship)) $relationship = 'Friend';

        // 3. Parse Birthday
        $rawBday = trim($row[$map['birthday'] ?? ($map['birthdate'] ?? -1)] ?? '');
        $dob = null;
        $hasYear = 0;

        if (!empty($rawBday)) {
            if (preg_match('/\b(19\d\d|20\d\d)\b/', $rawBday)) {
                $hasYear = 1;
                $dob = date('Y-m-d', strtotime($rawBday));
            } else {
                $hasYear = 0;
                $time = strtotime($rawBday . " 1900");
                if ($time !== false) {
                    $dob = date('1900-m-d', $time);
                }
            }
        }

        // 4. Resolve Person
        $personId = null;
        if (!empty($name)) {
            $stmt = $pdo->prepare("SELECT id, relationship, birthdate FROM people WHERE LOWER(full_name) = LOWER(?)");
            $stmt->execute([$name]);
            $existingPerson = $stmt->fetch();

            if ($existingPerson) {
                $personId = $existingPerson['id'];
                $updates = [];
                $params = [];

                if (empty($existingPerson['relationship']) || $existingPerson['relationship'] === 'Friend') {
                    $updates[] = "relationship = ?";
                    $params[] = $relationship;
                }
                if (empty($existingPerson['birthdate']) && $dob) {
                    $updates[] = "birthdate = ?";
                    $updates[] = "birthdate_has_year = ?";
                    $params[] = $dob;
                    $params[] = $hasYear;
                }

                if (!empty($updates)) {
                    $params[] = $personId;
                    $pdo->prepare("UPDATE people SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO people (full_name, relationship, birthdate, birthdate_has_year) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $relationship, $dob, $hasYear]);
                $personId = $pdo->lastInsertId();
            }

            // Groups Junction
            $groupStr = trim($row[$map['group'] ?? ($map['groups'] ?? -1)] ?? 'General');
            $groups = array_filter(array_map('trim', explode(',', $groupStr)));
            if (empty($groups)) $groups = ['General'];

            foreach ($groups as $gName) {
                $pdo->prepare("INSERT IGNORE INTO groups (name) VALUES (?)")->execute([$gName]);
                $getG = $pdo->prepare("SELECT id FROM groups WHERE name = ?");
                $getG->execute([$gName]);
                $groupId = $getG->fetchColumn();

                if ($groupId) {
                    $pdo->prepare("INSERT IGNORE INTO person_groups (person_id, group_id) VALUES (?, ?)")->execute([$personId, $groupId]);
                }
            }
        }

        // 5. Parse Event
        $eventTitle = trim($row[$map['event'] ?? -1] ?? '');
        $eventId = null;

        if (!empty($eventTitle)) {
            $stmt = $pdo->prepare("SELECT id FROM events WHERE LOWER(title) = LOWER(?) AND event_year = ?");
            $stmt->execute([$eventTitle, $year]);
            $eventId = $stmt->fetchColumn();

            if (!$eventId) {
                $insEvt = $pdo->prepare("INSERT INTO events (title, event_year, is_recurring) VALUES (?, ?, 1)");
                $insEvt->execute([$eventTitle, $year]);
                $eventId = $pdo->lastInsertId();
            }
        }

        // 6. Parse and Insert Gift
        $giftItem = trim($row[$map['gift_item'] ?? ($map['gift'] ?? ($map['item'] ?? -1))] ?? '');
        $statusRaw = ucfirst(strtolower(trim($row[$map['status'] ?? -1] ?? 'Idea')));
        $status = in_array($statusRaw, $validStatuses, true) ? $statusRaw : 'Idea';

        if (!empty($giftItem)) {
            $stmt = $pdo->prepare("INSERT INTO gifts (person_id, event_id, item_name, status, year_given) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$personId, $eventId, $giftItem, $status, $year]);
        }

        $importedCount++;
        
        // Flexible parsing for missing columns:
        $rawAbroad = strtolower(trim($row[$map['abroad'] ?? ($map['is_abroad'] ?? -1)] ?? '0'));
        $isAbroad = in_array($rawAbroad, ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
        
        // When checking existing person:
        $stmt = $pdo->prepare("SELECT id, relationship, birthdate, is_abroad FROM people WHERE LOWER(full_name) = LOWER(?)");
        $stmt->execute([$name]);
        $existingPerson = $stmt->fetch();
        
        if ($existingPerson) {
            $personId = $existingPerson['id'];
            $updates = [];
            $params = [];
        
            if (empty($existingPerson['relationship']) || $existingPerson['relationship'] === 'Friend') {
                $updates[] = "relationship = ?";
                $params[] = $relationship;
            }
            if (empty($existingPerson['birthdate']) && $dob) {
                $updates[] = "birthdate = ?";
                $updates[] = "birthdate_has_year = ?";
                $params[] = $dob;
                $params[] = $hasYear;
            }
            // Update abroad status if header was present in CSV
            if (isset($map['abroad']) || isset($map['is_abroad'])) {
                $updates[] = "is_abroad = ?";
                $params[] = $isAbroad;
            }
        
            if (!empty($updates)) {
                $params[] = $personId;
                $pdo->prepare("UPDATE people SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO people (full_name, relationship, birthdate, birthdate_has_year, is_abroad) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $relationship, $dob, $hasYear, $isAbroad]);
            $personId = $pdo->lastInsertId();
        }
    }

    fclose($file);
    echo json_encode(['success' => true, 'imported' => $importedCount]);
    exit;
}