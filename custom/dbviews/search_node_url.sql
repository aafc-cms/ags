 CREATE OR REPLACE  View search_node_url AS

Select `np`.`node_id` AS `node_id`,
concat('<a href="', concat('/',convert(`np`.`langcode` using utf8mb4),`np`.`url`) , '"', ' hreflang="', `np`.`langcode`, '">',`nd`.`title`, '</a>')   as `title`,
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
       then 'Employ'
  else  `nms`.`type`
  end
) AS `pagetype`,
(case when (`np`.`revision_id` is not null)
then `nms`.`moderation_state` else 'previousrevision' end
) AS `moderationstate`,
(case when (`np`.`revision_id` is not null)
then TRUE else FALSE end
) AS `iscurrenturl`
from
((
  select distinct cast(replace(`a`.`path`,'/node/','') as UNSIGNED) AS `node_id`,
         `a`.`langcode` AS `langcode`,`a`.`alias` AS `url`,
         char_length(`a`.`alias`) AS `numofchars`, `b`.`revision_id` AS `revision_id`
  from
  ((
    select `path_alias`.`path` AS `path`,`path_alias`.`langcode` AS `langcode`,
          `path_alias`.`alias` AS `alias`,`path_alias`.`revision_id` AS `revision_id`
          from `path_alias`
    where ((`path_alias`.`langcode` <> 'und')
    and (`path_alias`.`path` like '/node/%'))
    )
    `a`
    left join
    (
      select `path_alias`.`path` AS `path`,`path_alias`.`langcode` AS `langcode`,
       max(`path_alias`.`revision_id`) AS `revision_id` from `path_alias`
      where ((`path_alias`.`langcode` <> 'und') and (`path_alias`.`path` like '/node/%'))
      group by `path_alias`.`path`,`path_alias`.`langcode`
    )
    `b`
    on(((`a`.`path` = `b`.`path`)
    and (`a`.`langcode` = `b`.`langcode`)
    and (`a`.`revision_id` = `b`.`revision_id`))))
    order by cast(replace(`a`.`path`,'/node/','') as UNSIGNED),`a`.`langcode`) `np`
join
(
  select distinct `m`.`moderation_state` AS `moderation_state`,`n`.`nid` AS `nid`,
  `m`.`langcode` AS `langcode`,`n`.`type` AS `type`
  from (`content_moderation_state_field_revision` `m`
  join `node` `n`
  on(((`m`.`content_entity_id` = `n`.`nid`)
  and (`m`.`content_entity_revision_id` = `n`.`vid`))))
  where ((`m`.`workflow` = 'editorial')
  and (`m`.`content_entity_type_id` = 'node')
  and (`n`.`type` = 'landing_page' or `n`.`type` = 'page' or `n`.`type` = 'dir_listing' or `n`.`type` = 'news' or `n`.`type` = 'empl'))
)
`nms`
on(((`np`.`node_id` = `nms`.`nid`) and (`np`.`langcode` = `nms`.`langcode`)))
join
`node_field_data` `nd`
on (((`np`.`node_id` = `nd`.`nid`) and (`np`.`langcode` = `nd`.`langcode`)))
);