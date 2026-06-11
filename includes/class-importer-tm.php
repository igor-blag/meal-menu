<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Importer_TM {

	public static function parse( string $filepath ): array {
		require_once MEAL_MENU_DIR . 'vendor/autoload.php';

		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $filepath );
		$sheet       = $spreadsheet->getActiveSheet();

		$meal_map = array(
			'завтрак'   => 'breakfast',
			'завтрак 2' => 'breakfast',
			'завтрак2'  => 'breakfast',
			'обед'      => 'lunch',
			'полдник'   => 'afternoon_snack',
			'ужин'      => 'dinner',
			'ужин 2'    => 'dinner2',
			'ужин2'     => 'dinner2',
		);

		$days        = array();
		$day_index   = 0;
		$current_meal = null;
		$day_items   = array();
		$max_row     = $sheet->getHighestDataRow();

		for ( $row = 6; $row <= $max_row; $row++ ) {
			$cell_a = $sheet->getCell( 'A' . $row );
			$raw_a  = trim( (string) $cell_a->getValue() );
			$col_a  = $cell_a->isFormula() ? '' : $raw_a;
			$col_c  = trim( (string) $sheet->getCell( 'C' . $row )->getValue() );
			$col_d  = trim( (string) $sheet->getCell( 'D' . $row )->getValue() );
			$col_e  = trim( (string) $sheet->getCell( 'E' . $row )->getValue() );

			if ( $col_e === '' && $col_d === '' && $col_c === '' ) {
				continue;
			}

			// New day detected — column A has week number (ignoring formula references from merged cells)
			if ( is_numeric( $col_a ) ) {
				if ( $day_index > 0 && ! empty( $day_items ) ) {
					$days[ $day_index ] = $day_items;
				}
				$day_index++;
				$current_meal = null;
				$day_items    = array();
			}

			// Skip summary rows
			if ( mb_strtolower( $col_d ) === 'итого' ) {
				continue;
			}
			if ( mb_strpos( mb_strtolower( $col_c ), 'итого за день' ) === 0 ) {
				continue;
			}

			// Detect meal type
			$key = mb_strtolower( $col_c );
			if ( isset( $meal_map[ $key ] ) ) {
				$current_meal = $meal_map[ $key ];
				continue;
			}

			if ( $current_meal === null || $col_e === '' ) {
				continue;
			}

			$val = function( string $col ) use ( $sheet, $row ): ?float {
				$v = trim( (string) $sheet->getCell( $col . $row )->getValue() );
				return ( $v !== '' && is_numeric( $v ) ) ? (float) $v : null;
			};

			$day_items[] = array(
				'meal_type'  => $current_meal,
				'section'    => $col_d,
				'recipe_num' => trim( (string) $sheet->getCell( 'K' . $row )->getValue() ),
				'dish_name'  => $col_e,
				'grams'      => $val( 'F' ),
				'price'      => $val( 'L' ),
				'kcal'       => $val( 'J' ),
				'protein'    => $val( 'G' ),
				'fat'        => $val( 'H' ),
				'carbs'      => $val( 'I' ),
			);
		}

		// Save last day
		if ( $day_index > 0 && ! empty( $day_items ) ) {
			$days[ $day_index ] = $day_items;
		}

		return $days;
	}
}
