<?php
require_once "session_init.php";

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require "db.php";

// Hämta alla fysiska tabeller i databasen för dina dropdowns
$tables = [];
$res = $db->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    // Hoppa över interna admin-tabeller så listan blir ren
    if (str_starts_with($row[0], 'admin_')) continue;
    $tables[] = $row[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generic Column Comparator</title>
    <style>
        body { background: #0d0f11; color: #e2e8f0; font-family: 'DM Sans', sans-serif; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: #15191e; padding: 30px; border-radius: 8px; border: 1px solid #232931; }
        .row { display: flex; gap: 20px; margin-bottom: 20px; }
        .box { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        select, button { background: #0d0f11; color: #e2e8f0; border: 1px solid #232931; padding: 10px; border-radius: 4px; font-size: 14px; }
        button { background: #298BA8; color: white; cursor: pointer; font-weight: bold; margin-top: 10px; transition: background 0.2s; }
        button:hover { background: #22728a; }
        textarea { width: 100%; height: 300px; background: #0d0f11; color: #e2e8f0; border: 1px solid #232931; border-radius: 4px; padding: 15px; font-family: monospace; font-size: 14px; resize: vertical; margin-top: 20px; }
        h2 { margin-bottom: 20px; color: #298BA8; }
        label { font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container">
    <h2>⚡ Generic Column Comparator</h2>
    <p style="color: #94a3b8; margin-bottom: 20px;">Find values that exist in the source column but are missing in the target column.</p>

    <div class="row">
        <!-- Source Configuration -->
        <div class="box">
            <label>Source Table (Look inside...)</label>
            <select id="source-table" onchange="loadColumns('source')">
                <option value="">-- Select Table --</option>
                <?php foreach ($tables as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
            </select>
            
            <label>Source Column (Values to find)</label>
            <select id="source-column" disabled><option value="">-- Select Table First --</option></select>
        </div>

        <!-- Target Configuration -->
        <div class="box">
            <label>Target Table (Compare against...)</label>
            <select id="target-table" onchange="loadColumns('target')">
                <option value="">-- Select Table --</option>
                <?php foreach ($tables as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
            </select>
            
            <label>Target Column (Where it should exist)</label>
            <select id="target-column" disabled><option value="">-- Select Table First --</option></select>
        </div>
    </div>

    <button onclick="compareColumns()">Run Comparison</button>

    <textarea id="result-box" readonly placeholder="Missing values will appear here row by row..."></textarea>
</div>

<script>
// Dynamically fetch columns using your database metadata via API
async function loadColumns(type) {
    const table = document.getElementById(`${type}-table`).value;
    const colSelect = document.getElementById(`${type}-column`);
    
    if (!table) {
        colSelect.innerHTML = '<option value="">-- Select Table First --</option>';
        colSelect.disabled = true;
        return;
    }

    try {
        // Re-using your existing table.php action=schema to get the column list cleanly!
        const response = await fetch(`api/table.php?action=schema&table=${table}`);
        const schema = await response.json();
        
        colSelect.innerHTML = '<option value="">-- Select Column --</option>';
        if (schema && schema.columns) {
            schema.columns.forEach(col => {
                const opt = document.createElement('option');
                opt.value = col.field;
                opt.innerText = col.field;
                colSelect.appendChild(opt);
            });
            colSelect.disabled = false;
        }
    } catch (err) {
        console.error("Failed to load columns:", err);
    }
}

// Execute the comparison against the backend processor
async function compareColumns() {
    const srcTable = document.getElementById('source-table').value;
    const srcCol = document.getElementById('source-column').value;
    const tgtTable = document.getElementById('target-table').value;
    const tgtCol = document.getElementById('target-column').value;
    const resultBox = document.getElementById('result-box');

    if (!srcTable || !srcCol || !tgtTable || !tgtCol) {
        alert("Please select both tables and columns before running the comparison.");
        return;
    }

    resultBox.value = "Comparing fields... Please wait...";

    try {
        const response = await fetch(`api/compareColumns.php?src_table=${srcTable}&src_col=${srcCol}&tgt_table=${tgtTable}&tgt_col=${tgtCol}`);
        const text = await response.text();
        resultBox.value = text;
    } catch (err) {
        resultBox.value = "A network error occurred during execution.";
        console.error(err);
    }
}
</script>
</body>
</html>
