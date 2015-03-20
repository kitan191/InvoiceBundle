<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_oci;

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
                id NUMBER(10) NOT NULL, 
                amount NUMERIC(10, 5) NOT NULL, 
                approved_amount NUMERIC(10, 5) NOT NULL, 
                approving_amount NUMERIC(10, 5) NOT NULL, 
                created_at TIMESTAMP(0) NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                currency VARCHAR2(3) NOT NULL, 
                deposited_amount NUMERIC(10, 5) NOT NULL, 
                depositing_amount NUMERIC(10, 5) NOT NULL, 
                extended_data CLOB NOT NULL, 
                payment_system_name VARCHAR2(100) NOT NULL, 
                reversing_approved_amount NUMERIC(10, 5) NOT NULL, 
                reversing_credited_amount NUMERIC(10, 5) NOT NULL, 
                reversing_deposited_amount NUMERIC(10, 5) NOT NULL, 
                state NUMBER(5) NOT NULL, 
                updated_at TIMESTAMP(0) DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'PAYMENT_INSTRUCTIONS' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE PAYMENT_INSTRUCTIONS ADD CONSTRAINT PAYMENT_INSTRUCTIONS_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE PAYMENT_INSTRUCTIONS_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER PAYMENT_INSTRUCTIONS_AI_PK BEFORE INSERT ON PAYMENT_INSTRUCTIONS FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT PAYMENT_INSTRUCTIONS_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT PAYMENT_INSTRUCTIONS_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'PAYMENT_INSTRUCTIONS_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT PAYMENT_INSTRUCTIONS_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            COMMENT ON COLUMN payment_instructions.extended_data IS '(DC2Type:extended_payment_data)'
        ");
        $this->addSql("
            CREATE TABLE payments (
                id NUMBER(10) NOT NULL, 
                payment_instruction_id NUMBER(10) NOT NULL, 
                approved_amount NUMERIC(10, 5) NOT NULL, 
                approving_amount NUMERIC(10, 5) NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                deposited_amount NUMERIC(10, 5) NOT NULL, 
                depositing_amount NUMERIC(10, 5) NOT NULL, 
                expiration_date TIMESTAMP(0) DEFAULT NULL, 
                reversing_approved_amount NUMERIC(10, 5) NOT NULL, 
                reversing_credited_amount NUMERIC(10, 5) NOT NULL, 
                reversing_deposited_amount NUMERIC(10, 5) NOT NULL, 
                state NUMBER(5) NOT NULL, 
                target_amount NUMERIC(10, 5) NOT NULL, 
                attention_required NUMBER(1) NOT NULL, 
                expired NUMBER(1) NOT NULL, 
                created_at TIMESTAMP(0) NOT NULL, 
                updated_at TIMESTAMP(0) DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'PAYMENTS' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE PAYMENTS ADD CONSTRAINT PAYMENTS_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE PAYMENTS_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER PAYMENTS_AI_PK BEFORE INSERT ON PAYMENTS FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT PAYMENTS_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT PAYMENTS_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'PAYMENTS_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT PAYMENTS_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE INDEX IDX_65D29B328789B572 ON payments (payment_instruction_id)
        ");
        $this->addSql("
            CREATE TABLE financial_transactions (
                id NUMBER(10) NOT NULL, 
                credit_id NUMBER(10) DEFAULT NULL, 
                payment_id NUMBER(10) DEFAULT NULL, 
                extended_data CLOB DEFAULT NULL, 
                processed_amount NUMERIC(10, 5) NOT NULL, 
                reason_code VARCHAR2(100) DEFAULT NULL, 
                reference_number VARCHAR2(100) DEFAULT NULL, 
                requested_amount NUMERIC(10, 5) NOT NULL, 
                response_code VARCHAR2(100) DEFAULT NULL, 
                state NUMBER(5) NOT NULL, 
                created_at TIMESTAMP(0) NOT NULL, 
                updated_at TIMESTAMP(0) DEFAULT NULL, 
                tracking_id VARCHAR2(100) DEFAULT NULL, 
                transaction_type NUMBER(5) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'FINANCIAL_TRANSACTIONS' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE FINANCIAL_TRANSACTIONS ADD CONSTRAINT FINANCIAL_TRANSACTIONS_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE FINANCIAL_TRANSACTIONS_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER FINANCIAL_TRANSACTIONS_AI_PK BEFORE INSERT ON FINANCIAL_TRANSACTIONS FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT FINANCIAL_TRANSACTIONS_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT FINANCIAL_TRANSACTIONS_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'FINANCIAL_TRANSACTIONS_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT FINANCIAL_TRANSACTIONS_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE INDEX IDX_1353F2D9CE062FF9 ON financial_transactions (credit_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_1353F2D94C3A3BB ON financial_transactions (payment_id)
        ");
        $this->addSql("
            COMMENT ON COLUMN financial_transactions.extended_data IS '(DC2Type:extended_payment_data)'
        ");
        $this->addSql("
            CREATE TABLE credits (
                id NUMBER(10) NOT NULL, 
                payment_instruction_id NUMBER(10) NOT NULL, 
                payment_id NUMBER(10) DEFAULT NULL, 
                attention_required NUMBER(1) NOT NULL, 
                created_at TIMESTAMP(0) NOT NULL, 
                credited_amount NUMERIC(10, 5) NOT NULL, 
                crediting_amount NUMERIC(10, 5) NOT NULL, 
                reversing_amount NUMERIC(10, 5) NOT NULL, 
                state NUMBER(5) NOT NULL, 
                target_amount NUMERIC(10, 5) NOT NULL, 
                updated_at TIMESTAMP(0) DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'CREDITS' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE CREDITS ADD CONSTRAINT CREDITS_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE CREDITS_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER CREDITS_AI_PK BEFORE INSERT ON CREDITS FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT CREDITS_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT CREDITS_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'CREDITS_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT CREDITS_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
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