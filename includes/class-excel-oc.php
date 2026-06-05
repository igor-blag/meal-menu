<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Excel_OC {

	public static function generate(): string {
		require_once MEAL_MENU_DIR . 'vendor/autoload.php';

		$db        = DB::instance();
		$d         = $db->get_oc_monitoring();
		$template  = MEAL_MENU_DIR . 'data/findex-template.xlsx';

		if ( ! file_exists( $template ) ) {
			return '';
		}

		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $template );
		$sheet       = $spreadsheet->getActiveSheet();

		$sheet->getCell( 'B1' )->setValue( $d['school_name'] );
		if ( $d['report_date'] ) {
			$dt = new \DateTime( $d['report_date'] );
			$sheet->getCell( 'D1' )->setValue( $dt->format( 'd.m.Y' ) );
			$sheet->getStyle( 'D1' )->getNumberFormat()->setFormatCode( '@' );
		}

		$sheet->getCell( 'C4' )->setValue( $d['s1_url'] );

		$sheet->getCell( 'C6' )->setValue( $d['s2_hotline'] );
		$sheet->getCell( 'C7' )->setValue( $d['s2_chat_url'] );
		$sheet->getCell( 'C8' )->setValue( $d['s2_forum_url'] );

		$diets = array(
			1 => array( 'type' => $d['s3_diet1_type'], 'url' => $d['s3_diet1_url'], 'row_t' => 10, 'row_u' => 11 ),
			2 => array( 'type' => $d['s3_diet2_type'], 'url' => $d['s3_diet2_url'], 'row_t' => 12, 'row_u' => 13 ),
			3 => array( 'type' => $d['s3_diet3_type'], 'url' => $d['s3_diet3_url'], 'row_t' => 14, 'row_u' => 15 ),
			4 => array( 'type' => $d['s3_diet4_type'], 'url' => $d['s3_diet4_url'], 'row_t' => 16, 'row_u' => 17 ),
		);
		foreach ( $diets as $diet ) {
			$sheet->getCell( 'C' . $diet['row_t'] )->setValue( $diet['type'] );
			$sheet->getCell( 'C' . $diet['row_u'] )->setValue( $diet['url'] );
		}

		$sheet->getCell( 'C19' )->setValue( $d['s4_survey_url'] );
		$sheet->getCell( 'C20' )->setValue( $d['s4_results_url'] );
		$sheet->getCell( 'C22' )->setValue( $d['s5_page_url'] );
		$sheet->getCell( 'C23' )->setValue( $d['s5_materials_url'] );
		$sheet->getCell( 'C25' )->setValue( $d['s6_acts_url'] );
		$sheet->getCell( 'C26' )->setValue( $d['s6_photos_url'] );

		foreach ( array( 28, 29, 30, 31, 32 ) as $r ) {
			$sheet->getCell( 'C' . $r )->setValue( '' );
		}
		$waste_row_map = array( '20' => 28, '30' => 29, '40' => 30, '50' => 31, 'none' => 32 );
		if ( ! empty( $d['s7_waste_level'] ) && isset( $waste_row_map[ $d['s7_waste_level'] ] ) ) {
			$sheet->getCell( 'C' . $waste_row_map[ $d['s7_waste_level'] ] )->setValue( '+' );
		}

		$upload_dir = wp_upload_dir();
		$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
		if ( ! is_dir( $meal_dir ) ) {
			wp_mkdir_p( $meal_dir );
		}
		$filepath = $meal_dir . '/findex.xlsx';
		( new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet ) )->save( $filepath );
		meal_publish_file( $filepath );
		return $filepath;
	}
}
