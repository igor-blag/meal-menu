<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Importer_Photo {

	const TMP_DIR = 'meal-menu/ai-import';

	public static function analyze( string $image_path ) {
		if ( ! self::is_ai_available() ) {
			return new \WP_Error( 'ai_unavailable', __( 'AI не настроен. Настройте коннектор в Settings → Connectors.', 'meal-menu' ) );
		}

		$prompt = self::build_prompt();

		$model_prefs = array( 'google/gemini-2.5-flash', 'gemini-2.5-flash', 'gpt-4o-mini', 'claude-3-haiku' );

		try {
			$builder = wp_ai_client_prompt( $prompt )
				->using_temperature( 0.1 )
				->using_max_tokens( 4000 )
				->with_file( $image_path );

			self::apply_model_prefs( $builder, $model_prefs );

			if ( method_exists( $builder, 'as_json_response' ) ) {
				$builder->as_json_response( self::json_schema() );
			}

			$result = $builder->generate_text();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$cleaned = self::clean_ai_response( $result );
			$data = json_decode( $cleaned, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$builder = wp_ai_client_prompt( $prompt . "\n\nВАЖНО: ОТВЕТЬ ТОЛЬКО JSON БЕЗ ПОЯСНЕНИЙ. НЕ ИСПОЛЬЗУЙ ```json." )
					->using_temperature( 0.1 )
					->using_max_tokens( 4000 )
					->with_file( $image_path );

				self::apply_model_prefs( $builder, $model_prefs );

				if ( method_exists( $builder, 'as_json_response' ) ) {
					$builder->as_json_response( self::json_schema() );
				}

				$result = $builder->generate_text();
				if ( is_wp_error( $result ) ) {
					return $result;
				}

				$cleaned = self::clean_ai_response( $result );
				$data = json_decode( $cleaned, true );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					return new \WP_Error( 'parse_error', __( 'Не удалось распознать меню. Попробуйте другое фото.', 'meal-menu' ) );
				}
			}

			$data = self::normalize( $data );

			if ( empty( $data['items'] ) ) {
				return new \WP_Error( 'no_items', __( 'Не удалось найти блюда на фото. Попробуйте другое фото.', 'meal-menu' ) );
			}

			return array(
				'ok'   => true,
				'data' => $data,
			);

		} catch ( \Exception $e ) {
			return new \WP_Error( 'ai_error', $e->getMessage() );
		}
	}

	public static function is_ai_available(): bool {
		if ( ! function_exists( 'wp_supports_ai' ) || ! wp_supports_ai() ) {
			return false;
		}
		return true;
	}

	public static function build_prompt(): string {
		$custom = get_option( 'meal_ai_prompt', '' );
		if ( $custom !== '' ) {
			return $custom;
		}
		return self::get_default_prompt();
	}

	public static function get_default_prompt(): string {
		return 'Проанализируй фото меню и извлеки данные строго в формате JSON.

ВАЖНО: Ответ должен быть объектом, содержащим ключ "items".
Формат: {"items": [ {...}, {...} ]}

Ключи для каждого объекта в массиве:
- "meal_type": "breakfast", "breakfast2", "lunch", "afternoon_snack", "dinner".
- "section": категория (каша, суп, салат и т.д.).
- "recipe_num": номер тех. карты (из первой колонки).
- "dish_name": название блюда.
- "grams": вес (число).
- "protein": белки (число).
- "fat": жиры (число).
- "carbs": углеводы (число).
- "kcal": калории (число).

Правила:
- Если данных в таблице нет — ставь null.
- Используй точку как десятичный разделитель.
- Не включай строки "Итого" или "Всего".
- Верни ТОЛЬКО чистый JSON, без markdown-разметки (без ```json).';
	}

	private static function json_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'day_number' => array(
					'type' => array( 'integer', 'null' ),
				),
				'items'      => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'meal_type'  => array( 'type' => 'string' ),
							'section'    => array( 'type' => 'string' ),
							'dish_name'  => array( 'type' => 'string' ),
							'recipe_num' => array( 'type' => 'string' ),
							'grams'      => array( 'type' => array( 'number', 'null' ) ),
							'kcal'       => array( 'type' => array( 'number', 'null' ) ),
							'protein'    => array( 'type' => array( 'number', 'null' ) ),
							'fat'        => array( 'type' => array( 'number', 'null' ) ),
							'carbs'      => array( 'type' => array( 'number', 'null' ) ),
						),
						'required' => array( 'meal_type', 'dish_name' ),
					),
				),
			),
			'required'   => array( 'items' ),
		);
	}

	private static function apply_model_prefs( $builder, array $models ): void {
		try {
			$builder->using_model_preference( ...$models );
		} catch ( \Exception $e ) {
			try {
				$builder->using_model_preference( $models[0] );
			} catch ( \Exception $e2 ) {
			}
		}
	}

	private static function clean_ai_response( string $raw ): string {
		$raw = trim( $raw );
		$raw = preg_replace( '/^```(?:json)?\s*\n?/i', '', $raw );
		$raw = preg_replace( '/\n?```\s*$/i', '', $raw );
		return trim( $raw );
	}

	private static function val( array $item, string $primary, array $fallbacks ) {
		foreach ( array_merge( array( $primary ), $fallbacks ) as $key ) {
			if ( isset( $item[ $key ] ) && is_numeric( $item[ $key ] ) ) {
				return (float) $item[ $key ];
			}
		}
		return null;
	}

	private static function normalize( array $data ): array {
		$valid_meal_types = array( 'breakfast', 'breakfast2', 'lunch', 'afternoon_snack', 'dinner', 'dinner2' );

		$items = array();
		foreach ( $data['items'] ?? array() as $item ) {
			$meal_type = sanitize_text_field( $item['meal_type'] ?? '' );
			if ( ! in_array( $meal_type, $valid_meal_types, true ) ) {
				continue;
			}
			$dish_name = trim( sanitize_text_field( $item['dish_name'] ?? '' ) );
			if ( $dish_name === '' ) {
				continue;
			}

			$items[] = array(
				'meal_type'  => $meal_type,
				'section'    => sanitize_text_field( $item['section'] ?? '' ),
				'dish_name'  => $dish_name,
				'recipe_num' => sanitize_text_field( $item['recipe_num'] ?? $item['ttk'] ?? $item['recipe'] ?? '' ),
				'grams'      => self::val( $item, 'grams', array( 'weight', 'gram', 'massa', 'weight_g', 'portion' ) ),
				'kcal'       => self::val( $item, 'kcal', array( 'kKall', 'kkal', 'calories', 'kal', 'kalorii', 'energy' ) ),
				'protein'    => self::val( $item, 'protein', array( 'b', 'belki', 'protein_g', 'proteins' ) ),
				'fat'        => self::val( $item, 'fat', array( 'j', 'zhiry', 'fat_g', 'fats' ) ),
				'carbs'      => self::val( $item, 'carbs', array( 'u', 'uglevody', 'carbs_g', 'carbohydrates' ) ),
			);
		}

		$day_number = isset( $data['day_number'] ) && is_numeric( $data['day_number'] ) ? (int) $data['day_number'] : null;

		return array(
			'day_number' => $day_number,
			'items'      => $items,
		);
	}

	public static function save_upload( string $source_path ) {
		$upload_dir = wp_upload_dir();
		if ( $upload_dir['error'] ) {
			return new \WP_Error( 'upload_error', $upload_dir['error'] );
		}

		$tmp_dir = $upload_dir['basedir'] . '/' . self::TMP_DIR;
		if ( ! is_dir( $tmp_dir ) ) {
			wp_mkdir_p( $tmp_dir );
		}

		$ext      = pathinfo( $source_path, PATHINFO_EXTENSION ) ?: 'jpg';
		$filename = md5( uniqid( '', true ) ) . '.' . $ext;
		$dest     = $tmp_dir . '/' . $filename;

		if ( ! copy( $source_path, $dest ) ) {
			return new \WP_Error( 'copy_error', __( 'Ошибка сохранения файла', 'meal-menu' ) );
		}

		return $dest;
	}

	public static function cleanup(): int {
		$upload_dir = wp_upload_dir();
		$tmp_dir    = $upload_dir['basedir'] . '/' . self::TMP_DIR;
		if ( ! is_dir( $tmp_dir ) ) {
			return 0;
		}

		$count    = 0;
		$cutoff   = time() - 3600;
		$iterator = new \DirectoryIterator( $tmp_dir );

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && $file->getMTime() < $cutoff ) {
				@unlink( $file->getPathname() );
				$count++;
			}
		}

		return $count;
	}

	public static function delete_file( string $path ): void {
		if ( file_exists( $path ) ) {
			@unlink( $path );
		}
	}
}
