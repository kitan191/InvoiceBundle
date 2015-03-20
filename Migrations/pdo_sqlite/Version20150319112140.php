<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_sqlite;

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
                id INTEGER NOT NULL, 
                amount NUMERIC(10, 5) NOT NULL, 
                approved_amount NUMERIC(10, 5) NOT NULL, 
                approving_amount NUMERIC(10, 5) NOT NULL, 
                created_at DATETIME NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                currency VARCHAR(3) NOT NULL, 
                deposited_amount NUMERIC(10, 5) NOT NULL, 
                depositing_amount NUMERIC(10, 5) NOT NULL, 
                extended_data CLOB NOT NULL, 
                payment_system_name VARCHAR(100) NOT NULL, 
                reversing_approved_amount NUMERIC(10, 5) NOT NULL, 
                reversing_credited_amount NUMERIC(10, 5) NOT NULL, 
                reversing_deposited_amount NUMERIC(10, 5) NOT NULL, 
                state INTEGER NOT NULL, 
                updated_at DATETIME DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE TABLE payments (
                id INTEGER NOT NULL, 
                payment_instruction_id INTEGER NOT NULL, 
                approved_amount NUMERIC(10, 5) NOT NULL, 
                approving_amount NUMERIC(10, 5) NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                deposited_amount NUMERIC(10, 5) NOT NULL, 
                depositing_amount NUMERIC(10, 5) NOT NULL, 
                expiration_date DATETIME DEFAULT NULL, 
                reversing_approved_amount NUMERIC(10, 5) NOT NULL, 
                reversing_credited_amount NUMERIC(10, 5) NOT NULL, 
                reversing_deposited_amount NUMERIC(10, 5) NOT NULL, 
                state INTEGER NOT NULL, 
                target_amount NUMERIC(10, 5) NOT NULL, 
                attention_required BOOLEAN NOT NULL, 
                expired BOOLEAN NOT NULL, 
                created_at DATETIME NOT NULL, 
                updated_at DATETIME DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_65D29B328789B572 ON payments (payment_instruction_id)
        ");
        $this->addSql("
            CREATE TABLE financial_transactions (
                id INTEGER NOT NULL, 
                credit_id INTEGER DEFAULT NULL, 
                payment_id INTEGER DEFAULT NULL, 
                extended_data CLOB DEFAULT NULL, 
                processed_amount NUMERIC(10, 5) NOT NULL, 
                reason_code VARCHAR(100) DEFAULT NULL, 
                reference_number VARCHAR(100) DEFAULT NULL, 
                requested_amount NUMERIC(10, 5) NOT NULL, 
                response_code VARCHAR(100) DEFAULT NULL, 
                state INTEGER NOT NULL, 
                created_at DATETIME NOT NULL, 
                updated_at DATETIME DEFAULT NULL, 
                tracking_id VARCHAR(100) DEFAULT NULL, 
                transaction_type INTEGER NOT NULL, 
                PRIMARY KEY(id)
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
                id INTEGER NOT NULL, 
                payment_instruction_id INTEGER NOT NULL, 
                payment_id INTEGER DEFAULT NULL, 
                attention_required BOOLEAN NOT NULL, 
                created_at DATETIME NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                reversing_amount NUMERIC(10, 5) NOT NULL, 
                state INTEGER NOT NULL, 
                target_amount NUMERIC(10, 5) NOT NULL, 
                updated_at DATETIME DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_4117D17E8789B572 ON credits (payment_instruction_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_4117D17E4C3A3BB ON credits (payment_id)
        ");
    }

    public function down(Schema $schema)
    {
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