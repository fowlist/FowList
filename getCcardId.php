<!-- index.php -->
<?php
include_once "sqlServerinfo.php";
$books = [];
$cards = [];

$result1 = $conn->query("SELECT DISTINCT Book FROM cmdCardsText ORDER BY Book ASC");
while ($row = $result1->fetch_assoc()) {
    $books[] = $row['Book'];
}

$result2 = $conn->query("SELECT DISTINCT card FROM cmdCardsText ORDER BY card ASC");
while ($row = $result2->fetch_assoc()) {
    $cards[] = $row['card'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Get Card Number</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        select, button { padding: 8px; margin: 10px 0; }
        #result { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<h2>Find Card Code</h2>

<label for="book">Select Book:</label>
<select id="book">
    <option value="">-- Choose a book --</option>
    <?php foreach ($books as $book): ?>
        <option value="<?= htmlspecialchars($book) ?>"><?= htmlspecialchars($book) ?></option>
    <?php endforeach ?>
</select>

<br>

<label for="cardTitle">Select Card Title:</label>
<select id="cardTitle">
    <option value="">-- Choose a book first --</option>
</select>

<script>
document.getElementById("book").addEventListener("change", function () {
    const book = this.value;
    const cardSelect = document.getElementById("cardTitle");

    cardSelect.innerHTML = `<option value="">Loading cards...</option>`;

    if (!book) {
        cardSelect.innerHTML = `<option value="">-- Choose a book first --</option>`;
        return;
    }

    fetch(`getCardsByBook.php?book=${encodeURIComponent(book)}`)
        .then(res => res.json())
        .then(data => {
            cardSelect.innerHTML = "";

            if (data.success) {
                data.cards.forEach(card => {
                    const option = document.createElement("option");
                    option.value = card;
                    option.textContent = card;
                    cardSelect.appendChild(option);
                });
            } else {
                cardSelect.innerHTML = `<option value="">No cards found</option>`;
            }
        })
        .catch(err => {
            cardSelect.innerHTML = `<option value="">Error loading cards</option>`;
        });
});
</script>

<br>

<button id="fetchBtn">Get Card Code</button>

<div id="result"></div>

<script>
document.getElementById("fetchBtn").addEventListener("click", function() {
    const book = document.getElementById("book").value;
    const cardTitle = document.getElementById("cardTitle").value;

    if (!book || !cardTitle) {
        document.getElementById("result").innerText = "Please select both book and card title.";
        return;
    }

    fetch("getCardNumberFromDB.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ book, cardTitle })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.card.length > 0) {
            document.getElementById("result").innerText = `Card Code: ${data.card[0].code}`;
        } else {
            document.getElementById("result").innerText = "Card not found.";
        }
    })
    .catch(err => {
        document.getElementById("result").innerText = "Error: " + err.message;
    });
});
</script>

</body>
</html>
