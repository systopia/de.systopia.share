-- CiviShare.Node: the connection to another node in the network
CREATE TABLE IF NOT EXISTS `civicrm_share_node`(
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
     `name`                 varchar(255)        COMMENT 'Name of the node',
     `short_name`           varchar(16)         COMMENT 'Short name identifier',
     `description`          varchar(255)        COMMENT 'Description to clarify what/where that node is',
     `rest_url`             varchar(255)        COMMENT 'URL of the REST API of the node',
     `site_key`             varchar(64) NULL    COMMENT 'SITE_KEY of the node',
     `api_key`              varchar(64) NULL    COMMENT 'API_KEY of the node',
     `is_enabled`           tinyint             COMMENT 'is this node enabled?',
--     `time_offset`          bigint              COMMENT 'time offset in seconds',
     `receive_profile`      text                COMMENT 'defines what data will be sent to this node',
     `send_profile`         text                COMMENT 'defines what data will be received from this node',
    PRIMARY KEY ( `id` ),
    UNIQUE INDEX `short_name` (short_name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


-- CiviShare.Handler: handlers implement the data processing
CREATE TABLE IF NOT EXISTS `civicrm_share_handler`(
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
     `name`                 varchar(255)        COMMENT 'human-readable name of this handler instance',
     `class`                varchar(128)        COMMENT 'name of the implementing class',
     `weight`               int                 COMMENT 'defines the order of the handlers',
     `is_enabled`           tinyint             COMMENT 'is this node enabled?',
     `configuration`        text                COMMENT 'defines what data will be sent to this node',
    PRIMARY KEY ( `id` ),
    INDEX `is_enabled` (is_enabled),
    INDEX `weight` (weight)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


-- CiviShare.Change: data structure to record changes
CREATE TABLE IF NOT EXISTS `civicrm_share_change`(
    `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
    `change_id`            varchar(128)        COMMENT 'network wide unique change ID',
    `change_hash`          varchar(64)         COMMENT 'SHA1 hash of the change to detect duplicates',
    `handler_class`        varchar(128)        COMMENT 'name of the handler class tha produced this change',
    `source_node_id`       int unsigned        COMMENT 'FK to node ID to civicrm_share_node where the change came from',
    `change_date`          datetime NOT NULL   COMMENT 'timestamp of the change',
    `received_date`        datetime NOT NULL   COMMENT 'timestamp of the reception of the change',
    `processed_date`       datetime            COMMENT 'timestamp of the processing of the change',
    `data_before`          text                COMMENT 'the data before the change',
    `data_after`           text                COMMENT 'the data after the change',
    PRIMARY KEY ( `id` ),
    UNIQUE INDEX `change_id` (change_id),
    INDEX `change_hash` (change_hash),
    INDEX `change_date` (change_date),
    CONSTRAINT FK_civicrm_source_node_id FOREIGN KEY (`source_node_id`) REFERENCES `civicrm_share_node`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
