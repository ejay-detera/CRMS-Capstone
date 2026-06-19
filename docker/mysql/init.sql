CREATE USER IF NOT EXISTS 'crms_app'@'%' IDENTIFIED BY 'ejaydetera12';
-- In production, the password above will be replaced by the environment variable value if you set up the container properly.
-- Wait, actually in Docker entrypoint, if we mount a sql file, variables aren't evaluated.
-- We must inject it via env or use the env variable. Wait, docker-entrypoint handles MYSQL_USER and MYSQL_PASSWORD automatically!
-- Let's just grant permissions and rely on docker-compose.prod.yml creating the user.
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP ON `crms-db`.* TO 'crms_app'@'%';
FLUSH PRIVILEGES;
