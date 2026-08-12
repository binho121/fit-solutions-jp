<?php
// Handler da lista de espera do Influ (influenciadores + empresas).
// Honeypot + time-trap contra spam, mesmo padrão usado nos outros sites do cofre.
declare(strict_types=1);

$to = 'fabiano@nogaih.com';
$referer = $_SERVER['HTTP_REFERER'] ?? '/influ/';

function back(string $referer, string $status): void {
    $sep = strpos($referer, '?') === false ? '?' : '&';
    header('Location: ' . $referer . $sep . 'sent=' . $status);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back($referer, '0');
}

// honeypot
if (!empty($_POST['website'])) {
    back($referer, '1'); // finge sucesso pro bot, não envia nada
}

// time-trap: ts=0 (bot que não roda JS) ou envio em menos de 3s = bloqueia
$ts = isset($_POST['ts']) ? (int) $_POST['ts'] : 0;
if ($ts <= 0 || (microtime(true) * 1000 - $ts) < 3000) {
    back($referer, '0');
}

function clean(string $v): string {
    return trim(str_replace(["\r", "\n"], ' ', $v));
}

$origem  = clean($_POST['origem'] ?? '');
$name    = clean($_POST['name'] ?? '');
$email   = clean($_POST['email'] ?? '');

if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back($referer, '0');
}

if ($origem === 'influenciador') {
    $handle = clean($_POST['handle'] ?? '');
    $city   = clean($_POST['city'] ?? '');
    if ($handle === '') {
        back($referer, '0');
    }
    $subject = 'Influ — novo influenciador na lista de espera';
    $body = "Novo cadastro na lista de espera (INFLUENCIADOR)\n\n"
        . "Nome: {$name}\n"
        . "E-mail: {$email}\n"
        . "Instagram/TikTok: {$handle}\n"
        . "Cidade no Japão: {$city}\n";
} elseif ($origem === 'empresa') {
    $company = clean($_POST['company'] ?? '');
    $segment = clean($_POST['segment'] ?? '');
    if ($company === '') {
        back($referer, '0');
    }
    $subject = 'Influ — nova empresa na lista de espera';
    $body = "Novo cadastro na lista de espera (EMPRESA)\n\n"
        . "Nome: {$name}\n"
        . "E-mail: {$email}\n"
        . "Empresa: {$company}\n"
        . "Segmento: {$segment}\n";
} else {
    back($referer, '0');
}

$headers = "MIME-Version: 1.0\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "From: no-reply@fit-solutions.jp\r\n"
    . "Reply-To: {$email}\r\n";

$ok = mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

back($referer, $ok ? '1' : '0');
