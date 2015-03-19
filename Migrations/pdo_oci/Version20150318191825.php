<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_oci;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/03/18 07:18:26
 */
class Version20150318191825 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre_order_workspace (
                id NUMBER(10) NOT NULL, 
                paymentInstruction_id NUMBER(10) DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'FORMALIBRE_ORDER_WORKSPACE' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE FORMALIBRE_ORDER_WORKSPACE ADD CONSTRAINT FORMALIBRE_ORDER_WORKSPACE_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE FORMALIBRE_ORDER_WORKSPACE_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER FORMALIBRE_ORDER_WORKSPACE_AI_PK BEFORE INSERT ON FORMALIBRE_ORDER_WORKSPACE FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT FORMALIBRE_ORDER_WORKSPACE_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT FORMALIBRE_ORDER_WORKSPACE_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'FORMALIBRE_ORDER_WORKSPACE_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT FORMALIBRE_ORDER_WORKSPACE_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_F259060AFD913E4D ON formalibre_order_workspace (paymentInstruction_id)
        ");
        $this->addSql("
            CREATE TABLE formalibre_product (
                id NUMBER(10) NOT NULL, 
                code VARCHAR2(255) NOT NULL, 
                type VARCHAR2(255) NOT NULL, 
                details CLOB DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'FORMALIBRE_PRODUCT' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE FORMALIBRE_PRODUCT ADD CONSTRAINT FORMALIBRE_PRODUCT_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE FORMALIBRE_PRODUCT_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER FORMALIBRE_PRODUCT_AI_PK BEFORE INSERT ON FORMALIBRE_PRODUCT FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT FORMALIBRE_PRODUCT_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT FORMALIBRE_PRODUCT_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'FORMALIBRE_PRODUCT_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT FORMALIBRE_PRODUCT_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_C38DA1BB77153098 ON formalibre_product (code)
        ");
        $this->addSql("
            COMMENT ON COLUMN formalibre_product.details IS '(DC2Type:json_array)'
        ");
        $this->addSql("
            CREATE TABLE formalibre_workspace_product (
                id NUMBER(10) NOT NULL, 
                owner_id NUMBER(10) NOT NULL, 
                product_id NUMBER(10) DEFAULT NULL, 
                code VARCHAR2(256) NOT NULL, 
                name VARCHAR2(256) NOT NULL, 
                end_date TIMESTAMP(0) DEFAULT NULL, 
                maxSize VARCHAR2(255) NOT NULL, 
                maxUser NUMBER(10) NOT NULL, 
                maxRes NUMBER(10) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'FORMALIBRE_WORKSPACE_PRODUCT' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE FORMALIBRE_WORKSPACE_PRODUCT ADD CONSTRAINT FORMALIBRE_WORKSPACE_PRODUCT_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE FORMALIBRE_WORKSPACE_PRODUCT_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER FORMALIBRE_WORKSPACE_PRODUCT_AI_PK BEFORE INSERT ON FORMALIBRE_WORKSPACE_PRODUCT FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT FORMALIBRE_WORKSPACE_PRODUCT_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT FORMALIBRE_WORKSPACE_PRODUCT_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'FORMALIBRE_WORKSPACE_PRODUCT_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT FORMALIBRE_WORKSPACE_PRODUCT_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE INDEX IDX_98490B4A7E3C61F9 ON formalibre_workspace_product (owner_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_98490B4A4584665A ON formalibre_workspace_product (product_id)
        ");
        $this->addSql("
            ALTER TABLE formalibre_order_workspace 
            ADD CONSTRAINT FK_F259060AFD913E4D FOREIGN KEY (paymentInstruction_id) 
            REFERENCES payment_instructions (id)
        ");
        $this->addSql("
            ALTER TABLE formalibre_workspace_product 
            ADD CONSTRAINT FK_98490B4A7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE formalibre_workspace_product 
            ADD CONSTRAINT FK_98490B4A4584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre_product (id) 
            ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre_workspace_product 
            DROP CONSTRAINT FK_98490B4A4584665A
        ");
        $this->addSql("
            DROP TABLE formalibre_order_workspace
        ");
        $this->addSql("
            DROP TABLE formalibre_product
        ");
        $this->addSql("
            DROP TABLE formalibre_workspace_product
        ");
    }
}