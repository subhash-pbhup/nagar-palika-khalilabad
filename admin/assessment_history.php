<?php
session_start();

include "./include/header.php";

$id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
if ($id <= 0) die('Invalid assessment ID.');

$s = $conn->prepare("SELECT id,property_id,new_holding,municipality_name FROM assessments WHERE id=? LIMIT 1");
$s->bind_param("i", $id);
$s->execute();
$assessment = $s->get_result()->fetch_assoc();
$s->close();
if (!$assessment) die('Assessment not found.');

$s = $conn->prepare("SELECT * FROM assessment_updates_history WHERE assessment_id=? ORDER BY created_at DESC,id DESC");
$s->bind_param("i", $id);
$s->execute();
$history = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function pj($v)
{
    if (!$v) return '—';
    $a = json_decode($v, true);
    return h(json_encode($a ?? $v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
?>

<main class="flex-1 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl border p-5 mb-5 flex justify-between gap-4">
            <div>
                <div class="text-xs text-slate-500">AUDIT TRAIL</div>
                <h1 class="text-2xl font-bold">Assessment Update History</h1>
                <p class="text-sm text-slate-500 mt-1">Property ID: <b><?= h($assessment['property_id']) ?></b> | Holding: <b><?= h($assessment['new_holding']) ?></b></p>
            </div>
            <!-- <a href="view_assesment_details.php?id=<?= $id ?>" class="h-fit px-4 py-2 rounded-lg bg-emerald-600 text-white"><i class="fa fa-arrow-left"></i> Back</a> -->
            <a href="view-assesment.php" class="h-fit px-4 py-2 rounded-lg bg-emerald-600 text-white"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
        <div class="bg-white rounded-2xl border overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="p-3 text-left">Date/Time</th>
                        <th class="p-3 text-left">Action</th>
                        <th class="p-3 text-left">User</th>
                        <th class="p-3 text-left">Entity</th>
                        <th class="p-3 text-left">Changed Fields</th>
                        <th class="p-3 text-left">IP</th>
                        <th class="p-3 text-left">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (!$history): ?><tr>
                            <td colspan="7" class="p-10 text-center text-slate-500">No history found.</td>
                        </tr>
                        <?php else: foreach ($history as $r): ?>
                            <tr class="align-top hover:bg-slate-50">
                                <td class="p-3 whitespace-nowrap"><?= h(date('d-m-Y h:i:s A', strtotime($r['created_at']))) ?></td>
                                <td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-bold <?= $r['action'] === 'DELETE' ? 'bg-red-100 text-red-700' : ($r['action'] === 'CREATE' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700') ?>"><?= h($r['action']) ?></span></td>
                                <td class="p-3"><b><?= h($r['user_name'] ?: 'System') ?></b><br><span class="text-xs text-slate-500"><?= h($r['username']) ?> (ID: <?= h($r['user_id']) ?>)</span></td>
                                <td class="p-3"><?= h($r['entity_type']) ?><br><span class="text-xs text-slate-500">ID: <?= h($r['entity_id']) ?></span></td>
                                <td class="p-3"><?php $f = json_decode($r['changed_fields'] ?? '[]', true);
                                                if (is_array($f)) foreach ($f as $x): ?><span class="inline-block bg-slate-100 rounded px-2 py-1 mr-1 mb-1 text-xs"><?= h($x) ?></span><?php endforeach; ?></td>
                                <td class="p-3 font-mono text-xs"><?= h($r['ip_address']) ?></td>
                                <td class="p-3">
                                    <details>
                                        <summary class="cursor-pointer text-emerald-700 font-semibold">Old / New Data</summary>
                                        <div class="mt-2">
                                            <div class="text-xs font-bold text-red-600">OLD</div>
                                            <pre class="bg-red-50 p-2 rounded text-xs max-w-xl overflow-auto"><?= pj($r['old_data']) ?></pre>
                                            <div class="text-xs font-bold text-green-600 mt-2">NEW</div>
                                            <pre class="bg-green-50 p-2 rounded text-xs max-w-xl overflow-auto"><?= pj($r['new_data']) ?></pre>
                                            <?php if ($r['remark']): ?><div class="text-xs mt-2"><b>Remark:</b> <?= h($r['remark']) ?></div><?php endif; ?>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php include "./include/footer.php"; ?>