<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include 'sqlServerinfo.php';
$userID = $_SESSION['user_id']??"";
$username = $_SESSION['username']??"";
include "functions.php";
include "login.php";

$linkQuery = $_SESSION['linkQuery']??"";

?>
<!DOCTYPE html>
<html>
<head>
    <title><?=$_SESSION['username']??""?>'s Lists</title>
    <link rel="icon" type="image/x-icon" href="/img/cardSmall.svg">
    <link rel='stylesheet' href='css/list.css'>
</head>
<body>

<?php 
$beta ="";
include "menu.php"; 
?>

<div class="page-container">
    <div class="header">
        <h2><?=$_SESSION['username']??""?>'s Lists</h2>
    </div>
    <div>
        <a href="index.php?<?=$linkQuery?>">Back</a>
    </div>
    <?php

if (isset($_SESSION['username'])) {
    $Books = $conn->query("
    SELECT  * 
    FROM    nationBooks");
    
    $results = $pdo->prepare(
        "SELECT * 
        FROM saved_lists 
        WHERE user_id=?
        ORDER by url");
    $results->execute([$userID]);

?>




<table id="listTable">
    <thead>
        <tr>
            <th>Period</th>
            <th>Nation</th>
            <th>Book</th>
            <th>List Name</th>
            <th>Points</th>
            <th>Rename</th>
            <th>Delete</th>
        </tr>
        <tr id="filterRow">
            <th>
                <select class="filter-input" data-column-index="0">
                    <option value="all">All</option>
                </select>
            </th>
            <th>
            <select class="filter-input" data-column-index="1">
                    <option value="all">All</option>
                </select>
            </th>
            <th>
            <select class="filter-input" data-column-index="2">
                    <option value="all">All</option>
                </select>
            </th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
<?php

    foreach ($results as $key => $row) {
        $parts = parse_url($row['url']);
        parse_str($parts['query'], $query);
        $query["BookName"] = "";
        foreach ($Books as $key => $value) {
            if ($value['code']==$query["Book"]) {
                $query["BookName"] = $value["Book"];
                $query["pd"] = $value["periodLong"];

            }
        }
?>
        <tr>
            <td><?= $query["pd"] ?></td>
            <td><?= $query["ntn"] ?></td>
            <td><?= $query["BookName"] ?></td>
            <td><a href="<?= $row['url'] ?>"><?= $row['name'] ?></a></td>
            <td><?= "(" . $row['cost'] . " points)" ?></td>
            <td>
                <form method='post' action='rename_url.php'>
                    <input type='hidden' name='id' value='<?= $row['id'] ?>'>
                    <input type='text' name='name' value='<?= $row['name'] ?>'>
                    <button type='submit'>Rename</button>
                </form>
            </td>
            <td>
                <form method='post' action='delete_url.php'>
                    <input type='hidden' name='id' value='<?= $row['id'] ?>'>
                    <button type='submit'>Delete</button>
                </form>
            </td>
        </tr>
<?php
    }

?>
    </tbody>
</table>
<?php
} else {
    echo "You are not logged in.";
}
$conn->close();
$pdo = null;
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const listTable = document.getElementById("listTable");
        const headers = listTable.querySelectorAll("th");
        const filterInputs = document.querySelectorAll(".filter-input");
        
        headers.forEach((header, index) => {
            const columnIndex = index; // Column index
            const filterInput = filterInputs[index];

            // Extract unique values from the column
            const values = new Set();
            listTable.querySelectorAll(`tbody td:nth-child(${columnIndex + 1})`).forEach(cell => {
                values.add(cell.textContent.trim());
            });

            // Create options for the dropdown
            values.forEach(value => {
                const option = document.createElement("option");
                option.textContent = value;
                option.value = value.toLowerCase().replace(/\s+/g, "-");
                filterInput.appendChild(option);
            });

            // Add event listener for filtering
            filterInput.addEventListener("change", () => {
                const filterValue = filterInput.value;
                const rows = listTable.querySelectorAll("tbody tr");
                rows.forEach(row => {
                    const cell = row.children[columnIndex];
                    if (filterValue === "all" || cell.textContent.trim().toLowerCase().replace(/\s+/g, "-") === filterValue) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            });
        });

        headers.forEach(header => {
            header.addEventListener("click", () => {
                const table = header.closest("table");
                const columnIndex = Array.prototype.indexOf.call(header.parentNode.children, header);
                const rows = Array.from(table.querySelectorAll("tr"));
                const descending = header.dataset.order === "desc";
                const sortedRows = rows.slice(1).sort((a, b) => {
                    const aText = a.children[columnIndex].textContent.trim();
                    const bText = b.children[columnIndex].textContent.trim();
                    return descending ? bText.localeCompare(aText) : aText.localeCompare(bText);
                });
                sortedRows.forEach(row => table.appendChild(row));
                header.dataset.order = descending ? "asc" : "desc";
            });
        });

        const filterInput = document.getElementById("filterInput");
        filterInput.addEventListener("input", () => {
            const filterValue = filterInput.value.toLowerCase();
            const rows = listTable.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const cells = row.querySelectorAll("td");
                let isVisible = false;
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filterValue)) {
                        isVisible = true;
                    }
                });
                row.style.display = isVisible ? "" : "none";
            });
        });
    });
</script>

    <button onclick="printPage()">Print This Page</button>
</div>
    <script>
        function printPage() {
            // Open the print dialog
            window.print();
        }
    </script>
</body>
</html>