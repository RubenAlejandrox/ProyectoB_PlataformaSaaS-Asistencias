-- Opcional: ejecutar en Supabase SQL Editor si SESSION_DRIVER/CACHE_STORE=database
-- y las migraciones de Laravel aún no corrieron en ese proyecto.
--
-- Si sessions.user_id quedó en bigint (Laravel por defecto) y users.id es uuid, ejecuta:
--   ALTER TABLE sessions DROP CONSTRAINT IF EXISTS sessions_user_id_foreign;
--   ALTER TABLE sessions ALTER COLUMN user_id TYPE uuid USING NULL;
-- O despliega la migración 2026_06_02_180000_ensure_laravel_http_sessions_table_uuid_user_id.

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity);

CREATE TABLE IF NOT EXISTS cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration BIGINT NOT NULL
);
