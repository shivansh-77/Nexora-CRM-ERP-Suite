<?php
// project_delete.php
session_start();
include('connection.php');

// 1) Validate ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    echo "<script>alert('Invalid project ID.'); window.location.href='project_display.php';</script>";
    exit;
}

$project_id = (int)$_GET['id'];

// 2) Optional: ensure project exists
$chk = mysqli_query($connection, "SELECT id FROM projects WHERE id = $project_id");
if (!$chk || mysqli_num_rows($chk) === 0) {
    echo "<script>alert('Project not found.'); window.location.href='project_display.php';</script>";
    exit;
}

// 3) Delete inside a transaction
mysqli_begin_transaction($connection);
try {
    // If you have FK with ON DELETE CASCADE, the next two deletes can be skipped.
    // Delete child records first (defensive if no FK cascade)
    if (!mysqli_query($connection, "DELETE FROM project_operation_items WHERE project_id = $project_id")) {
        throw new Exception('Failed to delete operation items: ' . mysqli_error($connection));
    }
    if (!mysqli_query($connection, "DELETE FROM project_task_items WHERE project_id = $project_id")) {
        throw new Exception('Failed to delete task items: ' . mysqli_error($connection));
    }

    // Delete the project
    if (!mysqli_query($connection, "DELETE FROM projects WHERE id = $project_id")) {
        throw new Exception('Failed to delete project: ' . mysqli_error($connection));
    }

    mysqli_commit($connection);
    // Clean output if anything printed before
    if (ob_get_length()) { ob_end_clean(); }
    echo "<script>alert('Project deleted successfully.'); window.location.href='project_display.php';</script>";
    exit;

} catch (Exception $e) {
    mysqli_rollback($connection);
    if (ob_get_length()) { ob_end_clean(); }
    // For production you might want a generic message and log $e->getMessage()
    echo "<script>alert('Error deleting project.'); window.location.href='project_display.php';</script>";
    exit;
}
