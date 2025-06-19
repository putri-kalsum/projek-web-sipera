<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../login.php");
    exit();
}

require_once '../../../config/database.php';

$id_user = $_SESSION['user_id'];
$success = $error = "";

// Proses kirim konsultasi baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_dokter = intval($_POST['id_dokter']);
    $pesan = trim($_POST['pesan']);

    if ($id_dokter > 0 && $pesan !== '') {
        $stmt = $conn->prepare("INSERT INTO konsultasi (id_user, id_dokter, pesan, status, created_at) VALUES (?, ?, ?, 'baru', NOW())");
        $stmt->bind_param("iis", $id_user, $id_dokter, $pesan);

        if ($stmt->execute()) {
            $id_konsultasi = $conn->insert_id;
            $stmt_chat = $conn->prepare("INSERT INTO chat_konsultasi (id_konsultasi, pengirim, pesan, waktu_kirim) VALUES (?, 'penjual', ?, NOW())");
            $stmt_chat->bind_param("is", $id_konsultasi, $pesan);
            $stmt_chat->execute();
            $stmt_chat->close();

            $success = "Pesan konsultasi berhasil dikirim.";
        } else {
            $error = "Gagal mengirim pesan konsultasi: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Dokter dan pesan harus diisi.";
    }
}

// Ambil daftar dokter
$dokters = [];
$sql = "SELECT id, nama FROM users WHERE role = 'dokter'";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dokters[] = $row;
    }
}

// Ambil riwayat konsultasi
$riwayat = [];
$stmt = $conn->prepare("SELECT k.id, k.id_dokter, k.created_at, u.nama AS nama_dokter FROM konsultasi k JOIN users u ON k.id_dokter = u.id WHERE k.id_user = ? ORDER BY k.created_at DESC");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $id_konsultasi = $row['id'];
    $stmt_chat = $conn->prepare("SELECT pengirim, pesan, waktu_kirim FROM chat_konsultasi WHERE id_konsultasi = ? ORDER BY waktu_kirim ASC");
    $stmt_chat->bind_param("i", $id_konsultasi);
    $stmt_chat->execute();
    $result_chat = $stmt_chat->get_result();
    $chats = [];
    while ($chat = $result_chat->fetch_assoc()) {
        $chats[] = $chat;
    }
    $row['chats'] = $chats;
    $riwayat[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Form Konsultasi Penjual</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f9f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #264d00;
        }
        .navbar-sipera {
            background-color: #2ecc71;
        }
        .navbar-brand, .logout-link {
            color: white !important;
            font-weight: bold;
        }
        .container {
            max-width: 700px;
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 40px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #1ca127;
            margin-bottom: 25px;
            font-weight: 700;
        }
        label {
            font-weight: 600;
        }
        .form-control, select, textarea {
            border-radius: 8px;
            padding: 10px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }
        .btn-submit {
            background-color: #1ca127;
            color: white;
            padding: 10px 20px;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #145d00;
        }
        .chat-container {
            background: #f0f0f0;
            border-radius: 10px;
            padding: 15px;
        }
        .chat-message {
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
        }
        .chat-message.penjual .bubble {
            background-color: #d4edda;
            align-self: flex-end;
        }
        .chat-message.dokter .bubble {
            background-color: #d1ecf1;
            align-self: flex-start;
        }
        .bubble {
            padding: 10px 14px;
            border-radius: 10px;
            max-width: 80%;
        }
        .timestamp {
            font-size: 0.8em;
            color: gray;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-sipera px-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">SIPERA</a>
        <div class="ms-auto">
            <a class="nav-link logout-link" href="/sipera/logout.php">Logout</a>
        </div>
    </div>
</nav>

<!-- Konten -->
<div class="container">
    <h2>🩺 Forum Konsultasi ke Dokter Hewan</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Form Konsultasi -->
    <form method="POST" action="">
        <label for="id_dokter">Pilih Dokter:</label>
        <select name="id_dokter" id="id_dokter" class="form-select" required>
            <option value="">-- Pilih Dokter --</option>
            <?php foreach ($dokters as $dokter): ?>
                <option value="<?= htmlspecialchars($dokter['id']) ?>">
                    <?= htmlspecialchars($dokter['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="pesan">Pesan Konsultasi:</label>
        <textarea name="pesan" id="pesan" class="form-control" rows="5" placeholder="Tulis keluhan atau pertanyaan Anda..." required></textarea>

        <button type="submit" class="btn-submit">Kirim Konsultasi</button>
        <a href="dashboard.php" class="btn btn-secondary ms-2">Kembali</a>
    </form>

    <hr>

    <!-- Riwayat Chat -->
    <h4>📥 Obrolan Balasan dari Dokter:</h4>
    <?php if (empty($riwayat)): ?>
        <p class="text-muted">Belum ada konsultasi atau belum ada balasan dari dokter.</p>
    <?php else: ?>
        <?php foreach ($riwayat as $r): ?>
            <?php if (count($r['chats']) > 1): ?>
                <div class="mb-4">
                    <strong>👨‍⚕️ Dokter: <?= htmlspecialchars($r['nama_dokter']) ?></strong><br>
                    <small><em>Dibuat: <?= date('d M Y H:i', strtotime($r['created_at'])) ?></em></small>
                    <div class="chat-container mt-2">
                        <?php foreach ($r['chats'] as $c): ?>
                            <div class="chat-message <?= $c['pengirim'] ?>">
                                <div class="bubble"><?= nl2br(htmlspecialchars($c['pesan'])) ?></div>
                                <div class="timestamp"><?= date('d M H:i', strtotime($c['waktu_kirim'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
