<?php
session_start();
include 'nav.php'; 

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] !== true) {
    header('Location: login.php');
    exit;
}
ini_set('memory_limit', '512M');
set_time_limit(60);

class GlobalPerMachine {
    private $data = [];

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
                        if ($nums[$i] == '0' && $nums[$i+1] == '5' && $nums[$i+2] == '1') {
                            $isCorrect = true;
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
}

$dash = new GlobalPerMachine();
$data = $dash->getData();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8" />
    <title>📊 Suivi Production Cofat avec Filtre Texte et Logo</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1"></script>

    <style>
     body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

h1 {
    color: #0ea5e9;
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 30px;
}

.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 900px;
    margin: 0 auto 30px auto;
    background: #ffffff;
    padding: 15px 20px;
    border-radius: 16px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.logo {
    max-height: 60px;
}

.filter-container {
    flex-grow: 1;
    margin-left: 20px;
    margin-top: 20px;
    text-align: center;
}

.filter-container input {
    width: 100%;
    max-width: 400px;
    padding: 12px 18px;
    font-size: 1rem;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #f1f5f9;
    color: #1e293b;
    transition: box-shadow 0.3s;
}

.filter-container input:focus {
    box-shadow: 0 0 8px #0ea5e9;
    outline: none;
    
}

.clear-btn {
    margin-left: 10px;
    padding: 10px 16px;
    font-size: 1rem;
    border-radius: 10px;
    border: none;
    background: #ef4444;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.clear-btn:hover {
    background-color: #dc2626;
}

.machine {
    background: #ffffff;
    margin: 20px auto;
    padding: 25px;
    border-radius: 16px;
    max-width: 900px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.machine:hover {
    transform: scale(1.01);
}

h2 {
    color: #0f172a;
    margin-bottom: 10px;
    font-size: 1.5rem;
}

.info {
    font-size: 1.1rem;
    margin: 8px 0;
    color: #0f172a;
}

.correcte {
    color: #16a34a;
    font-weight: bold;
}

.errone {
    color: #dc2626;
    font-weight: bold;
}

ul {
    list-style: none;
    padding: 0;
}

li {
    background: #f1f5f9;
    margin: 6px 0;
    padding: 10px;
    border-radius: 8px;
    font-family: monospace;
    font-size: 0.9rem;
    white-space: pre-wrap;
    color: #1e293b;
}

.heure {
    color: #2563eb;
    font-weight: bold;
}

canvas {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-top: 20px;
    max-width: 100%;
    height: 300px;
}
 

        .logout-form button {
            padding: 10px 16px;
            font-size: 0.95rem;
            border-radius: 8px;
            border: none;
            background: #ef4444;
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .logout-form button:hover {
            background-color: #dc2626;
        }

    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
<body>
   
    <div class="filter-container">
        <input type="text" id="machineFilter" placeholder="Tapez le nom de la machine pour filtrer..." aria-label="Filtrer par nom de machine" />
        <button class="clear-btn" id="clearFilterBtn" title="Effacer le filtre">✕</button>
    </div>


<h1>📈 Suivi de Production par Machine</h1>

<?php if (empty($data)): ?>
    <p style="color: orange; text-align: center;">⚠️ Aucune donnée trouvée. Vérifiez les fichiers dans <code>Prot/</code>.</p>
<?php endif; ?>

<?php foreach ($data as $us => $splices):
    $divId = 'machine_' . preg_replace('/\W+/', '_', $us);
?>
    <div class="machine" id="<?= htmlspecialchars($divId) ?>" data-machine-name="<?= strtolower(htmlspecialchars($us)) ?>">
        <h2><?= htmlspecialchars($us) ?></h2>
        <canvas id="<?= $divId ?>_canvas"></canvas>

        <?php foreach ($splices as $sp => $info): ?>
            <div class="info">
                <strong><?= htmlspecialchars($sp) ?></strong> — 
                ✅ <span class="correcte"><?= $info['ok'] ?></span> |
                ❌ <span class="errone"><?= $info['ko'] ?></span>
            </div>

            <?php if (!empty($info['erreurs'])): ?>
                <ul>
                    <?php foreach ($info['erreurs'] as $err): ?>
                        <li>
                            🕒 <span class="heure"><?= $err['time'] ?></span> — 🔎
                            <?= htmlspecialchars($err['line']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterInput = document.getElementById('machineFilter');
    const clearBtn = document.getElementById('clearFilterBtn');
    const machineDivs = document.querySelectorAll('.machine');

    // Filtrage au tapé
    filterInput.addEventListener('input', () => {
        const val = filterInput.value.trim().toLowerCase();
        machineDivs.forEach(div => {
            const name = div.getAttribute('data-machine-name');
            div.style.display = (name.includes(val)) ? 'block' : 'none';
        });
    });

    clearBtn.addEventListener('click', () => {
        filterInput.value = '';
        machineDivs.forEach(div => div.style.display = 'block');
        filterInput.focus();
    });

    // Génération des graphiques
    <?php foreach ($data as $us => $splices):
        $divId = 'machine_' . preg_replace('/\W+/', '_', $us);
        $jsonSplices = json_encode($splices, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    ?>
    {
        const ctx = document.getElementById('<?= $divId ?>_canvas').getContext('2d');
        const splices = <?= $jsonSplices ?>;

        const labelsSet = new Set();
        Object.values(splices).forEach(splice => {
            splice.points.forEach(p => {
                labelsSet.add(p.time);
            });
        });

        const labels = Array.from(labelsSet).sort((a,b) => {
            return luxon.DateTime.fromFormat(a, 'HH:mm:ss').toMillis() - luxon.DateTime.fromFormat(b, 'HH:mm:ss').toMillis();
        });

        const datasets = [];
        const colors = ['#22c55e', '#ef4444'];

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
                label: sp ,
                data: data,
                borderColor: colors[0],
                backgroundColor: colors[0] + "55",
                fill: true,
                tension: 0.3,
                pointRadius: 2,
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
                    legend: { labels: { color: "#e0e7ff" } },
                    tooltip: {
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
                        title: { display: true, text: 'Heure (HH:mm:ss)', color: '#94a3b8' },
                        ticks: { color: '#cbd5e1' },
                        grid: { color: '#334155' }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Compteur OK (remis à 0 si erreur)', color: '#94a3b8' },
                        ticks: { color: '#cbd5e1', stepSize: 1 },
                        grid: { color: '#334155' }
                    }
                }
            }
        });
    }
    <?php endforeach; ?>

    // Rechargement automatique
    setTimeout(() => location.reload(), 15000);
});
</script>

</body>
</html>
