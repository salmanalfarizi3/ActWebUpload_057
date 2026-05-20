<?php
$upload_dir = "uploads/";

// --- Aksi Unduh ---
if (isset($_GET['unduh'])) {
    $namaFile = basename($_GET['unduh']);
    $filePath = $upload_dir . $namaFile;

    if (file_exists($filePath) && strpos(realpath($filePath), realpath($upload_dir)) === 0) {
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $namaFile . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        ob_clean();
        flush();
        readfile($filePath);
        exit;
    } else {
        header("Location: lihatini.php?pesan=unduh_gagal");
        exit;
    }
}

// --- Aksi Hapus ---
if (isset($_GET['hapus'])) {
    $namaFile = basename($_GET['hapus']); // basename() cegah path traversal
    $filePath = $upload_dir . $namaFile;

    // Hanya hapus jika file benar-benar ada di folder uploads/
    if (file_exists($filePath) && strpos(realpath($filePath), realpath($upload_dir)) === 0) {
        unlink($filePath);
        header("Location: lihatini.php?pesan=hapus_ok&nama=" . urlencode($namaFile));
        exit;
    } else {
        header("Location: lihatini.php?pesan=hapus_gagal");
        exit;
    }
}

// --- Ambil semua file di folder uploads/ ---
$files = [];
if (is_dir($upload_dir)) {
    $items = scandir($upload_dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $upload_dir . $item;
        if (is_file($path)) {
            $files[] = [
                'name'    => $item,
                'path'    => $path,
                'size'    => filesize($path),
                'time'    => filemtime($path),
                'ext'     => strtolower(pathinfo($item, PATHINFO_EXTENSION)),
                'is_img'  => in_array(strtolower(pathinfo($item, PATHINFO_EXTENSION)),
                             ['jpg','jpeg','png','gif','webp','bmp']),
            ];
        }
    }
    // Urutkan: paling baru di atas
    usort($files, fn($a, $b) => $b['time'] - $a['time']);
}

// --- Format ukuran file ---
function formatSize($bytes) {
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Riwayat File Diunggah</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            padding: 2rem 1rem;
            color: #222;
        }

        .container { max-width: 1000px; margin: 0 auto; }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .header h1 { font-size: 1.6rem; font-weight: 700; }
        .header h1 span { color: #4f7df3; }

        .btn-upload {
            background: #4f7df3;
            color: #fff;
            text-decoration: none;
            padding: .55rem 1.2rem;
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 600;
            transition: background .2s;
        }
        .btn-upload:hover { background: #3a66d8; }

        /* Notifikasi */
        .notif {
            padding: .75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: .9rem;
            font-weight: 500;
        }
        .notif.ok  { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .notif.err { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Statistik ringkas */
        .stats {
            background: #fff;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .stat-item .label { font-size: .78rem; color: #888; text-transform: uppercase; letter-spacing: .05em; }
        .stat-item .val   { font-size: 1.4rem; font-weight: 700; color: #4f7df3; }

        /* Grid galeri */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.25rem;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            transition: transform .2s, box-shadow .2s;
            display: flex;
            flex-direction: column;
        }
        .card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }

        .card-thumb {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            display: block;
            background: #f5f5f5;
            cursor: pointer;
        }

        /* Placeholder untuk file non-gambar */
        .card-icon {
            width: 100%;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f0fe, #d2e3fc);
            gap: .5rem;
        }
        .card-icon .ext {
            font-size: 1.5rem;
            font-weight: 800;
            color: #4f7df3;
            text-transform: uppercase;
        }
        .card-icon .ico { font-size: 2.5rem; }

        .card-body {
            padding: .75rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }

        .card-name {
            font-size: .82rem;
            font-weight: 600;
            word-break: break-all;
            color: #333;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            font-size: .75rem;
            color: #888;
        }

        .card-actions {
            display: flex;
            gap: .4rem;
            margin-top: auto;
            flex-wrap: wrap;
        }

        .btn-lihat, .btn-unduh, .btn-hapus {
            flex: 1;
            min-width: 0;
            padding: .4rem .2rem;
            border: none;
            border-radius: 6px;
            font-size: .75rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .2rem;
            transition: background .2s, transform .1s;
            white-space: nowrap;
        }
        .btn-lihat, .btn-unduh, .btn-hapus { line-height: 1.3; }
        .btn-lihat:hover, .btn-unduh:hover, .btn-hapus:hover { transform: translateY(-1px); }

        .btn-lihat  { background: #e8f0fe; color: #4f7df3; }
        .btn-lihat:hover  { background: #d2e3fc; }
        .btn-unduh  { background: #e6f9f0; color: #1a7a4a; }
        .btn-unduh:hover  { background: #c8f0dc; }
        .btn-hapus  { background: #fde8e8; color: #d93025; }
        .btn-hapus:hover  { background: #f5c6c6; }

        /* Kosong */
        .empty {
            text-align: center;
            padding: 4rem 1rem;
            color: #aaa;
        }
        .empty .ico { font-size: 3.5rem; }
        .empty p { margin-top: .75rem; font-size: 1rem; }

        /* Modal preview */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.8);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.aktif { display: flex; }

        .modal-box {
            background: #fff;
            border-radius: 14px;
            max-width: 700px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0,0,0,.4);
            position: relative;
        }

        .modal-img {
            width: 100%;
            max-height: 70vh;
            object-fit: contain;
            display: block;
            background: #111;
        }

        .modal-info {
            padding: .85rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .modal-info .nama   { font-size: .9rem; font-weight: 600; }
        .modal-info .ukuran { font-size: .82rem; color: #888; }

        .modal-btns { display: flex; gap: .5rem; flex-shrink: 0; }

        .modal-close {
            position: absolute;
            top: .75rem; right: .75rem;
            background: rgba(0,0,0,.5);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 32px; height: 32px;
            font-size: 1rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .modal-close:hover { background: rgba(0,0,0,.8); }

        /* Konfirmasi hapus */
        .konfirm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .konfirm-overlay.aktif { display: flex; }

        .konfirm-box {
            background: #fff;
            border-radius: 14px;
            padding: 2rem;
            max-width: 360px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,.3);
        }
        .konfirm-box .ico { font-size: 2.5rem; }
        .konfirm-box h3 { margin: .75rem 0 .5rem; font-size: 1.1rem; }
        .konfirm-box p { font-size: .85rem; color: #666; word-break: break-all; }
        .konfirm-actions { display: flex; gap: .75rem; margin-top: 1.25rem; }
        .konfirm-actions a, .konfirm-actions button {
            flex: 1;
            padding: .6rem;
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            text-align: center;
        }
        .btn-batal { background: #f0f2f5; color: #333; }
        .btn-batal:hover { background: #e0e2e5; }
        .btn-konfirm-hapus { background: #d93025; color: #fff; }
        .btn-konfirm-hapus:hover { background: #b32318; }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h1>Riwayat <span>File Diunggah</span></h1>
        <a href="index.html" class="btn-upload">+ Unggah File Baru</a>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] === 'hapus_ok'): ?>
            <div class="notif ok">✅ File "<?= htmlspecialchars($_GET['nama'] ?? '') ?>" berhasil dihapus.</div>
        <?php elseif ($_GET['pesan'] === 'hapus_gagal'): ?>
            <div class="notif err">❌ Gagal menghapus file. File tidak ditemukan.</div>
        <?php elseif ($_GET['pesan'] === 'unduh_gagal'): ?>
            <div class="notif err">❌ Gagal mengunduh file. File tidak ditemukan.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Statistik -->
    <?php
    $totalSize = array_sum(array_column($files, 'size'));
    $jumlahGambar = count(array_filter($files, fn($f) => $f['is_img']));
    ?>
    <div class="stats">
        <div class="stat-item">
            <div class="label">Total File</div>
            <div class="val"><?= count($files) ?></div>
        </div>
        <div class="stat-item">
            <div class="label">Total Ukuran</div>
            <div class="val"><?= formatSize($totalSize) ?></div>
        </div>
        <div class="stat-item">
            <div class="label">Gambar</div>
            <div class="val"><?= $jumlahGambar ?></div>
        </div>
        <div class="stat-item">
            <div class="label">File Lain</div>
            <div class="val"><?= count($files) - $jumlahGambar ?></div>
        </div>
    </div>

    <!-- Grid file -->
    <?php if (empty($files)): ?>
        <div class="empty">
            <div class="ico">📂</div>
            <p>Belum ada file yang diunggah.</p>
        </div>
    <?php else: ?>
    <div class="grid">
        <?php foreach ($files as $f): ?>
        <div class="card">
            <?php if ($f['is_img']): ?>
                <img
                    class="card-thumb"
                    src="<?= htmlspecialchars($f['path']) ?>"
                    alt="<?= htmlspecialchars($f['name']) ?>"
                    onclick="bukaModal('<?= htmlspecialchars($f['path'], ENT_QUOTES) ?>',
                                       '<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>',
                                       '<?= formatSize($f['size']) ?>')"
                />
            <?php else: ?>
                <div class="card-icon">
                    <span class="ico">📄</span>
                    <span class="ext">.<?= htmlspecialchars($f['ext']) ?></span>
                </div>
            <?php endif; ?>

            <div class="card-body">
                <div class="card-name"><?= htmlspecialchars($f['name']) ?></div>
                <div class="card-meta">
                    <span><?= formatSize($f['size']) ?></span>
                    <span><?= date('d/m/Y', $f['time']) ?></span>
                </div>
                <div class="card-actions">
                    <a href="<?= htmlspecialchars($f['path']) ?>" target="_blank" class="btn-lihat">👁 Lihat</a>
                    <a href="lihatini.php?unduh=<?= urlencode($f['name']) ?>" class="btn-unduh">⬇ Unduh</a>
                    <button
                        class="btn-hapus"
                        onclick="konfirmasiHapus('<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>')"
                    >🗑 Hapus</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Preview Gambar -->
<div class="modal-overlay" id="modalOverlay" onclick="tutupModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="tutupModal()">✕</button>
        <img class="modal-img" id="modalImg" src="" alt=""/>
        <div class="modal-info">
            <div>
                <div class="nama" id="modalNama"></div>
                <div class="ukuran" id="modalUkuran"></div>
            </div>
            <div class="modal-btns">
                <a id="modalUnduhDirect" href="#" target="_blank"
                   class="btn-lihat" style="text-decoration:none;padding:.42rem .85rem;">👁 Buka</a>
                <a id="modalUnduh" href="#"
                   class="btn-unduh" style="text-decoration:none;padding:.42rem .85rem;">⬇ Unduh</a>
            </div>
        </div>
    </div>
</div>

<!-- Dialog Konfirmasi Hapus -->
<div class="konfirm-overlay" id="konfirmOverlay">
    <div class="konfirm-box">
        <div class="ico">⚠️</div>
        <h3>Hapus File?</h3>
        <p id="konfirmNama"></p>
        <div class="konfirm-actions">
            <button class="btn-batal" onclick="tutupKonfirm()">Batal</button>
            <a id="konfirmLink" href="#" class="btn-konfirm-hapus">Ya, Hapus</a>
        </div>
    </div>
</div>

<script>
    function bukaModal(src, nama, ukuran) {
        document.getElementById('modalImg').src                    = src;
        document.getElementById('modalNama').textContent           = nama;
        document.getElementById('modalUkuran').textContent         = ukuran;
        document.getElementById('modalUnduhDirect').href           = src;
        document.getElementById('modalUnduh').href = 'lihatini.php?unduh=' + encodeURIComponent(nama);
        document.getElementById('modalOverlay').classList.add('aktif');
    }

    function tutupModal(e) {
        if (!e || e.target === document.getElementById('modalOverlay')) {
            document.getElementById('modalOverlay').classList.remove('aktif');
        }
    }

    function konfirmasiHapus(nama) {
        document.getElementById('konfirmNama').textContent = nama;
        document.getElementById('konfirmLink').href = 'lihatini.php?hapus=' + encodeURIComponent(nama);
        document.getElementById('konfirmOverlay').classList.add('aktif');
    }

    function tutupKonfirm() {
        document.getElementById('konfirmOverlay').classList.remove('aktif');
    }
</script>
</body>
</html>