<?php
require_once "session_init.php";

require "db.php";

$out = [];

$rules = $db->query("
    SELECT *
    FROM admin_validation_rules
    WHERE enabled = 1
");

while ($rule = $rules->fetch_assoc()) {

    /*
    ---------------------------------
    EXISTS RULE
    ---------------------------------
    */
    if ($rule['rule_type'] == "exists") {
        $sql = "
            SELECT s.*
            FROM {$rule['source_table']} s
            LEFT JOIN {$rule['target_table']} t 
              ON s.{$rule['source_column']} = t.{$rule['target_column']}
            WHERE s.{$rule['source_column']} != ''
              AND t.{$rule['target_column']} IS NULL
        ";

        $res = $db->query($sql);
        while ($r = $res->fetch_assoc()) {
            $out[] = [
                "type"    => "error",
                "table"   => $rule['source_table'],
                "message" => $rule['message'],
                "value"   => $r[$rule['source_column']],
                "row"     => $r
            ];
        }
    }

    /*
    ---------------------------------
    PIPE STRING
    ---------------------------------
    */
    if ($rule['rule_type'] == "contains_multiple") {
        $res = $db->query("SELECT * FROM {$rule['source_table']}");

        while ($row = $res->fetch_assoc()) {
            $items = explode("|", $row[$rule['source_column']]);

            foreach ($items as $item) {
                $item = trim($item);

                if (!$item) {
                    continue;
                }

                $escaped = $db->real_escape_string($item);
                $ok = $db->query("
                    SELECT 1
                    FROM {$rule['target_table']}
                    WHERE {$rule['target_column']} = '$escaped'
                    LIMIT 1
                ");

                if ($ok->num_rows == 0) {
                    $out[] = [
                        "type"    => "error",
                        "table"   => $rule['source_table'],
                        "message" => $rule['message'],
                        "value"   => $item,
                        "row"     => $row
                    ];
                }
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($out);