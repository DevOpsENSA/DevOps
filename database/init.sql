-- Relational schema initialization for plateformEtudiant (PostgreSQL)

-- Drop in dependency order so script can be rerun safely.
DROP TABLE IF EXISTS cours CASCADE;
DROP TABLE IF EXISTS comptes CASCADE;
DROP TABLE IF EXISTS semestres CASCADE;
DROP TABLE IF EXISTS filieres CASCADE;
DROP TABLE IF EXISTS etudiants CASCADE;
DROP TABLE IF EXISTS ecoles CASCADE;

CREATE TABLE ecoles (
    "idEcole" BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    "nomEcole" VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE filieres (
    "idFiliere" BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    "nomFiliere" VARCHAR(255) NOT NULL,
    "idEcole" BIGINT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT filieres_idEcole_fk
        FOREIGN KEY ("idEcole")
        REFERENCES ecoles("idEcole")
        ON DELETE CASCADE
);

CREATE TABLE semestres (
    "idSemestre" BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    "idEcole" BIGINT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT semestres_idEcole_fk
        FOREIGN KEY ("idEcole")
        REFERENCES ecoles("idEcole")
        ON DELETE CASCADE
);

CREATE TABLE etudiants (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE comptes (
    mail VARCHAR(255) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    "idEtudiant" BIGINT NOT NULL,
    status VARCHAR(20) NOT NULL CHECK (status IN ('admin', 'student')),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT comptes_idEtudiant_fk
        FOREIGN KEY ("idEtudiant")
        REFERENCES etudiants(id)
        ON DELETE CASCADE
);

CREATE TABLE cours (
    "idCours" BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    "idEtudiant" BIGINT NOT NULL,
    "idFiliere" BIGINT NOT NULL,
    "idSemestre" BIGINT NOT NULL,
    file_path VARCHAR(255) NULL,
    lesson_url VARCHAR(2048) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT cours_idEtudiant_fk
        FOREIGN KEY ("idEtudiant")
        REFERENCES etudiants(id)
        ON DELETE CASCADE,
    CONSTRAINT cours_idFiliere_fk
        FOREIGN KEY ("idFiliere")
        REFERENCES filieres("idFiliere")
        ON DELETE CASCADE,
    CONSTRAINT cours_idSemestre_fk
        FOREIGN KEY ("idSemestre")
        REFERENCES semestres("idSemestre")
        ON DELETE CASCADE
);

-- ===== Default seed data =====
-- One school, six semesters, ready for lesson uploads.
INSERT INTO ecoles ("nomEcole", created_at, updated_at)
VALUES ('ENSATE', NOW(), NOW());

INSERT INTO semestres ("idEcole", created_at, updated_at)
VALUES (1, NOW(), NOW()),
       (1, NOW(), NOW()),
       (1, NOW(), NOW()),
       (1, NOW(), NOW()),
       (1, NOW(), NOW()),
       (1, NOW(), NOW());

