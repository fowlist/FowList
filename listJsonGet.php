<?php
header("Content-Type: application/json");
session_start();
// 1️⃣ Starta output buffer
ob_start();

// 2️⃣ Inkludera din befintliga fil
include("listPrintGet.php");

// 3️⃣ Kasta HTML-output
ob_end_clean();
/* ================================
   BUILD JSON STRUCTURE
================================ */

$response = [
    "meta" => [
        "generated" => date("c"),
        "source" => "fowlist"
    ],
    "arsenal" => [],
    "weapons" => [],
    "rules" => []
];


/* ================================
   ARSENAL (platoonSoftStatsTotal)
================================ */

if (!empty($platoonsInForce) && !empty($platoonSoftStatsTotal)) {

    // Bygg en lookup-tabell för stats så vi slipper dubbel-loop
    $statsByCode = [];
    foreach ($platoonSoftStatsTotal as $statRow) {
        $statsByCode[$statRow["code"]] = $statRow;
    }

    foreach ($platoonsInForce as $platoonRow) {

        $code = $platoonRow["code"];

        if (!isset($statsByCode[$code])) {
            continue; // om ingen statrad finns
        }

        $statRow = $statsByCode[$code];

        $response["arsenal"][] = [
            "code" => $code,
            "title" => $platoonRow["title"] ?? $statRow["title"] ?? null,

            // ALLA stats tas från $platoonSoftStatsTotal
            "stats" => [
                "movement" => [
                    "tactical" => $statRow["TACTICAL"] ?? null,
                    "terrainDash" => $statRow["TERRAIN_DASH"] ?? null,
                    "crossCountryDash" => $statRow["CROSS_COUNTRY_DASH"] ?? null,
                    "roadDash" => $statRow["ROAD_DASH"] ?? null,
                    "crossCheck" => $statRow["CROSScheck"] ?? null
                ],
                "motivation" => $statRow["MOTIVATION"] ?? null,
                "skill" => $statRow["SKILL"] ?? null,
                "isHitOn" => $statRow["IS_HIT_ON"] ?? null,
                "armourSave" => $statRow["ARMOUR_SAVE"] ?? null
            ],

            "keywords" => $statRow["Keywords"] ?? null
        ];
    }
}


/* ================================
   WEAPONS
================================ */

if (isset($weaponsTeamsInForce) && isset($weapons)) {

    $weaponsTeamsInForce = array_unique($weaponsTeamsInForce);

    foreach ($weaponsTeamsInForce as $teamName) {

        foreach ($weapons as $row) {

            if (strtolower($teamName) === strtolower($row["team"] ?? "")) {

                $response["weapons"][] = [
                    "team" => $row["team"],
                    "weapon" => $row["weapon"],
                    "range" => $row["ranges"],
                    "haltedROF" => $row["haltedROF"],
                    "movingROF" => $row["movingROF"],
                    "antiTank" => $row["antiTank"],
                    "firePower" => $row["firePower"],
                    "notes" => $row["notes"]
                ];
            }
        }

        if ($weapons instanceof mysqli_result) {
            mysqli_data_seek($weapons ,0);
        }
    }
}


/* ================================
   RULES
================================ */

if (isset($rulesInForce) && isset($rules)) {

    $rulesInForce = array_unique($rulesInForce);

    foreach ($rulesInForce as $ruleName) {

        foreach ($rules as $ruleRow) {

            if ($ruleName === $ruleRow["name"]) {

                $response["rules"][] = [
                    "name" => $ruleRow["name"],
                    "text" => $ruleRow["text"]
                ];
            }
        }

        if ($rules instanceof mysqli_result) {
            mysqli_data_seek($rules ,0);
        }
    }
}


/* ================================
   OUTPUT JSON
================================ */

echo json_encode($response, JSON_PRETTY_PRINT);

exit;