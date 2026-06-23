<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Ajax {

	public function __construct() {
		add_action( 'wp_ajax_meal_save_day', array( $this, 'ajax_save_day' ) );
		add_action( 'wp_ajax_meal_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_meal_save_oc', array( $this, 'ajax_save_oc' ) );
		add_action( 'wp_ajax_meal_upload_oc', array( $this, 'ajax_upload_oc' ) );
		add_action( 'wp_ajax_meal_list_files', array( $this, 'ajax_list_files' ) );
		add_action( 'wp_ajax_meal_save_theme', array( $this, 'ajax_save_theme' ) );
		add_action( 'wp_ajax_meal_import_dropzone', array( $this, 'ajax_import_dropzone' ) );
		add_action( 'wp_ajax_meal_import_tm', array( $this, 'ajax_import_tm' ) );
		add_action( 'wp_ajax_meal_bulk_delete_templates', array( $this, 'ajax_bulk_delete_templates' ) );
		add_action( 'wp_ajax_meal_delete_file', array( $this, 'ajax_delete_file' ) );
		add_action( 'wp_ajax_meal_cleanup_files', array( $this, 'ajax_cleanup_files' ) );
		add_action( 'wp_ajax_meal_photo_upload', array( $this, 'ajax_photo_upload' ) );
		add_action( 'wp_ajax_meal_photo_analyze', array( $this, 'ajax_photo_analyze' ) );
		add_action( 'wp_ajax_meal_photo_save_template', array( $this, 'ajax_photo_save_template' ) );
		add_action( 'wp_ajax_meal_get_day_menu', array( $this, 'ajax_get_day_menu' ) );
		add_action( 'wp_ajax_nopriv_meal_get_day_menu', array( $this, 'ajax_get_day_menu' ) );
		add_action( 'wp_ajax_meal_get_calendar', array( $this, 'ajax_get_calendar' ) );
		add_action( 'wp_ajax_nopriv_meal_get_calendar', array( $this, 'ajax_get_calendar' ) );
	}

	public function ajax_save_day(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		if ( ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-save-day.php';
	}

	public function ajax_get_day_menu(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		$date    = $_GET['date'] ?? '';
		$type    = $_GET['type'] ?? '';
		$_GET['camp'] = ! empty( $_GET['camp'] ) ? '1' : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! $type ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-get-day-menu.php';
	}

	public function ajax_save_settings(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		$nonce = $data['nonce'] ?? '';
		if ( ! wp_verify_nonce( $nonce, 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-save-settings.php';
	}

	public function ajax_save_oc(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-save-oc.php';
	}

	public function ajax_upload_oc(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-upload-oc.php';
	}

	public function ajax_list_files(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$db = DB::instance();
		require MEAL_MENU_DIR . 'includes/ajax/ajax-list-files.php';
	}

	public function ajax_delete_file(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		if ( ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$files = isset( $data['files'] ) && is_array( $data['files'] ) ? $data['files'] : array( $data['file'] ?? '' );
		$upload_dir = wp_upload_dir();
		$deleted = 0;
		$errors = array();
		foreach ( $files as $filename ) {
			$filename = basename( $filename );
			if ( ! $filename ) continue;
			$filepath = $upload_dir['basedir'] . '/meal-menu/' . $filename;
			if ( ! is_file( $filepath ) ) {
				$errors[] = sprintf( __( 'Файл не найден: %s', 'meal-menu' ), $filename );
				continue;
			}
			if ( unlink( $filepath ) ) {
				$deleted++;
				$food_path = meal_food_dir() . $filename;
				if ( is_file( $food_path ) ) {
					unlink( $food_path );
				}
			} else {
				$errors[] = sprintf( __( 'Не удалось удалить: %s', 'meal-menu' ), $filename );
			}
		}
		wp_send_json( array(
			'ok'      => true,
			'deleted' => $deleted,
			'errors'  => $errors,
		) );
	}

	public function ajax_cleanup_files(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		if ( ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		$days  = max( 1, (int) ( $data['days'] ?? 15 ) );
		$cutoff = time() - ( $days * 86400 );
		$deleted = 0;
		$upload_dir = wp_upload_dir();
		$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
		foreach ( glob( $meal_dir . '/*.xlsx' ) ?: array() as $filepath ) {
			$name = basename( $filepath );
			if ( str_starts_with( $name, 'kp' ) || str_starts_with( $name, 'tm' ) || str_starts_with( $name, 'findex' ) ) {
				continue;
			}
			if ( filemtime( $filepath ) < $cutoff ) {
				unlink( $filepath );
				$deleted++;
				$food_path = meal_food_dir() . $name;
				if ( is_file( $food_path ) ) {
					unlink( $food_path );
				}
			}
		}
		wp_send_json( array( 'ok' => true, 'deleted' => $deleted ) );
	}

	public function ajax_save_theme(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true );
		if ( ! $data || ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}
		update_option( 'meal_theme_palette', sanitize_key( $data['palette'] ?? 'retro' ) );
		update_option( 'meal_theme_layout', sanitize_key( $data['layout'] ?? 'classic' ) );
		wp_send_json( array( 'ok' => true ) );
	}

	public function ajax_import_dropzone(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		$type    = sanitize_key( $_POST['type'] ?? 'sm' );
		$is_camp = ! empty( $_POST['camp'] );
		if ( ! isset( $_FILES['xlsx'] ) || $_FILES['xlsx']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка загрузки файла', 'meal-menu' ) ) );
		}

		$allowed = array(
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/zip',
			'application/octet-stream',
		);
		if ( ! in_array( mime_content_type( $_FILES['xlsx']['tmp_name'] ), $allowed, true ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверный формат файла', 'meal-menu' ) ) );
		}

		try {
			require_once MEAL_MENU_DIR . 'vendor/autoload.php';
			$tmp = $_FILES['xlsx']['tmp_name'];

			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $tmp );
			$a3 = trim( (string) $spreadsheet->getActiveSheet()->getCell( 'A3' )->getValue() );

			if ( $a3 === 'Возрастная категория' ) {
				$days = \Meal_Menu\Importer_TM::parse( $tmp );
				if ( empty( $days ) ) {
					wp_send_json( array( 'ok' => false, 'error' => __( 'В TM-файле нет данных', 'meal-menu' ) ) );
				}

				$db   = DB::instance();
				$dept = $db->get_department( $type );

				$existing = $is_camp ? $db->get_camp_templates( $type ) : $db->get_templates( $type );
				foreach ( $existing as $tpl ) {
					if ( $is_camp ) {
						$db->delete_camp_template( (int) $tpl['id'] );
					} else {
						$db->delete_template( (int) $tpl['id'] );
					}
				}

				$imported = 0;
				ksort( $days );
				foreach ( $days as $items ) {
					$tpl_id = $is_camp ? $db->add_camp_template( $type ) : $db->add_template( $type );

					if ( $is_camp && $dept && ! empty( $dept['camp_is_boarding'] ) ) {
						$db->set_camp_template_boarding( $tpl_id, 1 );
					} elseif ( ! $is_camp && $dept && ! empty( $dept['is_boarding'] ) ) {
						$db->set_template_boarding( $tpl_id, 1 );
					}

					if ( $is_camp ) {
						$db->save_camp_template_items( $tpl_id, $items );
					} else {
						$db->save_template_items( $tpl_id, $items );
					}
					$imported++;
				}

				\Meal_Menu\Shortcodes::invalidate_calendar_cache();
			wp_send_json( array( 'ok' => true, 'imported' => $imported ) );
			}

			$items = \Meal_Menu\Importer::parse( $tmp );
			if ( empty( $items ) ) {
				wp_send_json( array( 'ok' => false, 'error' => __( 'В файле нет блюд', 'meal-menu' ) ) );
			}

			$db     = DB::instance();
			$tpl_id = $is_camp ? $db->add_camp_template( $type ) : $db->add_template( $type );

			$dept = $db->get_department( $type );
			if ( $is_camp && $dept && ! empty( $dept['camp_is_boarding'] ) ) {
				$db->set_camp_template_boarding( $tpl_id, 1 );
			} elseif ( ! $is_camp && $dept && ! empty( $dept['is_boarding'] ) ) {
				$db->set_template_boarding( $tpl_id, 1 );
			}

			if ( $is_camp ) {
				$db->save_camp_template_items( $tpl_id, $items );
			} else {
				$db->save_template_items( $tpl_id, $items );
			}

			\Meal_Menu\Shortcodes::invalidate_calendar_cache();
			wp_send_json( array( 'ok' => true, 'id' => $tpl_id ) );
		} catch ( \Exception $e ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка обработки файла', 'meal-menu' ) ) );
		}
	}

	public function ajax_import_tm(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		$type    = sanitize_key( $_POST['type'] ?? 'sm' );
		$is_camp = ! empty( $_POST['camp'] );

		if ( ! isset( $_FILES['tm_xlsx'] ) || $_FILES['tm_xlsx']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка загрузки файла', 'meal-menu' ) ) );
		}

		$allowed = array(
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/zip',
			'application/octet-stream',
		);
		if ( ! in_array( mime_content_type( $_FILES['tm_xlsx']['tmp_name'] ), $allowed, true ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверный формат файла', 'meal-menu' ) ) );
		}

		try {
			$days = \Meal_Menu\Importer_TM::parse( $_FILES['tm_xlsx']['tmp_name'] );
			if ( empty( $days ) ) {
				wp_send_json( array( 'ok' => false, 'error' => __( 'В файле нет данных', 'meal-menu' ) ) );
			}

			$db   = DB::instance();
			$dept = $db->get_department( $type );

			$existing = $is_camp ? $db->get_camp_templates( $type ) : $db->get_templates( $type );
			foreach ( $existing as $tpl ) {
				if ( $is_camp ) {
					$db->delete_camp_template( (int) $tpl['id'] );
				} else {
					$db->delete_template( (int) $tpl['id'] );
				}
			}

			$imported = 0;
			ksort( $days );
			foreach ( $days as $items ) {
				$tpl_id = $is_camp ? $db->add_camp_template( $type ) : $db->add_template( $type );

				if ( $is_camp && $dept && ! empty( $dept['camp_is_boarding'] ) ) {
					$db->set_camp_template_boarding( $tpl_id, 1 );
				} elseif ( ! $is_camp && $dept && ! empty( $dept['is_boarding'] ) ) {
					$db->set_template_boarding( $tpl_id, 1 );
				}

				if ( $is_camp ) {
					$db->save_camp_template_items( $tpl_id, $items );
				} else {
					$db->save_template_items( $tpl_id, $items );
				}
				$imported++;
			}

			\Meal_Menu\Shortcodes::invalidate_calendar_cache();
			wp_send_json( array( 'ok' => true, 'imported' => $imported ) );
		} catch ( \Exception $e ) {
			wp_send_json( array( 'ok' => false, 'error' => $e->getMessage() ) );
		}
	}

	public function ajax_bulk_delete_templates(): void {
		$data = json_decode( file_get_contents( 'php://input' ), true ) ?? array();
		if ( ! wp_verify_nonce( $data['nonce'] ?? '', 'meal_menu_nonce' ) ) {
			wp_die( -1 );
		}
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		$db      = DB::instance();
		$is_camp = ! empty( $data['camp'] );
		$action  = $data['action'] ?? '';

		if ( $action === 'delete' ) {
			$id = (int) ( $data['id'] ?? 0 );
			if ( $id ) {
				if ( $is_camp ) {
					$db->delete_camp_template( $id );
				} else {
					$db->delete_template( $id );
				}
			}
			\Meal_Menu\Shortcodes::invalidate_calendar_cache();
			wp_send_json( array( 'ok' => true ) );
		}

		if ( $action === 'bulk_delete' ) {
			$ids = $data['ids'] ?? array();
			if ( ! is_array( $ids ) ) {
				wp_send_json( array( 'ok' => false, 'error' => __( 'Неверный запрос', 'meal-menu' ) ) );
			}
			$deleted = 0;
			foreach ( $ids as $id ) {
				$id = (int) $id;
				if ( $id < 1 ) continue;
				if ( $is_camp ) {
					$db->delete_camp_template( $id );
				} else {
					$db->delete_template( $id );
				}
				$deleted++;
			}
			\Meal_Menu\Shortcodes::invalidate_calendar_cache();
			wp_send_json( array( 'ok' => true, 'deleted' => $deleted ) );
		}

		wp_send_json( array( 'ok' => false, 'error' => __( 'Неизвестное действие', 'meal-menu' ) ) );
	}

	public function is_ai_available(): bool {
		return \Meal_Menu\Importer_Photo::is_ai_available();
	}

	public function ajax_photo_upload(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		if ( ! isset( $_FILES['photo'] ) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка загрузки файла', 'meal-menu' ) ) );
		}

		$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
		$mime    = mime_content_type( $_FILES['photo']['tmp_name'] );
		if ( ! in_array( $mime, $allowed, true ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Неверный формат файла. Поддерживаются JPEG, PNG, WebP.', 'meal-menu' ) ) );
		}

		if ( $_FILES['photo']['size'] > 15 * 1024 * 1024 ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Файл слишком большой (макс. 15 MB)', 'meal-menu' ) ) );
		}

		\Meal_Menu\Importer_Photo::cleanup();

		$dest = \Meal_Menu\Importer_Photo::save_upload( $_FILES['photo']['tmp_name'] );
		if ( is_wp_error( $dest ) ) {
			wp_send_json( array( 'ok' => false, 'error' => $dest->get_error_message() ) );
		}

		wp_send_json( array( 'ok' => true, 'path' => $dest ) );
	}

	public function ajax_photo_analyze(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		$path = sanitize_text_field( $_POST['path'] ?? '' );
		if ( ! $path || ! file_exists( $path ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Файл не найден', 'meal-menu' ) ) );
		}

		if ( ! $this->is_ai_available() ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'AI не настроен. Настройте коннектор в Settings → Connectors.', 'meal-menu' ) ) );
		}

		$result = \Meal_Menu\Importer_Photo::analyze( $path );

		if ( is_wp_error( $result ) ) {
			wp_send_json( array( 'ok' => false, 'error' => $result->get_error_message() ) );
		}

		wp_send_json( $result );
	}

	public function ajax_photo_save_template(): void {
		check_ajax_referer( 'meal_menu_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_meal_menu' ) ) {
			wp_die( -1 );
		}

		$type       = sanitize_key( $_POST['type'] ?? 'sm' );
		$is_boarding = ! empty( $_POST['is_boarding'] );
		$day_number  = isset( $_POST['day_number'] ) && $_POST['day_number'] !== '' ? (int) $_POST['day_number'] : null;

		$raw_items = array();
		$items_json = isset( $_POST['items_json'] ) ? trim( $_POST['items_json'] ) : '';
		if ( $items_json ) {
			$decoded = json_decode( wp_unslash( $items_json ), true );
			if ( is_array( $decoded ) ) {
				$raw_items = $decoded;
			}
		}
		if ( empty( $raw_items ) && isset( $_POST['items'] ) && is_array( $_POST['items'] ) ) {
			$raw_items = $_POST['items'];
		}

		if ( empty( $raw_items ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Нет блюд для сохранения', 'meal-menu' ) ) );
		}

		$valid_meal_types = array( 'breakfast', 'breakfast2', 'lunch', 'afternoon_snack', 'dinner', 'dinner2' );
		$items = array();
		foreach ( $raw_items as $raw ) {
			$meal_type = sanitize_text_field( $raw['meal_type'] ?? '' );
			if ( ! in_array( $meal_type, $valid_meal_types, true ) ) {
				continue;
			}
			$dish_name = trim( sanitize_text_field( $raw['dish_name'] ?? '' ) );
			if ( $dish_name === '' ) {
				continue;
			}

			$items[] = array(
				'meal_type'  => $meal_type,
				'section'    => sanitize_text_field( $raw['section'] ?? '' ),
				'dish_name'  => $dish_name,
				'recipe_num' => sanitize_text_field( $raw['recipe_num'] ?? '' ),
				'grams'      => isset( $raw['grams'] ) && $raw['grams'] !== '' && is_numeric( $raw['grams'] ) ? (float) $raw['grams'] : null,
				'kcal'       => isset( $raw['kcal'] ) && $raw['kcal'] !== '' && is_numeric( $raw['kcal'] ) ? (float) $raw['kcal'] : null,
				'protein'    => isset( $raw['protein'] ) && $raw['protein'] !== '' && is_numeric( $raw['protein'] ) ? (float) $raw['protein'] : null,
				'fat'        => isset( $raw['fat'] ) && $raw['fat'] !== '' && is_numeric( $raw['fat'] ) ? (float) $raw['fat'] : null,
				'carbs'      => isset( $raw['carbs'] ) && $raw['carbs'] !== '' && is_numeric( $raw['carbs'] ) ? (float) $raw['carbs'] : null,
			);
		}

		if ( empty( $items ) ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Нет валидных блюд для сохранения', 'meal-menu' ) ) );
		}

		$db = DB::instance();

		if ( $day_number ) {
			$tpl_id = $db->add_template_with_day( $type, $day_number );
		} else {
			$tpl_id = $db->add_template( $type );
		}

		if ( ! $tpl_id ) {
			wp_send_json( array( 'ok' => false, 'error' => __( 'Ошибка создания шаблона', 'meal-menu' ) ) );
		}

		if ( $is_boarding ) {
			$db->set_template_boarding( $tpl_id, 1 );
		}

		$db->save_template_items( $tpl_id, $items );

		\Meal_Menu\Shortcodes::invalidate_calendar_cache();
		wp_send_json( array( 'ok' => true, 'id' => $tpl_id ) );
	}

	public function ajax_get_calendar(): void {
		$type   = sanitize_key( $_GET['meal_type'] ?? '' );
		$year   = (int) ( $_GET['meal_y'] ?? 0 );
		$month  = (int) ( $_GET['meal_m'] ?? 0 );
		$is_camp = ! empty( $_GET['meal_camp'] );

		if ( ! $type || ! $year || ! $month || $month < 1 || $month > 12 ) {
			wp_send_json( array( 'ok' => false, 'html' => '' ) );
		}

		$html = \Meal_Menu\Shortcodes::render_calendar_body( $type, $year, $month, $is_camp );
		wp_send_json( array( 'ok' => true, 'html' => $html ) );
	}
}
