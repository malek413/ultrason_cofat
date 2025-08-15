<?php
session_start();
include 'nav.php';


if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] !== true) {
    header('Location: login.php');
    exit;
}

$errorCodes = [
    78 => "Below Safety Height", 101 => "Data file already exists", 102 => "Data file does not exist",
    103 => "DOWNWARD-COUNTER reached", 104 => "DOWNWARD-COUNTER reached", 105 => "Input: Value invalid",
    106 => "Anvil-size is not useful", 107 => "Maintenance: Diagnosis", 108 => "Maintenance: Clean",
    109 => "Maintenance: Lubrication", 110 => "Sequence-definition is not complete",
    111 => "Splice of the sequence is lacking", 112 => "Statistics are not possible",
    113 => "Data file of the splice cannot be read", 114 => "Data file of the statistics cannot be used",
    115 => "Input error: starting no. > final no.", 116 => "Input error: starting date > final date",
    117 => "Input error: starting time > final time", 118 => "Input error: time invalid",
    119 => "Input error: date invalid", 120 => "Hard disk area complete",
    121 => "Hard disk area complete - stop stat.-record", 122 => "Hard disk area nearly complete",
    123 => "Data file already exists -> overwrite", 124 => "Delete data file",
    125 => "Error: Disk - again", 126 => "No Files - again", 127 => "ERROR: Value PD",
    128 => "ERROR: Value WD", 129 => "ERROR: Value Time", 201 => "Close timeout sound insulation",
    202 => "Timeout pressure", 203 => "Foot-switch -open-", 204 => "Open timeout sound insulation",
    205 => "Generator", 206 => "Main voltage is lacking", 207 => "Foot-switch -close-",
    208 => "Compacting height", 209 => "Welding height", 210 => "Welding time",
    216 => "Initialisation. Sideslider", 217 => "Timeout positioning gliding jaw",
    218 => "Sensor-Slider not found", 219 => "Incremental-sensor", 220 => "Welding not possible",
    221 => "Compacting height external limit reached", 222 => "Welding height external limit reached",
    223 => "Data transmission Controller", 224 => "Timeout Protection",
    225 => "Safety Stop reached", 226 => "Delta-H", 227 => "Delta-H external limit reached",
    236 => "Timeout Outter open", 237 => "Sonotrode not down", 238 => "Communication Generator",
    239 => "Error Generator", 240 => "Line Pressure", 241 => "Energy",
    242 => "Initialisation failed", 243 => "Knife not down", 244 => "ASSK not in back position"
];

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
                            'error_message' => $errorNumber ? $this->errorCodes[$errorNumber] : 'Erreur inconnue'
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

if (!isset($_GET['machine'])) {
    echo "<p style='color:red; text-align:center; margin-top: 30px;'>❌ Machine non spécifiée.</p>";
    exit;
}

$machine = $_GET['machine'];
$dash = new GlobalPerMachine('Prot', $errorCodes);
$data = $dash->getData();
$modes = $dash->getMachineModes();

if (!isset($data[$machine])) {
    echo "<p style='color:red; text-align:center; margin-top: 30px;'>❌ Données non trouvées pour la machine : " . htmlspecialchars($machine) . "</p>";
    exit;
}

$splices = $data[$machine];
$totalEpaisseurs = 0;
$lastTime = null;
foreach ($splices as $sp => $info) {
    $totalEpaisseurs += $info['ok'] + $info['ko'];
    foreach ($info['points'] as $pt) {
        if ($lastTime === null || strtotime($pt['time']) > strtotime($lastTime)) {
            $lastTime = $pt['time'];
        }
    }
}

$mode = $modes[$machine] ?? 0;
$colors = ['#2563eb', '#f97316', '#10b981', '#8b5cf6', '#ef4444', '#db2777', '#14b8a6', '#f59e0b'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Détails de <?= htmlspecialchars($machine) ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1"></script>
<style>
    * {
        box-sizing: border-box;
    }
    body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }
    .page-wrapper {
        max-width: 950px;
        width: 100%;
    }
    /* Carte contenant titre و details */
    .card-header {
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        padding: 30px 40px;
        margin-bottom: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .card-header h1 {
        font-size: 3rem;
        font-weight: 800;
        margin: 0 0 15px 0;
        color: #1e40af;
        display: flex;
        align-items: center;
        gap: 12px;
        justify-content: center;
    }
    .mode {
        font-weight: 700;
        font-size: 1.5rem;
        padding: 10px 28px;
        border-radius: 9999px;
        color: white;
        user-select: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    .mode-1 { background-color: #22c55e; } /* vert */
    .mode-0 { background-color: #ef4444; } /* rouge */

    .summary {
        width: 100%;
        margin-top: 15px;
    }
    .summary p {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 8px 0;
        color: #475569;
    }
    /* Liste épaisseurs */
    .summary ul {
        list-style: none;
        padding: 0;
        margin-top: 30px;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 25px;
    }
    .summary ul li {
        background: white;
        padding: 20px 28px;
        border-radius: 15px;
        font-weight: 700;
        color: #334155;
        box-shadow: 0 8px 25px rgba(0,0,0,0.07);
        min-width: 150px;
        text-align: center;
        border-left: 8px solid transparent;
        cursor: default;
        user-select: none;
        transition: box-shadow 0.3s ease;
        position: relative;
    }
    .summary ul li:hover {
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }
    .summary ul li span.ok {
        color: #16a34a;
        margin-right: 8px;
        font-size: 1.2rem;
    }
    .summary ul li span.ko {
        color: #dc2626;
        font-size: 1.2rem;
    }
    .summary ul li div:first-child {
        margin-bottom: 10px;
        font-size: 1.25rem;
        color: #1e293b;
    }
    
    /* Tooltip pour les erreurs */
    .error-tooltip {
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: normal;
        white-space: nowrap;
        z-index: 10;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
        margin-bottom: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .error-tooltip:after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px;
        border-style: solid;
        border-color: #1e293b transparent transparent transparent;
    }
    .summary ul li:hover .error-tooltip {
        opacity: 1;
        visibility: visible;
    }

    /* Sélecteur email */
    label[for="emailSelect"] {
        display: block;
        font-weight: 700;
        margin-top: 45px;
        margin-bottom: 15px;
        font-size: 1.2rem;
        color: #475569;
        text-align: center;
    }
    #emailSelect {
        width: 320px;
        padding: 14px 22px;
        font-size: 1.1rem;
        border-radius: 14px;
        border: 2px solid #cbd5e1;
        transition: border-color 0.3s ease;
        cursor: pointer;
        outline-offset: 3px;
        outline-color: transparent;
        margin: 0 auto 50px auto;
        display: block;
    }
    #emailSelect:focus {
        border-color: #2563eb;
        outline-color: #93c5fd;
    }

    /* Conteneur graphique */
    .chart-container {
        background: white;
        border-radius: 24px;
        padding: 35px 50px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        margin-bottom: 60px;
    }

    canvas {
        width: 100% !important;
        height: 400px !important;
        border-radius: 20px;
    }

    /* Responsive */
    @media (max-width: 720px) {
        .summary ul {
            flex-direction: column;
            gap: 15px;
        }
        .summary ul li {
            min-width: auto;
            width: 100%;
        }
        #emailSelect {
            width: 90%;
        }
        .card-header h1 {
            font-size: 2.2rem;
        }
    }
</style>
</head>
<body>

    <section class="card-header">
        <h1>
            <?= htmlspecialchars($machine) ?>
            <span class="mode mode-<?= $mode ?>"><?= $mode ? '🟢 Mode' : '🔴 Mode' ?></span>
        </h1>

        <div class="summary">
            <p><strong>Dernière production :</strong> <?= $lastTime ?? 'N/A' ?></p>
            <p><strong>Total épaisseurs :</strong> <?= $totalEpaisseurs ?></p>

            <ul>
                <?php 
                $colorIndex = 0;
                foreach ($splices as $sp => $info): 
                    $color = $colors[$colorIndex % count($colors)];
                    $colorIndex++;
                    
                    // Récupérer les messages d'erreur uniques
                    $uniqueErrors = [];
                    foreach ($info['erreurs'] as $erreur) {
                        if ($erreur['error_message'] && !in_array($erreur['error_message'], $uniqueErrors)) {
                            $uniqueErrors[] = $erreur['error_message'];
                        }
                    }
                    $errorTooltip = $info['ko'] > 0 ? "Erreurs rencontrées:<br>" . implode("<br>", $uniqueErrors) : "Aucune erreur";
                ?>
                <li style="border-left-color: <?= $color ?>;">
                    <div><?= htmlspecialchars($sp) ?></div>
                    <div>
                        <span class="ok">✅ <?= $info['ok'] ?></span> | 
                        <span class="ko">❌ <?= $info['ko'] ?></span>
                    </div>
                    <?php if ($info['ko'] > 0): ?>
                    <div class="error-tooltip"><?= $errorTooltip ?></div>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
          <?php
            // Section des erreurs détectées - CORRIGÉE (utilisation de $splices au lieu de $resultats)
            $erreursDetectees = [];
            foreach ($splices as $sp => $info) {
                if (!empty($info['erreurs'])) {
                    foreach ($info['erreurs'] as $err) {
                        $code = $err['error_code'];
                        $desc = $err['error_message'] ?? '—';
                        $cleUnique = "$code-$desc";
                        $erreursDetectees[$cleUnique] = [
                            'code' => $code,
                            'description' => $desc
                        ];
                    }
                }
            }
            ?>

            <?php if (!empty($erreursDetectees)): ?>
                <div style="margin-top: 30px; padding: 15px; background: #fff5f5; border: 1px solid #fca5a5; border-radius: 8px;">
                    <h3 style="color: #b91c1c;">🚨 Erreurs détectées :</h3>
                    <ul style="margin-top: 10px; padding-left: 20px; color: #991b1b;">
                        <?php foreach ($erreursDetectees as $err): ?>
                            <li>
                                <strong>Code:</strong> <?= htmlspecialchars($err['code']) ?> |
                                <strong>Description:</strong> <?= htmlspecialchars($err['description']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>



            <label for="emailSelect">Envoyer une réclamation à :</label>
            <select id="emailSelect" aria-label="Choisir un email pour réclamation">
                <option value="">-- Choisissez un email --</option>
                <option value="adem.makki26@gmail.com">adem.makki26@gmail.com</option>
                <option value="support@cofat.com">support@cofat.com</option>
                <option value="service.client@cofat.com">service.client@cofat.com</option>
            </select>
        </div>
    
        <canvas id="machineChart"></canvas>
    </section>

<script>
const ctx = document.getElementById('machineChart').getContext('2d');
const splices = <?= json_encode($splices, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const colors = <?= json_encode($colors) ?>;

// Labels triés (temps)
const labelsSet = new Set();
Object.values(splices).forEach(splice => {
    splice.points.forEach(p => labelsSet.add(p.time));
});
const labels = Array.from(labelsSet).sort((a,b) => {
    return luxon.DateTime.fromFormat(a, 'HH:mm:ss').toMillis() - luxon.DateTime.fromFormat(b, 'HH:mm:ss').toMillis();
});

// Préparation des datasets
const datasets = [];

Object.entries(splices).forEach(([sp, info], i) => {
    let compteur = 0;
    const data = labels.map(t => {
        const point = info.points.find(p => p.time === t);
        if (!point) return compteur;
        if (parseInt(point.ko) === 1) {
            compteur = 0;
            return 0;
        } else if (parseInt(point.ok) === 1) {
            compteur++;
        }
        return compteur;
    });

    datasets.push({
        label: sp,
        data: data,
        borderColor: colors[i % colors.length],
        backgroundColor: colors[i % colors.length] + "55",
        fill: true,
        tension: 0.25,
        pointRadius: 3,
        pointHoverRadius: 5,
        borderWidth: 3
    });
});

new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
        responsive: true,
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false,
        },
        plugins: {
            legend: {
                labels: {
                    color: '#334155',
                    font: { weight: '700', size: 14 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(51,65,85,0.9)',
                titleFont: { weight: '700' },
                bodyFont: { size: 13 },
                callbacks: {
                    label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y}`
                }
            }
        },
        scales: {
            x: {
                type: 'time',
                time: {
                    parser: 'HH:mm:ss',
                    tooltipFormat: 'HH:mm:ss',
                    displayFormats: {
                        second: 'HH:mm:ss',
                        minute: 'HH:mm',
                        hour: 'HH:mm'
                    }
                },
                title: {
                    display: true,
                    text: 'Heure (HH:mm:ss)',
                    color: '#64748b',
                    font: { size: 14, weight: '600' }
                },
                ticks: { color: '#64748b' },
                grid: { color: '#e2e8f0' }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Compteur OK (reset si erreur)',
                    color: '#64748b',
                    font: { size: 14, weight: '600' }
                },
                ticks: { color: '#64748b', stepSize: 1 },
                grid: { color: '#e2e8f0' }
            }
        }
    }
});

document.getElementById('emailSelect').addEventListener('change', function() {
    const email = this.value;
    if (!email) return;

    const subject = encodeURIComponent("Réclamation pour la machine <?= addslashes($machine) ?>");
    let bodyText = "Bonjour,%0D%0A%0D%0AJe souhaite faire une réclamation concernant la machine <?= addslashes($machine) ?>.%0D%0AVoici les détails :%0D%0A%0D%0A";

    <?php foreach ($splices as $sp => $info): ?>
    bodyText += "🔹 <?= addslashes($sp) ?> : OK = <?= $info['ok'] ?>, KO = <?= $info['ko'] ?> épaisseurs.%0D%0A";
    <?php if ($info['ko'] > 0): ?>
    bodyText += "Erreurs rencontrées :%0D%0A";
    <?php 
    $uniqueErrors = [];
    foreach ($info['erreurs'] as $erreur) {
        if ($erreur['error_message'] && !in_array($erreur['error_message'], $uniqueErrors)) {
            $uniqueErrors[] = $erreur['error_message'];
        }
    }
    foreach ($uniqueErrors as $error): ?>
    bodyText += "- <?= addslashes($error) ?>%0D%0A";
    <?php endforeach; ?>
    <?php endif; ?>
    <?php endforeach; ?>

    bodyText += "%0D%0AMerci de prendre en compte.%0D%0A%0D%0ACordialement.";

    window.location.href = `mailto:${email}?subject=${subject}&body=${bodyText}`;
    this.value = '';
});
</script>

</body>
</html>