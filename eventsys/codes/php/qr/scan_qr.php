<?php
/**
 * QR Code Scanner Page
 * For event heads to scan attendee QR codes for check-in/check-out
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

include('../../includes/db.php');
require_once('../../includes/qr_function.php');

$user_id = $_SESSION['user_id'];

// Check role
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'event_head' && $role !== 'admin') {
    die("Access denied. Only event heads can scan QR codes.");
}

// Get user's events
$email_stmt = $conn->prepare("SELECT email FROM user WHERE user_id = ?");
$email_stmt->bind_param("i", $user_id);
$email_stmt->execute();
$email_stmt->bind_result($email);
$email_stmt->fetch();
$email_stmt->close();

$events_query = "
    SELECT e.event_id, e.title, e.start_time
    FROM event e
    JOIN organizer o ON e.organizer_id = o.organizer_id
    WHERE o.contact_email = ?
    ORDER BY e.start_time DESC
";
$stmt = $conn->prepare($events_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$events = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner - Eventix</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/qr_scanner.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        .scanner-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .scanner-box {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        #qr-reader {
            border: 3px solid #e63946;
            border-radius: 12px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .scanner-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .scanner-controls select,
        .scanner-controls button {
            flex: 1;
            min-width: 200px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
        }
        
        .result-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .result-success {
            border-left: 5px solid #10b981;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }
        
        .result-error {
            border-left: 5px solid #ef4444;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        }
        
        .result-warning {
            border-left: 5px solid #f59e0b;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }
        
        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #6b6b6b;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 1rem;
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .scan-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #e63946;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6b6b6b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .scanner-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f0f0f0;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-active {
            background: #10b981;
        }
        
        .status-inactive {
            background: #ef4444;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>
<body class="dashboard-layout">
    <?php include('../components/sidebar.php'); ?>
    
    <main class="main-content">
        <header class="banner">
            <div>
                <h1>QR Code Scanner</h1>
                <p>Scan attendee QR codes for quick check-in/check-out</p>
            </div>
            <img src="../../assets/eventix-logo.png" alt="Eventix logo" />
        </header>
        
        <div class="scanner-container">
            <div class="scanner-box">
                <h2 style="margin-bottom: 20px;">
                    <i data-lucide="scan" style="width: 24px; height: 24px; vertical-align: middle;"></i>
                    Scanner Controls
                </h2>
                
                <div class="scanner-controls">
                    <select id="eventSelect">
                        <option value="">Select Event</option>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <option value="<?= $event['event_id'] ?>">
                                <?= htmlspecialchars($event['title']) ?> - 
                                <?= date('M j, Y', strtotime($event['start_time'])) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <select id="actionSelect">
                        <option value="checkin">Check In</option>
                        <option value="checkout">Check Out</option>
                    </select>
                    
                    <button onclick="startScanner()" id="startBtn" class="btn btn-primary">
                        <i data-lucide="camera"></i>
                        Start Scanner
                    </button>
                    
                    <button onclick="stopScanner()" id="stopBtn" class="btn btn-secondary" style="display: none;">
                        <i data-lucide="square"></i>
                        Stop Scanner
                    </button>
                </div>
                
                <div class="scanner-status" id="scannerStatus" style="display: none;">
                    <div class="status-indicator status-inactive" id="statusIndicator"></div>
                    <span id="statusText">Scanner Inactive</span>
                </div>
                
                <div id="qr-reader" style="display: none;"></div>
                
                <div class="scan-stats">
                    <div class="stat-box">
                        <div class="stat-number" id="totalScans">0</div>
                        <div class="stat-label">Total Scans</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" id="successScans">0</div>
                        <div class="stat-label">Successful</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" id="errorScans">0</div>
                        <div class="stat-label">Errors</div>
                    </div>
                </div>
            </div>
            
            <div id="resultContainer"></div>
        </div>
    </main>
    
    <script>
        lucide.createIcons();
        
        let html5QrCode;
        let isScanning = false;
        let scanStats = {
            total: 0,
            success: 0,
            errors: 0
        };
        
        function startScanner() {
            const eventId = document.getElementById('eventSelect').value;
            if (!eventId) {
                alert('Please select an event first!');
                return;
            }
            
            document.getElementById('qr-reader').style.display = 'block';
            document.getElementById('scannerStatus').style.display = 'flex';
            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('stopBtn').style.display = 'inline-flex';
            
            html5QrCode = new Html5Qrcode("qr-reader");
            
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                onScanSuccess,
                onScanError
            ).then(() => {
                isScanning = true;
                updateStatus(true, 'Scanner Active - Ready to scan');
            }).catch(err => {
                console.error('Failed to start scanner:', err);
                alert('Failed to start camera. Please check permissions.');
                stopScanner();
            });
        }
        
        function stopScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    document.getElementById('qr-reader').style.display = 'none';
                    document.getElementById('scannerStatus').style.display = 'none';
                    document.getElementById('startBtn').style.display = 'inline-flex';
                    document.getElementById('stopBtn').style.display = 'none';
                    updateStatus(false, 'Scanner Inactive');
                }).catch(err => {
                    console.error('Failed to stop scanner:', err);
                });
            }
        }
        
        function updateStatus(active, text) {
            const indicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');
            
            if (active) {
                indicator.className = 'status-indicator status-active';
            } else {
                indicator.className = 'status-indicator status-inactive';
            }
            
            statusText.textContent = text;
        }
        
        function updateStats() {
            document.getElementById('totalScans').textContent = scanStats.total;
            document.getElementById('successScans').textContent = scanStats.success;
            document.getElementById('errorScans').textContent = scanStats.errors;
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            const action = document.getElementById('actionSelect').value;
            
            scanStats.total++;
            updateStats();
            updateStatus(true, 'Processing scan...');
            
            // Send to backend for processing
            fetch('process_qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    qr_data: decodedText,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    scanStats.success++;
                    displayResult(data, 'success');
                } else {
                    scanStats.errors++;
                    displayResult(data, 'error');
                }
                updateStats();
                updateStatus(true, 'Scanner Active - Ready to scan');
            })
            .catch(error => {
                scanStats.errors++;
                updateStats();
                displayResult({
                    success: false,
                    message: 'Network error: ' + error.message
                }, 'error');
                updateStatus(true, 'Scanner Active - Ready to scan');
            });
            
            // Pause scanning briefly
            html5QrCode.pause();
            setTimeout(() => {
                if (isScanning) {
                    html5QrCode.resume();
                }
            }, 2000);
        }
        
        function onScanError(errorMessage) {
            // Silent - normal scanning errors
        }
        
        function displayResult(data, type) {
            const container = document.getElementById('resultContainer');
            const resultCard = document.createElement('div');
            
            let className = 'result-card ';
            if (type === 'success') {
                className += 'result-success';
            } else if (data.already_checked_in || data.already_checked_out) {
                className += 'result-warning';
            } else {
                className += 'result-error';
            }
            
            resultCard.className = className;
            
            let content = `
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                    <i data-lucide="${type === 'success' ? 'check-circle' : 'alert-circle'}" 
                       style="width: 32px; height: 32px; color: ${type === 'success' ? '#10b981' : '#ef4444'};"></i>
                    <div>
                        <h3 style="margin: 0; font-size: 1.2rem;">${data.message}</h3>
                        <small style="color: #6b6b6b;">${new Date().toLocaleTimeString()}</small>
                    </div>
                </div>
            `;
            
            if (data.registration) {
                const reg = data.registration;
                const fullName = `${reg.first_name} ${reg.middle_name || ''} ${reg.last_name}`.trim();
                
                content += `
                    <div class="user-info">
                        <div class="info-item">
                            <span class="info-label">Attendee Name</span>
                            <span class="info-value">${fullName}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">${reg.email}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Event</span>
                            <span class="info-value">${reg.event_title}</span>
                        </div>
                `;
                
                if (data.check_in_time) {
                    content += `
                        <div class="info-item">
                            <span class="info-label">Check-In Time</span>
                            <span class="info-value">${new Date(data.check_in_time).toLocaleString()}</span>
                        </div>
                    `;
                }
                
                if (data.check_out_time) {
                    content += `
                        <div class="info-item">
                            <span class="info-label">Check-Out Time</span>
                            <span class="info-value">${new Date(data.check_out_time).toLocaleString()}</span>
                        </div>
                    `;
                }
                
                content += `</div>`;
            }
            
            resultCard.innerHTML = content;
            
            // Add to top of results
            container.insertBefore(resultCard, container.firstChild);
            
            // Reinitialize icons
            lucide.createIcons();
            
            // Remove old results (keep last 5)
            while (container.children.length > 5) {
                container.removeChild(container.lastChild);
            }
        }
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (isScanning) {
                stopScanner();
            }
        });
    </script>
</body>
</html>