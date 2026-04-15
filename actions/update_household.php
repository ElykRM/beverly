<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: ../pages/habitants.php');
    exit;
}

$id = (int)$_POST['id'];

try {
    $pdo->beginTransaction();

    // Handle move-out date based on status dropdown
    $moveOutDate = null;
    if (isset($_POST['move_out_status']) && $_POST['move_out_status'] === 'moved' && !empty($_POST['move_out_date'])) {
        $moveOutDate = $_POST['move_out_date'];
    }

    // Update primary household
    $stmt = $pdo->prepare("
            UPDATE households SET
                last_name = ?, first_name = ?, middle_name = ?,
                home_status = ?, block = ?, lot = ?, street = ?,
                birthday = ?, gender = ?,
                contact_no = ?, occupation = ?, num_pets = ?,
                move_in_date = ?, move_out_date = ?,
                updated_at = NOW()
            WHERE id = ?
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
            $_POST['num_pets'] ?? 0,
            $_POST['move_in_date'] ?: null,
            $moveOutDate,
            $id
        ]);

    // Delete existing additional members
    $pdo->prepare("DELETE FROM household_members WHERE household_id = ?")->execute([$id]);

    // Insert updated additional members
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
                $id,
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

    // Delete existing vehicles
    $pdo->prepare("DELETE FROM vehicles WHERE household_id = ?")->execute([$id]);

    // Insert updated vehicles
    if (!empty($_POST['vehicles']) && is_array($_POST['vehicles'])) {
        $vstmt = $pdo->prepare("
            INSERT INTO vehicles (household_id, brand, type_model, color, plate_no)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($_POST['vehicles'] as $v) {
            // Require plate number for vehicle record
            if (empty($v['plate_no'])) {
                continue;
            }

            $vstmt->execute([
                $id,
                $v['brand'] ?? null,
                $v['type_model'] ?? null,
                $v['color'] ?? null,
                $v['plate_no'] ?? null
            ]);
        }
    }

    $pdo->commit();

    header("Location: ../actions/view.php?id=$id&msg=Household updated successfully");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error updating record: " . $e->getMessage());
}