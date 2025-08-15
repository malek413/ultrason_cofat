<?php
session_start();
include 'nav.php';
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] !== true) {
    header('Location: login.php');
    exit;
}

class GlobalPerMachine {
    private $data = [];
    private $machineModes = [];

    public function __construct($folder = 'Prot') {
        $this->readLogs($folder);
    }

    private function readLogs($folder) {
        if (!is_dir($folder)) {
            echo "<p style='color:red;'>❌ Dossier non trouvé : $folder</p>";
            return;
        }
        $files = glob($folder . '/*SPC_Server*');
        if (empty($files)) {
            echo "<p style='color:red;'>❌ Aucun fichier SPC_Server trouvé dans '$folder'</p>";
            return;
        }
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $currentUS = '';
            $currentSP = '';
            foreach ($lines as $line) {
                preg_match('/(\d{2}:\d{2}:\d{2})/', $line, $match);
                $time = $match[1] ?? '';
                if (preg_match('/(US\d+\s+welded splice)/', $line, $match)) {
                    $currentUS = trim($match[1]);
                }
                if (preg_match('/US\d+\s+welded splice\s+([A-Z0-9\-]+)/', $line, $match)) {
                    $currentSP = trim($match[1]);
                }
                if (stripos($line, 'Lock') !== false || stripos($line, 'SharedMemory') !== false) {
                    continue;
                }
                preg_match_all('/-?\d+(?:\.\d+)?/', $line, $matches);
                $nums = $matches[0];
                if (count($nums) >= 9 && $currentUS && $currentSP) {
                    $isCorrect = false;
                    for ($i = 0; $i < count($nums) - 2; $i++) {
                        if ($nums[$i] == '0' && $nums[$i+1] == '05') {
                            $mode = intval($nums[$i+2]);
                            $this->machineModes[$currentUS] = $mode;
                            if ($mode == 1) {
                                $isCorrect = true;
                            }
                            break;
                        }
                    }
                    $this->init($currentUS, $currentSP);
                    if ($isCorrect) {
                        $this->data[$currentUS][$currentSP]['ok']++;
                    } else {
                        $this->data[$currentUS][$currentSP]['ko']++;
                        $this->data[$currentUS][$currentSP]['erreurs'][] = [
                            'time' => $time,
                            'line' => $line
                        ];
                    }
                    $this->data[$currentUS][$currentSP]['points'][] = [
                        'time' => $time,
                        'ok' => $isCorrect ? 1 : 0,
                        'ko' => $isCorrect ? 0 : 1,
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

    public function getData() {
        return $this->data;
    }

    public function getMachineModes() {
        return $this->machineModes;
    }
}

$dash = new GlobalPerMachine();
$data = $dash->getData();
$modes = $dash->getMachineModes();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta charset="UTF-8" />
<title>Détails par Machine</title>
<style>
   body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }
    h1 {
        color: #1e40af;
        margin-bottom: 30px;
    }
    nav a {
        margin-right: 20px;
        text-decoration: none;
        color: #1d4ed8;
        font-weight: bold;
    }
    .grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    .card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        width: calc(33.333% - 20px);
        box-sizing: border-box;
        transition: transform 0.2s;
        cursor: pointer;
    }
    .card:hover {
        transform: translateY(-4px);
    }
    .card h2 {
        margin-top: 0;
        font-size: 1.3em;
        color: #1e40af;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .mode {
        padding: 6px 12px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        text-decoration: none;
    }
    .mode-1 { background-color: #22c55e; }
    .mode-0 { background-color: #ef4444; }
    .epaisseur {
        margin-left: 10px;
        padding: 6px 0;
        border-bottom: 1px solid #cbd5e1;
        font-size: 0.95em;
    }
</style>
</head>
<body>


<h1>🧾 Détails par Machine</h1>

<div class="grid">
<?php foreach ($data as $machine => $splices): ?>
    <?php
        $total = 0;
        $lastTime = null;
        foreach ($splices as $sp => $info) {
            $total += $info['ok'] + $info['ko'];
            foreach ($info['points'] as $pt) {
                if ($lastTime === null || strtotime($pt['time']) > strtotime($lastTime)) {
                    $lastTime = $pt['time'];
                }
            }
        }
        $mode = $modes[$machine] ?? 0;
    ?>
    <div class="card" onclick="window.location.href='dashboard.php?machine=<?= urlencode($machine) ?>'">
        <h2>
            <?= htmlspecialchars($machine) ?>
           <a href="mode.php?machine=<?= urlencode($machine) ?>" class="mode mode-<?= $mode ?>" style="text-decoration:none; cursor:pointer;">
    <?= $mode ? '🟢' : '🔴' ?>
</a>

        </h2>
        <p><strong>Dernière production :</strong> <?= $lastTime ?? 'N/A' ?></p>
        <p><small>Total : <?= $total ?> épaisseurs</small></p>
        <?php foreach ($splices as $sp => $info): ?>
            <div class="epaisseur">
                🔹 <strong><?= htmlspecialchars($sp) ?></strong> : <?= $info['ok'] + $info['ko'] ?> épaisseurs
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>
