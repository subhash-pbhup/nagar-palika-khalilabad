<?php
session_start();

include "./include/header.php";

$id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
if ($id <= 0) die('Invalid assessment ID.');

// Fetch Assessment Details
$s = $conn->prepare("SELECT id, property_id, new_holding, municipality_name FROM assessments WHERE id=? LIMIT 1");
$s->bind_param("i", $id);
$s->execute();
$assessment = $s->get_result()->fetch_assoc();
$s->close();
if (!$assessment) die('Assessment not found.');

// Fetch History
$s = $conn->prepare("SELECT * FROM assessment_updates_history WHERE assessment_id=? ORDER BY created_at DESC, id DESC");
$s->bind_param("i", $id);
$s->execute();
$history = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

// Helper Functions
function h($v)
{
    if (is_array($v) || is_object($v)) {
        return htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatFieldName($field)
{
    // Convert field names like 'plot_area' to 'Plot Area'
    return ucwords(str_replace('_', ' ', $field));
}
?>

<style>
    /* Custom Styling for the details accordion */
    details>summary {
        list-style: none;
    }

    details>summary::-webkit-details-marker {
        display: none;
    }

    .timeline-dot {
        position: absolute;
        left: -33px;
        top: 1.5rem;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #4f46e5;
        border: 4px solid #e0e7ff;
    }
</style>

<main class="flex-1 p-6 bg-slate-50 min-h-screen">
    <div class="max-w-6xl mx-auto">

        <!-- ========================================== -->
        <!-- HEADER SECTION -->
        <!-- ========================================== -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center text-2xl border border-indigo-100">
                    <i class="fa fa-history"></i>
                </div>
                <div>
                    <div class="text-xs font-bold text-indigo-500 tracking-wider uppercase mb-1">Official Audit Trail</div>
                    <h1 class="text-2xl font-extrabold text-slate-800">Assessment Update History</h1>
                    <div class="text-sm text-slate-600 mt-1.5 flex gap-4 flex-wrap">
                        <span>Property ID: <strong class="text-slate-900"><?= h($assessment['property_id']) ?></strong></span>
                        <span class="text-slate-300">|</span>
                        <span>Holding No: <strong class="text-slate-900"><?= h($assessment['new_holding']) ?></strong></span>
                    </div>
                </div>
            </div>
            <a href="view-assesment.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-slate-800 text-white font-medium hover:bg-slate-700 transition shadow-sm">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>

        <!-- ========================================== -->
        <!-- TIMELINE SECTION -->
        <!-- ========================================== -->
        <div class="ml-4 pl-6 border-l-2 border-indigo-200 relative space-y-8">
            <?php if (!$history): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-10 text-center shadow-sm relative">
                    <div class="timeline-dot" style="background-color: #94a3b8; border-color: #f1f5f9;"></div>
                    <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">
                        <i class="fa fa-folder-open-o"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700">No History Found</h3>
                    <p class="text-slate-500 text-sm mt-1">There are no recorded updates for this assessment yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($history as $index => $r):
                    $old_data = json_decode($r['old_data'], true) ?: [];
                    $new_data = json_decode($r['new_data'], true) ?: [];
                    $changed_fields = json_decode($r['changed_fields'], true) ?: [];

                    // Badge Styling Logic
                    $actionClass = 'bg-slate-100 text-slate-700 border-slate-200';
                    $actionIcon = 'fa-pencil';
                    $dotColor = '#64748b'; // Default Slate

                    if ($r['action'] === 'CREATE') {
                        $actionClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                        $actionIcon = 'fa-plus-circle';
                        $dotColor = '#10b981'; // Emerald
                    } elseif ($r['action'] === 'DELETE') {
                        $actionClass = 'bg-rose-100 text-rose-800 border-rose-200';
                        $actionIcon = 'fa-trash';
                        $dotColor = '#f43f5e'; // Rose
                    } elseif ($r['action'] === 'UPDATE') {
                        $actionClass = 'bg-blue-100 text-blue-800 border-blue-200';
                        $actionIcon = 'fa-edit';
                        $dotColor = '#3b82f6'; // Blue
                    }
                ?>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm relative">
                        <!-- Timeline Dot -->
                        <div class="timeline-dot" style="background-color: <?= $dotColor ?>;"></div>

                        <!-- Card Header -->
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center gap-4 rounded-t-xl">
                            <div class="flex items-center gap-4">
                                <div class="border <?= $actionClass ?> px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fa <?= $actionIcon ?>"></i> <?= h($r['action']) ?>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-800">
                                        Target Entity: <?= h($r['entity_type']) ?>
                                        <span class="text-slate-500 font-medium text-xs ml-1">(DB ID: <?= h($r['entity_id']) ?>)</span>
                                    </div>
                                    <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5 mt-1">
                                        <i class="fa fa-calendar text-slate-400"></i> <?= h(date('d M Y, h:i A', strtotime($r['created_at']))) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 border-l border-slate-200 pl-4">
                                <div class="text-right">
                                    <div class="text-sm font-bold text-slate-800"><?= h($r['user_name'] ?: 'System') ?></div>
                                    <div class="text-xs font-medium text-slate-500">Role: <?= h($r['username']) ?></div>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600">
                                    <i class="fa fa-user"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 space-y-4">

                            <!-- Remark Box -->
                            <?php if ($r['remark']): ?>
                                <div class="bg-amber-50 border-l-4 border-amber-400 p-3 rounded-r-lg text-sm text-amber-800 font-medium">
                                    <i class="fa fa-commenting-o mr-1"></i> <strong>Remark:</strong> <?= h($r['remark']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Modified Fields Tags -->
                            <?php if (!empty($changed_fields)): ?>
                                <div>
                                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fields Modified:</div>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($changed_fields as $field): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold">
                                                <?= formatFieldName($field) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Expandable Detailed View -->
                            <details class="group border border-slate-200 rounded-lg overflow-hidden">
                                <summary class="flex items-center justify-between gap-2 text-sm font-bold text-indigo-700 cursor-pointer select-none bg-indigo-50/50 hover:bg-indigo-50 px-4 py-3 transition">
                                    <span class="flex items-center gap-2">
                                        <i class="fa fa-table"></i>
                                        <span class="group-open:hidden">View Detailed Data Changes</span>
                                        <span class="hidden group-open:inline">Hide Data Changes</span>
                                    </span>
                                    <i class="fa fa-chevron-down text-indigo-400 group-open:rotate-180 transition-transform"></i>
                                </summary>

                                <div class="border-t border-slate-200 bg-white">
                                    <?php if ($r['action'] === 'CREATE'): ?>
                                        <!-- Only New Data for CREATE -->
                                        <div class="bg-emerald-50 px-4 py-2 border-b border-emerald-100 text-emerald-800 font-bold text-xs uppercase tracking-wider">Inserted Data Record</div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left border-collapse">
                                                <tbody class="divide-y divide-slate-100">
                                                    <?php foreach ($new_data as $key => $val): if ($val === '' || $val === null) continue; ?>
                                                        <tr class="hover:bg-slate-50">
                                                            <td class="px-4 py-2.5 font-semibold text-slate-700 w-1/3 bg-slate-50/50 border-r border-slate-100"><?= formatFieldName($key) ?></td>
                                                            <td class="px-4 py-2.5 text-slate-800"><?= h($val) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                    <?php elseif ($r['action'] === 'DELETE'): ?>
                                        <!-- Only Old Data for DELETE -->
                                        <div class="bg-rose-50 px-4 py-2 border-b border-rose-100 text-rose-800 font-bold text-xs uppercase tracking-wider">Deleted Data Record</div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left border-collapse">
                                                <tbody class="divide-y divide-slate-100">
                                                    <?php foreach ($old_data as $key => $val): if ($val === '' || $val === null) continue; ?>
                                                        <tr class="hover:bg-slate-50">
                                                            <td class="px-4 py-2.5 font-semibold text-slate-700 w-1/3 bg-slate-50/50 border-r border-slate-100"><?= formatFieldName($key) ?></td>
                                                            <td class="px-4 py-2.5 text-rose-700 bg-rose-50/30 line-through"><?= h($val) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                    <?php else: ?>
                                        <!-- Side-by-side Comparison for UPDATE -->
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left border-collapse">
                                                <thead class="bg-slate-100/80 text-slate-600 text-xs uppercase border-b border-slate-200">
                                                    <tr>
                                                        <th class="px-4 py-3 font-bold w-1/4 border-r border-slate-200">Data Field Name</th>
                                                        <th class="px-4 py-3 font-bold w-3/8 text-rose-700 bg-rose-50/50 border-r border-slate-200">Previous Value (Old)</th>
                                                        <th class="px-4 py-3 font-bold w-3/8 text-emerald-700 bg-emerald-50/50">Updated Value (New)</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-200">
                                                    <?php
                                                    $fields_to_show = !empty($changed_fields) ? $changed_fields : array_keys($new_data);
                                                    foreach ($fields_to_show as $field):
                                                        $old_val = isset($old_data[$field]) ? (string)$old_data[$field] : '';
                                                        $new_val = isset($new_data[$field]) ? (string)$new_data[$field] : '';
                                                        if ($old_val === $new_val && $old_val === '') continue; // Skip empty unchanged fields
                                                    ?>
                                                        <tr class="hover:bg-slate-50 transition-colors">
                                                            <td class="px-4 py-3 font-semibold text-slate-700 bg-slate-50/50 border-r border-slate-200"><?= formatFieldName($field) ?></td>
                                                            <td class="px-4 py-3 text-rose-600 bg-rose-50/20 border-r border-slate-200 break-words">
                                                                <?= $old_val !== '' ? h($old_val) : '<span class="text-slate-400 italic font-medium">N/A</span>' ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-emerald-700 bg-emerald-50/20 font-bold break-words">
                                                                <?= $new_val !== '' ? h($new_val) : '<span class="text-slate-400 italic font-medium">N/A</span>' ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include "./include/footer.php"; ?>