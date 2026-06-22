<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Activator {

	public static function activate(): void {
		self::create_tables();
		self::seed_default_data();
		self::schedule_cron();
		Roles::add_role();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'meal_daily_check' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'meal_daily_check' );
		}
		flush_rewrite_rules();
	}

	public static function uninstall(): void {
		DB::drop_tables();
		Roles::remove_role();
	}

	private static function create_tables(): void {
		global $wpdb;

		$sql   = array();
		$p     = $wpdb->prefix . 'meal_';

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}camp_templates (
			id int(10) unsigned NOT NULL auto_increment,
			day_number tinyint(3) unsigned NOT NULL,
			school_type varchar(20) NOT NULL default 'sm',
			is_boarding tinyint(1) NOT NULL default 0,
			label varchar(100) NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_day_type (day_number, school_type)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}camp_items (
			id int(10) unsigned NOT NULL auto_increment,
			template_id int(10) unsigned NOT NULL,
			meal_type varchar(20) NOT NULL default 'breakfast',
			section varchar(50) default NULL,
			recipe_num varchar(30) default NULL,
			dish_name varchar(255) default NULL,
			grams decimal(8,1) default NULL,
			price decimal(8,2) default NULL,
			kcal decimal(8,2) default NULL,
			protein decimal(8,2) default NULL,
			fat decimal(8,2) default NULL,
			carbs decimal(8,2) default NULL,
			sort_order smallint NOT NULL default 0,
			PRIMARY KEY  (id),
			KEY template_id (template_id)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}camp_calendar (
			date date NOT NULL,
			school_type varchar(20) NOT NULL default 'sm',
			template_id int(10) unsigned default NULL,
			school varchar(100) default NULL,
			dept varchar(50) default NULL,
			is_cycle_start tinyint(1) NOT NULL default 0,
			iterate_number tinyint(1) NOT NULL default 0,
			PRIMARY KEY  (date, school_type),
			KEY template_id (template_id)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}users (
			id int(10) unsigned NOT NULL auto_increment,
			username varchar(50) NOT NULL,
			password_hash varchar(255) NOT NULL,
			email varchar(255) default NULL,
			email_verified tinyint(1) NOT NULL default 0,
			created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY username (username),
			UNIQUE KEY email (email)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}email_tokens (
			id int(10) unsigned NOT NULL auto_increment,
			user_id int(10) unsigned default NULL,
			purpose varchar(10) NOT NULL default 'setup',
			code char(6) NOT NULL,
			email varchar(255) NOT NULL,
			expires_at datetime NOT NULL,
			used tinyint(1) NOT NULL default 0,
			created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}templates (
			id int(10) unsigned NOT NULL auto_increment,
			day_number tinyint(3) unsigned NOT NULL,
			school_type varchar(20) NOT NULL default 'sm',
			is_boarding tinyint(1) NOT NULL default 0,
			label varchar(100) NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_day_type (day_number, school_type)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}items (
			id int(10) unsigned NOT NULL auto_increment,
			template_id int(10) unsigned NOT NULL,
			meal_type varchar(20) NOT NULL default 'breakfast',
			section varchar(50) default NULL,
			recipe_num varchar(30) default NULL,
			dish_name varchar(255) default NULL,
			grams decimal(8,1) default NULL,
			price decimal(8,2) default NULL,
			kcal decimal(8,2) default NULL,
			protein decimal(8,2) default NULL,
			fat decimal(8,2) default NULL,
			carbs decimal(8,2) default NULL,
			sort_order smallint NOT NULL default 0,
			PRIMARY KEY  (id),
			KEY template_id (template_id)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}calendar (
			date date NOT NULL,
			school_type varchar(20) NOT NULL default 'sm',
			template_id int(10) unsigned default NULL,
			school varchar(100) default NULL,
			dept varchar(50) default NULL,
			is_cycle_start tinyint(1) NOT NULL default 0,
			iterate_number tinyint(1) NOT NULL default 0,
			PRIMARY KEY  (date, school_type),
			KEY template_id (template_id)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}kitchen_settings (
			id int(10) unsigned NOT NULL auto_increment,
			org_name varchar(255) NOT NULL default '',
			institution_type varchar(20) NOT NULL default 'school',
			academic_year_start varchar(5) NOT NULL default '09-01',
			academic_year_end varchar(5) NOT NULL default '05-26',
			reset_cycle_after_vacation tinyint(1) NOT NULL default 0,
			tm_approver_position varchar(100) NOT NULL default '',
			tm_approver_name varchar(100) NOT NULL default '',
			tm_approve_date date default NULL,
			updated_at timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}departments (
			id int(10) unsigned NOT NULL auto_increment,
			code varchar(20) NOT NULL,
			label varchar(100) NOT NULL,
			label_short varchar(30) NOT NULL default '',
			dept_name varchar(100) NOT NULL default '',
			is_enabled tinyint(1) NOT NULL default 0,
			is_builtin tinyint(1) NOT NULL default 1,
			is_boarding tinyint(1) NOT NULL default 0,
			workdays varchar(20) NOT NULL default '1,2,3,4,5',
			publish_xlsx tinyint(1) NOT NULL default 1,
			file_suffix varchar(20) NOT NULL default '',
			sort_order smallint NOT NULL default 0,
			note varchar(255) default NULL,
			ignore_vacations tinyint(1) NOT NULL default 0,
			merged_with varchar(20) default NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}vacations (
			id int(10) unsigned NOT NULL auto_increment,
			academic_year varchar(9) NOT NULL,
			label varchar(100) NOT NULL,
			date_from date NOT NULL,
			date_to date NOT NULL,
			actual_date date NULL default NULL,
			created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_year (academic_year),
			KEY idx_dates (date_from, date_to)
		)";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$p}oc_monitoring (
			id int(10) unsigned NOT NULL auto_increment,
			school_name varchar(255) NOT NULL default '',
			report_date date default NULL,
			s1_url varchar(500) NOT NULL default '',
			s2_hotline varchar(255) NOT NULL default '',
			s2_chat_url varchar(500) NOT NULL default '',
			s2_forum_url varchar(500) NOT NULL default '',
			s3_diet1_type varchar(255) NOT NULL default '',
			s3_diet1_url varchar(500) NOT NULL default '',
			s3_diet2_type varchar(255) NOT NULL default '',
			s3_diet2_url varchar(500) NOT NULL default '',
			s3_diet3_type varchar(255) NOT NULL default '',
			s3_diet3_url varchar(500) NOT NULL default '',
			s3_diet4_type varchar(255) NOT NULL default '',
			s3_diet4_url varchar(500) NOT NULL default '',
			s4_survey_url varchar(500) NOT NULL default '',
			s4_results_url varchar(500) NOT NULL default '',
			s5_page_url varchar(500) NOT NULL default '',
			s5_materials_url varchar(500) NOT NULL default '',
			s6_acts_url varchar(500) NOT NULL default '',
			s6_photos_url varchar(500) NOT NULL default '',
			s7_waste_level varchar(10) NOT NULL default 'none',
			updated_at timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		)";

		foreach ( $sql as $create ) {
			$wpdb->query( $create );
		}

		$d = $wpdb->prefix . 'meal_departments';
		if ( ! $wpdb->get_var( "SELECT sql FROM sqlite_master WHERE type='table' AND name='$d' AND sql LIKE '%merged_with%'" ) ) {
			$wpdb->query( "ALTER TABLE $d ADD COLUMN merged_with varchar(20) default NULL" );
		}
		if ( ! $wpdb->get_var( "SELECT sql FROM sqlite_master WHERE type='table' AND name='$d' AND sql LIKE '%has_summer_camp%'" ) ) {
			$wpdb->query( "ALTER TABLE $d ADD COLUMN has_summer_camp tinyint(1) NOT NULL default 0" );
			$wpdb->query( "ALTER TABLE $d ADD COLUMN camp_start_date date default NULL" );
			$wpdb->query( "ALTER TABLE $d ADD COLUMN camp_end_date date default NULL" );
			$wpdb->query( "ALTER TABLE $d ADD COLUMN camp_workdays varchar(20) NOT NULL default '1,2,3,4,5'" );
			$wpdb->query( "ALTER TABLE $d ADD COLUMN camp_is_boarding tinyint(1) NOT NULL default 0" );
			$wpdb->query( "ALTER TABLE $d ADD COLUMN camp_publish_xlsx tinyint(1) NOT NULL default 0" );
		}
	}

	private static function seed_default_data(): void {
		global $wpdb;

		$s = $wpdb->prefix . 'meal_kitchen_settings';
		if ( ! $wpdb->get_var( "SELECT COUNT(*) FROM `$s`" ) ) {
			$wpdb->insert( $s, array( 'org_name' => '' ) );
		}

		$d = $wpdb->prefix . 'meal_departments';
		// Departments are not seeded on activation.
		// User selects institution type in Settings → Настройки пищеблока,
		// which triggers seed_default_departments().

		$o = $wpdb->prefix . 'meal_oc_monitoring';
		if ( ! $wpdb->get_var( "SELECT COUNT(*) FROM `$o`" ) ) {
			$wpdb->insert( $o, array( 'school_name' => '' ) );
		}
	}

	private static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'meal_daily_check' ) ) {
			wp_schedule_event( time(), 'daily', 'meal_daily_check' );
		}
	}
}
