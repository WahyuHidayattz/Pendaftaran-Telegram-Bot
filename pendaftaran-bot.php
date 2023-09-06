<?php


/**
 * Nama Program         : Pendaftaran BOT Telegram
 * Deskripsi            : Bot untuk melakukan pendaftaran USER agar bisa menggunakan Fungsi dari BOT ini
 * Bahasa Pemerograman  : PHP (Native)
 * Versi                : 1.0
 * Tanggal Dibuat       : 2023-08-10
 * Database             : Ya (MYSQL Database / MARIADB)
 * Pengembang           : Wahyu Hidayat
 * Github               : https://github.com/wahyuhidayattz 
 */

/**
 * Membuat Database Telegarm
 * Database    : telegram
 * Table       : telegram_id
 */


//--------------------------- SETTING VARIABLE --------------------------//
$bot_token          = "6xxxxxxxx:Aaxxxxxxxxxxxxxxxxxxxxxxxxk";
$bot_username       = "@waris_query_bot";
$koneksi            = mysqli_connect("localhost", "root", "");

$owner_telegram_id  = "8xxxxxxx6";
$owner_telegram_name = "WAHYU HIDAYAT";
$tanggal_insert     = date("Y-m-d");

mysqli_query($koneksi, "insert ignore into telegram.telegram_id (telegram_id, telegram_name, tanggal_input) values ('$owner_telegram_id','$owner_telegram_name','$tanggal_insert')");

$state              = [];

function apiRequest($method, $parameters)
{
    $url        = "https://api.telegram.org/bot" . $GLOBALS['bot_token'] . "/" . $method;
    $handle     = curl_init($url);

    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $parameters);

    $response   = curl_exec($handle);

    if ($response === false) {
        curl_close($handle);
        return false;
    } else {
        $response = json_decode($response, true);
        curl_close($handle);
        return $response;
    }
}

function sendTyping($chat_id, $action = 'typing')
{
    $parameters = [
        'chat_id' => $chat_id,
        'action' => $action,
    ];
    return apiRequest('sendChatAction', $parameters);
}

function sendMessage($chat_id, $text)
{
    sendTyping($chat_id);
    apiRequest('sendMessage', 'typing');
    $parameters = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    return apiRequest('sendMessage', $parameters);
}

function sendDocument($chat_id, $file_path)
{
    sendTyping($chat_id, 'upload_document');

    $data["chat_id"]        = $chat_id;
    $data["document"]       = $file_path;

    $url        = "https://api.telegram.org/bot" . $GLOBALS['bot_token'] . "/sendDocument";
    $cfile      = new CURLFile($file_path);
    $ch         = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $post       = array("chat_id" => $chat_id, "document" => $cfile);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $output     = curl_exec($ch);

    return $output;
}

function keyboardMarkup($chat_id, $keyboard)
{
    $param = [
        'keyboard' => $keyboard,
        'resize_keyboard' => true,
        'one_time_keyboard' => true
    ];
    $data = [
        'chat_id' => $chat_id,
        'text' => 'Klik Tombol dibawah : ',
        'reply_markup' => json_encode($param)
    ];
    return apiRequest('sendMessage', $data);
}

function hideKeyboardMarkup($chat_id)
{
    $param = ['remove_keyboard' => true];
    $data = [
        'chat_id' => $chat_id,
        'text' => 'Keyboard Disembunyikan. /start untuk mulai ulang.',
        'reply_markup' => json_encode($param)
    ];
    return apiRequest('sendMessage', $data);
}

function processMessage($message)
{
    global $koneksi, $owner_telegram_id, $state;

    $data_user      = mysqli_query($koneksi, "select * from telegram.telegram_id");
    $allowed_id     = [];

    while ($d = mysqli_fetch_assoc($data_user)) {
        $allowed_id[] = $d['telegram_id'];
    }

    $tanggal        = date("Y-m-d");
    $message_id     = $message['message_id'];
    $chat_id        = $message['chat']['id'];
    $chat_name      = $message['chat']['first_name'] ?? "";
    $chat_username  = $message['chat']['username'] ?? "";
    $reply_message  = $message['reply_to_message']['text'] ?? "";
    $text           = $message['text'];

    $default_keyboard = [
        ['MENU CLIENT 1', 'MENU CLIENT 2'],
        ['MENU CLIENT 3', 'MENU CLIENT 4'],
        ['MENU CLIENT 5', 'MENU CLIENT 6'],
        ['KELUAR']
    ];

    // Response Jika User Telegram Belum Terdaftar
    if (!in_array($chat_id, $allowed_id)) {
        switch ($text) {
            case '/start':
                sendMessage($chat_id, "Selamat Datang Di BOT Telegram Ini, Akun anda belum terdaftar.\nketik /daftar untuk melakukan pendaftaran.");
                break;
            case '/daftar':
                // informasi kepada user
                sendMessage($chat_id, "Anda melakukan pendaftaran di BOT ini.\nNama Telegram : $chat_name \nID Telegram : $chat_id\nPermintaan pendaftaran sudah kami kirim ke Owner Telegram, harap tunggu sampai akun telegram anda di aktifkan.");
                // kirim informasi pendaftaran kepada owner
                sendMessage($owner_telegram_id, "🔔 Notifikasi 🔔\nAda User yang ingin mendaftar di Bot Telegram ini\nBerikut rincian informasi user telegram :\nNama Telegram : $chat_name\nID Telegram : $chat_id\nTanggal Pengajuan : $tanggal\n\nHarap balas pesan ini dengan /terima untuk menerima, atau /tolak untuk menolak.");
                break;
        }
    }

    // Response Jika User Telegram Sudah Terdaftar
    if (in_array($chat_id, $allowed_id) == true) {
        if ($chat_id != $owner_telegram_id) {
            switch ($text) {
                case '/start':
                    keyboardMarkup($chat_id, $default_keyboard);
                    break;
            }
        }
        switch ($text) {
            case 'MENU CLIENT 1':
            case 'MENU CLIENT 2':
            case 'MENU CLIENT 3':
            case 'MENU CLIENT 4':
            case 'MENU CLIENT 5':
            case 'MENU CLIENT 6':
                sendMessage($chat_id, "Ini adalah Menu Client");
                break;
        }
    }


    // ADMIN / OWNER COMMAND
    if ($chat_id == $owner_telegram_id) {

        // Menambahkan Menu Admin (Khusus Admin)
        $keyboard_admin = [
            ['MANAJEMEN BOT']
        ];
        $keyboard_menu_admin = [
            ['LIST USER'],
            ['MENU UTAMA']
        ];
        array_splice($default_keyboard, count($default_keyboard) - 1, 0, $keyboard_admin);

        switch ($text) {
            case '/start':
            case 'MENU UTAMA':
                keyboardMarkup($chat_id, $default_keyboard);
                break;
            case 'MANAJEMEN BOT':
                keyboardMarkup($chat_id, $keyboard_menu_admin);
                break;
            case 'LIST USER':
                // Ambil Data User
                $data   = mysqli_query($koneksi, "select * from telegram.telegram_id where telegram_id not in('$owner_telegram_id')");
                if (mysqli_affected_rows($koneksi) > 0) {

                    $no = 0;
                    while ($d = mysqli_fetch_assoc($data)) {
                        $no++;
                        $data_id        = $d['telegram_id'];
                        $data_name      = $d['telegram_name'];
                        $data_tanggal   = $d['tanggal_input'];

                        // Kirim List User
                        sendMessage($chat_id, "( $no )\nID Telegram : " . $data_id . "\nNama Telegram : " . $data_name . "\nTanggal Daftar : " . $data_tanggal);
                    }
                    sendMessage($chat_id, "Balas Pesan /hapus untuk menghapus user telegram.");
                } else {
                    sendMessage($chat_id, "Belum ada User yang terdaftar di BOT ini.");
                }
                break;
        }

        // Response Jika Owner Membalas Telegram
        if ($reply_message != "") {
            // set Variabel
            preg_match("/Nama Telegram : (.*?)\n/", $reply_message, $get_nama);
            preg_match("/ID Telegram : (.*?)\n/", $reply_message, $get_id);

            $get_id         = $get_id[1];
            $get_nama       = $get_nama[1];
            $get_tanggal    = date("Y-m-d");

            switch ($text) {
                case '/terima':
                    // Insert ke Database
                    mysqli_query($koneksi, "insert ignore into telegram.telegram_id (telegram_id, telegram_name, tanggal_input) values ('$get_id','$get_nama','$get_tanggal')");

                    // Jawab Hasil Ke Owner
                    sendMessage($chat_id, "Sukses, User ini berhasil didaftarkan.");

                    // Jawab Hasil ke User
                    sendMessage($get_id, "🎉Selamat, akun kamu sudah terdaftar di bot ini, selanjutnya kamu sudah bisa menggunakan fitur dari telegram ini.");
                    break;

                case '/tolak':
                    // Jawab Hasil ke Owner
                    sendMessage($chat_id, "Akun telegram ini berhasil di tolak.");

                    // Jawab Hasil ke User
                    sendMessage($get_id, "😿 Mohon Maaf, Akun Telegram kamu ditolak oleh Owner. Tetap semangat jangan kecewa :)");
                    break;

                case '/hapus':
                    mysqli_query($koneksi, "delete from telegram.telegram_id where telegram_id='$get_id'");
                    // Jawab Hasil ke Owner
                    sendMessage($chat_id, "Akses User ini telah berhasil anda hapus");
                    break;
            }
        }
    }
}


function getUpdates()
{
    $update_id = 0;
    while (true) {
        $response = apiRequest(
            'getUpdates',
            [
                'offset' => $update_id + 1,
                'limit' => 1
            ]
        );
        if (isset($response['result'][0])) {
            $tanggal            = date("Y-m-d");
            $jam                = date("H:i:s");

            $update             = $response['result'][0];
            $update_id          = $update['update_id'];

            $chat_id            = $update['message']['from']['id'];
            $chat_name          = $update['message']['from']['first_name'];
            $chat_username      = $update['message']['from']['username'] ?? "no username";
            $chat_text          = $update['message']['text'];

            processMessage($update['message']);

            // Kirim Log
            echo "Request Dari " . $chat_name . " (@" . $chat_username . ") - Jam [" . $tanggal . " / " . $jam . "] - Berhasil.\n";
        }
        sleep(1);
    }
}

getUpdates();
