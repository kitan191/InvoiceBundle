<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_oci;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/03/25 11:41:21
 */
class Version20150325114120 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre__product (
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
            WHERE TABLE_NAME = 'FORMALIBRE__PRODUCT' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE FORMALIBRE__PRODUCT ADD CONSTRAINT FORMALIBRE__PRODUCT_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE FORMALIBRE__PRODUCT_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER FORMALIBRE__PRODUCT_AI_PK BEFORE INSERT ON FORMALIBRE__PRODUCT FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT FORMALIBRE__PRODUCT_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT FORMALIBRE__PRODUCT_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'FORMALIBRE__PRODUCT_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT FORMALIBRE__PRODUCT_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_53C6972477153098 ON formalibre__product (code)
        ");
        $this->addSql("
            COMMENT ON COLUMN formalibre__product.details IS '(DC2Type:json_array)'
        ");
        $this->addSql("
            CREATE TABLE formalibre__order (
                id NUMBER(10) NOT NULL, 
                product_id NUMBER(10) DEFAULT NULL, 
                price_solution_id NUMBER(10) DEFAULT NULL, 
                paymentInstruction_id NUMBER(10) DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'FORMALIBRE__ORDER' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE FORMALIBRE__ORDER ADD CONSTRAINT FORMALIBRE__ORDER_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE FORMALIBRE__ORDER_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER FORMALIBRE__ORDER_AI_PK BEFORE INSERT ON FORMALIBRE__ORDER FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT FORMALIBRE__ORDER_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT FORMALIBRE__ORDER_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'FORMALIBRE__ORDER_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT FORMALIBRE__ORDER_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_62CE339EFD913E4D ON formalibre__order (paymentInstruction_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_62CE339E4584665A ON formalibre__order (product_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_62CE339E1BD2AD95 ON formalibre__order (price_solution_id)
        ");
        $this->addSql("
            CREATE TABLE formalibre__price_solution (
                id NUMBER(10) NOT NULL, 
                product_id NUMBER(10) DEFAULT NULL, 
                monthDuration NUMBER(10) NOT NULL, 
                price DOUBLE PRECISION NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'FORMALIBRE__PRICE_SOLUTION' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE FORMALIBRE__PRICE_SOLUTION ADD CONSTRAINT FORMALIBRE__PRICE_SOLUTION_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE FORMALIBRE__PRICE_SOLUTION_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER FORMALIBRE__PRICE_SOLUTION_AI_PK BEFORE INSERT ON FORMALIBRE__PRICE_SOLUTION FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT FORMALIBRE__PRICE_SOLUTION_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT FORMALIBRE__PRICE_SOLUTION_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'FORMALIBRE__PRICE_SOLUTION_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT FORMALIBRE__PRICE_SOLUTION_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE INDEX IDX_E2B632A84584665A ON formalibre__price_solution (product_id)
        ");
        $this->addSql("
            CREATE TABLE formalibre__shared_workspace (
                id NUMBER(10) NOT NULL, 
                owner_id NUMBER(10) NOT NULL, 
                product_id NUMBER(10) DEFAULT NULL, 
                remoteId NUMBER(10) NOT NULL, 
                end_date TIMESTAMP(0) DEFAULT NULL, 
                maxSize VARCHAR2(255) NOT NULL, 
                maxUser NUMBER(10) NOT NULL, 
                maxRes NUMBER(10) NOT NULL, 
                autoSubscribe NUMBER(1) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'FORMALIBRE__SHARED_WORKSPACE' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE FORMALIBRE__SHARED_WORKSPACE ADD CONSTRAINT FORMALIBRE__SHARED_WORKSPACE_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE FORMALIBRE__SHARED_WORKSPACE_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER FORMALIBRE__SHARED_WORKSPACE_AI_PK BEFORE INSERT ON FORMALIBRE__SHARED_WORKSPACE FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT FORMALIBRE__SHARED_WORKSPACE_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT FORMALIBRE__SHARED_WORKSPACE_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'FORMALIBRE__SHARED_WORKSPACE_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT FORMALIBRE__SHARED_WORKSPACE_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE INDEX IDX_1559C4C27E3C61F9 ON formalibre__shared_workspace (owner_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_1559C4C24584665A ON formalibre__shared_workspace (product_id)
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339EFD913E4D FOREIGN KEY (paymentInstruction_id) 
            REFERENCES payment_instructions (id)
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339E4584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre__product (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339E1BD2AD95 FOREIGN KEY (price_solution_id) 
            REFERENCES formalibre__price_solution (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__price_solution 
            ADD CONSTRAINT FK_E2B632A84584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre__product (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__shared_workspace 
            ADD CONSTRAINT FK_1559C4C27E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE formalibre__shared_workspace 
            ADD CONSTRAINT FK_1559C4C24584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre__product (id) 
            ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre__order 
            DROP CONSTRAINT FK_62CE339E4584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__price_solution 
            DROP CONSTRAINT FK_E2B632A84584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__shared_workspace 
            DROP CONSTRAINT FK_1559C4C24584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            DROP CONSTRAINT FK_62CE339E1BD2AD95
        ");
        $this->addSql("
            DROP TABLE formalibre__product
        ");
        $this->addSql("
            DROP TABLE formalibre__order
        ");
        $this->addSql("
            DROP TABLE formalibre__price_solution
        ");
        $this->addSql("
            DROP TABLE formalibre__shared_workspace
        ");
    }
}