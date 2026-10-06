<?php
require_once "api/session_init.php";

// Om användaren inte är inloggad, skicka dem till inloggningssidan direkt
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">

    <title>FoW Admin</title>

    <meta name="viewport" content="width=device-width, initial-scale=0.7">
    <script src="https://cdn.jsdelivr.net/npm/jspreadsheet-ce@5/dist/index.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jspreadsheet-ce@5/dist/jspreadsheet.min.css" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/jsuites@5/dist/jsuites.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsuites@5/dist/jsuites.min.css" type="text/css" />
    <link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v5/jspreadsheet.themes.css" type="text/css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons" />
    <link rel="stylesheet" href="css/style.css?v=1.9">


</head>

<body>

    <div class="layout">
        <button id="sidebar-toggle" class="sidebar-toggle">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="sidebar">

            <div >
                <label>Select Period</label>
                <select id="period-select">
                    <option value="">-- Choose Period --</option>
                    <?php 
                    if (isset($_SESSION['allowed_databases'])) {
                        foreach ($_SESSION['allowed_databases'] as $db_name) {
                            // Snygga till namnet för visning (t.ex. fow_mid_war blir Fow Mid War)
                            $display_name = ucwords(str_replace('_', ' ', $db_name));
                            echo "<option value='" . htmlspecialchars($db_name) . "'>" . htmlspecialchars($display_name) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <h2>Database</h2>

            <div id="table-list"></div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--border);">
            <h2>Tools</h2>
                <a href="dpParse.html">Pares Dynamic Points</a>

                <?php 
                    $userId = $_SESSION['user_id'] ?? null;
                    $isAdmin = in_array($userId, [5]); 
                        
                    if ($isAdmin) {
                ?>
            <h2>Backend Tools</h2>
                <!-- Sync Metadata (Sync/Cloud/Refresh Icon) -->
                <button id="syncMetadata" title="Sync Metadata">
                    ⟳ Sync Metadata for backend
                </button>

            <h2>Live site</h2>
                    
                    <!-- Rebuild Table (Database/Gear/Flash Icon) -->
                <button id="rebuild-table" title="Rebuild Table">
                    ⚡ Rebuild Live Tables
                </button>
                <?php 

                    }

 ?>

            <!-- Snygg röd utloggningsknapp som stänger din session -->
            <a href="logout.php" style="display: block; padding: 10px; margin: 10px 0; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; border-radius: var(--radius, 4px); color: #ef4444; text-decoration: none; text-align: center; font-weight: bold; transition: background 0.2s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.25)';" onmouseout="this.style.background='rgba(239, 68, 68, 0.15)';">
                🚪 Log Out
            </a>
        </div>

        <div class="content">

            <div class="topbar">

                <h1 id="current-table">
                    Select table
                </h1>

                <div id="sync-indicator" class="sync-indicator synced">
                    <span class="sync-icon">●</span> <span class="sync-text">Synced with server</span>
                </div>

            </div>

            <div class="toolbar" id="toolbar">
                <input type="checkbox" id="toggle-help" style="display: none;">
                <label for="toggle-help" class="help-label" title="Click here to see what the buttons do">ℹ️ Help</label>
                <div id="toolbar-buttons" style="display:flex; gap:6px;">

                    <button id="reload-table" title="Reload Table">↻</button>

                    <button id="sort-table-column" title="Sort Column">⇅</button>

                    <button id="gen-shortid" title="Generate Unique ShortID" style="display:none;">🆔</button>

                    <button id="gen-run-all" title="Run All Generators" >⚡</button>
                </div>

                <div id="generator-toolbar-buttons" style="display:flex; gap:6px;"></div>
            </div>
            <div class="table-padding-wrapper">
                <div id="table"></div>
            </div>

        </div>

        <!-- === NYTT: HÖGERSIDOMENY (TOOLS & COMPARATOR) === -->
        <button id="right-sidebar-toggle" class="sidebar-toggle-right" title="Toggle Tools">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="sidebar-right">
            <h2>⚡ Smart Compare</h2>
            <p style="color: #94a3b8; font-size: 11px; margin-bottom: 15px;">
                Automatic validation based on table metadata columns.
            </p>

            <div class="comparator-widget" style="display: flex; flex-direction: column; gap: 12px;">
                
                <!-- Aktiv tabell (Låst och visas bara som info) -->
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 11px; color: #94a3b8; font-weight: bold;">Active Table</label>
                    <input type="text" id="comp-active-table" readonly style="background: #0d0f11; color: #94a3b8; border: 1px solid #232931; padding: 8px; border-radius: 4px; font-size: 13px;" value="No table loaded">
                </div>

                <!-- Välj vilken av kolumnerna du vill validera -->
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 11px; color: #94a3b8; font-weight: bold;">Select Column to Verify</label>
                    <select id="comp-active-column" style="width: 100%;">
                        <option value="">-- Select Column --</option>
                    </select>
                </div>

                <!-- Knapp för att köra den automatiska matchningen -->
                <button id="run-smart-compare" style="background: #298BA8; color: white; border: none; padding: 10px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 5px;">
                    Verify Missing Data
                </button>

                <!-- Resultattextruta -->
                <textarea id="comp-result-box" readonly placeholder="Missing entries will appear here row by row..." style="width: 100%; height: 220px; background: #0d0f11; color: #e2e8f0; border: 1px solid #232931; border-radius: 4px; padding: 10px; font-family: monospace; font-size: 12px; resize: none; box-sizing: border-box;"></textarea>
            </div>
        </div>

    </div>


    <!-- Extern förslagsruta som flyter ovanpå Jspreadsheet -->
    <div id="custom-autocomplete-box" style="position: absolute; display: none; background: #15191e; border: 1px solid #232931; border-radius: 4px; z-index: 99999; max-height: 150px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.5); min-width: 180px;"></div>

    <script src="js/admin.js"></script>

</body>

</html>