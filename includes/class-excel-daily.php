<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Excel_Daily {

	public static function generate( string $date, string $type = 'sm' ): ?string {
		require_once MEAL_MENU_DIR . 'vendor/autoload.php';

		$db   = DB::instance();
		$cal  = $db->get_calendar_day( $date, $type );
		if ( ! $cal || ! $cal['template_id'] ) {
			return null;
		}

		$tpl      = $db->get_template( (int) $cal['template_id'] );
		$items    = $db->get_template_items( (int) $cal['template_id'] );
		$dept_info = $db->get_department( $type );
		$org_name = $db->get_org_name() ?: ( $cal['school'] ?? '-' );
		$dept     = $dept_info ? $dept_info['dept_name'] : ( $cal['dept'] ?? '' );
		$date_obj = new \DateTime( $date );
		$day_number = $tpl ? (int) $tpl['day_number'] : null;

		$sections = array(
			'breakfast'  => array( 'label' => 'Завтрак',   'items' => $items['breakfast'] ),
			'breakfast2' => array( 'label' => 'Завтрак 2', 'items' => $items['breakfast2'] ),
			'lunch'      => array( 'label' => 'Обед',       'items' => $items['lunch'] ),
		);
		if ( $tpl && ! empty( $tpl['is_boarding'] ) ) {
			$sections['afternoon_snack'] = array( 'label' => 'Полдник', 'items' => $items['afternoon_snack'] );
			$sections['dinner']          = array( 'label' => 'Ужин',    'items' => $items['dinner'] );
			$sections['dinner2']         = array( 'label' => 'Ужин 2',  'items' => $items['dinner2'] );
		}

		$standard_sections = array(
			'breakfast'       => array( 'гор.блюдо', 'гор.напиток', 'хлеб', 'фрукты' ),
			'breakfast2'      => array( 'фрукты' ),
			'lunch'           => array( 'закуска', '1 блюдо', '2 блюдо', 'гарнир', 'напиток', 'хлеб бел.', 'хлеб черн.' ),
			'afternoon_snack' => array( 'булочное', 'напиток' ),
			'dinner'          => array( 'гор.блюдо', 'гарнир', 'напиток', 'хлеб' ),
			'dinner2'         => array( 'кисломол.', 'булочное', 'напиток', 'фрукты' ),
		);

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();

		$tab_name = $day_number ? $day_number . ' день' : 'Меню';
		$sheet->setTitle( mb_substr( $tab_name, 0, 31 ) );
		$sheet->getTabColor()->setARGB( 'FFFFD966' );
		$sheet->setShowGridlines( false );
		$sheet->setShowRowColHeaders( false );

		foreach ( array( 'A' => 12.14, 'B' => 11.57, 'C' => 8, 'D' => 41.57, 'E' => 10.14, 'G' => 13.43, 'H' => 7.71, 'I' => 7.86, 'J' => 10.43 ) as $c => $w ) {
			$sheet->getColumnDimension( $c )->setWidth( $w );
		}

		$sheet->setCellValue( 'A1', 'Школа' );
		$sheet->setCellValue( 'B1', $org_name );
		$sheet->mergeCells( 'B1:D1' );
		$sheet->setCellValue( 'E1', 'Отд./корп' );
		$sheet->setCellValue( 'F1', $dept );
		$sheet->setCellValue( 'I1', 'День' );
		$sheet->setCellValue( 'J1', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( $date_obj ) );
		$sheet->getStyle( 'J1' )->getNumberFormat()->setFormatCode( 'm/d/yyyy' );

		foreach ( array( 'B1:D1', 'F1', 'J1' ) as $r ) {
			$sheet->getStyle( $r )->getProtection()->setLocked( \PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED );
		}

		$fill_yellow = array(
			'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
			'startColor' => array( 'argb' => 'FFFFF2CC' ),
			'endColor'   => array( 'argb' => 'FFFFFFFF' ),
		);
		$fill_white  = array(
			'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
			'startColor' => array( 'argb' => 'FFFFFFFF' ),
			'endColor'   => array( 'argb' => 'FFFFFFFF' ),
		);

		foreach ( array( 'B1:D1', 'J1' ) as $range ) {
			$sheet->getStyle( $range )->applyFromArray( array(
				'fill'    => $fill_yellow,
				'borders' => array( 'allBorders' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN ) ),
			) );
		}
		$sheet->getStyle( 'F1' )->applyFromArray( array(
			'fill'         => $fill_yellow,
			'borders'      => array( 'allBorders' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN ) ),
			'numberFormat' => array( 'formatCode' => '@' ),
		) );

		$sheet->getRowDimension( 2 )->setRowHeight( 7.5 );

		$headers = array(
			'A' => 'Прием пищи', 'B' => 'Раздел', 'C' => '№ рец.', 'D' => 'Блюдо',
			'E' => 'Выход, г', 'F' => 'Цена', 'G' => 'Калорийность', 'H' => 'Белки', 'I' => 'Жиры', 'J' => 'Углеводы',
		);
		foreach ( $headers as $col => $val ) {
			$sheet->setCellValue( $col . '3', $val );
		}
		$sheet->getRowDimension( 3 )->setRowHeight( 15 );

		$sheet->getStyle( 'A3:J3' )->applyFromArray( array(
			'alignment' => array(
				'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
				'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM,
			),
			'borders' => array(
				'allBorders' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN ),
			),
		) );
		foreach ( range( 'A', 'J' ) as $col ) {
			$sheet->getStyle( $col . '3' )->getBorders()->getTop()->setBorderStyle( \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM );
			$sheet->getStyle( $col . '3' )->getBorders()->getBottom()->setBorderStyle( \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM );
		}
		$sheet->getStyle( 'A3' )->getBorders()->getLeft()->setBorderStyle( \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM );
		$sheet->getStyle( 'J3' )->getBorders()->getRight()->setBorderStyle( \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM );

		$row = 4;
		foreach ( $sections as $section_key => $section ) {
			$meal_rows = $section['items'];
			if ( empty( $meal_rows ) ) {
				$meal_rows = array( array() );
			}
			$std_list = array_map( 'mb_strtolower', $standard_sections[ $section_key ] ?? array() );

			foreach ( $meal_rows as $i => $item ) {
				$is_first = ( $i === 0 );

				$sheet->setCellValue( 'A' . $row, $is_first ? $section['label'] : '' );
				$sheet->setCellValue( 'B' . $row, $item['section']    ?? '' );
				$sheet->setCellValue( 'C' . $row, $item['recipe_num'] ?? '' );
				$sheet->setCellValue( 'D' . $row, $item['dish_name']  ?? '' );
				$sheet->setCellValue( 'E' . $row, isset( $item['grams'] )   ? (float) $item['grams']   : '' );
				$sheet->setCellValue( 'F' . $row, isset( $item['price'] )   ? (float) $item['price']   : '' );
				$sheet->setCellValue( 'G' . $row, isset( $item['kcal'] )    ? (float) $item['kcal']    : '' );
				$sheet->setCellValue( 'H' . $row, isset( $item['protein'] ) ? (float) $item['protein'] : '' );
				$sheet->setCellValue( 'I' . $row, isset( $item['fat'] )     ? (float) $item['fat']     : '' );
				$sheet->setCellValue( 'J' . $row, isset( $item['carbs'] )   ? (float) $item['carbs']   : '' );

				if ( mb_strlen( $item['dish_name'] ?? '' ) > 40 ) {
					$sheet->getRowDimension( $row )->setRowHeight( 28.8 );
				}

				$is_standard = in_array( mb_strtolower( trim( $item['section'] ?? '' ) ), $std_list, true );
				$top_brd     = $is_first ? \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM : \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;

				$sheet->getStyle( 'A' . $row )->applyFromArray( array(
					'borders'   => array(
						'left' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM ),
						'top'  => array( 'borderStyle' => $is_first ? \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM : \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE ),
					),
					'alignment' => array( 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM ),
				) );

				$sheet->getStyle( 'B' . $row . ':J' . $row )->applyFromArray( array(
					'fill'    => $fill_yellow,
					'borders' => array(
						'allBorders' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN ),
						'top'        => array( 'borderStyle' => $top_brd ),
					),
					'alignment' => array( 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM ),
				) );

				if ( $is_standard ) {
					$sheet->getStyle( 'B' . $row )->applyFromArray( array( 'fill' => $fill_white ) );
				}
				$sheet->getStyle( 'J' . $row )->getBorders()->getRight()->setBorderStyle( \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM );

				if ( $is_standard ) {
					$sheet->getStyle( 'C' . $row . ':J' . $row )->getProtection()->setLocked( \PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED );
				} else {
					$sheet->getStyle( 'B' . $row . ':J' . $row )->getProtection()->setLocked( \PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED );
				}

				$sheet->getStyle( 'E' . $row )->getNumberFormat()->setFormatCode( '0' );
				$sheet->getStyle( 'F' . $row )->getNumberFormat()->setFormatCode( '0.00' );
				$sheet->getStyle( 'G' . $row . ':J' . $row )->getNumberFormat()->setFormatCode( '0' );
				$sheet->getStyle( 'D' . $row )->getAlignment()->setWrapText( true );
				$row++;
			}

			$sheet->getStyle( 'A' . $row )->applyFromArray( array(
				'borders'   => array(
					'left'   => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM ),
					'bottom' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM ),
				),
				'alignment' => array( 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM ),
			) );
			$sheet->getStyle( 'B' . $row . ':J' . $row )->applyFromArray( array(
				'fill'    => $fill_yellow,
				'borders' => array(
					'allBorders' => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN ),
					'bottom'     => array( 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM ),
				),
				'alignment' => array( 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM ),
			) );
			$sheet->getStyle( 'J' . $row )->getBorders()->getRight()->setBorderStyle( \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM );
			$sheet->getStyle( 'B' . $row . ':J' . $row )->getProtection()->setLocked( \PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED );
			$sheet->getStyle( 'E' . $row )->getNumberFormat()->setFormatCode( '0' );
			$sheet->getStyle( 'F' . $row )->getNumberFormat()->setFormatCode( '0.00' );
			$sheet->getStyle( 'G' . $row . ':J' . $row )->getNumberFormat()->setFormatCode( '0' );
			$sheet->getStyle( 'D' . $row )->getAlignment()->setWrapText( true );
			$row++;
		}

		$sheet->getProtection()->setSheet( true );

		$sheet->getPageSetup()
			->setOrientation( \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE )
			->setPaperSize( \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4 );
		$sheet->getPageMargins()->setLeft( 0.25 )->setRight( 0.25 )->setTop( 0.75 )->setBottom( 0.75 );

		$upload_dir = wp_upload_dir();
		$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
		if ( ! is_dir( $meal_dir ) ) {
			wp_mkdir_p( $meal_dir );
		}

		$suffix   = $dept_info ? $dept_info['file_suffix'] : ( ( $type !== 'main' ) ? "-{$type}" : '' );
		$filepath = $meal_dir . '/' . $date_obj->format( 'Y-m-d' ) . $suffix . '.xlsx';
		( new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet ) )->save( $filepath );
		meal_publish_file( $filepath );
		return $filepath;
	}

	public static function cleanup_old_files(): int {
		$upload_dir = wp_upload_dir();
		$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
		$deleted    = 0;
		$cutoff     = time() - ( (int) get_option( 'meal_delete_after_days', 14 ) * 86400 );
		foreach ( glob( $meal_dir . '/*.xlsx' ) ?: array() as $file ) {
			if ( filemtime( $file ) < $cutoff ) {
				unlink( $file );
				$deleted++;
			}
		}
		$food_dir = meal_food_dir();
		if ( is_dir( $food_dir ) ) {
			foreach ( glob( $food_dir . '*.xlsx' ) ?: array() as $file ) {
				if ( filemtime( $file ) < $cutoff ) {
					unlink( $file );
					$deleted++;
				}
			}
		}
		return $deleted;
	}
}
