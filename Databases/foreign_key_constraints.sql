-- Foreign Key Constraints for Public Safety Campaign Management System
-- Run this script AFTER the main schema has been imported

-- Select the database
USE public_safety_db;

-- Add indexes to referenced columns if they don't exist
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_id (id);

-- Clean up any invalid foreign key references before adding constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Campaigns table foreign keys
UPDATE campaigns SET created_by = NULL WHERE created_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE campaigns SET assigned_to = NULL WHERE assigned_to IS NOT NULL AND assigned_to NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE campaigns ADD CONSTRAINT fk_campaigns_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE campaigns ADD CONSTRAINT fk_campaigns_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

-- Surveys table foreign keys
UPDATE surveys SET created_by = NULL WHERE created_by IS NOT NULL AND created_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE surveys ADD CONSTRAINT fk_surveys_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Events table foreign keys
UPDATE events SET created_by = NULL WHERE created_by IS NOT NULL AND created_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE events ADD CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Content table foreign keys
UPDATE content SET uploaded_by = NULL WHERE uploaded_by IS NOT NULL AND uploaded_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE content ADD CONSTRAINT fk_content_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL;

-- Feedback table foreign keys
UPDATE feedback SET assigned_to = NULL WHERE assigned_to IS NOT NULL AND assigned_to NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE feedback SET campaign_id = NULL WHERE campaign_id IS NOT NULL AND campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE feedback SET incident_id = NULL WHERE incident_id IS NOT NULL AND incident_id NOT IN (SELECT id FROM incidents WHERE id IS NOT NULL);
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL;
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_incident_id FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE SET NULL;

-- Segments table foreign keys
UPDATE segments SET created_by = NULL WHERE created_by IS NOT NULL AND created_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE segments ADD CONSTRAINT fk_segments_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Incident responses table foreign keys
UPDATE incident_responses SET responder_id = NULL WHERE responder_id IS NOT NULL AND responder_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE incident_responses ADD CONSTRAINT fk_incident_responses_responder_id FOREIGN KEY (responder_id) REFERENCES users(id) ON DELETE SET NULL;

-- Event registrations table foreign keys
UPDATE event_registrations SET user_id = NULL WHERE user_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE event_registrations ADD CONSTRAINT fk_event_registrations_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Campaign-related foreign keys
UPDATE campaign_resources SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_team_members SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_team_members SET user_id = NULL WHERE user_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE campaign_documents SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_activities SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_activities SET user_id = NULL WHERE user_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE campaign_metrics SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_demographics SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE channel_analytics SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE generated_reports SET generated_by = NULL WHERE generated_by IS NOT NULL AND generated_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE campaign_scores SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE predictive_forecasts SET campaign_id = NULL WHERE campaign_id IS NOT NULL AND campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);

ALTER TABLE campaign_resources ADD CONSTRAINT fk_campaign_resources_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_team_members ADD CONSTRAINT fk_campaign_team_members_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_team_members ADD CONSTRAINT fk_campaign_team_members_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE campaign_documents ADD CONSTRAINT fk_campaign_documents_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_activities ADD CONSTRAINT fk_campaign_activities_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_activities ADD CONSTRAINT fk_campaign_activities_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE campaign_metrics ADD CONSTRAINT fk_campaign_metrics_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_demographics ADD CONSTRAINT fk_campaign_demographics_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE channel_analytics ADD CONSTRAINT fk_channel_analytics_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE generated_reports ADD CONSTRAINT fk_generated_reports_generated_by FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE campaign_scores ADD CONSTRAINT fk_campaign_scores_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE predictive_forecasts ADD CONSTRAINT fk_predictive_forecasts_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL;

-- Integration systems foreign keys
UPDATE integration_logs SET integration_id = NULL WHERE integration_id IS NOT NULL AND integration_id NOT IN (SELECT id FROM integration_systems WHERE id IS NOT NULL);
UPDATE api_logs SET integration_id = NULL WHERE integration_id IS NOT NULL AND integration_id NOT IN (SELECT id FROM integration_systems WHERE id IS NOT NULL);
ALTER TABLE integration_logs ADD CONSTRAINT fk_integration_logs_integration_id FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE SET NULL;
ALTER TABLE api_logs ADD CONSTRAINT fk_api_logs_integration_id FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE SET NULL;
ALTER TABLE security_compliance ADD CONSTRAINT fk_security_compliance_integration_id FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE CASCADE;
ALTER TABLE data_flows ADD CONSTRAINT fk_data_flows_integration_id FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE CASCADE;

-- Incidents table foreign keys
UPDATE incidents SET reported_by = NULL WHERE reported_by IS NOT NULL AND reported_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE incidents SET assigned_to = NULL WHERE assigned_to IS NOT NULL AND assigned_to NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE incidents ADD CONSTRAINT fk_incidents_reported_by FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE incidents ADD CONSTRAINT fk_incidents_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

-- Survey-related foreign keys
UPDATE survey_questions SET survey_id = NULL WHERE survey_id NOT IN (SELECT id FROM surveys WHERE id IS NOT NULL);
UPDATE survey_responses SET survey_id = NULL WHERE survey_id NOT IN (SELECT id FROM surveys WHERE id IS NOT NULL);
UPDATE survey_responses SET respondent_id = NULL WHERE respondent_id IS NOT NULL AND respondent_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE survey_answers SET response_id = NULL WHERE response_id NOT IN (SELECT id FROM survey_responses WHERE id IS NOT NULL);
UPDATE survey_answers SET question_id = NULL WHERE question_id NOT IN (SELECT id FROM survey_questions WHERE id IS NOT NULL);
UPDATE survey_distribution SET survey_id = NULL WHERE survey_id NOT IN (SELECT id FROM surveys WHERE id IS NOT NULL);
UPDATE survey_distribution SET channel_id = NULL WHERE channel_id IS NOT NULL AND channel_id NOT IN (SELECT id FROM distribution_channels WHERE id IS NOT NULL);

ALTER TABLE survey_questions ADD CONSTRAINT fk_survey_questions_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE;
ALTER TABLE survey_responses ADD CONSTRAINT fk_survey_responses_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE;
ALTER TABLE survey_responses ADD CONSTRAINT fk_survey_responses_respondent_id FOREIGN KEY (respondent_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE survey_answers ADD CONSTRAINT fk_survey_answers_response_id FOREIGN KEY (response_id) REFERENCES survey_responses(id) ON DELETE CASCADE;
ALTER TABLE survey_answers ADD CONSTRAINT fk_survey_answers_question_id FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE;
ALTER TABLE survey_distribution ADD CONSTRAINT fk_survey_distribution_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE;
ALTER TABLE survey_distribution ADD CONSTRAINT fk_survey_distribution_channel_id FOREIGN KEY (channel_id) REFERENCES distribution_channels(id) ON DELETE CASCADE;

-- Event-related foreign keys
UPDATE event_resource_allocations SET event_id = NULL WHERE event_id NOT IN (SELECT id FROM events WHERE id IS NOT NULL);
UPDATE event_resource_allocations SET resource_id = NULL WHERE resource_id IS NOT NULL AND resource_id NOT IN (SELECT id FROM event_resources WHERE id IS NOT NULL);
UPDATE event_feedback SET event_id = NULL WHERE event_id NOT IN (SELECT id FROM events WHERE id IS NOT NULL);
UPDATE event_feedback SET user_id = NULL WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);

ALTER TABLE event_resource_allocations ADD CONSTRAINT fk_event_resource_allocations_event_id FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE;
ALTER TABLE event_resource_allocations ADD CONSTRAINT fk_event_resource_allocations_resource_id FOREIGN KEY (resource_id) REFERENCES event_resources(id) ON DELETE CASCADE;
ALTER TABLE event_feedback ADD CONSTRAINT fk_event_feedback_event_id FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE;
ALTER TABLE event_feedback ADD CONSTRAINT fk_event_feedback_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Campaign-related foreign keys (continued)
UPDATE campaign_milestones SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_category_assignments SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_category_assignments SET category_id = NULL WHERE category_id NOT IN (SELECT id FROM campaign_categories WHERE id IS NOT NULL);

ALTER TABLE campaign_milestones ADD CONSTRAINT fk_campaign_milestones_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_category_assignments ADD CONSTRAINT fk_campaign_category_assignments_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_category_assignments ADD CONSTRAINT fk_campaign_category_assignments_category_id FOREIGN KEY (category_id) REFERENCES campaign_categories(id) ON DELETE CASCADE;

-- Segmentation-related foreign keys
UPDATE demographic_criteria SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE behavioral_criteria SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE geographic_criteria SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE psychographic_criteria SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_members SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_members SET member_id = NULL WHERE member_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE segment_channel_preferences SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_channel_preferences SET channel_id = NULL WHERE channel_id IS NOT NULL AND channel_id NOT IN (SELECT id FROM communication_channels WHERE id IS NOT NULL);
UPDATE ab_testing_groups SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE privacy_compliance SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_overlap SET segment1_id = NULL WHERE segment1_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_overlap SET segment2_id = NULL WHERE segment2_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_analytics SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);

ALTER TABLE demographic_criteria ADD CONSTRAINT fk_demographic_criteria_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE behavioral_criteria ADD CONSTRAINT fk_behavioral_criteria_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE geographic_criteria ADD CONSTRAINT fk_geographic_criteria_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE psychographic_criteria ADD CONSTRAINT fk_psychographic_criteria_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_members ADD CONSTRAINT fk_segment_members_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_channel_preferences ADD CONSTRAINT fk_segment_channel_preferences_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_channel_preferences ADD CONSTRAINT fk_segment_channel_preferences_channel_id FOREIGN KEY (channel_id) REFERENCES communication_channels(id) ON DELETE CASCADE;
ALTER TABLE ab_testing_groups ADD CONSTRAINT fk_ab_testing_groups_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE privacy_compliance ADD CONSTRAINT fk_privacy_compliance_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_overlap ADD CONSTRAINT fk_segment_overlap_segment1_id FOREIGN KEY (segment1_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_overlap ADD CONSTRAINT fk_segment_overlap_segment2_id FOREIGN KEY (segment2_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_analytics ADD CONSTRAINT fk_segment_analytics_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;