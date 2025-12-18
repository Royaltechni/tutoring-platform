CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "phone" varchar,
  "is_active" tinyint(1) not null default '1',
  "last_login_at" datetime,
  "preferences" text,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "role" varchar not null default 'teacher',
  "country" varchar,
  "city" varchar,
  "headline" varchar,
  "bio" text,
  "main_subject" varchar,
  "experience_years" integer,
  "teaches_online" tinyint(1) not null default '0',
  "teaches_onsite" tinyint(1) not null default '0',
  "hourly_rate_online" numeric,
  "half_hour_rate_online" numeric,
  "hourly_rate_onsite" numeric,
  "half_hour_rate_onsite" numeric,
  "grade_min" integer,
  "grade_max" integer,
  "curricula" varchar,
  "offers_trial" tinyint(1) not null default '0',
  "trial_duration_minutes" integer,
  "trial_price" numeric,
  "average_rating" numeric not null default '0',
  "ratings_count" integer not null default '0',
  "available_today" tinyint(1) not null default '0',
  "available_tomorrow" tinyint(1) not null default '0',
  "intro_video_url" varchar,
  "teacher_status" varchar not null default 'pending'
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "roles_slug_unique" on "roles"("slug");
CREATE TABLE IF NOT EXISTS "role_user"(
  "user_id" integer not null,
  "role_id" integer not null,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("user_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "lesson_delivery_modes"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "name" varchar not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "lesson_delivery_modes_code_unique" on "lesson_delivery_modes"(
  "code"
);
CREATE TABLE IF NOT EXISTS "cities"(
  "id" integer primary key autoincrement not null,
  "name_en" varchar not null,
  "name_ar" varchar,
  "emirate" varchar check("emirate" in('Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah')) not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "name" varchar,
  "country_id" integer
);
CREATE TABLE IF NOT EXISTS "subjects"(
  "id" integer primary key autoincrement not null,
  "name_en" varchar not null,
  "name_ar" varchar,
  "category" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "teacher_profiles"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "bio" text,
  "years_of_experience" integer not null default '0',
  "country" varchar,
  "time_zone" varchar not null default 'Asia/Dubai',
  "photo_url" varchar,
  "onboarding_status" varchar check("onboarding_status" in('pending_review', 'approved', 'rejected', 'incomplete')) not null default 'incomplete',
  "meta" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "profile_photo_path" varchar,
  "intro_video_url" varchar,
  "id_document_path" varchar,
  "teaching_permit_path" varchar,
  "headline" varchar,
  "city" varchar,
  "main_subject" varchar,
  "experience_years" integer,
  "teaches_online" tinyint(1) not null default '0',
  "teaches_onsite" tinyint(1) not null default '0',
  "hourly_rate_online" numeric,
  "half_hour_rate_online" numeric,
  "hourly_rate_onsite" numeric,
  "half_hour_rate_onsite" numeric,
  "account_status" varchar not null default 'pending',
  "country_id" integer,
  "onsite_city_ids" text,
  "subjects" text,
  "languages" varchar,
  "teaching_style" text,
  "cancel_policy" text,
  "availability" text,
  "min_grade" integer,
  "max_grade" integer,
  "curricula" text,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "teacher_delivery_modes"(
  "id" integer primary key autoincrement not null,
  "teacher_profile_id" integer not null,
  "lesson_delivery_mode_id" integer not null,
  "price_per_hour" numeric not null,
  "currency" varchar not null default 'AED',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("teacher_profile_id") references "teacher_profiles"("id") on delete cascade,
  foreign key("lesson_delivery_mode_id") references "lesson_delivery_modes"("id") on delete cascade
);
CREATE UNIQUE INDEX "teacher_mode_unique" on "teacher_delivery_modes"(
  "teacher_profile_id",
  "lesson_delivery_mode_id"
);
CREATE TABLE IF NOT EXISTS "teacher_cities"(
  "teacher_profile_id" integer not null,
  "city_id" integer not null,
  foreign key("teacher_profile_id") references "teacher_profiles"("id") on delete cascade,
  foreign key("city_id") references "cities"("id") on delete cascade,
  primary key("teacher_profile_id", "city_id")
);
CREATE TABLE IF NOT EXISTS "teacher_subjects"(
  "teacher_profile_id" integer not null,
  "subject_id" integer not null,
  foreign key("teacher_profile_id") references "teacher_profiles"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  primary key("teacher_profile_id", "subject_id")
);
CREATE TABLE IF NOT EXISTS "bookings"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "teacher_profile_id" integer not null,
  "user_id" integer not null,
  "lesson_delivery_mode_id" integer,
  "city_id" integer,
  "total_amount" numeric not null,
  "currency" varchar not null default 'AED',
  "status" varchar not null default 'pending',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "booking_date" date,
  "address" varchar,
  "city" varchar,
  "subject" varchar,
  "grade" integer,
  "curriculum" varchar,
  "mode" varchar check("mode" in('online', 'onsite')),
  "duration_minutes" integer,
  "lessons_count" integer not null default '1',
  "first_lesson_at" datetime,
  "location" varchar,
  "price_per_lesson" numeric,
  "total_price" numeric,
  "payment_status" varchar not null default 'pending',
  "booking_type" varchar not null default 'normal',
  "teacher_id" integer,
  "delivery_mode" varchar,
  "price" numeric,
  "status_updated_by" integer,
  "status_updated_at" datetime,
  "status_updated_source" varchar,
  "cancel_requested_at" datetime,
  "cancel_requested_by" integer,
  "cancel_request_reason" text,
  "cancel_request_status" varchar,
  "cancel_handled_at" datetime,
  "cancel_handled_by" integer,
  "cancel_handle_note" text,
  foreign key("teacher_profile_id") references "teacher_profiles"("id"),
  foreign key("user_id") references "users"("id"),
  foreign key("lesson_delivery_mode_id") references "lesson_delivery_modes"("id"),
  foreign key("city_id") references "cities"("id")
);
CREATE UNIQUE INDEX "bookings_uuid_unique" on "bookings"("uuid");
CREATE TABLE IF NOT EXISTS "lesson_sessions"(
  "id" integer primary key autoincrement not null,
  "booking_id" integer not null,
  "teacher_profile_id" integer not null,
  "user_id" integer not null,
  "scheduled_start_at" datetime not null,
  "scheduled_end_at" datetime not null,
  "actual_start_at" datetime,
  "actual_end_at" datetime,
  "status" varchar not null default 'scheduled',
  "video_link" varchar,
  "recording_url" varchar,
  "teacher_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("booking_id") references "bookings"("id") on delete cascade,
  foreign key("teacher_profile_id") references "teacher_profiles"("id"),
  foreign key("user_id") references "users"("id")
);
CREATE TABLE IF NOT EXISTS "payments"(
  "id" integer primary key autoincrement not null,
  "booking_id" integer not null,
  "amount" numeric not null,
  "currency" varchar not null default 'AED',
  "payment_provider" varchar not null,
  "payment_method" varchar,
  "status" varchar not null default 'pending',
  "external_transaction_id" varchar,
  "raw_payload" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("booking_id") references "bookings"("id")
);
CREATE INDEX "payments_external_transaction_id_index" on "payments"(
  "external_transaction_id"
);
CREATE TABLE IF NOT EXISTS "lesson_ratings"(
  "id" integer primary key autoincrement not null,
  "lesson_session_id" integer not null,
  "user_id" integer not null,
  "rating" integer not null,
  "comment" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("lesson_session_id") references "lesson_sessions"("id"),
  foreign key("user_id") references "users"("id")
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" text not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE INDEX "personal_access_tokens_expires_at_index" on "personal_access_tokens"(
  "expires_at"
);
CREATE TABLE IF NOT EXISTS "booking_status_histories"(
  "id" integer primary key autoincrement not null,
  "booking_id" integer not null,
  "old_status" varchar,
  "new_status" varchar not null,
  "changed_by" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("booking_id") references "bookings"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "booking_logs"(
  "id" integer primary key autoincrement not null,
  "booking_id" integer not null,
  "user_id" integer,
  "field" varchar not null,
  "old_value" text,
  "new_value" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("booking_id") references "bookings"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "booking_attachments"(
  "id" integer primary key autoincrement not null,
  "booking_id" integer not null,
  "uploaded_by_type" varchar not null,
  "uploaded_by_id" integer not null,
  "original_name" varchar not null,
  "file_path" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("booking_id") references "bookings"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "countries"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "name_ar" varchar not null,
  "name_en" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "countries_code_unique" on "countries"("code");
CREATE INDEX "cities_country_id_index" on "cities"("country_id");
CREATE INDEX "teacher_profiles_country_id_index" on "teacher_profiles"(
  "country_id"
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "teacher_profile_onsite_cities"(
  "id" integer primary key autoincrement not null,
  "teacher_profile_id" integer not null,
  "city_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("teacher_profile_id") references "teacher_profiles"("id") on delete cascade,
  foreign key("city_id") references "cities"("id") on delete cascade
);
CREATE UNIQUE INDEX "tp_city_unique" on "teacher_profile_onsite_cities"(
  "teacher_profile_id",
  "city_id"
);
CREATE INDEX "tp_account_status_idx" on "teacher_profiles"("account_status");
CREATE INDEX "tp_country_id_idx" on "teacher_profiles"("country_id");
CREATE INDEX "tp_teaches_online_idx" on "teacher_profiles"("teaches_online");
CREATE INDEX "tp_teaches_onsite_idx" on "teacher_profiles"("teaches_onsite");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_12_08_230432_create_roles_and_role_user_tables',1);
INSERT INTO migrations VALUES(5,'2025_12_08_230610_create_teacher_domain_tables',1);
INSERT INTO migrations VALUES(6,'2025_12_08_230725_create_bookings_and_payments_tables',1);
INSERT INTO migrations VALUES(7,'2025_12_09_005714_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(8,'2025_12_10_023508_create_booking_status_histories_table',1);
INSERT INTO migrations VALUES(9,'2025_12_10_125635_add_name_to_cities_table',2);
INSERT INTO migrations VALUES(10,'2025_12_10_131205_add_booking_date_to_bookings_table',3);
INSERT INTO migrations VALUES(11,'2025_12_10_132858_add_address_to_bookings_table',4);
INSERT INTO migrations VALUES(12,'2025_12_10_164334_add_role_to_users_table',5);
INSERT INTO migrations VALUES(13,'2025_12_10_212906_add_city_to_bookings_table',6);
INSERT INTO migrations VALUES(14,'2025_12_10_213010_create_booking_logs_table',6);
INSERT INTO migrations VALUES(15,'2025_12_11_025611_add_teacher_profile_fields_to_users_table',7);
INSERT INTO migrations VALUES(16,'2025_12_11_025756_add_details_to_bookings_table',8);
INSERT INTO migrations VALUES(17,'2025_12_11_025840_create_booking_attachments_table',8);
INSERT INTO migrations VALUES(18,'2025_12_11_122836_add_media_fields_to_teacher_profiles_table',9);
INSERT INTO migrations VALUES(19,'2025_12_11_132517_add_headline_to_teacher_profiles_table',10);
INSERT INTO migrations VALUES(20,'2025_12_11_133107_add_core_fields_to_teacher_profiles_table',11);
INSERT INTO migrations VALUES(21,'2025_12_12_005629_add_teacher_id_to_bookings_table',12);
INSERT INTO migrations VALUES(22,'2025_12_12_073244_add_teacher_status_to_users_table',13);
INSERT INTO migrations VALUES(23,'2025_12_12_092745_add_account_status_to_teacher_profiles_table',14);
INSERT INTO migrations VALUES(24,'2025_12_12_181429_create_countries_table',15);
INSERT INTO migrations VALUES(25,'2025_12_12_192320_create_cities_table',16);
INSERT INTO migrations VALUES(26,'2025_12_12_194016_add_country_id_to_cities_table',17);
INSERT INTO migrations VALUES(27,'2025_12_13_003632_add_country_id_to_teacher_profiles_table',18);
INSERT INTO migrations VALUES(28,'2025_12_13_005745_add_onsite_city_ids_to_teacher_profiles_table',19);
INSERT INTO migrations VALUES(29,'2025_12_13_120448_create_notifications_table',20);
INSERT INTO migrations VALUES(30,'2025_12_14_040708_add_extra_fields_to_teacher_profiles_table',21);
INSERT INTO migrations VALUES(31,'2025_12_14_044230_create_teacher_profile_onsite_cities_table',22);
INSERT INTO migrations VALUES(32,'2025_12_14_053409_cleanup_teacher_profiles_legacy_columns',23);
INSERT INTO migrations VALUES(33,'2025_12_14_054228_add_delivery_fields_to_bookings_table',24);
INSERT INTO migrations VALUES(34,'2025_12_14_201055_add_status_audit_fields_to_bookings_table',25);
INSERT INTO migrations VALUES(35,'2025_12_14_202136_add_status_audit_fields_v2_to_bookings_table',26);
INSERT INTO migrations VALUES(36,'2025_12_14_210601_add_cancel_request_fields_to_bookings_table',27);
INSERT INTO migrations VALUES(37,'2025_12_15_024617_add_grades_and_curricula_to_teacher_profiles_table',28);
