<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Excel_KP {

	public static function generate( int $year, string $type = 'sm' ): string {
		require_once MEAL_MENU_DIR . 'vendor/autoload.php';

		$db      = DB::instance();
		$dept_info = $db->get_department( $type );
		$data_type = $type;
		if ( $dept_info && ! empty( $dept_info['merged_with'] ) ) {
			$target = $db->get_department( $dept_info['merged_with'] );
			if ( $target ) {
				$data_type = $target['code'];
			}
		}
		$cal_data = $db->get_calendar_year( $year, $data_type );

		$upload_dir = wp_upload_dir();
		$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
		if ( ! is_dir( $meal_dir ) ) {
			wp_mkdir_p( $meal_dir );
		}
		$filepath = $meal_dir . "/kp{$year}.xlsx";

		if ( file_exists( $filepath ) ) {
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $filepath );
			$sheet       = $spreadsheet->getActiveSheet();
			self::update_day_cells( $sheet, $year, $cal_data );
		} else {
			$spreadsheet = self::create( $year, $cal_data );
			$sheet       = $spreadsheet->getActiveSheet();
		}

		( new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet ) )->save( $filepath );
		meal_publish_file( $filepath );
		return $filepath;
	}

	private static function create( int $year, array $cal_data ): \PhpOffice\PhpSpreadsheet\Spreadsheet {
		$month_names_ru = array(
			1 => 'январь', 2 => 'февраль', 3 => 'март',  4 => 'апрель',
			5 => 'май',    6 => 'июнь',    9 => 'сентябрь', 10 => 'октябрь',
			11 => 'ноябрь', 12 => 'декабрь',
		);
		$school_months = array( 1, 2, 3, 4, 5, 6, 9, 10, 11, 12 );

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$spreadsheet->getDefaultStyle()->getFont()->setSize( 10 );
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle( 'Кп ' . $year );

		$sheet->getColumnDimension( 'A' )->setWidth( 7.857 );
		for ( $col = 2; $col <= 32; $col++ ) {
			$col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $col );
			$sheet->getColumnDimension( $col_letter )->setWidth( 4.286 );
		}

		$sheet->getRowDimension( 1 )->setRowHeight( 18.75 );

		$db       = DB::instance();
		$org_name = $db->get_org_name();

		$sheet->setCellValue( 'A1', 'Школа' );
		$sheet->mergeCells( 'B1:J1' );
		$sheet->setCellValue( 'B1', $org_name );
		$sheet->getStyle( 'B1:J1' )->applyFromArray( array(
			'fill'      => array( 'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => array( 'argb' => 'FFFFF2CC' ) ),
			'alignment' => array( 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT ),
			'borders'   => array( 'outline' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN ) ),
		) );
		$sheet->setCellValue( 'L1', 'Календарь питания' );
		$sheet->getStyle( 'L1' )->getFont()->setBold( true )->setSize( 14 );
		$sheet->setCellValue( 'AC1', 'Год' );
		$sheet->mergeCells( 'AD1:AE1' );
		$sheet->setCellValue( 'AD1', $year );
		$sheet->getStyle( 'AD1:AE1' )->applyFromArray( array(
			'fill'         => array( 'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => array( 'argb' => 'FFFFF2CC' ) ),
			'alignment'    => array( 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER ),
			'borders'      => array( 'outline' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN ) ),
			'numberFormat' => array( 'formatCode' => '0' ),
		) );

		$sheet->setCellValue( 'A3', 'Месяц' );
		$sheet->getStyle( 'A3' )->getAlignment()->setHorizontal( \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER );
		for ( $d = 1; $d <= 31; $d++ ) {
			$col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $d + 1 );
			$sheet->setCellValue( $col_letter . '3', $d );
			$sheet->getStyle( $col_letter . '3' )->getAlignment()->setHorizontal( \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER );
		}

		$row = 4;
		foreach ( $school_months as $month ) {
			$sheet->setCellValue( 'A' . $row, $month_names_ru[ $month ] );
			$sheet->getStyle( 'A' . $row )->getAlignment()->setHorizontal( \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER );
			self::fill_month_row( $sheet, $row, $year, $month, $cal_data );
			$row++;
		}

		$sheet->getStyle( 'A3:AF13' )->applyFromArray( array(
			'borders' => array( 'allBorders' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN ) ),
		) );

		$sheet->getPageSetup()
			->setOrientation( \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT )
			->setPaperSize( \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4 )
			->setFitToWidth( 1 )
			->setFitToHeight( 1 );

		return $spreadsheet;
	}

	private static function fill_month_row( $sheet, int $row, int $year, int $month, array $cal_data ): void {
		$days_in_month = (int) ( new \DateTime( sprintf( '%04d-%02d-01', $year, $month ) ) )->format( 't' );
		for ( $d = 1; $d <= 31; $d++ ) {
			$col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $d + 1 );
			$cell_ref   = $col_letter . $row;

			if ( $d > $days_in_month ) {
				$sheet->setCellValue( $cell_ref, null );
				continue;
			}

			$date_str = sprintf( '%04d-%02d-%02d', $year, $month, $d );
			$wday     = (int) ( new \DateTime( $date_str ) )->format( 'N' );

			if ( $wday >= 6 ) {
				$sheet->setCellValue( $cell_ref, null );
				continue;
			}

			if ( isset( $cal_data[ $date_str ] ) && $cal_data[ $date_str ]['template_id'] !== null ) {
				$day_num = (int) $cal_data[ $date_str ]['day_number'];
				$sheet->setCellValue( $cell_ref, $day_num );
				$sheet->getStyle( $cell_ref )->getAlignment()->setHorizontal( \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER );
			} else {
				$sheet->setCellValue( $cell_ref, null );
			}
		}
	}

	private static function update_day_cells( $sheet, int $year, array $cal_data ): void {
		$school_months = array( 1, 2, 3, 4, 5, 6, 9, 10, 11, 12 );
		$row = 4;
		foreach ( $school_months as $month ) {
			self::fill_month_row( $sheet, $row, $year, $month, $cal_data );
			$row++;
		}
	}
}
