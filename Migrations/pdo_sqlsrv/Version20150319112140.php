<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_sqlsrv;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/03/19 11:21:41
 */
class Version20150319112140 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE payment_instructions (
                id INT IDENTITY NOT NULL, 
                amount NUMERIC(10, 5) NOT NULL, 
                approved_amount NUMERIC(10, 5) NOT NULL, 
                approving_amount NUMERIC(10, 5) NOT NULL, 
                created_at DATETIME2(6) NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                currency NVARCHAR(3) NOT NULL, 
                deposited_amount NUMERIC(10, 5) NOT NULL, 
                depositing_amount NUMERIC(10, 5) NOT NULL, 
                extended_data VARCHAR(MAX) NOT NULL, 
                payment_system_name NVARCHAR(100) NOT NULL, 
                reversing_approved_amount NUMERIC(10, 5) NOT NULL, 
                reversing_credited_amount NUMERIC(10, 5) NOT NULL, 
                reversing_deposited_amount NUMERIC(10, 5) NOT NULL, 
                state SMALLINT NOT NULL, 
                updated_at DATETIME2(6), 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE TABLE payments (
                id INT IDENTITY NOT NULL, 
                payment_instruction_id INT NOT NULL, 
                approved_amount NUMERIC(10, 5) NOT NULL, 
                approving_amount NUMERIC(10, 5) NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                deposited_amount NUMERIC(10, 5) NOT NULL, 
                depositing_amount NUMERIC(10, 5) NOT NULL, 
                expiration_date DATETIME2(6), 
                reversing_approved_amount NUMERIC(10, 5) NOT NULL, 
                reversing_credited_amount NUMERIC(10, 5) NOT NULL, 
                reversing_deposited_amount NUMERIC(10, 5) NOT NULL, 
                state SMALLINT NOT NULL, 
                target_amount NUMERIC(10, 5) NOT NULL, 
                attention_required BIT NOT NULL, 
                expired BIT NOT NULL, 
                created_at DATETIME2(6) NOT NULL, 
                updated_at DATETIME2(6), 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_65D29B328789B572 ON payments (payment_instruction_id)
        ");
        $this->addSql("
            CREATE TABLE financial_transactions (
                id INT IDENTITY NOT NULL, 
                credit_id INT, 
                payment_id INT, 
                extended_data VARCHAR(MAX), 
                processed_amount NUMERIC(10, 5) NOT NULL, 
                reason_code NVARCHAR(100), 
                reference_number NVARCHAR(100), 
                requested_amount NUMERIC(10, 5) NOT NULL, 
                response_code NVARCHAR(100), 
                state SMALLINT NOT NULL, 
                created_at DATETIME2(6) NOT NULL, 
                updated_at DATETIME2(6), 
                tracking_id NVARCHAR(100), 
                transaction_type SMALLINT NOT NULL, 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_1353F2D9CE062FF9 ON financial_transactions (credit_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_1353F2D94C3A3BB ON financial_transactions (payment_id)
        ");
        $this->addSql("
            CREATE TABLE credits (
                id INT IDENTITY NOT NULL, 
                payment_instruction_id INT NOT NULL, 
                payment_id INT, 
                attention_required BIT NOT NULL, 
                created_at DATETIME2(6) NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                reversing_amount NUMERIC(10, 5) NOT NULL, 
                state SMALLINT NOT NULL, 
                target_amount NUMERIC(10, 5) NOT NULL, 
                updated_at DATETIME2(6), 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_4117D17E8789B572 ON credits (payment_instruction_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_4117D17E4C3A3BB ON credits (payment_id)
        ");
        $this->addSql("
            ALTER TABLE payments 
            ADD CONSTRAINT FK_65D29B328789B572 FOREIGN KEY (payment_instruction_id) 
            REFERENCES payment_instructions (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE financial_transactions 
            ADD CONSTRAINT FK_1353F2D9CE062FF9 FOREIGN KEY (credit_id) 
            REFERENCES credits (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE financial_transactions 
            ADD CONSTRAINT FK_1353F2D94C3A3BB FOREIGN KEY (payment_id) 
            REFERENCES payments (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE credits 
            ADD CONSTRAINT FK_4117D17E8789B572 FOREIGN KEY (payment_instruction_id) 
            REFERENCES payment_instructions (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE credits 
            ADD CONSTRAINT FK_4117D17E4C3A3BB FOREIGN KEY (payment_id) 
            REFERENCES payments (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE payments 
            DROP CONSTRAINT FK_65D29B328789B572
        ");
        $this->addSql("
            ALTER TABLE credits 
            DROP CONSTRAINT FK_4117D17E8789B572
        ");
        $this->addSql("
            ALTER TABLE financial_transactions 
            DROP CONSTRAINT FK_1353F2D94C3A3BB
        ");
        $this->addSql("
            ALTER TABLE credits 
            DROP CONSTRAINT FK_4117D17E4C3A3BB
        ");
        $this->addSql("
            ALTER TABLE financial_transactions 
            DROP CONSTRAINT FK_1353F2D9CE062FF9
        ");
        $this->addSql("
            DROP TABLE payment_instructions
        ");
        $this->addSql("
            DROP TABLE payments
        ");
        $this->addSql("
            DROP TABLE financial_transactions
        ");
        $this->addSql("
            DROP TABLE credits
        ");
    }
}