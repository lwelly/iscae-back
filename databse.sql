-- ╔══════════════════════════════════════════════════════════════════╗
-- ║   ISCAE RECLAMATION MANAGEMENT SYSTEM                           ║
-- ║   Script SQL Complet — Production Ready                         ║
-- ║   MySQL 8.0+ | utf8mb4 | InnoDB                                 ║
-- ║   Admin     : admin@iscae.mr                                    ║
-- ║   Password  : i@s@c@a@e@  (bcrypt cost=12)                     ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ============================================================
-- 0. CRÉATION BASE DE DONNÉES
-- ============================================================
DROP DATABASE IF EXISTS iscae_reclamations;

CREATE DATABASE iscae_reclamations
    CHARACTER SET utf8mb4
    COLLATE       utf8mb4_unicode_ci;

USE iscae_reclamations;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS  = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,
                ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ╔══════════════════════════════════════════════════════════════════╗
-- ║   GROUPE 1 — STRUCTURE ACADÉMIQUE                               ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ------------------------------------------------------------
-- TABLE: departments
-- ------------------------------------------------------------
CREATE TABLE departments (
    id          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name        VARCHAR(150)     NOT NULL,
    code        VARCHAR(20)      NOT NULL,
    head_name   VARCHAR(200)     NULL,
    head_email  VARCHAR(255)     NULL,
    is_active   TINYINT(1)       NOT NULL DEFAULT 1,
    created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_dept_code   (code),
    INDEX       idx_dept_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Départements académiques';

-- ------------------------------------------------------------
-- TABLE: filieres
-- ------------------------------------------------------------
CREATE TABLE filieres (
    id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    department_id BIGINT UNSIGNED  NOT NULL,
    name          VARCHAR(150)     NOT NULL,
    code          VARCHAR(30)      NOT NULL,
    description   TEXT             NULL,
    is_active     TINYINT(1)       NOT NULL DEFAULT 1,
    created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_filiere_code        (code),
    INDEX       idx_filiere_dept        (department_id),
    INDEX       idx_filiere_active      (is_active),
    INDEX       idx_filiere_dept_active (department_id, is_active),
    CONSTRAINT fk_filiere_dept
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Filières par département';

-- ------------------------------------------------------------
-- TABLE: niveaux
-- ------------------------------------------------------------
CREATE TABLE niveaux (
    id          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    code        VARCHAR(10)       NOT NULL COMMENT 'L1 | L2 | L3',
    label       VARCHAR(50)       NOT NULL,
    order_index TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    created_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_niveau_code  (code),
    INDEX       idx_niveau_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Niveaux L1 L2 L3';

-- ------------------------------------------------------------
-- TABLE: semestres
-- L1→S1,S2 | L2→S3,S4 | L3→S5,S6
-- ------------------------------------------------------------
CREATE TABLE semestres (
    id            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    niveau_id     BIGINT UNSIGNED   NOT NULL,
    code          VARCHAR(10)       NOT NULL,
    label         VARCHAR(50)       NOT NULL,
    order_index   TINYINT UNSIGNED  NOT NULL,
    is_open       TINYINT(1)        NOT NULL DEFAULT 0,
    open_at       TIMESTAMP         NULL,
    close_at      TIMESTAMP         NULL,
    academic_year VARCHAR(10)       NOT NULL,
    created_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_sem_code_year       (code, academic_year),
    INDEX       idx_sem_niveau          (niveau_id),
    INDEX       idx_sem_open            (is_open),
    INDEX       idx_sem_year            (academic_year),
    INDEX       idx_sem_open_year       (is_open, academic_year),
    INDEX       idx_sem_niveau_year     (niveau_id, academic_year),
    CONSTRAINT fk_sem_niveau
        FOREIGN KEY (niveau_id) REFERENCES niveaux(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_sem_dates
        CHECK (close_at IS NULL OR open_at IS NULL OR close_at > open_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Semestres S1-S6';

-- ------------------------------------------------------------
-- TABLE: modules
-- ------------------------------------------------------------
CREATE TABLE modules (
    id          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    filiere_id  BIGINT UNSIGNED   NOT NULL,
    semestre_id BIGINT UNSIGNED   NOT NULL,
    code        VARCHAR(30)       NOT NULL,
    name        VARCHAR(200)      NOT NULL,
    coefficient DECIMAL(4,2)      NOT NULL DEFAULT 1.00,
    credits     TINYINT UNSIGNED  NOT NULL DEFAULT 3,
    is_active   TINYINT(1)        NOT NULL DEFAULT 1,
    created_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_module_code_fil_sem  (code, filiere_id, semestre_id),
    INDEX       idx_module_filiere       (filiere_id),
    INDEX       idx_module_semestre      (semestre_id),
    INDEX       idx_module_active        (is_active),
    INDEX       idx_module_fil_sem       (filiere_id, semestre_id),
    INDEX       idx_module_fil_sem_act   (filiere_id, semestre_id, is_active),
    FULLTEXT KEY ft_module_name          (name),
    CONSTRAINT fk_module_filiere
        FOREIGN KEY (filiere_id)  REFERENCES filieres(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_module_semestre
        FOREIGN KEY (semestre_id) REFERENCES semestres(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_module_coef
        CHECK (coefficient > 0 AND coefficient <= 10),
    CONSTRAINT chk_module_credits
        CHECK (credits > 0 AND credits <= 30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Matières par filière et semestre';


-- ╔══════════════════════════════════════════════════════════════════╗
-- ║   GROUPE 2 — UTILISATEURS & AUTHENTIFICATION                    ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ------------------------------------------------------------
-- TABLE: users  (UN SEUL LOGIN pour tout le monde)
-- ------------------------------------------------------------
CREATE TABLE users (
    id                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    role                ENUM('student','admin') NOT NULL,
    login_identifier    VARCHAR(100)      NOT NULL
                        COMMENT 'matricule pour étudiant | email pour admin',
    email               VARCHAR(255)      NOT NULL,
    password            VARCHAR(255)      NOT NULL
                        COMMENT 'bcrypt cost=12 — JAMAIS en clair',
    password_changed_at TIMESTAMP         NULL,
    is_active           TINYINT(1)        NOT NULL DEFAULT 1,
    is_verified         TINYINT(1)        NOT NULL DEFAULT 0,
    verified_at         TIMESTAMP         NULL,
    failed_login_count  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until        TIMESTAMP         NULL,
    last_login_at       TIMESTAMP         NULL,
    last_login_ip       VARCHAR(45)       NULL,
    deleted_at          TIMESTAMP         NULL,
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                   ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_user_login        (login_identifier),
    UNIQUE  KEY uq_user_email        (email),
    INDEX       idx_user_role         (role),
    INDEX       idx_user_active       (is_active),
    INDEX       idx_user_deleted      (deleted_at),
    INDEX       idx_user_locked       (locked_until),
    INDEX       idx_user_login_role   (login_identifier, role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Auth unifiée — UN SEUL endpoint login';

-- ------------------------------------------------------------
-- TABLE: students_preloaded
-- SOURCE DE VÉRITÉ pour valider l'inscription étudiant
-- ------------------------------------------------------------
CREATE TABLE students_preloaded (
    id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    matricule       VARCHAR(30)      NOT NULL,
    nni             VARCHAR(30)      NOT NULL,
    nom             VARCHAR(100)     NOT NULL,
    prenom          VARCHAR(100)     NOT NULL,
    email           VARCHAR(255)     NOT NULL,
    filiere_id      BIGINT UNSIGNED  NOT NULL,
    niveau_id       BIGINT UNSIGNED  NOT NULL,
    academic_year   VARCHAR(10)      NOT NULL,
    is_registered   TINYINT(1)       NOT NULL DEFAULT 0,
    registered_at   TIMESTAMP        NULL,
    imported_by     BIGINT UNSIGNED  NULL,
    import_batch    VARCHAR(50)      NULL,
    import_filename VARCHAR(255)     NULL,
    created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                              ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_preload_mat_nni  (matricule, nni),
    UNIQUE  KEY uq_preload_mat      (matricule),
    INDEX       idx_preload_email    (email),
    INDEX       idx_preload_filiere  (filiere_id),
    INDEX       idx_preload_niveau   (niveau_id),
    INDEX       idx_preload_year     (academic_year),
    INDEX       idx_preload_reg      (is_registered),
    INDEX       idx_preload_verif    (matricule, nni, email, is_registered),
    CONSTRAINT fk_preload_filiere
        FOREIGN KEY (filiere_id) REFERENCES filieres(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_preload_niveau
        FOREIGN KEY (niveau_id)  REFERENCES niveaux(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Données préchargées admin — validation inscription';

-- ------------------------------------------------------------
-- TABLE: students
-- ------------------------------------------------------------
CREATE TABLE students (
    id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED  NOT NULL,
    preloaded_id    BIGINT UNSIGNED  NOT NULL,
    matricule       VARCHAR(30)      NOT NULL,
    nni             VARCHAR(30)      NOT NULL,
    nom             VARCHAR(100)     NOT NULL,
    prenom          VARCHAR(100)     NOT NULL,
    email           VARCHAR(255)     NOT NULL,
    filiere_id      BIGINT UNSIGNED  NOT NULL,
    niveau_id       BIGINT UNSIGNED  NOT NULL,
    academic_year   VARCHAR(10)      NOT NULL,
    phone           VARCHAR(20)      NULL,
    date_naissance  DATE             NULL,
    lieu_naissance  VARCHAR(100)     NULL,
    nationalite     VARCHAR(50)      NULL DEFAULT 'Mauritanienne',
    adresse         TEXT             NULL,
    photo_path      VARCHAR(500)     NULL,
    status          ENUM('active','suspended','graduated','withdrawn')
                    NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                              ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_student_user       (user_id),
    UNIQUE  KEY uq_student_preloaded  (preloaded_id),
    UNIQUE  KEY uq_student_matricule  (matricule),
    INDEX       idx_student_filiere    (filiere_id),
    INDEX       idx_student_niveau     (niveau_id),
    INDEX       idx_student_status     (status),
    INDEX       idx_student_year       (academic_year),
    INDEX       idx_student_fil_niv    (filiere_id, niveau_id),
    INDEX       idx_student_fil_niv_yr (filiere_id, niveau_id, academic_year),
    FULLTEXT KEY ft_student_name       (nom, prenom),
    CONSTRAINT fk_student_user
        FOREIGN KEY (user_id)      REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_student_preloaded
        FOREIGN KEY (preloaded_id) REFERENCES students_preloaded(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_student_filiere
        FOREIGN KEY (filiere_id)   REFERENCES filieres(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_student_niveau
        FOREIGN KEY (niveau_id)    REFERENCES niveaux(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Profil complet étudiant';

-- ------------------------------------------------------------
-- TABLE: admins
-- ------------------------------------------------------------
CREATE TABLE admins (
    id                     BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id                BIGINT UNSIGNED   NOT NULL,
    nom                    VARCHAR(100)      NOT NULL,
    prenom                 VARCHAR(100)      NOT NULL,
    email                  VARCHAR(255)      NOT NULL,
    phone                  VARCHAR(20)       NULL,
    role_label             ENUM('super_admin','admin','department_head','staff')
                           NOT NULL DEFAULT 'admin',
    department_id          BIGINT UNSIGNED   NULL,
    two_factor_enabled     TINYINT(1)        NOT NULL DEFAULT 1,
    require_2fa_after_days SMALLINT UNSIGNED NOT NULL DEFAULT 7,
    last_2fa_verified_at   TIMESTAMP         NULL,
    photo_path             VARCHAR(500)      NULL,
    created_at             TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                       ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_admin_user       (user_id),
    UNIQUE  KEY uq_admin_email      (email),
    INDEX       idx_admin_role       (role_label),
    INDEX       idx_admin_dept       (department_id),
    INDEX       idx_admin_role_dept  (role_label, department_id),
    CONSTRAINT fk_admin_user
        FOREIGN KEY (user_id)       REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admin_dept
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Profil admins et chefs de département';


-- ╔══════════════════════════════════════════════════════════════════╗
-- ║   GROUPE 3 — SÉCURITÉ & SESSIONS                                ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ------------------------------------------------------------
-- TABLE: otp_codes
-- ------------------------------------------------------------
CREATE TABLE otp_codes (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       BIGINT UNSIGNED NULL,
    email         VARCHAR(255) NULL,
    preloaded_id  BIGINT UNSIGNED NULL,

    type          ENUM('registration','password_reset','admin_2fa','email_verification') NOT NULL,

    code_hash     VARCHAR(255) NOT NULL,
    attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts  TINYINT UNSIGNED NOT NULL DEFAULT 5,

    expires_at    TIMESTAMP NOT NULL,
    is_used       TINYINT(1) NOT NULL DEFAULT 0,
    used_at       TIMESTAMP NULL,

    ip_address    VARCHAR(45) NULL,
    user_agent    TEXT NULL,

    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_otp_user_type    (user_id, type),
    INDEX idx_otp_email_type   (email, type),
    INDEX idx_otp_preloaded    (preloaded_id),
    INDEX idx_otp_expires      (expires_at),
    INDEX idx_otp_used         (is_used),

    CONSTRAINT fk_otp_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_otp_preloaded
        FOREIGN KEY (preloaded_id) REFERENCES students_preloaded(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: user_devices
-- ------------------------------------------------------------
CREATE TABLE user_devices (
    id                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id             BIGINT UNSIGNED   NOT NULL,
    device_fingerprint  VARCHAR(255)      NOT NULL,
    device_name         VARCHAR(200)      NULL,
    device_type         ENUM('web','mobile','tablet','unknown')
                        NOT NULL DEFAULT 'web',
    browser             VARCHAR(100)      NULL,
    os                  VARCHAR(100)      NULL,
    is_trusted          TINYINT(1)        NOT NULL DEFAULT 0,
    trusted_at          TIMESTAMP         NULL,
    trusted_until       TIMESTAMP         NULL,
    last_ip             VARCHAR(45)       NULL,
    device_token_hash   VARCHAR(255)      NULL,
    last_seen_at        TIMESTAMP         NULL,
    first_seen_at       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                   ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_device_user_fp    (user_id, device_fingerprint),
    INDEX       idx_device_user       (user_id),
    INDEX       idx_device_fp         (device_fingerprint),
    INDEX       idx_device_trusted    (is_trusted),
    INDEX       idx_device_trust_exp  (trusted_until),
    CONSTRAINT fk_device_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Appareils de confiance — 2FA admin';

-- ------------------------------------------------------------
-- TABLE: user_sessions
-- ------------------------------------------------------------
CREATE TABLE user_sessions (
    id                 BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id            BIGINT UNSIGNED  NOT NULL,
    device_id          BIGINT UNSIGNED  NULL,
    token_hash         VARCHAR(64)      NULL,
    ip_address         VARCHAR(45)      NOT NULL,
    user_agent         TEXT             NULL,
    country            VARCHAR(5)       NULL,
    city               VARCHAR(100)     NULL,
    is_active          TINYINT(1)       NOT NULL DEFAULT 1,
    last_activity_at   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at         TIMESTAMP        NULL,
    terminated_at      TIMESTAMP        NULL,
    termination_reason ENUM('logout','expired','revoked','security','timeout') NULL,
    created_at         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sess_user        (user_id),
    INDEX idx_sess_device      (device_id),
    INDEX idx_sess_active      (is_active),
    INDEX idx_sess_token       (token_hash),
    INDEX idx_sess_ip          (ip_address),
    INDEX idx_sess_activity    (last_activity_at),
    INDEX idx_sess_user_active (user_id, is_active),
    CONSTRAINT fk_sess_user
        FOREIGN KEY (user_id)   REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_sess_device
        FOREIGN KEY (device_id) REFERENCES user_devices(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sessions actives';

-- ------------------------------------------------------------
-- TABLE: login_attempts
-- ------------------------------------------------------------
CREATE TABLE login_attempts (
    id                 BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    login_identifier   VARCHAR(100)     NOT NULL,
    ip_address         VARCHAR(45)      NOT NULL,
    user_agent         TEXT             NULL,
    was_successful     TINYINT(1)       NOT NULL DEFAULT 0,
    failure_reason     ENUM(
                           'invalid_credentials','account_locked',
                           'account_inactive','account_unverified',
                           'otp_required','otp_invalid','too_many_attempts'
                       ) NULL,
    device_fingerprint VARCHAR(255)     NULL,
    attempted_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_att_identifier   (login_identifier),
    INDEX idx_att_ip            (ip_address),
    INDEX idx_att_time          (attempted_at),
    INDEX idx_att_success       (was_successful),
    INDEX idx_att_ip_time       (ip_address,       attempted_at),
    INDEX idx_att_ident_time    (login_identifier, attempted_at),
    INDEX idx_att_ip_fail_time  (ip_address, was_successful, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tentatives connexion — anti brute-force';


-- ╔══════════════════════════════════════════════════════════════════╗
-- ║   GROUPE 4 — NOTES                                              ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ------------------------------------------------------------
-- TABLE: notes
-- ------------------------------------------------------------
CREATE TABLE notes (
    id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    student_id      BIGINT UNSIGNED  NOT NULL,
    module_id       BIGINT UNSIGNED  NOT NULL,
    semestre_id     BIGINT UNSIGNED  NOT NULL,
    academic_year   VARCHAR(10)      NOT NULL,
    note_controle   DECIMAL(5,2)     NULL,
    note_examen     DECIMAL(5,2)     NULL,
    note_rattrapage DECIMAL(5,2)     NULL,
    note_finale     DECIMAL(5,2)     NULL,
    is_published    TINYINT(1)       NOT NULL DEFAULT 0,
    published_at    TIMESTAMP        NULL,
    published_by    BIGINT UNSIGNED  NULL,
    uploaded_by     BIGINT UNSIGNED  NULL,
    created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                              ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_note_stu_mod_yr     (student_id, module_id, academic_year),
    INDEX       idx_note_student        (student_id),
    INDEX       idx_note_module         (module_id),
    INDEX       idx_note_semestre       (semestre_id),
    INDEX       idx_note_year           (academic_year),
    INDEX       idx_note_published      (is_published),
    INDEX       idx_note_stu_sem_pub    (student_id, semestre_id, is_published),
    CONSTRAINT fk_note_student
        FOREIGN KEY (student_id)  REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_note_module
        FOREIGN KEY (module_id)   REFERENCES modules(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_note_semestre
        FOREIGN KEY (semestre_id) REFERENCES semestres(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_note_controle
        CHECK (note_controle   IS NULL OR note_controle   BETWEEN 0 AND 20),
    CONSTRAINT chk_note_examen
        CHECK (note_examen     IS NULL OR note_examen     BETWEEN 0 AND 20),
    CONSTRAINT chk_note_rattrapage
        CHECK (note_rattrapage IS NULL OR note_rattrapage BETWEEN 0 AND 20),
    CONSTRAINT chk_note_finale
        CHECK (note_finale     IS NULL OR note_finale     BETWEEN 0 AND 20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notes académiques';

-- ------------------------------------------------------------
-- TABLE: notes_history
-- ------------------------------------------------------------
CREATE TABLE notes_history (
    id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    note_id         BIGINT UNSIGNED  NOT NULL,
    changed_by      BIGINT UNSIGNED  NOT NULL,
    old_controle    DECIMAL(5,2)     NULL,
    old_examen      DECIMAL(5,2)     NULL,
    old_rattrapage  DECIMAL(5,2)     NULL,
    old_finale      DECIMAL(5,2)     NULL,
    new_controle    DECIMAL(5,2)     NULL,
    new_examen      DECIMAL(5,2)     NULL,
    new_rattrapage  DECIMAL(5,2)     NULL,
    new_finale      DECIMAL(5,2)     NULL,
    reason          TEXT             NULL,
    ip_address      VARCHAR(45)      NULL,
    created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_nhist_note       (note_id),
    INDEX idx_nhist_changed_by (changed_by),
    INDEX idx_nhist_created    (created_at),
    CONSTRAINT fk_nhist_note
        FOREIGN KEY (note_id)    REFERENCES notes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_nhist_changer
        FOREIGN KEY (changed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historique modifications notes';


-- ╔══════════════════════════════════════════════════════════════════╗
-- ║   GROUPE 5 — RÉCLAMATIONS                                       ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ------------------------------------------------------------
-- TABLE: reclamations
-- ------------------------------------------------------------
CREATE TABLE reclamations (
    id                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    student_id           BIGINT UNSIGNED  NOT NULL,
    module_id            BIGINT UNSIGNED  NOT NULL,
    semestre_id          BIGINT UNSIGNED  NOT NULL,
    note_id              BIGINT UNSIGNED  NULL,
    type                 ENUM('controle','examen','rattrapage') NOT NULL,
    note_actuelle        DECIMAL(5,2)     NOT NULL,
    note_reclamee        DECIMAL(5,2)     NULL
                         COMMENT 'Obligatoire seulement pour controle',
    justification        TEXT             NOT NULL,
    status               ENUM('submitted','received','in_review',
                               'resolved','rejected','escalated')
                         NOT NULL DEFAULT 'submitted',
    admin_response       TEXT             NULL,
    responded_by         BIGINT UNSIGNED  NULL,
    responded_at         TIMESTAMP        NULL,
    escalated_to         BIGINT UNSIGNED  NULL,
    escalated_at         TIMESTAMP        NULL,
    escalation_reason    TEXT             NULL,
    meeting_scheduled_at TIMESTAMP        NULL,
    meeting_notes        TEXT             NULL,
    academic_year        VARCHAR(10)      NOT NULL,
    reference_number     VARCHAR(30)      NOT NULL,
    created_at           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                   ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_reclam_ref           (reference_number),
    UNIQUE  KEY uq_reclam_no_dup        (student_id, module_id, type, academic_year),
    INDEX       idx_reclam_student       (student_id),
    INDEX       idx_reclam_module        (module_id),
    INDEX       idx_reclam_semestre      (semestre_id),
    INDEX       idx_reclam_status        (status),
    INDEX       idx_reclam_type          (type),
    INDEX       idx_reclam_year          (academic_year),
    INDEX       idx_reclam_status_year   (status, academic_year),
    INDEX       idx_reclam_student_year  (student_id, academic_year),
    CONSTRAINT fk_reclam_student
        FOREIGN KEY (student_id)  REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reclam_module
        FOREIGN KEY (module_id)   REFERENCES modules(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reclam_semestre
        FOREIGN KEY (semestre_id) REFERENCES semestres(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reclam_note
        FOREIGN KEY (note_id)     REFERENCES notes(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_reclam_note_reclamee
        CHECK (
            (type = 'controle'  AND note_reclamee IS NOT NULL)
            OR
            (type != 'controle' AND note_reclamee IS NULL)
        ),
    CONSTRAINT chk_reclam_note_actuelle
        CHECK (note_actuelle BETWEEN 0 AND 20),
    CONSTRAINT chk_reclam_note_reclamee_range
        CHECK (note_reclamee IS NULL OR note_reclamee BETWEEN 0 AND 20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Réclamations académiques';

-- ------------------------------------------------------------
-- TABLE: reclamation_history
-- ------------------------------------------------------------
CREATE TABLE reclamation_history (
    id             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    reclamation_id BIGINT UNSIGNED  NOT NULL,
    changed_by     BIGINT UNSIGNED  NOT NULL,
    old_status     ENUM('submitted','received','in_review',
                        'resolved','rejected','escalated') NULL,
    new_status     ENUM('submitted','received','in_review',
                        'resolved','rejected','escalated') NOT NULL,
    comment        TEXT             NULL,
    ip_address     VARCHAR(45)      NULL,
    created_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_rhist_reclamation (reclamation_id),
    INDEX idx_rhist_changed_by  (changed_by),
    INDEX idx_rhist_new_status  (new_status),
    INDEX idx_rhist_created     (created_at),
    CONSTRAINT fk_rhist_reclamation
        FOREIGN KEY (reclamation_id) REFERENCES reclamations(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_rhist_changer
        FOREIGN KEY (changed_by)     REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historique statuts réclamations';

-- ------------------------------------------------------------
-- TABLE: reclamation_attachments
-- ------------------------------------------------------------
CREATE TABLE reclamation_attachments (
    id             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    reclamation_id BIGINT UNSIGNED  NOT NULL,
    uploaded_by    BIGINT UNSIGNED  NOT NULL,
    original_name  VARCHAR(255)     NOT NULL,
    stored_name    VARCHAR(255)     NOT NULL COMMENT 'UUID.ext',
    file_path      VARCHAR(500)     NOT NULL,
    mime_type      VARCHAR(100)     NOT NULL,
    file_size      INT UNSIGNED     NOT NULL,
    disk           VARCHAR(50)      NOT NULL DEFAULT 'local',
    is_scanned     TINYINT(1)       NOT NULL DEFAULT 0,
    scanned_at     TIMESTAMP        NULL,
    is_safe        TINYINT(1)       NULL,
    created_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_attach_reclam   (reclamation_id),
    INDEX idx_attach_uploader (uploaded_by),
    CONSTRAINT fk_attach_reclam
        FOREIGN KEY (reclamation_id) REFERENCES reclamations(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attach_uploader
        FOREIGN KEY (uploaded_by)    REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_attach_size
        CHECK (file_size > 0 AND file_size <= 10485760)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pièces jointes réclamations';


-- ╔══════════════════════════════════════════════════════════════════╗
-- ║   GROUPE 6 — DOCUMENTS & NOTIFICATIONS                          ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ------------------------------------------------------------
-- TABLE: documents
-- ------------------------------------------------------------
CREATE TABLE documents (
    id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    student_id    BIGINT UNSIGNED  NOT NULL,
    uploaded_by   BIGINT UNSIGNED  NOT NULL,
    type          ENUM('attestation_inscription','carte_etudiant',
                       'releve_notes','demande_stage','autres') NOT NULL,
    label         VARCHAR(255)     NULL,
    original_name VARCHAR(255)     NOT NULL,
    stored_name   VARCHAR(255)     NOT NULL,
    file_path     VARCHAR(500)     NOT NULL,
    mime_type     VARCHAR(100)     NOT NULL,
    file_size     INT UNSIGNED     NOT NULL,
    disk          VARCHAR(50)      NOT NULL DEFAULT 'local',
    is_published  TINYINT(1)       NOT NULL DEFAULT 0,
    published_at  TIMESTAMP        NULL,
    expires_at    TIMESTAMP        NULL,
    academic_year VARCHAR(10)      NULL,
    created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_doc_student      (student_id),
    INDEX idx_doc_type         (type),
    INDEX idx_doc_published    (is_published),
    INDEX idx_doc_student_type (student_id, type),
    CONSTRAINT fk_doc_student
        FOREIGN KEY (student_id)  REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_doc_uploader
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Documents officiels étudiants';

-- ------------------------------------------------------------
-- TABLE: notifications
-- UUID comme PK — généré par Laravel Str::uuid()
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id              CHAR(36)         NOT NULL COMMENT 'UUID Laravel',
    user_id         BIGINT UNSIGNED  NOT NULL,
    type            VARCHAR(100)     NOT NULL,
    title           VARCHAR(255)     NOT NULL,
    body            TEXT             NOT NULL,
    data            JSON             NULL,
    sent_by         BIGINT UNSIGNED  NULL,
    is_read         TINYINT(1)       NOT NULL DEFAULT 0,
    read_at         TIMESTAMP        NULL,
    channel         ENUM('in_app','email','sms','push') NOT NULL DEFAULT 'in_app',
    sent_at         TIMESTAMP        NULL,
    notifiable_type VARCHAR(100)     NULL,
    notifiable_id   BIGINT UNSIGNED  NULL,
    created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_notif_user          (user_id),
    INDEX idx_notif_type          (type),
    INDEX idx_notif_read          (is_read),
    INDEX idx_notif_created       (created_at),
    INDEX idx_notif_user_read_dt  (user_id, is_read, created_at),
    CONSTRAINT fk_notif_user
        FOREIGN KEY (user_id)  REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notif_sender
        FOREIGN KEY (sent_by)  REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notifications in-app et email';


-- ╔══════════════════════════════════════════════════════════════════╗
-- ║   GROUPE 7 — AUDIT & CONFIGURATION                              ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ------------------------------------------------------------
-- TABLE: audit_logs  (PARTITIONNÉ — pas de FK possible)
-- ------------------------------------------------------------
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id, created_at)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
PARTITION BY RANGE COLUMNS (created_at) (
    PARTITION p2024 VALUES LESS THAN ('2025-01-01 00:00:00'),
    PARTITION p2025 VALUES LESS THAN ('2026-01-01 00:00:00'),
    PARTITION p2026 VALUES LESS THAN ('2027-01-01 00:00:00'),
    PARTITION pfuture VALUES LESS THAN (MAXVALUE)
);

-- ------------------------------------------------------------
-- TABLE: settings
-- ------------------------------------------------------------
CREATE TABLE settings (
    id          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `key`       VARCHAR(100)     NOT NULL,
    value       TEXT             NULL,
    type        ENUM('string','integer','boolean','json','float')
                NOT NULL DEFAULT 'string',
    `group`     VARCHAR(50)      NOT NULL DEFAULT 'general',
    label       VARCHAR(200)     NULL,
    is_public   TINYINT(1)       NOT NULL DEFAULT 0,
    updated_by  BIGINT UNSIGNED  NULL,
    created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_setting_key   (`key`),
    INDEX       idx_setting_group (`group`),
    CONSTRAINT fk_setting_updater
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuration système';


-- ============================================================
-- RÉACTIVATION FOREIGN KEYS
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;


-- ╔══════════════════════════════════════════════════════════════════╗
-- ║                                                                  ║
-- ║   SEED DATA — DONNÉES INITIALES COMPLÈTES                       ║
-- ║                                                                  ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ============================================================
-- 1. NIVEAUX
-- ============================================================
INSERT INTO niveaux (code, label, order_index) VALUES
    ('L1', 'Licence 1ère Année', 1),
    ('L2', 'Licence 2ème Année', 2),
    ('L3', 'Licence 3ème Année', 3);


-- ============================================================
-- 2. DEPARTMENTS
-- ============================================================
INSERT INTO departments (name, code, head_name, head_email, is_active) VALUES
    ('Finance et Comptabilité',    'FIN',  'Dr. Ahmed Ould Mohamed',  'ahmed@iscae.mr',  1),
    ('Marketing et Commerce',      'MKT',  'Dr. Fatima Mint Salem',   'fatima@iscae.mr', 1),
    ('Management et Organisation', 'MGT',  'Dr. Mohamed Ould Brahim', 'med@iscae.mr',    1),
    ('Informatique de Gestion',    'INFO', 'Dr. Aissa Ould Cheikh',   'aissa@iscae.mr',  1);


-- ============================================================
-- 3. FILIERES (IDs dynamiques)
-- ============================================================
INSERT INTO filieres (department_id, name, code, is_active)
SELECT id, 'Licence Finance',        'LIC-FIN',  1 FROM departments WHERE code='FIN'  UNION ALL
SELECT id, 'Licence Comptabilité',   'LIC-CPT',  1 FROM departments WHERE code='FIN'  UNION ALL
SELECT id, 'Licence Marketing',      'LIC-MKT',  1 FROM departments WHERE code='MKT'  UNION ALL
SELECT id, 'Licence Commerce',       'LIC-COM',  1 FROM departments WHERE code='MKT'  UNION ALL
SELECT id, 'Licence Management',     'LIC-MGT',  1 FROM departments WHERE code='MGT'  UNION ALL
SELECT id, 'Licence Informatique',   'LIC-INFO', 1 FROM departments WHERE code='INFO';


-- ============================================================
-- 4. SEMESTRES (IDs dynamiques — règle L1→S1,S2 | L2→S3,S4 | L3→S5,S6)
-- ============================================================
INSERT INTO semestres (niveau_id, code, label, order_index, is_open, academic_year)
SELECT id,'S1','Semestre 1',1,1,'2024-2025' FROM niveaux WHERE code='L1' UNION ALL
SELECT id,'S2','Semestre 2',2,1,'2024-2025' FROM niveaux WHERE code='L1' UNION ALL
SELECT id,'S3','Semestre 3',3,1,'2024-2025' FROM niveaux WHERE code='L2' UNION ALL
SELECT id,'S4','Semestre 4',4,1,'2024-2025' FROM niveaux WHERE code='L2' UNION ALL
SELECT id,'S5','Semestre 5',5,1,'2024-2025' FROM niveaux WHERE code='L3' UNION ALL
SELECT id,'S6','Semestre 6',6,1,'2024-2025' FROM niveaux WHERE code='L3';


-- ============================================================
-- 5. MODULES — TOUTES FILIÈRES — TOUS SEMESTRES
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- LIC-FIN — S1 (L1)
-- ─────────────────────────────────────────────────────────────
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S1' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'FIN-S1-01' code,'Comptabilité Générale I'       name,3.00 coef,4 cred UNION ALL
    SELECT 'FIN-S1-02','Mathématiques pour la Finance',          2.50,3              UNION ALL
    SELECT 'FIN-S1-03','Économie Générale',                      2.00,3              UNION ALL
    SELECT 'FIN-S1-04','Droit des Affaires',                     2.00,3              UNION ALL
    SELECT 'FIN-S1-05','Informatique Bureautique',               1.50,2              UNION ALL
    SELECT 'FIN-S1-06','Langue Française des Affaires',          1.00,2
) m WHERE f.code='LIC-FIN';

-- LIC-FIN — S2 (L1)
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S2' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'FIN-S2-01' code,'Comptabilité Générale II'      name,3.00 coef,4 cred UNION ALL
    SELECT 'FIN-S2-02','Statistiques Appliquées',                2.50,3              UNION ALL
    SELECT 'FIN-S2-03','Macroéconomie',                          2.00,3              UNION ALL
    SELECT 'FIN-S2-04','Fiscalité des Entreprises I',            2.00,3              UNION ALL
    SELECT 'FIN-S2-05','Techniques Quantitatives',               1.50,2              UNION ALL
    SELECT 'FIN-S2-06','Anglais des Affaires I',                 1.00,2
) m WHERE f.code='LIC-FIN';

-- LIC-FIN — S3 (L2)
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S3' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'FIN-S3-01' code,'Finance d\'Entreprise I'       name,3.00 coef,4 cred UNION ALL
    SELECT 'FIN-S3-02','Analyse Financière',                     3.00,4              UNION ALL
    SELECT 'FIN-S3-03','Comptabilité Analytique',                2.50,3              UNION ALL
    SELECT 'FIN-S3-04','Gestion de Trésorerie',                  2.00,3              UNION ALL
    SELECT 'FIN-S3-05','Droit Fiscal',                           2.00,3              UNION ALL
    SELECT 'FIN-S3-06','Anglais des Affaires II',                1.00,2
) m WHERE f.code='LIC-FIN';

-- LIC-FIN — S4 (L2)
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S4' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'FIN-S4-01' code,'Finance d\'Entreprise II'      name,3.00 coef,4 cred UNION ALL
    SELECT 'FIN-S4-02','Audit et Contrôle Interne',              3.00,4              UNION ALL
    SELECT 'FIN-S4-03','Marchés Financiers',                     2.50,3              UNION ALL
    SELECT 'FIN-S4-04','Fiscalité des Entreprises II',           2.00,3              UNION ALL
    SELECT 'FIN-S4-05','Gestion des Risques',                    2.00,3              UNION ALL
    SELECT 'FIN-S4-06','Économétrie Financière',                 1.50,2
) m WHERE f.code='LIC-FIN';

-- LIC-FIN — S5 (L3)
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S5' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'FIN-S5-01' code,'Finance Internationale'        name,3.00 coef,4 cred UNION ALL
    SELECT 'FIN-S5-02','Ingénierie Financière',                  3.00,4              UNION ALL
    SELECT 'FIN-S5-03','Consolidation des Comptes',              2.50,3              UNION ALL
    SELECT 'FIN-S5-04','Évaluation d\'Entreprise',               2.50,3              UNION ALL
    SELECT 'FIN-S5-05','Contrôle de Gestion',                    2.00,3              UNION ALL
    SELECT 'FIN-S5-06','Séminaire de Recherche I',               1.00,2
) m WHERE f.code='LIC-FIN';

-- LIC-FIN — S6 (L3)
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S6' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'FIN-S6-01' code,'Gestion de Portefeuille'       name,3.00 coef,4 cred UNION ALL
    SELECT 'FIN-S6-02','Finance Islamique',                      3.00,4              UNION ALL
    SELECT 'FIN-S6-03','Normes IFRS',                            2.50,3              UNION ALL
    SELECT 'FIN-S6-04','Stratégie Financière',                   2.50,3              UNION ALL
    SELECT 'FIN-S6-05','Projet de Fin d\'Études',                4.00,6              UNION ALL
    SELECT 'FIN-S6-06','Séminaire de Recherche II',              1.00,2
) m WHERE f.code='LIC-FIN';

-- ─────────────────────────────────────────────────────────────
-- LIC-INFO — S1
-- ─────────────────────────────────────────────────────────────
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S1' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'INFO-S1-01' code,'Algorithmique et Programmation I' name,3.00 coef,4 cred UNION ALL
    SELECT 'INFO-S1-02','Architecture des Ordinateurs',               2.50,3               UNION ALL
    SELECT 'INFO-S1-03','Mathématiques Discrètes',                    2.50,3               UNION ALL
    SELECT 'INFO-S1-04','Systèmes d\'Exploitation I',                 2.00,3               UNION ALL
    SELECT 'INFO-S1-05','Introduction aux Bases de Données',          2.00,3               UNION ALL
    SELECT 'INFO-S1-06','Bureautique Avancée',                        1.00,2
) m WHERE f.code='LIC-INFO';

-- LIC-INFO — S2
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S2' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'INFO-S2-01' code,'Algorithmique et Programmation II' name,3.00 coef,4 cred UNION ALL
    SELECT 'INFO-S2-02','Réseaux Informatiques I',                    2.50,3               UNION ALL
    SELECT 'INFO-S2-03','Bases de Données Relationnelles',            3.00,4               UNION ALL
    SELECT 'INFO-S2-04','Systèmes d\'Exploitation II',                2.00,3               UNION ALL
    SELECT 'INFO-S2-05','Programmation Web I',                        2.00,3               UNION ALL
    SELECT 'INFO-S2-06','Anglais Technique I',                        1.00,2
) m WHERE f.code='LIC-INFO';

-- LIC-INFO — S3
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S3' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'INFO-S3-01' code,'Génie Logiciel'                name,3.00 coef,4 cred UNION ALL
    SELECT 'INFO-S3-02','Programmation Orientée Objet',           3.00,4               UNION ALL
    SELECT 'INFO-S3-03','Administration Bases de Données',        2.50,3               UNION ALL
    SELECT 'INFO-S3-04','Réseaux Informatiques II',               2.00,3               UNION ALL
    SELECT 'INFO-S3-05','Programmation Web II',                   2.00,3               UNION ALL
    SELECT 'INFO-S3-06','Sécurité Informatique I',                2.00,3
) m WHERE f.code='LIC-INFO';

-- LIC-INFO — S4
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S4' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'INFO-S4-01' code,'Développement Mobile'          name,3.00 coef,4 cred UNION ALL
    SELECT 'INFO-S4-02','Intelligence Artificielle I',            2.50,3               UNION ALL
    SELECT 'INFO-S4-03','Cloud Computing',                        2.50,3               UNION ALL
    SELECT 'INFO-S4-04','Analyse et Conception SI',               3.00,4               UNION ALL
    SELECT 'INFO-S4-05','Sécurité Informatique II',               2.00,3               UNION ALL
    SELECT 'INFO-S4-06','Anglais Technique II',                   1.00,2
) m WHERE f.code='LIC-INFO';

-- LIC-INFO — S5
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S5' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'INFO-S5-01' code,'Développement Full Stack'      name,3.00 coef,4 cred UNION ALL
    SELECT 'INFO-S5-02','Big Data et Analyse',                    2.50,3               UNION ALL
    SELECT 'INFO-S5-03','DevOps et CI/CD',                        2.50,3               UNION ALL
    SELECT 'INFO-S5-04','Systèmes Distribués',                    2.00,3               UNION ALL
    SELECT 'INFO-S5-05','Intelligence Artificielle II',           2.50,3               UNION ALL
    SELECT 'INFO-S5-06','Séminaire Projet I',                     1.00,2
) m WHERE f.code='LIC-INFO';

-- LIC-INFO — S6
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S6' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'INFO-S6-01' code,'Architecture Microservices'    name,3.00 coef,4 cred UNION ALL
    SELECT 'INFO-S6-02','Machine Learning Appliqué',              3.00,4               UNION ALL
    SELECT 'INFO-S6-03','Cybersécurité Avancée',                  2.50,3               UNION ALL
    SELECT 'INFO-S6-04','Entrepreneuriat Tech',                   2.00,3               UNION ALL
    SELECT 'INFO-S6-05','Projet de Fin d\'Études',                4.00,6               UNION ALL
    SELECT 'INFO-S6-06','Séminaire Projet II',                    1.00,2
) m WHERE f.code='LIC-INFO';

-- ─────────────────────────────────────────────────────────────
-- LIC-MKT — S1 à S6
-- ─────────────────────────────────────────────────────────────
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S1' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MKT-S1-01' code,'Introduction au Marketing'     name,3.00 coef,4 cred UNION ALL
    SELECT 'MKT-S1-02','Économie de l\'Entreprise',              2.50,3               UNION ALL
    SELECT 'MKT-S1-03','Comptabilité pour Marketeurs',           2.00,3               UNION ALL
    SELECT 'MKT-S1-04','Comportement du Consommateur I',         2.00,3               UNION ALL
    SELECT 'MKT-S1-05','Informatique de Gestion',                1.50,2               UNION ALL
    SELECT 'MKT-S1-06','Communication d\'Entreprise',            1.00,2
) m WHERE f.code='LIC-MKT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S2' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MKT-S2-01' code,'Marketing Mix'                 name,3.00 coef,4 cred UNION ALL
    SELECT 'MKT-S2-02','Études de Marché I',                     2.50,3               UNION ALL
    SELECT 'MKT-S2-03','Distribution et Commerce',               2.00,3               UNION ALL
    SELECT 'MKT-S2-04','Comportement du Consommateur II',        2.00,3               UNION ALL
    SELECT 'MKT-S2-05','Statistiques Marketing',                 2.00,3               UNION ALL
    SELECT 'MKT-S2-06','Anglais Commercial I',                   1.00,2
) m WHERE f.code='LIC-MKT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S3' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MKT-S3-01' code,'Marketing Digital I'           name,3.00 coef,4 cred UNION ALL
    SELECT 'MKT-S3-02','Stratégie Marketing',                    3.00,4               UNION ALL
    SELECT 'MKT-S3-03','Études de Marché II',                    2.50,3               UNION ALL
    SELECT 'MKT-S3-04','Communication Publicitaire',             2.00,3               UNION ALL
    SELECT 'MKT-S3-05','E-Commerce',                             2.00,3               UNION ALL
    SELECT 'MKT-S3-06','Anglais Commercial II',                  1.00,2
) m WHERE f.code='LIC-MKT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S4' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MKT-S4-01' code,'Marketing Digital II'          name,3.00 coef,4 cred UNION ALL
    SELECT 'MKT-S4-02','Marketing des Services',                 2.50,3               UNION ALL
    SELECT 'MKT-S4-03','Gestion de la Relation Client',          2.50,3               UNION ALL
    SELECT 'MKT-S4-04','Brand Management',                       2.00,3               UNION ALL
    SELECT 'MKT-S4-05','Marketing International',                2.00,3               UNION ALL
    SELECT 'MKT-S4-06','Veille Stratégique',                     2.00,3
) m WHERE f.code='LIC-MKT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S5' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MKT-S5-01' code,'Marketing Stratégique'         name,3.00 coef,4 cred UNION ALL
    SELECT 'MKT-S5-02','Intelligence Artificielle Marketing',    2.50,3               UNION ALL
    SELECT 'MKT-S5-03','Marketing Sensoriel',                    2.00,3               UNION ALL
    SELECT 'MKT-S5-04','Neuromarketing',                         2.00,3               UNION ALL
    SELECT 'MKT-S5-05','Entrepreneuriat',                        2.50,3               UNION ALL
    SELECT 'MKT-S5-06','Séminaire Recherche I',                  1.00,2
) m WHERE f.code='LIC-MKT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S6' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MKT-S6-01' code,'Marketing Durable'             name,3.00 coef,4 cred UNION ALL
    SELECT 'MKT-S6-02','Social Media Management',                2.50,3               UNION ALL
    SELECT 'MKT-S6-03','Stratégie de Contenu',                   2.00,3               UNION ALL
    SELECT 'MKT-S6-04','Data Marketing',                         2.50,3               UNION ALL
    SELECT 'MKT-S6-05','Projet de Fin d\'Études',                4.00,6               UNION ALL
    SELECT 'MKT-S6-06','Séminaire Recherche II',                 1.00,2
) m WHERE f.code='LIC-MKT';

-- ─────────────────────────────────────────────────────────────
-- LIC-MGT — S1 à S6
-- ─────────────────────────────────────────────────────────────
INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S1' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MGT-S1-01' code,'Introduction au Management'    name,3.00 coef,4 cred UNION ALL
    SELECT 'MGT-S1-02','Droit des Sociétés',                     2.50,3               UNION ALL
    SELECT 'MGT-S1-03','Comptabilité Générale',                  2.00,3               UNION ALL
    SELECT 'MGT-S1-04','Économie d\'Entreprise',                 2.00,3               UNION ALL
    SELECT 'MGT-S1-05','Informatique de Gestion',                1.50,2               UNION ALL
    SELECT 'MGT-S1-06','Psychologie des Organisations',          1.00,2
) m WHERE f.code='LIC-MGT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S2' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MGT-S2-01' code,'Management des Organisations'  name,3.00 coef,4 cred UNION ALL
    SELECT 'MGT-S2-02','Gestion des Ressources Humaines I',      2.50,3               UNION ALL
    SELECT 'MGT-S2-03','Marketing Fondamental',                  2.00,3               UNION ALL
    SELECT 'MGT-S2-04','Analyse Financière Basique',             2.00,3               UNION ALL
    SELECT 'MGT-S2-05','Statistiques de Gestion',                2.00,3               UNION ALL
    SELECT 'MGT-S2-06','Communication Managériale',              1.00,2
) m WHERE f.code='LIC-MGT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S3' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MGT-S3-01' code,'Stratégie d\'Entreprise I'     name,3.00 coef,4 cred UNION ALL
    SELECT 'MGT-S3-02','Gestion de Projet',                      3.00,4               UNION ALL
    SELECT 'MGT-S3-03','GRH Avancée',                            2.50,3               UNION ALL
    SELECT 'MGT-S3-04','Management de la Qualité',               2.00,3               UNION ALL
    SELECT 'MGT-S3-05','Systèmes d\'Information',                2.00,3               UNION ALL
    SELECT 'MGT-S3-06','Leadership et Négociation',              1.00,2
) m WHERE f.code='LIC-MGT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S4' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MGT-S4-01' code,'Stratégie d\'Entreprise II'    name,3.00 coef,4 cred UNION ALL
    SELECT 'MGT-S4-02','Innovation et Entrepreneuriat',          2.50,3               UNION ALL
    SELECT 'MGT-S4-03','Management Interculturel',               2.50,3               UNION ALL
    SELECT 'MGT-S4-04','Gouvernance d\'Entreprise',              2.00,3               UNION ALL
    SELECT 'MGT-S4-05','Management des Risques',                 2.00,3               UNION ALL
    SELECT 'MGT-S4-06','Développement Durable en Entreprise',    2.00,3
) m WHERE f.code='LIC-MGT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S5' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MGT-S5-01' code,'Management International'      name,3.00 coef,4 cred UNION ALL
    SELECT 'MGT-S5-02','Conduite du Changement',                 2.50,3               UNION ALL
    SELECT 'MGT-S5-03','Audit Organisationnel',                  2.50,3               UNION ALL
    SELECT 'MGT-S5-04','Performance et Tableau de Bord',         2.00,3               UNION ALL
    SELECT 'MGT-S5-05','Éthique des Affaires',                   2.00,3               UNION ALL
    SELECT 'MGT-S5-06','Séminaire Recherche I',                  1.00,2
) m WHERE f.code='LIC-MGT';

INSERT INTO modules (filiere_id, semestre_id, code, name, coefficient, credits)
SELECT f.id, s.id, m.code, m.name, m.coef, m.cred
FROM filieres f
JOIN semestres s ON s.code='S6' AND s.academic_year='2024-2025'
JOIN (
    SELECT 'MGT-S6-01' code,'Management Agile'              name,3.00 coef,4 cred UNION ALL
    SELECT 'MGT-S6-02','Responsabilité Sociale des Entreprises', 2.50,3               UNION ALL
    SELECT 'MGT-S6-03','Transformation Digitale',                2.50,3               UNION ALL
    SELECT 'MGT-S6-04','Stratégie Globale',                      2.00,3               UNION ALL
    SELECT 'MGT-S6-05','Projet de Fin d\'Études',                4.00,6               UNION ALL
    SELECT 'MGT-S6-06','Séminaire Recherche II',                 1.00,2
) m WHERE f.code='LIC-MGT';


-- ============================================================
-- 6. ADMIN — admin@iscae.mr
-- Mot de passe original : i@s@c@a@e@
-- Hash bcrypt cost=12 généré par :
-- php -r "echo password_hash('i@s@c@a@e@', PASSWORD_BCRYPT, ['cost'=>12]);"
-- ============================================================
INSERT INTO users (
    role, login_identifier, email, password,
    is_active, is_verified, verified_at,
    created_at, updated_at
) VALUES (
    'admin',
    'admin@iscae.mr',
    'admin@iscae.mr',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    -- ↑ Remplacez par le hash réel généré par Laravel :
    -- php artisan tinker → Hash::make('i@s@c@a@e@')
    1, 1, NOW(), NOW(), NOW()
);

INSERT INTO admins (
    user_id, nom, prenom, email, phone,
    role_label, department_id,
    two_factor_enabled, require_2fa_after_days,
    created_at, updated_at
) VALUES (
    LAST_INSERT_ID(),
    'Administrateur', 'Principal',
    'admin@iscae.mr', '+222 00000000',
    'super_admin', NULL,
    1, 7,
    NOW(), NOW()
);


-- ============================================================
-- 7. STUDENTS_PRELOADED — étudiants par niveau et filière
-- ============================================================
INSERT INTO students_preloaded
    (matricule, nni, nom, prenom, email,
     filiere_id, niveau_id, academic_year,
     is_registered, import_batch)
SELECT
    d.matricule, d.nni, d.nom, d.prenom, d.email,
    f.id AS filiere_id,
    n.id AS niveau_id,
    '2024-2025',
    0,
    'BATCH-2024-001'
FROM (
    -- ══════════════════════════════
    -- LIC-FIN — L1 (10 étudiants)
    -- ══════════════════════════════
    SELECT '20240001' matricule,'1000000001' nni,'Ould Ahmed'    nom,'Mohamed'    prenom,'s20240001@etud.iscae.mr' email,'LIC-FIN' fc,'L1' nc UNION ALL
    SELECT '20240002','1000000002','Mint Salem',   'Mariem',    's20240002@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    SELECT '20240003','1000000003','Ould Brahim',  'Abdallah',  's20240003@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    SELECT '20240004','1000000004','Mint Mohamed', 'Khadija',   's20240004@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    SELECT '20240005','1000000005','Ould Sidi',    'Hamza',     's20240005@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    SELECT '20240006','1000000006','Mint Vall',    'Aminata',   's20240006@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    SELECT '20240007','1000000007','Ould Cheikh',  'Yahya',     's20240007@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    SELECT '20240008','1000000008','Mint Diallo',  'Fatou',     's20240008@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    SELECT '20240009','1000000009','Ould Habibi',  'Sidi',      's20240009@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    SELECT '20240010','1000000010','Mint Bouh',    'Nana',      's20240010@etud.iscae.mr','LIC-FIN','L1' UNION ALL
    -- ══════════════════════════════
    -- LIC-FIN — L2 (8 étudiants)
    -- ══════════════════════════════
    SELECT '20230001','2000000001','Ould Isselmou','Idriss',    's20230001@etud.iscae.mr','LIC-FIN','L2' UNION ALL
    SELECT '20230002','2000000002','Mint Bilal',   'Roukia',    's20230002@etud.iscae.mr','LIC-FIN','L2' UNION ALL
    SELECT '20230003','2000000003','Ould Deh',     'Moctar',    's20230003@etud.iscae.mr','LIC-FIN','L2' UNION ALL
    SELECT '20230004','2000000004','Mint Ethmane', 'Salma',     's20230004@etud.iscae.mr','LIC-FIN','L2' UNION ALL
    SELECT '20230005','2000000005','Ould Ghaber',  'Moussa',    's20230005@etud.iscae.mr','LIC-FIN','L2' UNION ALL
    SELECT '20230006','2000000006','Mint Abdi',    'Zeinab',    's20230006@etud.iscae.mr','LIC-FIN','L2' UNION ALL
    SELECT '20230007','2000000007','Ould Mohamed', 'Ahmed',     's20230007@etud.iscae.mr','LIC-FIN','L2' UNION ALL
    SELECT '20230008','2000000008','Mint Lekbir',  'Hawa',      's20230008@etud.iscae.mr','LIC-FIN','L2' UNION ALL
    -- ══════════════════════════════
    -- LIC-FIN — L3 (6 étudiants)
    -- ══════════════════════════════
    SELECT '20220001','3000000001','Ould Taleb',   'Boubacar',  's20220001@etud.iscae.mr','LIC-FIN','L3' UNION ALL
    SELECT '20220002','3000000002','Mint Wane',    'Coumba',    's20220002@etud.iscae.mr','LIC-FIN','L3' UNION ALL
    SELECT '20220003','3000000003','Ould Barry',   'Oumar',     's20220003@etud.iscae.mr','LIC-FIN','L3' UNION ALL
    SELECT '20220004','3000000004','Mint Sow',     'Maimouna',  's20220004@etud.iscae.mr','LIC-FIN','L3' UNION ALL
    SELECT '20220005','3000000005','Ould Diop',    'Cheikh',    's20220005@etud.iscae.mr','LIC-FIN','L3' UNION ALL
    SELECT '20220006','3000000006','Mint Kane',    'Oumou',     's20220006@etud.iscae.mr','LIC-FIN','L3' UNION ALL
    -- ══════════════════════════════
    -- LIC-INFO — L1 (8 étudiants)
    -- ══════════════════════════════
    SELECT '20240011','1000000011','Ould Limam',   'Ismail',    's20240011@etud.iscae.mr','LIC-INFO','L1' UNION ALL
    SELECT '20240012','1000000012','Mint Jeddou',  'Aicha',     's20240012@etud.iscae.mr','LIC-INFO','L1' UNION ALL
    SELECT '20240013','1000000013','Ould Moustaph','Ali',        's20240013@etud.iscae.mr','LIC-INFO','L1' UNION ALL
    SELECT '20240014','1000000014','Mint Khalil',  'Marwa',     's20240014@etud.iscae.mr','LIC-INFO','L1' UNION ALL
    SELECT '20240015','1000000015','Ould Seydi',   'Hamidou',   's20240015@etud.iscae.mr','LIC-INFO','L1' UNION ALL
    SELECT '20240016','1000000016','Mint Kebe',    'Fatimetou', 's20240016@etud.iscae.mr','LIC-INFO','L1' UNION ALL
    SELECT '20240017','1000000017','Ould Traoré',  'Ibrahim',   's20240017@etud.iscae.mr','LIC-INFO','L1' UNION ALL
    SELECT '20240018','1000000018','Mint Ndiaye',  'Khady',     's20240018@etud.iscae.mr','LIC-INFO','L1' UNION ALL
    -- ══════════════════════════════
    -- LIC-INFO — L2 (6 étudiants)
    -- ══════════════════════════════
    SELECT '20230009','2000000009','Ould Bocar',   'Samba',     's20230009@etud.iscae.mr','LIC-INFO','L2' UNION ALL
    SELECT '20230010','2000000010','Mint Fall',    'Adja',      's20230010@etud.iscae.mr','LIC-INFO','L2' UNION ALL
    SELECT '20230011','2000000011','Ould Konaté',  'Seydou',    's20230011@etud.iscae.mr','LIC-INFO','L2' UNION ALL
    SELECT '20230012','2000000012','Mint Touré',   'Binta',     's20230012@etud.iscae.mr','LIC-INFO','L2' UNION ALL
    SELECT '20230013','2000000013','Ould Camara',  'Mamadou',   's20230013@etud.iscae.mr','LIC-INFO','L2' UNION ALL
    SELECT '20230014','2000000014','Mint Gaye',    'Ndéye',     's20230014@etud.iscae.mr','LIC-INFO','L2' UNION ALL
    -- ══════════════════════════════
    -- LIC-INFO — L3 (5 étudiants)
    -- ══════════════════════════════
    SELECT '20220007','3000000007','Ould Baldé',   'Alpha',     's20220007@etud.iscae.mr','LIC-INFO','L3' UNION ALL
    SELECT '20220008','3000000008','Mint Diallo',  'Mariama',   's20220008@etud.iscae.mr','LIC-INFO','L3' UNION ALL
    SELECT '20220009','3000000009','Ould Mbaye',   'Pape',      's20220009@etud.iscae.mr','LIC-INFO','L3' UNION ALL
    SELECT '20220010','3000000010','Mint Thiam',   'Aminata',   's20220010@etud.iscae.mr','LIC-INFO','L3' UNION ALL
    SELECT '20220011','3000000011','Ould Sarr',    'Modou',     's20220011@etud.iscae.mr','LIC-INFO','L3' UNION ALL
    -- ══════════════════════════════
    -- LIC-MKT — L1 (8 étudiants)
    -- ══════════════════════════════
    SELECT '20240019','1000000019','Ould Sy',      'Mamoudou',  's20240019@etud.iscae.mr','LIC-MKT','L1' UNION ALL
    SELECT '20240020','1000000020','Mint Tall',    'Rougui',    's20240020@etud.iscae.mr','LIC-MKT','L1' UNION ALL
    SELECT '20240021','1000000021','Ould Bâ',      'Hamed',     's20240021@etud.iscae.mr','LIC-MKT','L1' UNION ALL
    SELECT '20240022','1000000022','Mint Ly',      'Aissatou',  's20240022@etud.iscae.mr','LIC-MKT','L1' UNION ALL
    SELECT '20240023','1000000023','Ould Coulibaly','Adama',    's20240023@etud.iscae.mr','LIC-MKT','L1' UNION ALL
    SELECT '20240024','1000000024','Mint Kouyaté', 'Kadiatou',  's20240024@etud.iscae.mr','LIC-MKT','L1' UNION ALL
    SELECT '20240025','1000000025','Ould Doumbia', 'Souleymane','s20240025@etud.iscae.mr','LIC-MKT','L1' UNION ALL
    SELECT '20240026','1000000026','Mint Sanogo',  'Tenin',     's20240026@etud.iscae.mr','LIC-MKT','L1' UNION ALL
    -- ══════════════════════════════
    -- LIC-MKT — L2 (6 étudiants)
    -- ══════════════════════════════
    SELECT '20230015','2000000015','Ould Keita',   'Bouréma',   's20230015@etud.iscae.mr','LIC-MKT','L2' UNION ALL
    SELECT '20230016','2000000016','Mint Cissé',   'Fatoumata', 's20230016@etud.iscae.mr','LIC-MKT','L2' UNION ALL
    SELECT '20230017','2000000017','Ould Diabaté', 'Lassana',   's20230017@etud.iscae.mr','LIC-MKT','L2' UNION ALL
    SELECT '20230018','2000000018','Mint Koné',    'Mariam',    's20230018@etud.iscae.mr','LIC-MKT','L2' UNION ALL
    SELECT '20230019','2000000019','Ould Coulibaly','Tièba',    's20230019@etud.iscae.mr','LIC-MKT','L2' UNION ALL
    SELECT '20230020','2000000020','Mint Traoré',  'Djeneba',   's20230020@etud.iscae.mr','LIC-MKT','L2' UNION ALL
    -- ══════════════════════════════
    -- LIC-MKT — L3 (4 étudiants)
    -- ══════════════════════════════
    SELECT '20220012','3000000012','Ould Fofana',  'Drissa',    's20220012@etud.iscae.mr','LIC-MKT','L3' UNION ALL
    SELECT '20220013','3000000013','Mint Bamba',   'Salimata',  's20220013@etud.iscae.mr','LIC-MKT','L3' UNION ALL
    SELECT '20220014','3000000014','Ould Diarra',  'Youssouf',  's20220014@etud.iscae.mr','LIC-MKT','L3' UNION ALL
    SELECT '20220015','3000000015','Mint Dembélé', 'Awa',       's20220015@etud.iscae.mr','LIC-MKT','L3' UNION ALL
    -- ══════════════════════════════
    -- LIC-MGT — L1 (6 étudiants)
    -- ══════════════════════════════
    SELECT '20240027','1000000027','Ould Sissoko', 'Bakary',    's20240027@etud.iscae.mr','LIC-MGT','L1' UNION ALL
    SELECT '20240028','1000000028','Mint Coulibaly','Rokia',    's20240028@etud.iscae.mr','LIC-MGT','L1' UNION ALL
    SELECT '20240029','1000000029','Ould Maïga',   'Hamidou',   's20240029@etud.iscae.mr','LIC-MGT','L1' UNION ALL
    SELECT '20240030','1000000030','Mint Samaké',  'Mariam',    's20240030@etud.iscae.mr','LIC-MGT','L1' UNION ALL
    SELECT '20240031','1000000031','Ould Dao',     'Ibrahim',   's20240031@etud.iscae.mr','LIC-MGT','L1' UNION ALL
    SELECT '20240032','1000000032','Mint Touré',   'Fatoumata', 's20240032@etud.iscae.mr','LIC-MGT','L1' UNION ALL
    -- ══════════════════════════════
    -- LIC-MGT — L2 (5 étudiants)
    -- ══════════════════════════════
    SELECT '20230021','2000000021','Ould Kanté',   'Sekou',     's20230021@etud.iscae.mr','LIC-MGT','L2' UNION ALL
    SELECT '20230022','2000000022','Mint Kouyaté', 'Hawa',      's20230022@etud.iscae.mr','LIC-MGT','L2' UNION ALL
    SELECT '20230023','2000000023','Ould Diallo',  'Oumar',     's20230023@etud.iscae.mr','LIC-MGT','L2' UNION ALL
    SELECT '20230024','2000000024','Mint Bah',     'Aissata',   's20230024@etud.iscae.mr','LIC-MGT','L2' UNION ALL
    SELECT '20230025','2000000025','Ould Baldé',   'Mamadou',   's20230025@etud.iscae.mr','LIC-MGT','L2' UNION ALL
    -- ══════════════════════════════
    -- LIC-MGT — L3 (4 étudiants)
    -- ══════════════════════════════
    SELECT '20220016','3000000016','Ould Sow',     'Ibrahima',  's20220016@etud.iscae.mr','LIC-MGT','L3' UNION ALL
    SELECT '20220017','3000000017','Mint Diallo',  'Oumou',     's20220017@etud.iscae.mr','LIC-MGT','L3' UNION ALL
    SELECT '20220018','3000000018','Ould Camara',  'Thierno',   's20220018@etud.iscae.mr','LIC-MGT','L3' UNION ALL
    SELECT '20220019','3000000019','Mint Balde',   'Mariama',   's20220019@etud.iscae.mr','LIC-MGT','L3'
) d
INNER JOIN filieres f ON f.code = d.fc
INNER JOIN niveaux  n ON n.code = d.nc;


-- ============================================================
-- 8. SETTINGS
-- ============================================================
INSERT INTO settings (`key`, value, type, `group`, label, is_public) VALUES
('otp_expiry_minutes',         '10',        'integer','security',    'Durée validité OTP (min)',              0),
('otp_max_attempts',           '5',         'integer','security',    'Max tentatives OTP',                    0),
('login_max_attempts',         '5',         'integer','security',    'Max tentatives connexion',              0),
('login_lockout_minutes',      '30',        'integer','security',    'Durée blocage compte (min)',             0),
('admin_2fa_after_days',       '7',         'integer','security',    'Re-demander 2FA après X jours',         0),
('device_trust_days',          '30',        'integer','security',    'Durée confiance appareil (jours)',      0),
('session_lifetime_minutes',   '120',       'integer','session',     'Durée session (min)',                   0),
('max_file_upload_mb',         '10',        'integer','uploads',     'Taille max upload (Mo)',                0),
('allowed_file_types',         '["pdf","jpg","jpeg","png","doc","docx"]',
                                            'json',  'uploads',     'Extensions autorisées',                 0),
('reclamation_max_per_student','3',         'integer','reclamation', 'Max réclamations par étudiant',         0),
('app_name',                   'ISCAE Reclamation System',
                                            'string','general',     'Nom application',                       1),
('academic_year_current',      '2024-2025', 'string','academic',    'Année académique courante',             1),
('maintenance_mode',           'false',     'boolean','general',    'Mode maintenance',                      0);


-- ============================================================
-- TRIGGERS
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_reclam_status_change
AFTER UPDATE ON reclamations FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO reclamation_history
            (reclamation_id, changed_by, old_status, new_status, created_at)
        VALUES
            (NEW.id, COALESCE(NEW.responded_by, NEW.escalated_to), OLD.status, NEW.status, NOW());
    END IF;
END$$

CREATE TRIGGER trg_note_change
AFTER UPDATE ON notes FOR EACH ROW
BEGIN
    IF COALESCE(OLD.note_controle,0)  <> COALESCE(NEW.note_controle,0)
    OR COALESCE(OLD.note_examen,0)    <> COALESCE(NEW.note_examen,0)
    OR COALESCE(OLD.note_rattrapage,0)<> COALESCE(NEW.note_rattrapage,0)
    OR COALESCE(OLD.note_finale,0)    <> COALESCE(NEW.note_finale,0)
    THEN
        INSERT INTO notes_history (
            note_id, changed_by,
            old_controle, old_examen, old_rattrapage, old_finale,
            new_controle, new_examen, new_rattrapage, new_finale,
            created_at
        ) VALUES (
            NEW.id, COALESCE(NEW.uploaded_by, NEW.published_by),
            OLD.note_controle, OLD.note_examen, OLD.note_rattrapage, OLD.note_finale,
            NEW.note_controle, NEW.note_examen, NEW.note_rattrapage, NEW.note_finale,
            NOW()
        );
    END IF;
END$$

CREATE TRIGGER trg_preloaded_lock
BEFORE UPDATE ON students_preloaded FOR EACH ROW
BEGIN
    IF OLD.is_registered = 1 AND NEW.is_registered = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Impossible : étudiant déjà inscrit.';
    END IF;
END$$

DELIMITER ;


-- ============================================================
-- EVENTS
-- ============================================================
SET GLOBAL event_scheduler = ON;

DELIMITER $$

CREATE EVENT IF NOT EXISTS evt_clean_otp
ON SCHEDULE EVERY 1 HOUR STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DELETE FROM otp_codes
    WHERE is_used = 1 OR expires_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
END$$

CREATE EVENT IF NOT EXISTS evt_clean_login_attempts
ON SCHEDULE EVERY 1 DAY
STARTS (DATE(NOW()) + INTERVAL 1 DAY + INTERVAL 2 HOUR)
DO
BEGIN
    DELETE FROM login_attempts
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$

CREATE EVENT IF NOT EXISTS evt_close_semestres
ON SCHEDULE EVERY 1 HOUR STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE semestres SET is_open = 0
    WHERE is_open = 1 AND close_at IS NOT NULL AND close_at < NOW();
END$$

DELIMITER ;


-- ============================================================
-- VÉRIFICATIONS FINALES
-- ============================================================

-- Résumé des tables créées
SELECT
    TABLE_NAME          AS `Table`,
    TABLE_ROWS          AS `Lignes (approx)`,
    TABLE_COMMENT       AS `Description`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'iscae_reclamations'
ORDER BY TABLE_NAME;

-- Vérifier l'admin
SELECT
    u.id,
    u.role,
    u.login_identifier,
    u.email,
    LEFT(u.password, 7)  AS hash_debut,
    a.role_label,
    a.nom, a.prenom
FROM users u
JOIN admins a ON a.user_id = u.id;

-- Vérifier les étudiants préchargés par niveau et filière
SELECT
    f.code  AS filiere,
    n.code  AS niveau,
    COUNT(*) AS total_etudiants
FROM students_preloaded sp
JOIN filieres f ON f.id = sp.filiere_id
JOIN niveaux  n ON n.id = sp.niveau_id
GROUP BY f.code, n.code
ORDER BY f.code, n.code;

-- Vérifier les modules par filière
SELECT
    f.code      AS filiere,
    s.code      AS semestre,
    COUNT(*)    AS nb_modules
FROM modules m
JOIN filieres  f ON f.id = m.filiere_id
JOIN semestres s ON s.id = m.semestre_id
GROUP BY f.code, s.code
ORDER BY f.code, s.order_index;

TRUNCATE TABLE students_preloaded;

SELECT id, code, name FROM filieres;
SELECT id, code FROM niveaux;

DESCRIBE students_preloaded;



INSERT INTO students_preloaded
(matricule, nni, nom, prenom, email, filiere_code, niveau_code, academic_year, is_registered, import_batch)
VALUES
(
 'I205099',
 '1010101012',
 'bhn',
 'El Ghassem',
 'ahmedabdellahi935@gmail.com',
 'LIC-INFO',
 'L2',
 '2025-2026',
 0,
 'BATCH-2024-001'
);