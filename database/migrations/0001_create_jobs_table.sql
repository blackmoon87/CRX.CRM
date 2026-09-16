CREATE TABLE IF NOT EXISTS jobs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    event        VARCHAR(255)     NOT NULL,
    listener     VARCHAR(255)     NOT NULL,
    payload      TEXT             NOT NULL,
    status       VARCHAR(50)      NOT NULL DEFAULT 'pending',
    attempts     INT              NOT NULL DEFAULT 0,
    max_attempts INT              NOT NULL DEFAULT 3,
    on_failure   VARCHAR(50)      NOT NULL DEFAULT 'retry',
    error        TEXT             NULL,
    run_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_jobs_worker ON jobs (status, run_at);
