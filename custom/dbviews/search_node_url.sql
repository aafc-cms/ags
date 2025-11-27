CREATE OR REPLACE  View search_node_url AS

SELECT `np`.`node_id` AS `node_id`,
concat('<a href="', concat('/',convert(`np`.`langcode` using utf8mb4),`np`.`url`) , '"', ' hreflang="', `np`.`langcode`, '">',`nfd`.`title`, '</a>')   as `title`,
`np`.`langcode` AS `langcode`,
concat('/',convert(`np`.`langcode` using utf8mb4),`np`.`url`) AS `url`,
char_length(concat('/',convert(`np`.`langcode` using utf8mb4),`np`.`url`)) AS `numofchars`,
( case when (`nms`.`type` = 'page')
       then 'Internal page'
       when (`nms`.`type` = 'landing_page')
       then 'Landing page'
       when (`nms`.`type` = 'dir_listing')
       then 'Directory listing'
       when (`nms`.`type` = 'news')
       then 'News'
       when (`nms`.`type` = 'empl')
       then 'Employment opportunity'
  else  `nms`.`type`
  end
) AS `pagetype`,
(case when (`np`.`revision_id` is not null)
then `nms`.`moderation_state` else 'previous_revision' end
) AS `moderationstate`,
(case when (`np`.`revision_id` is not null)
then TRUE else FALSE end
) AS `iscurrenturl`,
`np`.`url_revision_id` AS `url_revision_id`,
`np`.`date_of_revision` AS `date_of_revision`,

`nfdc`.`field_dc_date_created_value` AS `date_created`,

(case when (`np`.`revision_id` is not null)
then from_unixtime(`nr`.`revision_timestamp`,'%Y-%m-%d') else '' end
) AS `node_last_revision`,

(CASE
   WHEN (`np`.`revision_id` is not null) THEN
     CASE
       WHEN (`nb`.`body_summary` IS NULL OR `nb`.`body_summary` = '') THEN
         replace(replace(`nfdes`.`field_description_value`,char(13,10),''),'            ',' ')
       WHEN (`nfdes`.`field_description_value` IS NULL OR `nfdes`.`field_description_value` = '') THEN
         replace(replace(`nb`.`body_summary`,char(13,10),''),'            ',' ')
       WHEN (`nb`.`body_summary` = `nfdes`.`field_description_value`) THEN
         replace(replace(`nfdes`.`field_description_value`,char(13,10),''),'            ',' ')
       ELSE
         CONCAT(replace(replace(`nfdes`.`field_description_value`,char(13,10),''),'            ',' '), '; ', replace(replace(`nb`.`body_summary`,char(13,10),''),'            ',' '))
     END
   ELSE ''
 END) AS `description`

FROM
(SELECT DISTINCT cast(replace(`a`.`path`,'/node/','') as UNSIGNED) AS `node_id`,
         `a`.`langcode` AS `langcode`,`a`.`alias` AS `url`,
         char_length(`a`.`alias`) AS `numofchars`, `b`.`revision_id` AS `revision_id`,
         `a`.`revision_id` AS `url_revision_id`,  `a`. `date_of_revision` as `date_of_revision`

  FROM
  ( SELECT `path_alias`.`path` AS `path`,`path_alias`.`langcode` AS `langcode`,
          `path_alias`.`alias` AS `alias`,`path_alias`.`revision_id` AS `revision_id`,
          FROM_unixtime(`path_alias`.`changed`,'%Y-%m-%d') AS `date_of_revision`
          FROM `path_alias`
    WHERE `path_alias`.`langcode` <> 'und' and `path_alias`.`path` like '/node/%' ) `a`
    LEFT JOIN
    ( SELECT `path_alias_revision`.`path` AS `path`,`path_alias_revision`.`langcode` AS `langcode`,
       max(`path_alias_revision`.`revision_id`) AS `revision_id` FROM `path_alias_revision`
      WHERE `path_alias_revision`.`langcode` <> 'und' and `path_alias_revision`.`path` like '/node/%'
      group by `path_alias_revision`.`path`,`path_alias_revision`.`langcode`) `b`
    on`a`.`path` = `b`.`path` and `a`.`langcode` = `b`.`langcode` and `a`.`revision_id` = `b`.`revision_id`
    order by cast(replace(`a`.`path`,'/node/','') as UNSIGNED),`a`.`langcode`) `np`
JOIN
( SELECT DISTINCT `m`.`moderation_state` AS `moderation_state`,`n`.`nid` AS `nid`,`m`.`langcode` AS `langcode`,`n`.`type` AS `type`
  FROM `content_moderation_state_field_revision` `m`
  JOIN `node` `n` on `m`.`content_entity_id` = `n`.`nid` and `m`.`content_entity_revision_id` = `n`.`vid`
  WHERE `m`.`workflow` = 'editorial' and `m`.`content_entity_type_id` = 'node'
  AND (`n`.`type` = 'landing_page' or `n`.`type` = 'page' or `n`.`type` = 'dir_listing' or `n`.`type` = 'news' or `n`.`type` = 'empl')) `nms` ON`np`.`node_id` = `nms`.`nid` AND `np`.`langcode` = `nms`.`langcode`
JOIN `node_field_data` `nfd`on `np`.`node_id` = `nfd`.`nid` and `np`.`langcode` = `nfd`.`langcode`
LEFT JOIN `node__field_dc_date_created` `nfdc` ON `nfd`.`nid` = `nfdc`.`entity_id`
LEFT JOIN `node_revision` `nr` ON `nfd`.`nid` = `nr`.`nid` and `nfd`.`vid` = `nr`.`vid`
LEFT JOIN `node__body` `nb` ON `nb`.`entity_id` = `nfd`.`nid` and `nb`.`langcode` = `nfd`.`langcode`
LEFT JOIN `node__field_description` `nfdes` ON `nfd`.`nid` = `nfdes`.`entity_id` and `nfd`.`langcode` = `nfdes`.`langcode`
GROUP BY `np`.`node_id`, `np`.`langcode`, `np`.`url`, `np`.`revision_id`, `nms`.`moderation_state`, `np`.`url_revision_id`, `np`.`date_of_revision`, `nfdc`.`field_dc_date_created_value`, `nb`.`body_summary`, `nfdes`.`field_description_value`
ORDER BY `np`.`node_id`, `np`.`langcode`, `np`.`url_revision_id` DESC;
