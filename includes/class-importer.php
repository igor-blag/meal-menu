<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Importer {

	public static function parse( string $filepath ): array {
		require_once MEAL_MENU_DIR . 'vendor/autoload.php';

		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $filepath );
		$sheet       = $spreadsheet->getActiveSheet();

		$meal_map = array(
			'завтрак'   => 'breakfast',
			'завтрак 2' => 'breakfast2',
			'завтрак2'  => 'breakfast2',
			'обед'      => 'lunch',
			'полдник'   => 'afternoon_snack',
			'ужин'      => 'dinner',
			'ужин 2'    => 'dinner2',
			'ужин2'     => 'dinner2',
		);

		$items        = array();
		$current_meal = null;
		$sort_order   = 0;
		$max_row      = $sheet->getHighestDataRow();

		for ( $row = 4; $row <= $max_row; $row++ ) {
			$col_a = trim( (string) $sheet->getCell( 'A' . $row )->getValue() );
			$col_d = trim( (string) $sheet->getCell( 'D' . $row )->getValue() );

			if ( $col_a !== '' ) {
				$key = mb_strtolower( $col_a );
				if ( isset( $meal_map[ $key ] ) ) {
					$current_meal = $meal_map[ $key ];
					$sort_order   = 0;
				}
			}

			$col_b = trim( (string) $sheet->getCell( 'B' . $row )->getValue() );
			if ( $current_meal === null || ( $col_d === '' && $col_b === '' ) ) {
				continue;
			}

			$val = function( string $col ) use ( $sheet, $row ): ?float {
				$v = trim( (string) $sheet->getCell( $col . $row )->getValue() );
				return ( $v !== '' && is_numeric( $v ) ) ? (float) $v : null;
			};

			$items[] = array(
				'meal_type'  => $current_meal,
				'section'    => trim( (string) $sheet->getCell( 'B' . $row )->getValue() ),
				'recipe_num' => trim( (string) $sheet->getCell( 'C' . $row )->getValue() ),
				'dish_name'  => $col_d,
				'grams'      => $val( 'E' ),
				'price'      => $val( 'F' ),
				'kcal'       => $val( 'G' ),
				'protein'    => $val( 'H' ),
				'fat'        => $val( 'I' ),
				'carbs'      => $val( 'J' ),
				'sort_order' => $sort_order++,
			);
		}

		return $items;
	}
}
