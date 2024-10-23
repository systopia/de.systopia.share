-- noinspection SqlNoDataSourceInspectionForFile

-- CiviShare.Node: the connection to another node in the network
CREATE TABLE IF NOT EXISTS `civicrm_share_node`(
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
     `name`                 varchar(255)        COMMENT 'Name of the node',
     `short_name`           varchar(16)         COMMENT 'Short name identifier',
     `is_local`             tinyint             COMMENT 'is this node representing this system or a remote one?',
     `description`          varchar(255)        COMMENT 'Description to clarify what/where that node is',
     `rest_url`             varchar(255)        COMMENT 'URL of the REST API of the node',
--      `site_key`             varchar(64) NULL    COMMENT 'SITE_KEY of the node',
     `api_key`              varchar(64) NULL    COMMENT 'API_KEY of the node',
     `is_enabled`           tinyint             COMMENT 'is this node enabled?',
--     `time_offset`          bigint              COMMENT 'time offset in seconds',
     `receives_identifiers` text                COMMENT 'list of data identifiers (like civishare.change.contact.base) that will be received by this node',
     `sends_identifiers`    text                COMMENT 'defines what data identifiers (like civishare.change.contact.base) that will be sent by this node',
    PRIMARY KEY ( `id` ),
    UNIQUE INDEX `short_name` (short_name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Peering of two nodes
  CREATE TABLE IF NOT EXISTS `civicrm_share_node_peering`(
  `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
  `local_node`           int                 COMMENT 'local node'
  `remote_node`          int                 COMMENT 'remote node'
  `is_enabled`           tinyint             COMMENT 'is this node enabled?',
  `shared_secret`        varchar(64)         COMMENT 'bi-directional shared-secret',
  PRIMARY KEY ( `id` ),
  UNIQUE INDEX `local_node` (local_node),
  UNIQUE INDEX `remote_node` (remote_node)
  ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- insert local node, TODO: REMOVE, make this configurable
INSERT IGNORE INTO civicrm_share_node (id, name, short_name, description, is_enabled)
       VALUES (1, 'Local Environment', 'LOCAL', 'This very environment', '', 'no_key', '', '');

-- CiviShare.Handler: handlers implement the data processing
CREATE TABLE IF NOT EXISTS `civicrm_share_handler`(
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
     `name`                 varchar(255)        COMMENT 'human-readable name of this handler instance',
     `class`                varchar(128)        COMMENT 'name of the implementing class',
     `weight`               int                 COMMENT 'defines the order of the handlers',
     `is_enabled`           tinyint             COMMENT 'is this node enabled?',
     `configuration`        text                COMMENT 'JSON data that defines what data will be sent to this node',
    PRIMARY KEY ( `id` ),
    INDEX `is_enabled` (is_enabled),
    INDEX `weight` (weight)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


-- -- insert test handler, TODO: REMOVE, make this configurable
-- INSERT IGNORE INTO civicrm_share_handler (id,name,class,weight,is_enabled,configuration)
--    VALUES (1, "Test", "CRM_Share_Handler_ContactBase", 1, 1, "{}"),
--           (2, "Test2", "CRM_Share_Handler_ContactTag", 1, 1, "{}");

-- CiviShare.Change: data structure to record changes
CREATE TABLE IF NOT EXISTS `civicrm_share_change`(
    `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
    `change_id`            varchar(128)        COMMENT 'network wide unique change ID',
    `status`               varchar(8)          COMMENT 'status: LOCAL, PENDING, BUSY, FORWARD, DONE, ERROR',
    `hash`                 varchar(64)         COMMENT 'SHA1 hash of the change to detect duplicates and loops',
    `handler_class`        varchar(128)        COMMENT 'name of the handler class tha produced this change',
    `local_contact_id`     int unsigned        COMMENT 'FK to the local contact ID',
    `source_node_id`       int unsigned        COMMENT 'FK to node ID to civicrm_share_node where the change came from',
    `change_date`          datetime NOT NULL   COMMENT 'timestamp of the change',
    `received_date`        datetime NOT NULL   COMMENT 'timestamp of the reception of the change',
    `processed_date`       datetime            COMMENT 'timestamp of the processing of the change',
    `triggerd_by`          text                COMMENT 'list of change_ids that triggered this change',
    `data_before`          text                COMMENT 'the data before the change',
    `data_after`           text                COMMENT 'the data after the change',
    PRIMARY KEY ( `id` ),
    UNIQUE INDEX `change_id` (change_id),
    INDEX `change_group_id` (change_group_id),
    INDEX `change_hash` (hash),
    INDEX `change_date` (change_date),
    CONSTRAINT FK_civicrm_local_contact_id FOREIGN KEY (`local_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
    CONSTRAINT FK_civicrm_source_node_id FOREIGN KEY (`source_node_id`) REFERENCES `civicrm_share_node`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
