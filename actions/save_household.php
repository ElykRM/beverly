<?php
include '../includes/auth.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/add.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert primary household
    $stmt = $pdo->prepare("
        INSERT INTO households 
        (last_name, first_name, middle_name, home_status, block, lot, street, birthday, gender, contact_no, occupation, num_pets)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['last_name'] ?? '',
        $_POST['first_name'] ?? '',
        $_POST['middle_name'] ?? null,
        $_POST['home_status'] ?? 'Owner',
        $_POST['block'] ?? null,
        $_POST['lot'] ?? null,
        $_POST['street'] ?? null,
        $_POST['birthday'] ?: null,
        $_POST['gender'] ?? null,
        $_POST['contact_no'] ?? null,
        $_POST['occupation'] ?? null,
        $_POST['num_pets'] ?? 0
    ]);

    $household_id = $pdo->lastInsertId();

    // Insert additional members
    if (!empty($_POST['members']) && is_array($_POST['members'])) {
        $mstmt = $pdo->prepare("
            INSERT INTO household_members 
            (household_id, first_name, last_name, middle_name, relation, birthday, gender, contact_no, occupation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($_POST['members'] as $m) {
            // Skip if the entire row is empty (no first name, last name, or relation filled in)
            if (empty($m['first_name']) && empty($m['last_name']) && empty($m['relation'])) {
                continue;
            }

            // At least one of first_name or last_name should be provided
            if (empty($m['first_name']) && empty($m['last_name'])) {
                continue;
            }

            $mstmt->execute([
                $household_id,
                $m['first_name'] ?? '',
                $m['last_name'] ?? '',
                $m['middle_name'] ?? null,
                $m['relation'] ?? '',
                $m['birthday'] ?: null,
                $m['gender'] ?? null,
                $m['contact_no'] ?? null,
                $m['occupation'] ?? null
            ]);
        }
    }

    // Insert vehicles
    if (!empty($_POST['vehicles']) && is_array($_POST['vehicles'])) {
        $vstmt = $pdo->prepare("
            INSERT INTO vehicles (household_id, brand, type_model, color, plate_no)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($_POST['vehicles'] as $v) {
            if (empty($v['plate_no'])) continue;

            $vstmt->execute([
                $household_id,
                $v['brand'] ?? null,
                $v['type_model'] ?? null,
                $v['color'] ?? null,
                $v['plate_no'] ?? null
            ]);
        }
    }

    $pdo->commit();

    header("Location: ../pages/habitants.php?msg=Household added successfully");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error saving record: " . $e->getMessage());
}