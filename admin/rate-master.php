<?php
include "db.php";
$fy = $_GET['fy'] ?? '2024-25';

$sql = "SELECT * FROM rate_master WHERE financial_year=? ORDER BY ward_no, road_width";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$fy);
$stmt->execute();
$res = $stmt->get_result();

$data=[];
while($r=$res->fetch_assoc()){
  $data[$r['ward_no']]['name']=$r['ward_name'];
  $data[$r['ward_no']]['rows'][]=$r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rate Master</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="favicon.png" rel="icon">
<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>body{font-family:Inter}</style>
</head>

<body class="bg-gradient-to-br from-slate-100 to-indigo-50 min-h-screen">

<div class="max-w-[1500px] mx-auto px-6 py-6">

  <div class="bg-white rounded-2xl shadow-sm px-6 py-5 flex items-center justify-between">
    <div>
      <h1 class="text-xl font-semibold text-slate-800">Rate Master</h1>
      <p class="text-sm text-slate-500">Ward wise rate chart management</p>
    </div>

    <div class="flex items-center gap-3">
      <select onchange="location='rate-master.php?fy='+this.value"
        class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
        <option <?= $fy=='2023-24'?'selected':'' ?>>2023-24</option>
        <option <?= $fy=='2024-25'?'selected':'' ?>>2024-25</option>
        <option <?= $fy=='2025-26'?'selected':'' ?>>2025-26</option>
      </select>

      <button id="saveAll"
        class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-medium opacity-40 cursor-not-allowed transition">
        Save All
      </button>
    </div>
  </div>

  <div class="mt-4">
    <button onclick="location.href='view-assesment.php'"
      class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2 rounded-xl text-sm font-medium hover:bg-slate-50">
      <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
    </button>
  </div>

  <?php foreach($data as $ward=>$w){ ?>
  <div class="mt-6 bg-white rounded-2xl shadow-sm">
    <div class="flex items-center gap-3 px-6 py-4 border-b">
      <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white">
        <i data-lucide="map-pin" class="w-5 h-5"></i>
      </div>
      <h2 class="font-semibold text-slate-800">
        Ward <?= $ward ?> – <?= htmlspecialchars($w['name']) ?>
      </h2>
    </div>

    <div class="px-6 py-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-slate-500">
          <tr class="border-b">
            <th class="py-2 text-left">Road Width</th>
            <th>RCC</th><th>ACC</th><th>Other</th><th>Open Plot</th>
            <th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($w['rows'] as $r){ ?>
          <tr data-id="<?= $r['id'] ?>" data-ward="<?= htmlspecialchars($w['name']) ?>" class="border-b last:border-0">
            <td class="py-2 font-medium"><?= $r['road_width'] ?></td>

            <?php foreach(['rcc_rate','acc_rate','other_rate','open_plot_rate'] as $f){ ?>
            <td>
              <input type="number" step="0.01" value="<?= $r[$f] ?>" data-o="<?= $r[$f] ?>"
                class="w-full rounded-lg border border-slate-300 px-2 py-1 focus:ring-2 focus:ring-indigo-500">
            </td>
            <?php } ?>

            <td>
              <select data-o="<?= $r['status'] ?>"
                class="rounded-lg border border-slate-300 px-2 py-1">
                <option value="active" <?= $r['status']=='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $r['status']=='inactive'?'selected':'' ?>>Inactive</option>
              </select>
            </td>

            <td>
              <button
                class="row-save bg-emerald-500 text-white px-4 py-1 rounded-lg text-xs font-medium opacity-40 cursor-not-allowed">
                Save
              </button>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php } ?>
</div>

<script>
lucide.createIcons();
const edited=new Set();

document.querySelectorAll('input,select').forEach(el=>{
  el.addEventListener('input',()=>{
    const tr=el.closest('tr');
    const changed=[...tr.querySelectorAll('input,select')]
      .some(e=>e.value!=e.dataset.o);

    tr.classList.toggle('bg-indigo-50',changed);
    const btn=tr.querySelector('.row-save');
    btn.classList.toggle('opacity-40',!changed);
    btn.classList.toggle('cursor-not-allowed',!changed);

    changed?edited.add(tr):edited.delete(tr);
    toggleSaveAll();
  });
});

function toggleSaveAll(){
  const btn=document.getElementById('saveAll');
  const en=edited.size>0;
  btn.classList.toggle('opacity-40',!en);
  btn.classList.toggle('cursor-not-allowed',!en);
}

function payload(tr){
  const inputs = tr.querySelectorAll('input');
  const select  = tr.querySelector('select');
  return {
    id: tr.dataset.id,
    rcc_rate: inputs[0].value,
    acc_rate: inputs[1].value,
    other_rate: inputs[2].value,
    open_plot_rate: inputs[3].value,
    status: select.value
  };
}

document.querySelectorAll('.row-save').forEach(btn=>{
  btn.onclick=()=>{
    if(btn.classList.contains('opacity-40'))return;
    const tr=btn.closest('tr');
    const wardName = tr.dataset.ward; // Ward name fetch kiya

    fetch('update_rate.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify(payload(tr))
    })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
     alert("✅ Success!\n\nWard " + wardName + " data has been updated successfully.");

        location.reload();
      } else {
        alert("❌ Update failed!");
      }
    });
  };
});

document.getElementById('saveAll').onclick=()=>{
  if(!edited.size)return;
  fetch('update_all_rates.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify([...edited].map(payload))
  }).then(()=>location.reload());
};
</script>
</body>
</html>