<?php
require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama     = $_POST["nama"];
    $email    = $_POST["email"];
    $no_hp    = $_POST["no_hp"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role     = $_POST["role"];
    $alamat   = $_POST["alamat"];

    // Upload dokumen
    $dokumen_name = $_FILES["dokumen"]["name"];
    $dokumen_tmp = $_FILES["dokumen"]["tmp_name"];
    $ext = strtolower(pathinfo($dokumen_name, PATHINFO_EXTENSION));
    $file_name = uniqid() . "." . $ext;
    $upload_path = "public/uploads/" . $file_name;

    move_uploaded_file($dokumen_tmp, $upload_path);

    $stmt = $conn->prepare("INSERT INTO users (nama, email, no_hp, password, role, alamat, foto_ktp) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nama, $email, $no_hp, $password, $role, $alamat, $upload_path);

    if ($stmt->execute()) {
        session_start();
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['role'] = $role;
        header("Location: app/views/$role/dashboard.php");
        exit();
    } else {
        echo "<div style='color:red;text-align:center;'>Gagal mendaftar: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrasi Akun - SIPERA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f4f4;
        }
        .box {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        .header-box {
            background: green;
            color: #fff;
            text-align: center;
            padding: 10px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            width: 100%;
        }
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="box shadow">
    <div class="header-box">SIPERA</div>
    <h4 class="text-center mb-4">Daftar Akun</h4>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-2">
            <label>Nama lengkap</label>
            <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>No. Telepon</label>
            <input type="text" name="no_hp" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Password (minimal 8 karakter)</label>
            <input type="password" name="password" class="form-control" minlength="8" required>
        </div>
        <div class="mb-2">
            <label>Alamat Lengkap</label>
            <textarea name="alamat" class="form-control" required></textarea>
        </div>
        <div class="mb-2">
            <label>Daftar sebagai</label>
            <select name="role" class="form-control" required>
                <option value="">-- Pilih --</option>
                <option value="penjual">Penjual</option>
                <option value="pembeli">Pembeli</option>
            </select>
        </div>
        <div class="mb-2">
            <label>Upload Dokumen</label>
            <input type="file" name="dokumen" class="form-control" required>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" required>
            <label class="form-check-label">Saya menyetujui kebijakan privasi</label>
        </div>
        <button type="submit" class="btn btn-submit">Buat Akun</button>
        <div class="text-center mt-3">
            Sudah punya akun? <a href="login.php" style="color:green;">Login disini</a>
        </div>
    </form>
</div>
</body>
</html>
