<?php

namespace FormaLibre\InvoiceBundle\Migrations\drizzle_pdo_mysql;

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
                id INT AUTO_INCREMENT NOT NULL, 
                amount NUMERIC(10, 5) NOT NULL, 
                approved_amount NUMERIC(10, 5) NOT NULL, 
                approving_amount NUMERIC(10, 5) NOT NULL, 
                created_at DATETIME NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                currency VARCHAR(3) NOT NULL, 
                deposited_amount NUMERIC(10, 5) NOT NULL, 
                depositing_amount NUMERIC(10, 5) NOT NULL, 
                extended_data TEXT NOT NULL COMMENT '(DC2Type:extended_payment_data)', 
                payment_system_name VARCHAR(100) NOT NULL, 
                reversing_approved_amount NUMERIC(10, 5) NOT NULL, 
                reversing_credited_amount NUMERIC(10, 5) NOT NULL, 
                reversing_deposited_amount NUMERIC(10, 5) NOT NULL, 
                state INT NOT NULL, 
                updated_at DATETIME DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE TABLE payments (
                id INT AUTO_INCREMENT NOT NULL, 
                payment_instruction_id INT NOT NULL, 
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
                state INT NOT NULL, 
                target_amount NUMERIC(10, 5) NOT NULL, 
                attention_required BOOLEAN NOT NULL, 
                expired BOOLEAN NOT NULL, 
                created_at DATETIME NOT NULL, 
                updated_at DATETIME DEFAULT NULL, 
                PRIMARY KEY(id), 
                INDEX IDX_65D29B328789B572 (payment_instruction_id)
            )
        ");
        $this->addSql("
            CREATE TABLE financial_transactions (
                id INT AUTO_INCREMENT NOT NULL, 
                credit_id INT DEFAULT NULL, 
                payment_id INT DEFAULT NULL, 
                extended_data TEXT DEFAULT NULL COMMENT '(DC2Type:extended_payment_data)', 
                processed_amount NUMERIC(10, 5) NOT NULL, 
                reason_code VARCHAR(100) DEFAULT NULL, 
                reference_number VARCHAR(100) DEFAULT NULL, 
                requested_amount NUMERIC(10, 5) NOT NULL, 
                response_code VARCHAR(100) DEFAULT NULL, 
                state INT NOT NULL, 
                created_at DATETIME NOT NULL, 
                updated_at DATETIME DEFAULT NULL, 
                tracking_id VARCHAR(100) DEFAULT NULL, 
                transaction_type INT NOT NULL, 
                PRIMARY KEY(id), 
                INDEX IDX_1353F2D9CE062FF9 (credit_id), 
                INDEX IDX_1353F2D94C3A3BB (payment_id)
            )
        ");
        $this->addSql("
            CREATE TABLE credits (
                id INT AUTO_INCREMENT NOT NULL, 
                payment_instruction_id INT NOT NULL, 
                payment_id INT DEFAULT NULL, 
                attention_required BOOLEAN NOT NULL, 
                created_at DATETIME NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                reversing_amount NUMERIC(10, 5) NOT NULL, 
                state INT NOT NULL, 
                target_amount NUMERIC(10, 5) NOT NULL, 
                updated_at DATETIME DEFAULT NULL, 
                PRIMARY KEY(id), 
                INDEX IDX_4117D17E8789B572 (payment_instruction_id), 
                INDEX IDX_4117D17E4C3A3BB (payment_id)
            )
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
            DROP FOREIGN KEY FK_65D29B328789B572
        ");
        $this->addSql("
            ALTER TABLE credits 
            DROP FOREIGN KEY FK_4117D17E8789B572
        ");
        $this->addSql("
            ALTER TABLE financial_transactions 
            DROP FOREIGN KEY FK_1353F2D94C3A3BB
        ");
        $this->addSql("
            ALTER TABLE credits 
            DROP FOREIGN KEY FK_4117D17E4C3A3BB
        ");
        $this->addSql("
            ALTER TABLE financial_transactions 
            DROP FOREIGN KEY FK_1353F2D9CE062FF9
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