-- -------------------------------------------------------------
-- TablePlus 6.8.1(655)
--
-- https://tableplus.com/
--
-- Database: laravel
-- Generation Time: 2026-01-08 18:00:39.3830
-- -------------------------------------------------------------


-- Table Definition
CREATE TABLE "chrrprr-local"."migrations" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."password_reset_tokens" (
    PRIMARY KEY ("email")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."sessions" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."cache" (
    PRIMARY KEY ("key")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."cache_locks" (
    PRIMARY KEY ("key")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."jobs" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."job_batches" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."failed_jobs" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."love_reactants" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."love_reactions" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."love_reacters" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."love_reaction_types" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."love_reactant_reaction_counters" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."love_reactant_reaction_totals" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."tenants" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."domains" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."verb_events" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."verb_snapshots" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."verb_state_events" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."users" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."user_enterprise" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."user_organisation_access" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."teams" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."permissions" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."model_has_permissions" (
    PRIMARY KEY ("team_id","permission_id","model_id","model_type")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."model_has_roles" (
    PRIMARY KEY ("team_id","role_id","model_id","model_type")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."role_has_permissions" (
    PRIMARY KEY ("permission_id","role_id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."roles" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."activity_log" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."team_move_approvals" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."reactions" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."comments" (
    PRIMARY KEY ("id")
);

-- Table Definition
CREATE TABLE "chrrprr-local"."comment_notification_subscriptions" (
    PRIMARY KEY ("id")
);



-- Indices
CREATE INDEX sessions_user_id_index ON "chrrprr-local".sessions USING btree (user_id);
CREATE INDEX sessions_last_activity_index ON "chrrprr-local".sessions USING btree (last_activity);


-- Indices
CREATE INDEX jobs_queue_index ON "chrrprr-local".jobs USING btree (queue);


-- Indices
CREATE UNIQUE INDEX failed_jobs_uuid_unique ON "chrrprr-local".failed_jobs USING btree (uuid);


-- Indices
CREATE INDEX love_reactants_type_index ON "chrrprr-local".love_reactants USING btree (type);


-- Indices
CREATE INDEX love_reactions_reactant_id_reaction_type_id_index ON "chrrprr-local".love_reactions USING btree (reactant_id, reaction_type_id);
CREATE INDEX love_reactions_reactant_id_reacter_id_reaction_type_id_index ON "chrrprr-local".love_reactions USING btree (reactant_id, reacter_id, reaction_type_id);
CREATE INDEX love_reactions_reactant_id_reacter_id_index ON "chrrprr-local".love_reactions USING btree (reactant_id, reacter_id);
CREATE INDEX love_reactions_reacter_id_reaction_type_id_index ON "chrrprr-local".love_reactions USING btree (reacter_id, reaction_type_id);


-- Indices
CREATE INDEX love_reacters_type_index ON "chrrprr-local".love_reacters USING btree (type);


-- Indices
CREATE INDEX love_reaction_types_name_index ON "chrrprr-local".love_reaction_types USING btree (name);


-- Indices
CREATE INDEX love_reactant_reaction_counters_reactant_reaction_type_index ON "chrrprr-local".love_reactant_reaction_counters USING btree (reactant_id, reaction_type_id);


-- Indices
CREATE UNIQUE INDEX domains_domain_unique ON "chrrprr-local".domains USING btree (domain);


-- Indices
CREATE INDEX verb_events_type_index ON "chrrprr-local".verb_events USING btree (type);


-- Indices
CREATE INDEX verb_snapshots_state_id_type_index ON "chrrprr-local".verb_snapshots USING btree (state_id, type);
CREATE INDEX verb_snapshots_type_index ON "chrrprr-local".verb_snapshots USING btree (type);
CREATE INDEX verb_snapshots_expires_at_index ON "chrrprr-local".verb_snapshots USING btree (expires_at);


-- Indices
CREATE INDEX verb_state_events_event_id_index ON "chrrprr-local".verb_state_events USING btree (event_id);
CREATE INDEX verb_state_events_state_id_index ON "chrrprr-local".verb_state_events USING btree (state_id);
CREATE INDEX verb_state_events_state_type_index ON "chrrprr-local".verb_state_events USING btree (state_type);


-- Indices
CREATE UNIQUE INDEX users_email_unique ON "chrrprr-local".users USING btree (email);
CREATE UNIQUE INDEX users_ulid_unique ON "chrrprr-local".users USING btree (ulid);
CREATE INDEX users_tenant_id_index ON "chrrprr-local".users USING btree (tenant_id);
CREATE INDEX users_current_context_id_index ON "chrrprr-local".users USING btree (current_context_id);
CREATE INDEX users_fts_idx ON "chrrprr-local".users USING gin (search_vector);
CREATE INDEX users_fuzzy_idx ON "chrrprr-local".users USING gist (name gist_trgm_ops);


-- Indices
CREATE UNIQUE INDEX user_enterprise_user_id_enterprise_id_unique ON "chrrprr-local".user_enterprise USING btree (user_id, enterprise_id);


-- Indices
CREATE UNIQUE INDEX user_organisation_access_user_id_organisation_id_unique ON "chrrprr-local".user_organisation_access USING btree (user_id, organisation_id);


-- Indices
CREATE UNIQUE INDEX teams_ulid_unique ON "chrrprr-local".teams USING btree (ulid);
CREATE INDEX teams_type_index ON "chrrprr-local".teams USING btree (type);
CREATE INDEX teams_parent_id_index ON "chrrprr-local".teams USING btree (parent_id);
CREATE INDEX teams_state_index ON "chrrprr-local".teams USING btree (state);
CREATE INDEX teams_status_index ON "chrrprr-local".teams USING btree (status);
CREATE INDEX teams_tenant_id_index ON "chrrprr-local".teams USING btree (tenant_id);
CREATE INDEX teams_fts_idx ON "chrrprr-local".teams USING gin (search_vector);
CREATE INDEX teams_fuzzy_idx ON "chrrprr-local".teams USING gist (extract_json_text((name)::text, 'en'::text) gist_trgm_ops);


-- Indices
CREATE UNIQUE INDEX permissions_name_guard_name_unique ON "chrrprr-local".permissions USING btree (name, guard_name);


-- Indices
CREATE INDEX model_has_permissions_model_id_model_type_index ON "chrrprr-local".model_has_permissions USING btree (model_id, model_type);
CREATE INDEX model_has_permissions_team_foreign_key_index ON "chrrprr-local".model_has_permissions USING btree (team_id);


-- Indices
CREATE INDEX model_has_roles_model_id_model_type_index ON "chrrprr-local".model_has_roles USING btree (model_id, model_type);
CREATE INDEX model_has_roles_team_foreign_key_index ON "chrrprr-local".model_has_roles USING btree (team_id);


-- Indices
CREATE INDEX roles_team_foreign_key_index ON "chrrprr-local".roles USING btree (team_id);
CREATE UNIQUE INDEX roles_team_id_name_guard_name_unique ON "chrrprr-local".roles USING btree (team_id, name, guard_name);


-- Indices
CREATE INDEX subject ON "chrrprr-local".activity_log USING btree (subject_type, subject_id);
CREATE INDEX causer ON "chrrprr-local".activity_log USING btree (causer_type, causer_id);
CREATE INDEX activity_log_log_name_index ON "chrrprr-local".activity_log USING btree (log_name);


-- Indices
CREATE INDEX team_move_approvals_team_id_status_index ON "chrrprr-local".team_move_approvals USING btree (team_id, status);
CREATE INDEX team_move_approvals_requested_by_id_index ON "chrrprr-local".team_move_approvals USING btree (requested_by_id);


-- Indices
CREATE INDEX commentator_reactions ON "chrrprr-local".reactions USING btree (commentator_type, commentator_id);


-- Indices
CREATE INDEX commentator_comments ON "chrrprr-local".comments USING btree (commentator_type, commentator_id);
CREATE INDEX comments_commentable_type_commentable_id_index ON "chrrprr-local".comments USING btree (commentable_type, commentable_id);


-- Indices
CREATE INDEX cn_subscriptions_commentable ON "chrrprr-local".comment_notification_subscriptions USING btree (commentable_type, commentable_id);
CREATE INDEX cn_subscriptions_subscriber ON "chrrprr-local".comment_notification_subscriptions USING btree (subscriber_type, subscriber_id);
