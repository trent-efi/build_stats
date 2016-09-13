/******************************************************************************
 * Drop and Create DataBase
 *****************************************************************************/
DROP DATABASE IF EXISTS build_stats;
CREATE DATABASE build_stats;

USE build_stats;

/******************************************************************************
 * CREATE request table
 *****************************************************************************/
CREATE TABLE requests(
    id            INTEGER      NOT NULL AUTO_INCREMENT,
    build_date    INTEGER      NOT NULL,
    request_id    INTEGER      NOT NULL,
    region_id     INTEGER      NOT NULL,
    code_base_id  INTEGER      NOT NULL,
    os_id         INTEGER      NOT NULL,
    build_time    INTEGER      NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (region_id)    REFERENCES region(id),
    FOREIGN KEY (code_base_id) REFERENCES code_base(id),
    FOREIGN KEY (os_id)        REFERENCES os(id)
);


/******************************************************************************
 * region data
 *****************************************************************************/
CREATE TABLE region(
    id            INTEGER      NOT NULL AUTO_INCREMENT,
    name          VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)    
);

INSERT INTO region (name) VALUES 
    ('Fremont'),
    ('VC_Fremont'),
    ('IDC'),
    ('VC_IDC');

SELECT * from region;

/******************************************************************************
 * code_base data
 *****************************************************************************/
CREATE TABLE code_base(
    id            INTEGER      NOT NULL AUTO_INCREMENT,
    name          VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
);

INSERT INTO code_base (name) VALUES 
    ('flame10'),
    ('flame20'),
    ('flame30'),
    ('flame40'),
    ('flame45'),
    ('flame50'),
    ('flame60'),
    ('emerald10'),
    ('barracuda10'),
    ('ice21'),
    ('ice22'),
    ('ice23'),
    ('tourmaline10'),
    ('sky21'),
    ('sky22'),
    ('sky23');

SELECT * FROM code_base;

/******************************************************************************
 * os data
 *****************************************************************************/
CREATE TABLE os(
    id            INTEGER      NOT NULL AUTO_INCREMENT,
    name          VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)    
);

INSERT INTO os (name) VALUES 
  ('windows'),
  ('linux');

SELECT * FROM os;

/******************************************************************************
 *
 *****************************************************************************/
CREATE TABLE daily_stats(
    id            INTEGER      NOT NULL AUTO_INCREMENT,
    build_date    INTEGER      NOT NULL,
    region_id     INTEGER      NOT NULL,
    code_base_id  INTEGER      NOT NULL,
    os_id         INTEGER      NOT NULL,
    total         INTEGER,
    median        INTEGER,
    mean          INTEGER,
    PRIMARY KEY (id),
    FOREIGN KEY (region_id)    REFERENCES region(id),
    FOREIGN KEY (code_base_id) REFERENCES code_base(id),
    FOREIGN KEY (os_id)        REFERENCES os(id),
    FOREIGN KEY (build_date)   REFERENCES requests(build_date)
);

/******************************************************************************
 *
 *****************************************************************************/
CREATE TABLE weekly_stats(
    id            INTEGER      NOT NULL AUTO_INCREMENT,
    start_date    INTEGER      NOT NULL,
    end_date      INTEGER      NOT NULL,
    region_id     INTEGER      NOT NULL,
    code_base_id  INTEGER      NOT NULL,
    os_id         INTEGER      NOT NULL,
    total         INTEGER,
    median        INTEGER,
    mean          INTEGER,
    PRIMARY KEY (id),
    FOREIGN KEY (region_id)    REFERENCES region(id),
    FOREIGN KEY (code_base_id) REFERENCES code_base(id),
    FOREIGN KEY (os_id)        REFERENCES os(id)
);



/******************************************************************************
 * INDEX:
 *****************************************************************************/
CREATE INDEX build_stats_index
ON requests (build_date, region_id, code_base_id, os_id);
