-- POSITROM schema — MySQL 8 / MariaDB 10.5+
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(120) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('incomplete', 'pending_activation', 'active', 'past_due', 'cancelled', 'expired') NOT NULL DEFAULT 'incomplete',
    mollie_customer_id VARCHAR(64) NULL,
    mollie_mandate_id VARCHAR(64) NULL,
    mollie_subscription_id VARCHAR(64) NULL,
    plan_price_eur DECIMAL(10, 2) NOT NULL DEFAULT 12.00,
    current_period_start DATE NULL,
    current_period_end DATE NULL,
    next_payment_date DATE NULL,
    activated_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    cancel_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sub_user (user_id),
    KEY idx_sub_status (status),
    KEY idx_sub_next_pay (next_payment_date),
    CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    subscription_id INT UNSIGNED NULL,
    mollie_payment_id VARCHAR(64) NOT NULL,
    amount_eur DECIMAL(10, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'EUR',
    status VARCHAR(32) NOT NULL,
    method VARCHAR(32) NULL,
    sequence_type VARCHAR(16) NULL,
    is_first TINYINT(1) NOT NULL DEFAULT 0,
    paid_at DATETIME NULL,
    raw_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pay_mollie (mollie_payment_id),
    KEY idx_pay_user (user_id),
    KEY idx_pay_status (status),
    CONSTRAINT fk_pay_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_pay_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL DEFAULT 'Nueva conversación',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_conv_user (user_id),
    CONSTRAINT fk_conv_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id INT UNSIGNED NOT NULL,
    role ENUM('system', 'user', 'assistant') NOT NULL,
    content MEDIUMTEXT NOT NULL,
    tokens_in INT UNSIGNED NOT NULL DEFAULT 0,
    tokens_out INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_msg_conv (conversation_id),
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usage_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NULL,
    model VARCHAR(80) NOT NULL,
    tokens_in INT UNSIGNED NOT NULL,
    tokens_out INT UNSIGNED NOT NULL,
    cost_eur DECIMAL(12, 6) NOT NULL,
    period_ym CHAR(7) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_usage_user_period (user_id, period_ym),
    KEY idx_usage_created (created_at),
    CONSTRAINT fk_usage_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_visits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    path VARCHAR(255) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    user_agent VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_visits_created (created_at),
    KEY idx_visits_path (path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(80) NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    type VARCHAR(40) NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    status VARCHAR(20) NOT NULL,
    error_text VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_email_type (type),
    KEY idx_email_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_reminders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    period_ym CHAR(7) NOT NULL,
    sent_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reminder (user_id, period_ym)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scope VARCHAR(80) NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    window_start DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rate (scope, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
