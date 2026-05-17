<?php
// Real-time Asset Management System
session_start();

// Simple in-memory database for demo
if (!isset($_SESSION['assets'])) {
    $_SESSION['assets'] = [
        ['id' => 1, 'name' => 'Industrial Pump A1', 'category' => 'Machinery', 'status' => 'Active', 'value' => 15000],
        ['id' => 2, 'name' => 'Conveyor Belt B2', 'category' => 'Equipment', 'status' => 'Active', 'value' => 8000],
        ['id' => 3, 'name' => 'Forklift C1', 'category' => 'Vehicles', 'status' => 'Maintenance', 'value' => 25000]
    ];
}

if (!isset($_SESSION['work_orders'])) {
    $_SESSION['work_orders'] = [
        ['id' => 1, 'title' => 'Fix Pump A1', 'asset_id' => 1, 'status' => 'Pending', 'priority' => 'High'],
        ['id' => 2, 'title' => 'Inspect Conveyor B2', 'asset_id' => 2, 'status' => 'In Progress', 'priority' => 'Medium']
    ];
}

// Handle API requests
$action = $_GET['action'] ?? '';
$response = ['success' => false, 'data' => null, 'message' => ''];

switch ($action) {
    case 'get_assets':
        $response['success'] = true;
        $response['data'] = $_SESSION['assets'];
        break;
        
    case 'add_asset':
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $value = $_POST['value'] ?? 0;
        
        if ($name && $category) {
            $newAsset = [
                'id' => max(array_column($_SESSION['assets'], 'id')) + 1,
                'name' => $name,
                'category' => $category,
                'status' => 'Active',
                'value' => floatval($value)
            ];
            $_SESSION['assets'][] = $newAsset;
            $response['success'] = true;
            $response['data'] = $newAsset;
            $response['message'] = 'Asset added successfully!';
        } else {
            $response['message'] = 'Name and category are required';
        }
        break;
        
    case 'update_asset':
        $id = intval($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if ($id && ($name || $category || $status)) {
            foreach ($_SESSION['assets'] as &$asset) {
                if ($asset['id'] == $id) {
                    if ($name) $asset['name'] = $name;
                    if ($category) $asset['category'] = $category;
                    if ($status) $asset['status'] = $status;
                    $response['success'] = true;
                    $response['data'] = $asset;
                    $response['message'] = 'Asset updated successfully!';
                    break;
                }
            }
        } else {
            $response['message'] = 'Asset ID and at least one field to update are required';
        }
        break;
        
    case 'delete_asset':
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $_SESSION['assets'] = array_filter($_SESSION['assets'], fn($asset) => $asset['id'] != $id);
            $response['success'] = true;
            $response['message'] = 'Asset deleted successfully!';
        } else {
            $response['message'] = 'Asset ID is required';
        }
        break;
        
    case 'get_work_orders':
        $response['success'] = true;
        $response['data'] = $_SESSION['work_orders'];
        break;
        
    case 'add_work_order':
        $title = $_POST['title'] ?? '';
        $asset_id = intval($_POST['asset_id'] ?? 0);
        $priority = $_POST['priority'] ?? 'Medium';
        
        if ($title && $asset_id) {
            $newWorkOrder = [
                'id' => max(array_column($_SESSION['work_orders'], 'id')) + 1,
                'title' => $title,
                'asset_id' => $asset_id,
                'status' => 'Pending',
                'priority' => $priority,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $_SESSION['work_orders'][] = $newWorkOrder;
            $response['success'] = true;
            $response['data'] = $newWorkOrder;
            $response['message'] = 'Work order created successfully!';
        } else {
            $response['message'] = 'Title and asset ID are required';
        }
        break;
        
    case 'update_work_order':
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($id && $status) {
            foreach ($_SESSION['work_orders'] as &$workOrder) {
                if ($workOrder['id'] == $id) {
                    $workOrder['status'] = $status;
                    $response['success'] = true;
                    $response['data'] = $workOrder;
                    $response['message'] = 'Work order updated successfully!';
                    break;
                }
            }
        } else {
            $response['message'] = 'Work order ID and status are required';
        }
        break;
        
    case 'get_stats':
        $totalAssets = count($_SESSION['assets']);
        $activeAssets = count(array_filter($_SESSION['assets'], fn($a) => $a['status'] === 'Active'));
        $totalValue = array_sum(array_column($_SESSION['assets'], 'value'));
        $pendingWorkOrders = count(array_filter($_SESSION['work_orders'], fn($w) => $w['status'] === 'Pending'));
        
        $response['success'] = true;
        $response['data'] = [
            'total_assets' => $totalAssets,
            'active_assets' => $activeAssets,
            'total_value' => $totalValue,
            'pending_work_orders' => $pendingWorkOrders,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        break;
        
    default:
        $response['message'] = 'Unknown action';
}

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
?>
