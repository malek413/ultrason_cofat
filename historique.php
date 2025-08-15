<?php
session_start();
include 'nav.php';
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] !== true) {
    header('Location: login.php');
    exit;
}

include 'erreur.php'; // $errorCodes est maintenant inclus depuis un fichier

class GlobalPerMachine {
    private $data = [];
    private $machineModes = [];
    private $errorCodes;

    public function __construct($folder = 'Prot', $errorCodes) {
        $this->errorCodes = $errorCodes;
        $this->readLogs($folder);
    }

    private function readLogs($folder) {
        if (!is_dir($folder)) return;
        $files = glob($folder . '/*SPC_Server*');
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $currentUS = '';
            $currentSP = '';
            foreach ($lines as $line) {
                preg_match('/(\d{2}:\d{2}:\d{2})/', $line, $match);
                $time = $match[1] ?? '';
                if (preg_match('/(US\d+\s+welded splice)/', $line, $match)) $currentUS = trim($match[1]);
                if (preg_match('/US\d+\s+welded splice\s+([A-Z0-9\-]+)/', $line, $match)) $currentSP = trim($match[1]);

                if (stripos($line, 'Lock') !== false || stripos($line, 'SharedMemory') !== false) continue;
                preg_match_all('/-?\d+(?:\.\d+)?/', $line, $matches);
                $nums = $matches[0];

                if (count($nums) >= 9 && $currentUS && $currentSP) {
                    $isCorrect = false;
                    $errorNumber = null;
                    for ($i = 0; $i < count($nums) - 2; $i++) {
                        if ($nums[$i] == '0' && $nums[$i+1] == '05') {
                            $mode = intval($nums[$i+2]);
                            $this->machineModes[$currentUS] = $mode;
                            if ($mode == 1) $isCorrect = true;
                            break;
                        }
                        if (isset($this->errorCodes[intval($nums[$i])])) {
                            $errorNumber = intval($nums[$i]);
                        }
                    }

                    $this->init($currentUS, $currentSP);
                    if ($isCorrect) {
                        $this->data[$currentUS][$currentSP]['ok']++;
                    } else {
                        $this->data[$currentUS][$currentSP]['ko']++;
                        $this->data[$currentUS][$currentSP]['erreurs'][] = [
                            'time' => $time,
                            'line' => $line,
                            'error_code' => $errorNumber,
                            'error_message' => $errorNumber ? $this->errorCodes[$errorNumber] : "Erreur inconnue ($errorNumber)"
                        ];
                    }
                    $this->data[$currentUS][$currentSP]['points'][] = [
                        'time' => $time,
                        'ok' => $isCorrect ? 1 : 0,
                        'ko' => $isCorrect ? 0 : 1,
                        'error' => $errorNumber ? $this->errorCodes[$errorNumber] : null
                    ];
                }
            }
        }
    }

    private function init($us, $sp) {
        if (!isset($this->data[$us])) $this->data[$us] = [];
        if (!isset($this->data[$us][$sp])) {
            $this->data[$us][$sp] = ['ok' => 0, 'ko' => 0, 'erreurs' => [], 'points' => []];
        }
    }

    public function getData() { return $this->data; }
    public function getMachineModes() { return $this->machineModes; }
}

$dash = new GlobalPerMachine('Prot', $errorCodes);
$data = $dash->getData();
$modes = $dash->getMachineModes();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8">
    <title>Historique des Machines</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .machine-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
        }

        .machine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .machine-card h2 {
            font-size: 1.6rem;
            margin-bottom: 10px;
            color: #1e3a8a;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: bold;
            margin: 8px 10px 8px 0;
            color: white;
        }

        .ok { background: #16a34a; }
        .ko { background: #dc2626; }

        .link-btn {
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .error-list {
            margin-top: 10px;
            padding-left: 18px;
            color: #b91c1c;
        }

        .error-list li {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .no-error {
            color: #16a34a;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 style="grid-column: 1/-1; text-align:center; color:#0f172a;">🕒 Historique de production des machines</h1>

    <?php foreach ($data as $machine => $splices): 
        $okTotal = 0;
        $koTotal = 0;
        $lastTime = null;
        ?>
        <div class="machine-card">
            <h2><?= htmlspecialchars($machine) ?></h2>
            <?php foreach ($splices as $sp => $info): 
                $okTotal += $info['ok'];
                $koTotal += $info['ko'];
                foreach ($info['points'] as $pt) {
                    if ($lastTime === null || strtotime($pt['time']) > strtotime($lastTime)) {
                        $lastTime = $pt['time'];
                    }
                }
            endforeach; ?>
            <p>
                <span class="badge ok">✅: <?= $okTotal ?></span>
                <span class="badge ko">❌: <?= $koTotal ?></span> <br>
                <strong>Dernière prod :</strong> <?= $lastTime ?? 'N/A' ?>
                <a class="link-btn" href="mode.php?machine=<?= urlencode($machine) ?>">Détails ➜</a>
            </p>

            <ul class="error-list">
                <?php foreach ($splices as $sp => $info): ?>
                    <?php foreach ($info['erreurs'] as $err): ?>
                        <li>⛔ [<?= $err['error_code'] ?? 'N/A' ?>] <?= htmlspecialchars($err['error_message']) ?> (<?= $sp ?> à <?= $err['time'] ?>)</li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>

            <?php if (empty(array_merge(...array_column($splices, 'erreurs')))): ?>
                <p class="no-error">✅ Aucune erreur détectée.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
