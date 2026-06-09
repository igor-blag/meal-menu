<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class Excel_TM {

	public static function generate( string $school_type, int $year, bool $is_camp = false ): string {
		require_once MEAL_MENU_DIR . 'vendor/autoload.php';

		$db        = DB::instance();
		$templates = $is_camp ? $db->get_camp_templates( $school_type ) : $db->get_templates( $school_type );
		$settings  = $db->get_kitchen_settings();

		$org_name    = $settings['org_name']            ?? '';
		$position    = $settings['tm_approver_position'] ?? '';
		$name        = $settings['tm_approver_name']     ?? '';
		$approve_date = $settings['tm_approve_date']    ?? null;

		$total = count( $templates );
		if ( $total % 5 === 0 ) {
			$days_per_week = 5;
		} elseif ( $total % 6 === 0 ) {
			$days_per_week = 6;
		} elseif ( $total % 7 === 0 ) {
			$days_per_week = 7;
		} else {
			$days_per_week = 5;
		}

		$approve_day   = null;
		$approve_month = null;
		$approve_year  = $year;
		if ( $approve_date ) {
			$dt = date_create( $approve_date );
			if ( $dt ) {
				$approve_day   = (int) date_format( $dt, 'j' );
				$approve_month = (int) date_format( $dt, 'n' );
				$approve_year  = (int) date_format( $dt, 'Y' );
			}
		}

		$age_category = match ( $school_type ) {
			'sm'   => '7-11 лет',
			'main' => '11-15 лет',
			'ss'   => '15-18 лет',
			default => '',
		};

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();
		$sheet->setTitle( 'Типовое меню' );
		$sheet->getTabColor()->setARGB( 'FFFFD966' );

		$col_widths = array(
			'A' => 6, 'B' => 6, 'C' => 15, 'D' => 16, 'E' => 36,
			'F' => 9, 'G' => 8, 'H' => 8, 'I' => 8, 'J' => 10, 'K' => 12, 'L' => 10,
		);
		foreach ( $col_widths as $col => $width ) {
			$sheet->getColumnDimension( $col )->setWidth( $width );
		}

		$BRD_M  = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM;
		$BRD_T  = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
		$CLR_GREY = 'FFD9D9D9';
		$H_CENTER = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
		$V_CENTER = \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;

		$sheet->setCellValue( 'A1', 'Школа' );
		$sheet->mergeCells( 'C1:E1' );
		$sheet->setCellValue( 'C1', $org_name );
		$sheet->setCellValue( 'F1', 'Утвердил:' );
		$sheet->setCellValue( 'G1', 'должность' );
		$sheet->mergeCells( 'H1:K1' );
		$sheet->setCellValue( 'H1', $position );
		$sheet->getStyle( 'A1:L1' )->applyFromArray( array(
			'font'      => array( 'bold' => false, 'size' => 10 ),
			'alignment' => array( 'vertical' => $V_CENTER ),
		) );
		$sheet->getStyle( 'C1' )->applyFromArray( array(
			'borders' => array( 'bottom' => array( 'borderStyle' => $BRD_T ) ),
		) );
		$sheet->getStyle( 'H1' )->applyFromArray( array(
			'borders' => array( 'bottom' => array( 'borderStyle' => $BRD_T ) ),
		) );
		$sheet->getStyle( 'G1' )->applyFromArray( array(
			'font'      => array( 'color' => array( 'argb' => 'FF808080' ), 'size' => 8 ),
			'alignment' => array( 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT ),
		) );
		$sheet->getRowDimension( 1 )->setRowHeight( 15 );

		$sheet->setCellValue( 'A2', 'Типовое примерное меню приготавливаемых блюд' );
		$sheet->setCellValue( 'G2', 'фамилия' );
		$sheet->mergeCells( 'H2:K2' );
		$sheet->setCellValue( 'H2', $name );
		$sheet->getStyle( 'A2' )->applyFromArray( array(
			'font'      => array( 'bold' => true, 'size' => 11 ),
			'alignment' => array( 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => $V_CENTER ),
		) );
		$sheet->getStyle( 'H2' )->applyFromArray( array(
			'borders' => array( 'bottom' => array( 'borderStyle' => $BRD_T ) ),
		) );
		$sheet->getStyle( 'G2' )->applyFromArray( array(
			'font'      => array( 'color' => array( 'argb' => 'FF808080' ), 'size' => 8 ),
			'alignment' => array( 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT ),
		) );
		$sheet->getRowDimension( 2 )->setRowHeight( 18 );

		$sheet->setCellValue( 'A3', 'Возрастная категория' );
		$sheet->setCellValue( 'E3', $age_category );
		$sheet->setCellValue( 'G3', 'дата' );
		if ( $approve_day !== null ) {
			$sheet->setCellValue( 'H3', $approve_day );
		}
		if ( $approve_month !== null ) {
			$sheet->setCellValue( 'I3', $approve_month );
		}
		$sheet->setCellValue( 'J3', $approve_year );
		$sheet->getStyle( 'G3' )->applyFromArray( array(
			'font'      => array( 'color' => array( 'argb' => 'FF808080' ), 'size' => 8 ),
			'alignment' => array( 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT ),
		) );
		$sheet->getRowDimension( 3 )->setRowHeight( 15 );

		$sheet->setCellValue( 'H4', 'день' );
		$sheet->setCellValue( 'I4', 'месяц' );
		$sheet->setCellValue( 'J4', 'год' );
		$sheet->getStyle( 'H4:J4' )->applyFromArray( array(
			'font'      => array( 'color' => array( 'argb' => 'FF808080' ), 'size' => 8 ),
			'alignment' => array( 'horizontal' => $H_CENTER ),
		) );
		$sheet->getRowDimension( 4 )->setRowHeight( 10 );

		$col_headers = array(
			'A' => 'Неделя', 'B' => 'День недели', 'C' => 'Прием пищи',
			'D' => 'Раздел меню', 'E' => 'Блюда', 'F' => 'Вес блюда, г',
			'G' => 'Белки', 'H' => 'Жиры', 'I' => 'Углеводы',
			'J' => 'Калорийность', 'K' => '№ рецептуры', 'L' => 'Цена',
		);
		foreach ( $col_headers as $c => $v ) {
			$sheet->setCellValue( $c . '5', $v );
		}
		$sheet->getStyle( 'A5:L5' )->applyFromArray( array(
			'font'      => array( 'bold' => true, 'size' => 9 ),
			'alignment' => array( 'horizontal' => $H_CENTER, 'vertical' => $V_CENTER, 'wrapText' => true ),
			'fill'      => array( 'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => array( 'argb' => 'FFBDD7EE' ) ),
			'borders'   => array(
				'outline'        => array( 'borderStyle' => $BRD_M ),
				'insideVertical' => array( 'borderStyle' => $BRD_T ),
			),
		) );
		$sheet->getRowDimension( 5 )->setRowHeight( 28 );

		$row = 6;
		foreach ( $templates as $tpl ) {
			$day_num    = (int) $tpl['day_number'];
			$week       = (int) ceil( $day_num / $days_per_week );
			$day_in_week = ( ( $day_num - 1 ) % $days_per_week ) + 1;
			$items      = $db->get_template_items( (int) $tpl['id'] );

			$breakfast_items = array_merge( $items['breakfast'], $items['breakfast2'] );
			$lunch_items     = $items['lunch'];

			if ( empty( $breakfast_items ) ) {
				$breakfast_items = array( array() );
			}
			if ( empty( $lunch_items ) ) {
				$lunch_items = array( array() );
			}

			$b_start = $row;
			$week_row = $row;

			foreach ( $breakfast_items as $i => $item ) {
				$is_first = ( $i === 0 );
				if ( $is_first ) {
					$sheet->setCellValue( 'A' . $row, $week );
					$sheet->setCellValue( 'B' . $row, $day_in_week );
					$sheet->setCellValue( 'C' . $row, 'Завтрак' );
				}
				$sheet->setCellValue( 'D' . $row, $item['section']    ?? '' );
				$sheet->setCellValue( 'E' . $row, $item['dish_name']  ?? '' );
				if ( isset( $item['grams'] )   && $item['grams']   !== null ) { $sheet->setCellValue( 'F' . $row, (float) $item['grams'] ); }
				if ( isset( $item['protein'] ) && $item['protein'] !== null ) { $sheet->setCellValue( 'G' . $row, (float) $item['protein'] ); }
				if ( isset( $item['fat'] )     && $item['fat']     !== null ) { $sheet->setCellValue( 'H' . $row, (float) $item['fat'] ); }
				if ( isset( $item['carbs'] )   && $item['carbs']   !== null ) { $sheet->setCellValue( 'I' . $row, (float) $item['carbs'] ); }
				if ( isset( $item['kcal'] )    && $item['kcal']    !== null ) { $sheet->setCellValue( 'J' . $row, (float) $item['kcal'] ); }
				$sheet->setCellValue( 'K' . $row, $item['recipe_num'] ?? '' );
				if ( isset( $item['price'] )   && $item['price']   !== null ) { $sheet->setCellValue( 'L' . $row, (float) $item['price'] ); }
				self::row_style( $sheet, $row, $is_first, $BRD_T, $BRD_M );
				$row++;
			}
			$b_end = $row - 1;

			$b_total_row = $row;
			$sheet->setCellValue( 'D' . $row, 'итого' );
			$sheet->setCellValue( 'F' . $row, "=SUM(F{$b_start}:F{$b_end})" );
			$sheet->setCellValue( 'G' . $row, "=SUM(G{$b_start}:G{$b_end})" );
			$sheet->setCellValue( 'H' . $row, "=SUM(H{$b_start}:H{$b_end})" );
			$sheet->setCellValue( 'I' . $row, "=SUM(I{$b_start}:I{$b_end})" );
			$sheet->setCellValue( 'J' . $row, "=SUM(J{$b_start}:J{$b_end})" );
			$sheet->setCellValue( 'L' . $row, "=SUM(L{$b_start}:L{$b_end})" );
			$sheet->getStyle( 'A' . $row . ':L' . $row )->applyFromArray( array(
				'font'    => array( 'bold' => true, 'italic' => true ),
				'borders' => array(
					'top'    => array( 'borderStyle' => $BRD_T ),
					'bottom' => array( 'borderStyle' => $BRD_M ),
					'left'   => array( 'borderStyle' => $BRD_M ),
					'right'  => array( 'borderStyle' => $BRD_M ),
				),
			) );
			$sheet->getRowDimension( $row )->setRowHeight( 13 );
			$row++;

			$l_start = $row;
			foreach ( $lunch_items as $i => $item ) {
				$is_first = ( $i === 0 );
				if ( $is_first ) {
					$sheet->setCellValue( 'A' . $row, "=A{$week_row}" );
					$sheet->setCellValue( 'B' . $row, "=B{$week_row}" );
					$sheet->setCellValue( 'C' . $row, 'Обед' );
				}
				$sheet->setCellValue( 'D' . $row, $item['section']    ?? '' );
				$sheet->setCellValue( 'E' . $row, $item['dish_name']  ?? '' );
				if ( isset( $item['grams'] )   && $item['grams']   !== null ) { $sheet->setCellValue( 'F' . $row, (float) $item['grams'] ); }
				if ( isset( $item['protein'] ) && $item['protein'] !== null ) { $sheet->setCellValue( 'G' . $row, (float) $item['protein'] ); }
				if ( isset( $item['fat'] )     && $item['fat']     !== null ) { $sheet->setCellValue( 'H' . $row, (float) $item['fat'] ); }
				if ( isset( $item['carbs'] )   && $item['carbs']   !== null ) { $sheet->setCellValue( 'I' . $row, (float) $item['carbs'] ); }
				if ( isset( $item['kcal'] )    && $item['kcal']    !== null ) { $sheet->setCellValue( 'J' . $row, (float) $item['kcal'] ); }
				$sheet->setCellValue( 'K' . $row, $item['recipe_num'] ?? '' );
				if ( isset( $item['price'] )   && $item['price']   !== null ) { $sheet->setCellValue( 'L' . $row, (float) $item['price'] ); }
				self::row_style( $sheet, $row, $is_first, $BRD_T, $BRD_M );
				$row++;
			}
			$l_end = $row - 1;

			$l_total_row = $row;
			$sheet->setCellValue( 'D' . $row, 'итого' );
			$sheet->setCellValue( 'F' . $row, "=SUM(F{$l_start}:F{$l_end})" );
			$sheet->setCellValue( 'G' . $row, "=SUM(G{$l_start}:G{$l_end})" );
			$sheet->setCellValue( 'H' . $row, "=SUM(H{$l_start}:H{$l_end})" );
			$sheet->setCellValue( 'I' . $row, "=SUM(I{$l_start}:I{$l_end})" );
			$sheet->setCellValue( 'J' . $row, "=SUM(J{$l_start}:J{$l_end})" );
			$sheet->setCellValue( 'L' . $row, "=SUM(L{$l_start}:L{$l_end})" );
			$sheet->getStyle( 'A' . $row . ':L' . $row )->applyFromArray( array(
				'font'    => array( 'bold' => true, 'italic' => true ),
				'borders' => array(
					'top'    => array( 'borderStyle' => $BRD_T ),
					'bottom' => array( 'borderStyle' => $BRD_M ),
					'left'   => array( 'borderStyle' => $BRD_M ),
					'right'  => array( 'borderStyle' => $BRD_M ),
				),
			) );
			$sheet->getRowDimension( $row )->setRowHeight( 13 );
			$row++;

			$sheet->setCellValue( 'C' . $row, 'Итого за день:' );
			$sheet->setCellValue( 'F' . $row, "=F{$b_total_row}+F{$l_total_row}" );
			$sheet->setCellValue( 'G' . $row, "=G{$b_total_row}+G{$l_total_row}" );
			$sheet->setCellValue( 'H' . $row, "=H{$b_total_row}+H{$l_total_row}" );
			$sheet->setCellValue( 'I' . $row, "=I{$b_total_row}+I{$l_total_row}" );
			$sheet->setCellValue( 'J' . $row, "=J{$b_total_row}+J{$l_total_row}" );
			$sheet->setCellValue( 'L' . $row, "=L{$b_total_row}+L{$l_total_row}" );
			$sheet->getStyle( 'A' . $row . ':L' . $row )->applyFromArray( array(
				'fill'    => array( 'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => array( 'argb' => $CLR_GREY ) ),
				'font'    => array( 'bold' => true ),
				'borders' => array(
					'outline'        => array( 'borderStyle' => $BRD_M ),
					'insideVertical' => array( 'borderStyle' => $BRD_T ),
				),
			) );
			$sheet->getRowDimension( $row )->setRowHeight( 14 );
			$row++;
		}

		$upload_dir = wp_upload_dir();
		$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
		if ( ! is_dir( $meal_dir ) ) {
			wp_mkdir_p( $meal_dir );
		}
		$filepath = $meal_dir . "/tm{$year}-{$school_type}.xlsx";
		( new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet ) )->save( $filepath );
		meal_publish_file( $filepath );
		return $filepath;
	}

	private static function row_style( $sheet, int $row, bool $is_first, string $BRD_T, string $BRD_M ): void {
		$sheet->getStyle( 'A' . $row . ':L' . $row )->applyFromArray( array(
			'borders' => array(
				'top'            => array( 'borderStyle' => $is_first ? $BRD_M : $BRD_T ),
				'bottom'         => array( 'borderStyle' => $BRD_T ),
				'left'           => array( 'borderStyle' => $BRD_M ),
				'right'          => array( 'borderStyle' => $BRD_M ),
				'insideVertical' => array( 'borderStyle' => $BRD_T ),
			),
			'alignment' => array( 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER ),
		) );
		$sheet->getStyle( 'E' . $row )->getAlignment()->setWrapText( true );
		$sheet->getStyle( 'F' . $row . ':L' . $row )->getNumberFormat()->setFormatCode( '0.00' );
		$sheet->getRowDimension( $row )->setRowHeight( 13 );
	}
}
