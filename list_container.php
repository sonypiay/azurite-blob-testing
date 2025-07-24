<?php

/**
 * Script PHP untuk melihat daftar container di Azurite menggunakan REST API.
 *
 * Pastikan Azurite berjalan dan dapat diakses sebelum menjalankan script ini.
 * Default port untuk Azurite Blob adalah 10000.
 */

// Konfigurasi Azurite
$azuriteBaseUrl = "http://127.0.0.1:10000"; // Ganti jika Azurite berjalan di host atau port lain
$storageAccountName = "devstoreaccount1"; // Akun penyimpanan default untuk Azurite
$storageAccountKey = "Eby8vdM02xNOcqFlqUwJPLhnRWlqvZhxnGEIBCquJnrF2Zyaq4RNYFefjaZjpVyFVwTRk/YBqKI/FOGtgLdWGQ=="; // Kunci penyimpanan default untuk Azurite

// Header yang diperlukan untuk otentikasi Shared Key Lite
date_default_timezone_set('UTC');
$currentDate = gmdate('D, d M Y H:i:s T');

// String to sign untuk Shared Key Lite
// Format: VERB\nContent-Encoding\nContent-Language\nContent-Length\nContent-MD5\nContent-Type\nDate\nIf-Modified-Since\nIf-Match\nIf-None-Match\nIf-Unmodified-Since\nRange\nCanonicalizedHeaders\nCanonicalizedResource
// Untuk melihat daftar container (List Containers), permintaannya adalah GET pada akun penyimpanan dengan parameter ?comp=list.
$stringToSign = "GET\n" .     // HTTP Verb
                "\n" .     // Content-Encoding
                "\n" .     // Content-Language
                "\n" .     // Content-Length
                "\n" .     // Content-MD5
                "\n" .     // Content-Type
                $currentDate . "\n" . // Date
                "\n" .     // If-Modified-Since
                "\n" .     // If-Match
                "\n" .     // If-None-Match
                "\n" .     // If-Unmodified-Since
                "\n" .     // Range
                "x-ms-date:" . $currentDate . "\n" . // CanonicalizedHeaders
                "x-ms-version:2020-08-04\n" . // CanonicalizedHeaders
                "/" . $storageAccountName . "/\ncomp:list"; // CanonicalizedResource (penting: perhatikan parameter comp=list)

// Hashing the string to sign dengan HMAC-SHA256
$signature = base64_encode(
    hash_hmac(
        'sha256',
        $stringToSign,
        base64_decode($storageAccountKey),
        true
    )
);

// Header otentikasi
$authorizationHeader = "SharedKeyLite " . $storageAccountName . ":" . $signature;

// URL untuk melihat daftar container
$requestUrl = $azuriteBaseUrl . "/" . $storageAccountName . "/?comp=list";

// Inisialisasi cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $requestUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); // Menggunakan metode GET untuk melihat daftar
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Mengembalikan transfer sebagai string
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "x-ms-date: " . $currentDate,
    "x-ms-version: 2020-08-04", // Versi API Azure Storage yang didukung Azurite
    "Authorization: " . $authorizationHeader
));

// Eksekusi permintaan cURL
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Menangani kesalahan cURL
if (curl_errno($ch)) {
    echo 'Error cURL: ' . curl_error($ch);
} else {
    echo "Respons HTTP Code: " . $httpCode . "\n";
    echo "Respons XML:\n" . $response . "\n\n";

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "Daftar container berhasil diambil.\n";
        // Parse XML respons
        $xml = simplexml_load_string($response);

        if ($xml === false) {
            echo "Gagal mem-parse XML respons.\n";
        } else {
            if (isset($xml->Containers) && count($xml->Containers->Container) > 0) {
                echo "Container yang ditemukan:\n";
                foreach ($xml->Containers->Container as $container) {
                    echo "- " . $container->Name . "\n";
                }
            } else {
                echo "Tidak ada container yang ditemukan.\n";
            }
        }
    } else {
        echo "Gagal mengambil daftar container. Periksa pesan error di atas.\n";
    }
}

// Menutup cURL
curl_close($ch);

?>