<?php
/**
 * Notification Helper Functions
 * Automatically create notifications for various events
 */

function createNotification($conn, $userId, $type, $title, $message, $referenceId = null, $referenceType = null, $link = null) {
    $stmt = $conn->prepare(
        'INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type, link)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssiis', $userId, $type, $title, $message, $referenceId, $referenceType, $link);
    return $stmt->execute();
}

function notifyDIOSUsers($conn, $type, $title, $message, $referenceId = null, $referenceType = null, $link = null) {
    $result = $conn->query("SELECT id FROM users WHERE role = 'DIOS' AND active = 1");
    while ($row = $result->fetch_assoc()) {
        createNotification($conn, $row['id'], $type, $title, $message, $referenceId, $referenceType, $link);
    }
}

function notifyAdmins($conn, $type, $title, $message, $referenceId = null, $referenceType = null, $link = null) {
    $result = $conn->query("SELECT id FROM users WHERE role IN ('Super Admin', 'Admin', 'DIOS') AND active = 1");
    while ($row = $result->fetch_assoc()) {
        createNotification($conn, $row['id'], $type, $title, $message, $referenceId, $referenceType, $link);
    }
}

function notifyAllUsers($conn, $type, $title, $message, $referenceId = null, $referenceType = null, $link = null) {
    $result = $conn->query("SELECT id FROM users WHERE active = 1");
    while ($row = $result->fetch_assoc()) {
        createNotification($conn, $row['id'], $type, $title, $message, $referenceId, $referenceType, $link);
    }
}

function notifyPasswordResetRequest($conn, $username, $userFullName, $requestId) {
    notifyDIOSUsers(
        $conn,
        'password_reset',
        'New Password Reset Request',
        "$userFullName ($username) requested a password reset",
        $requestId,
        'password_reset_request',
        '/password-resets'
    );
}

function notifyLeaveRequest($conn, $employeeName, $leaveType, $leaveId) {
    notifyAdmins(
        $conn,
        'leave_request',
        'New Leave Request',
        "$employeeName filed a $leaveType request",
        $leaveId,
        'leave',
        '/leave'
    );
}

function notifyTravelOrder($conn, $employeeName, $destination, $toId) {
    notifyAdmins(
        $conn,
        'travel_order',
        'New Travel Order',
        "$employeeName filed a travel order to $destination",
        $toId,
        'travel_order',
        '/to'
    );
}

function notifyEmployeeAdded($conn, $employeeName, $employeeId) {
    notifyAdmins(
        $conn,
        'employee_added',
        'New Employee Added',
        "$employeeName has been added to the system",
        $employeeId,
        'employee',
        '/employees'
    );
}

function notifyTrainingAdded($conn, $trainingTitle, $trainingId) {
    notifyAdmins(
        $conn,
        'training_added',
        'New Training Added',
        "New training: $trainingTitle",
        $trainingId,
        'training',
        '/trainings'
    );
}
