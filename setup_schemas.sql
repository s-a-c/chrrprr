-- Create schemas if they don't exist
CREATE SCHEMA IF NOT EXISTS "chrrprr-local";
CREATE SCHEMA IF NOT EXISTS "search";

-- Grant all privileges on schemas to postgres user
GRANT ALL ON SCHEMA "chrrprr-local" TO postgres;
GRANT ALL ON SCHEMA "search" TO postgres;

-- Grant usage and create on schemas
GRANT USAGE, CREATE ON SCHEMA "chrrprr-local" TO postgres;
GRANT USAGE, CREATE ON SCHEMA "search" TO postgres;
