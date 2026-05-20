<?php
$upload_dir  = "uploads/";
$max_size    = 500000000;//500 MB
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];


if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}


if (!isset($_FILES["fileToUpload"]) || $_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_OK) {
    header("Location: index.html?pesan=gagal&alasan=tidak_ada_file");
    exit;
}

$file     = $_FILES["fileToUpload"];
$ext      = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
$tmpPath  = $file["tmp_name"];


if (!in_array($ext, $allowed_ext)) {
    header("Location: index.html?pesan=gagal&alasan=ekstensi_tidak_diizinkan");
    exit;
}


$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);
if (!in_array($mimeType, $allowed_mime)) {
    header("Location: index.html?pesan=gagal&alasan=bukan_gambar");
    exit;
}


if (!getimagesize($tmpPath)) {
    header("Location: index.html?pesan=gagal&alasan=bukan_gambar");
    exit;
}

// 4. Validasi ukuran file
if ($file["size"] > $max_size) {
    header("Location: index.html?pesan=gagal&alasan=ukuran_terlalu_besar");
    exit;
}

// Buat nama file aman (hindari karakter berbahaya)
$safeName   = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($file["name"]));
$targetFile = $upload_dir . $safeName;

// Tangani duplikat nama
if (file_exists($targetFile)) {
    $targetFile = $upload_dir . time() . '_' . $safeName;
}

// Pindahkan file
if (move_uploaded_file($tmpPath, $targetFile)) {
    header("Location: lihatini.php?pesan=upload_ok&nama=" . urlencode(basename($targetFile)));
    exit;
} else {
    header("Location: index.html?pesan=gagal&alasan=gagal_simpan");
    exit;
}
?>