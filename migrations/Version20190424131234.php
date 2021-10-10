<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190424131234 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $sql = <<<SQL
DROP PROCEDURE IF EXISTS get_delivery_closest_date;
CREATE PROCEDURE get_delivery_closest_date(
    IN offer_uuid VARCHAR(36),
    IN order_date_minimum DATETIME,
    IN days_to_add_max INT UNSIGNED,
    OUT order_date DATETIME,
    OUT delivery_date DATETIME,
    OUT error_message VARCHAR(255)
) BEGIN
    DECLARE offer_id_current INT;
    DECLARE receiver_working_time_from_current TIME;
    DECLARE receiver_working_time_to_current TIME;
    DECLARE delivery_accepting_minutes_current INT;
    DECLARE order_date_current DATETIME;
    DECLARE delivery_date_current DATETIME;
    DECLARE delivery_accepted_date_current DATETIME;
    DECLARE days_to_add INT DEFAULT 0;

    fetch_date: LOOP
        IF days_to_add > days_to_add_max THEN
            SET error_message = CONCAT('Delivery not exists for given days interval: ', days_to_add_max);
            LEAVE fetch_date;
        END IF;

        SELECT
            o.id AS offer_id,
            IF (rwe.is_working = 1 AND rwe.time_from IS NOT NULL, rwe.time_from,
                IF (rwe.is_working = 0, NULL, rws.time_from)
            ) AS receiver_working_time_from,
            IF (rwe.is_working = 1 AND rwe.time_to IS NOT NULL, rwe.time_to,
                IF (rwe.is_working = 0, NULL, rws.time_to)
            ) AS receiver_working_time_to,
            r.delivery_accepting_minutes,
            ds.order_date,
            ds.delivery_date,
            DATE_FORMAT(DATE_ADD(ds.delivery_date, INTERVAL r.delivery_accepting_minutes minute), '%Y-%m-%d %H:%i:%s') AS delivery_accepted_date
        INTO
            offer_id_current,
            receiver_working_time_from_current,
            receiver_working_time_to_current,
            delivery_accepting_minutes_current,
            order_date_current,
            delivery_date_current,
            delivery_accepted_date_current
        FROM offer AS o
        JOIN supplier AS r
            ON r.id = o.supplier_to_id
        JOIN (
            SELECT
                ws.working_place_id,
                ws.day_number,
                IF (we.is_working = 1 AND we.time_from IS NOT NULL, we.time_from,
                    IF (we.is_working = 0, NULL, ws.time_from)
                ) AS time_from,
                IF (we.is_working = 1 AND we.time_to IS NOT NULL, we.time_to,
                    IF (we.is_working = 0, NULL, ws.time_to)
                ) AS time_to
            FROM working_schedule AS ws
            LEFT JOIN working_extra_day AS we
                ON we.working_place_id = ws.working_place_id
                AND we.date = DATE_FORMAT(order_date_minimum, '%Y-%m-%d')
        ) AS sws
            ON sws.working_place_id = o.supplier_from_id
                AND sws.day_number = WEEKDAY(order_date_minimum) + 1
        JOIN (
            SELECT
                ds.offer_id,
                IF (de.is_supply IS NULL, DATE_FORMAT(CONCAT(DATE_FORMAT(order_date_minimum, '%Y-%m-%d '), ds.order_time), '%Y-%m-%d %H:%i:%s'),
                        IF (de.is_supply = 1, de.order_date, NULL)
                ) AS order_date,
                IF (de.is_supply IS NULL, DATE_FORMAT(DATE_ADD(CONCAT(DATE_FORMAT(order_date_minimum, '%Y-%m-%d '), ds.order_time), INTERVAL ds.delivery_minutes minute), '%Y-%m-%d %H:%i:%s'),
                        IF (de.is_supply = 1, de.delivery_date, NULL)
                ) AS delivery_date
                FROM delivery_schedule AS ds
                LEFT JOIN delivery_extra AS de
                    ON de.offer_id = ds.offer_id
                        AND DATE_FORMAT(de.order_date, '%H:%i:%s') = ds.order_time
                        AND DATE_FORMAT(de.order_date, '%Y-%m-%d') = DATE_FORMAT(order_date_minimum, '%Y-%m-%d')
                WHERE
                    ds.day_number = WEEKDAY(order_date_minimum) + 1
                    AND ds.order_time > DATE_FORMAT(order_date_minimum, '%H:%i:%s')
            UNION SELECT
                de.offer_id,
                de.order_date,
                de.delivery_date
            FROM delivery_extra AS de
            LEFT JOIN delivery_schedule AS ds
                ON ds.offer_id = de.offer_id
                AND ds.order_time = DATE_FORMAT(de.order_date, '%H:%i:%s')
                AND ds.day_number = WEEKDAY(order_date_minimum) + 1
            WHERE
                DATE_FORMAT(de.order_date, '%Y-%m-%d') = DATE_FORMAT(order_date_minimum, '%Y-%m-%d')
                AND DATE_FORMAT(de.order_date, '%H:%i:%s') > DATE_FORMAT(order_date_minimum, '%H:%i:%s')
                AND ds.id IS NULL
        ) AS ds
            ON ds.offer_id = o.id
        JOIN working_schedule AS rws
            ON rws.working_place_id = o.supplier_to_id
                AND rws.day_number = WEEKDAY(ds.delivery_date) + 1
        LEFT JOIN working_extra_day AS rwe
            ON rwe.working_place_id = o.supplier_to_id
            AND rwe.date = DATE_FORMAT(ds.delivery_date, '%Y-%m-%d')
        WHERE
            o.uuid = offer_uuid
            AND sws.time_from IS NOT NULL
            AND sws.time_to IS NOT NULL
            AND ds.order_date IS NOT NULL
            AND ds.delivery_date IS NOT NULL
        HAVING
            receiver_working_time_from IS NOT NULL
            AND receiver_working_time_to IS NOT NULL
            AND DATE_FORMAT(ds.delivery_date, '%H:%i:%s') >= receiver_working_time_from
            AND DATE_FORMAT(delivery_accepted_date, '%H:%i:%s') <= receiver_working_time_to
        ORDER BY ds.order_date
        LIMIT 1;

        IF offer_id_current IS NOT NULL THEN
            LEAVE fetch_date;
        END IF;

        SET order_date_minimum = DATE_ADD(DATE_FORMAT(order_date_minimum, '%Y-%m-%d'), INTERVAL 1 day);
        SET days_to_add = days_to_add + 1;
    END LOOP fetch_date;

    SET order_date = order_date_current;
    SET delivery_date = delivery_accepted_date_current;
END
SQL;

        $this->addSql($sql);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP PROCEDURE IF EXISTS get_delivery_closest_date');
    }
}
