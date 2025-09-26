
DROP TEMPORARY TABLE IF EXISTS merged_platoons;
CREATE TEMPORARY TABLE merged_platoons AS
SELECT 
    ps.code,
    ps.image,
    ps.title,
    ps.keywords,
    ps.MOTIVATION,
    ps.SKILL,
    ps.IS_HIT_ON,
    ps.ARMOUR_SAVE,
    ps.TACTICAL,
    ps.TERRAIN_DASH,
    ps.CROSS_COUNTRY_DASH,
    ps.ROAD_DASH,
    ps.CROSScheck,
    -- Merge teams from both sources
    TRIM(BOTH '|' FROM CONCAT_WS('|',
        ps.teams,
        GROUP_CONCAT(DISTINCT po.teams)
    )) AS all_teams
FROM platoonsStats ps
LEFT JOIN platoonOptions po
    ON po.code = ps.code
GROUP BY ps.code;



SET SESSION sort_buffer_size = 8 * 1024 * 1024;
SET SESSION group_concat_max_len = 1000000;
TRUNCATE TABLE team_platoon_formation_stats;

INSERT INTO team_platoon_formation_stats (...)
SELECT 
    wl.team,
    wl.image AS team_image,
    mp.image AS platoon_image,
    mp.code,
    mp.title,
    mp.keywords,
    mp.MOTIVATION,
    mp.SKILL,
    mp.IS_HIT_ON,
    mp.ARMOUR_SAVE,
    mp.TACTICAL,
    mp.TERRAIN_DASH,
    mp.CROSS_COUNTRY_DASH,
    mp.ROAD_DASH,
    mp.CROSScheck,
    w.weapon,
    w.ranges,
    w.haltedROF,
    w.movingROF,
    w.antiTank,
    w.firePower,
    w.notes,
    nb.Nation,
    GROUP_CONCAT(
        DISTINCT CONCAT(
            f.title, 
            ' (', f.Book, ', ', nb.code, ', ', f.code, ')'
        )
        ORDER BY f.title SEPARATOR '|'
    ) AS formation_info
FROM weaponsLink wl
JOIN merged_platoons mp
    ON FIND_IN_SET(wl.team, mp.all_teams)  -- now just one match needed
LEFT JOIN weapons w 
    ON wl.weapon = w.weapon
LEFT JOIN (
    SELECT platoon, formation FROM formation_DB
    UNION
    SELECT platoon, formation FROM support_DB
) all_platoons
    ON mp.code = all_platoons.platoon
LEFT JOIN formations f
    ON all_platoons.formation = f.code
LEFT JOIN nationBooks nb
    ON f.Book = nb.Book
GROUP BY wl.team, team_image, platoon_image, mp.code, nb.Nation,
         mp.title, mp.keywords, mp.MOTIVATION, mp.SKILL, mp.IS_HIT_ON, mp.ARMOUR_SAVE,
         mp.TACTICAL, mp.TERRAIN_DASH, mp.CROSS_COUNTRY_DASH, mp.ROAD_DASH, mp.CROSScheck,
         w.weapon, w.ranges, w.haltedROF, w.movingROF, w.antiTank, w.firePower, w.notes
ORDER BY wl.team;




DROP TEMPORARY TABLE IF EXISTS merged_platoons;
CREATE TEMPORARY TABLE merged_platoons AS
SELECT 
    ps.code,
    ps.image,
    ps.title,
    ps.keywords,
    ps.MOTIVATION,
    ps.SKILL,
    ps.IS_HIT_ON,
    ps.ARMOUR_SAVE,
    ps.TACTICAL,
    ps.TERRAIN_DASH,
    ps.CROSS_COUNTRY_DASH,
    ps.ROAD_DASH,
    ps.CROSScheck,
    -- Merge teams from both sources
    TRIM(BOTH '|' FROM CONCAT_WS('|',
        ps.teams,
        GROUP_CONCAT(DISTINCT po.teams)
    )) AS all_teams
FROM platoonsStats ps
LEFT JOIN platoonOptions po
    ON po.code = ps.code
GROUP BY ps.code;

SET SESSION sort_buffer_size = 8 * 1024 * 1024;
SET SESSION group_concat_max_len = 1000000;
TRUNCATE TABLE team_platoon_formation_stats;

INSERT INTO team_platoon_formation_stats 
(
    team,
    team_image,
    platoon_image,
    code,
    platoon_title,
    platoon_keywords,
    MOTIVATION,
    SKILL,
    IS_HIT_ON,
    ARMOUR_SAVE,
    TACTICAL,
    TERRAIN_DASH,
    CROSS_COUNTRY_DASH,
    ROAD_DASH,
    CROSScheck,
    weapon,
    ranges,
    haltedROF,
    movingROF,
    antiTank,
    firePower,
    weapon_notes,
    Nation,
    formation_info
)
SELECT 
    wl.team,
    wl.image AS team_image,
    ps.image AS platoon_image,
    ps.code AS code,
    ps.title AS platoon_title,
    ps.keywords AS platoon_keywords,
    ps.MOTIVATION,
    ps.SKILL,
    ps.IS_HIT_ON,
    ps.ARMOUR_SAVE,
    ps.TACTICAL,
    ps.TERRAIN_DASH,
    ps.CROSS_COUNTRY_DASH,
    ps.ROAD_DASH,
    ps.CROSScheck,
    w.weapon,
    w.ranges,
    w.haltedROF,
    w.movingROF,
    w.antiTank,
    w.firePower,
    w.notes AS weapon_notes,
    nb.Nation AS Nation,
    GROUP_CONCAT(
        DISTINCT CONCAT(
            f.title, 
            ' (', f.Book, ', ', nb.code, ', ', f.code, ')'
        )
        ORDER BY f.title SEPARATOR '|'
    ) AS formation_info
FROM weaponsLink wl
LEFT JOIN merged_platoons ps 
    ON ps.all_teams LIKE CONCAT('%', wl.team, '%')
LEFT JOIN weapons w 
    ON wl.weapon = w.weapon
LEFT JOIN (
    SELECT platoon, formation FROM formation_DB
    UNION
    SELECT platoon, formation FROM support_DB
) all_platoons
    ON ps.code = all_platoons.platoon
LEFT JOIN formations f
    ON all_platoons.formation = f.code
LEFT JOIN nationBooks nb
    ON f.Book = nb.Book
GROUP BY wl.team, COALESCE(ps.image, wl.image), ps.code, nb.Nation,
         ps.title, ps.keywords, ps.MOTIVATION, ps.SKILL, ps.IS_HIT_ON, ps.ARMOUR_SAVE,
         ps.TACTICAL, ps.TERRAIN_DASH, ps.CROSS_COUNTRY_DASH, ps.ROAD_DASH, ps.CROSScheck,
         w.weapon, w.ranges, w.haltedROF, w.movingROF, w.antiTank, w.firePower, w.notes
ORDER BY wl.team


SET SESSION sort_buffer_size = 8 * 1024 * 1024;
SET SESSION group_concat_max_len = 1000000;
TRUNCATE TABLE team_platoon_formation_stats;

INSERT INTO team_platoon_formation_stats 
(
    team,
    team_image,
    platoon_image,
    code,
    platoon_title,
    platoon_keywords,
    MOTIVATION,
    SKILL,
    IS_HIT_ON,
    ARMOUR_SAVE,
    TACTICAL,
    TERRAIN_DASH,
    CROSS_COUNTRY_DASH,
    ROAD_DASH,
    CROSScheck,
    weapon,
    ranges,
    haltedROF,
    movingROF,
    antiTank,
    firePower,
    weapon_notes,
    Nation,
    formation_info
)
SELECT 
    wl.team,
    wl.image AS team_image,
    ps.image AS platoon_image,
    ps.code AS code,
    ps.title AS platoon_title,
    ps.keywords AS platoon_keywords,
    ps.MOTIVATION,
    ps.SKILL,
    ps.IS_HIT_ON,
    ps.ARMOUR_SAVE,
    ps.TACTICAL,
    ps.TERRAIN_DASH,
    ps.CROSS_COUNTRY_DASH,
    ps.ROAD_DASH,
    ps.CROSScheck,
    w.weapon,
    w.ranges,
    w.haltedROF,
    w.movingROF,
    w.antiTank,
    w.firePower,
    w.notes AS weapon_notes,
    nb.Nation AS Nation,
    GROUP_CONCAT(
        DISTINCT CONCAT(
            f.title, 
            ' (', f.Book, ', ', nb.code, ', ', f.code, ')'
        )
        ORDER BY f.title SEPARATOR '|'
    ) AS formation_info
FROM weaponsLink wl
LEFT JOIN platoonsStats ps 
    ON ps.teams LIKE CONCAT('%', wl.team, '%')
LEFT JOIN weapons w 
    ON wl.weapon = w.weapon
LEFT JOIN (
    SELECT platoon, formation FROM formation_DB
    UNION
    SELECT platoon, formation FROM support_DB
) all_platoons
    ON ps.code = all_platoons.platoon
LEFT JOIN formations f
    ON all_platoons.formation = f.code
LEFT JOIN nationBooks nb
    ON f.Book = nb.Book
GROUP BY wl.team, COALESCE(ps.image, wl.image), ps.code, nb.Nation,
         ps.title, ps.keywords, ps.MOTIVATION, ps.SKILL, ps.IS_HIT_ON, ps.ARMOUR_SAVE,
         ps.TACTICAL, ps.TERRAIN_DASH, ps.CROSS_COUNTRY_DASH, ps.ROAD_DASH, ps.CROSScheck,
         w.weapon, w.ranges, w.haltedROF, w.movingROF, w.antiTank, w.firePower, w.notes
ORDER BY wl.team






CREATE TABLE team_platoon_formation_stats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    team VARCHAR(100) NOT NULL,
    team_image VARCHAR(255) DEFAULT NULL,
    code VARCHAR(20) DEFAULT NULL,
    platoon_title VARCHAR(100) DEFAULT NULL,
    platoon_keywords VARCHAR(255) DEFAULT NULL,

    MOTIVATION VARCHAR(100)  DEFAULT NULL,
    SKILL VARCHAR(100)  DEFAULT NULL,
    IS_HIT_ON VARCHAR(100)  DEFAULT NULL,
    ARMOUR_SAVE VARCHAR(100)  DEFAULT NULL,
    TACTICAL VARCHAR(20)  DEFAULT NULL,
    TERRAIN_DASH VARCHAR(20)  DEFAULT NULL,
    CROSS_COUNTRY_DASH VARCHAR(20)  DEFAULT NULL,
    ROAD_DASH VARCHAR(20)  DEFAULT NULL,
    CROSScheck VARCHAR(20)  DEFAULT NULL,

    weapon VARCHAR(50) DEFAULT NULL,
    ranges VARCHAR(50) DEFAULT NULL,
    haltedROF VARCHAR(20)  DEFAULT NULL,
    movingROF VARCHAR(20)  DEFAULT NULL,
    antiTank VARCHAR(20)  DEFAULT NULL,
    firePower VARCHAR(20)  DEFAULT NULL,
    weapon_notes VARCHAR(255) DEFAULT NULL,

    Nation VARCHAR(50) DEFAULT NULL,
    formation_info TEXT DEFAULT NULL,

    INDEX idx_team (team),
    INDEX idx_code (code),
    INDEX idx_weapon (weapon),
    INDEX idx_nation (Nation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;