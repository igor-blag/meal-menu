<?php if ( ! defined( 'ABSPATH' ) ) exit;
$db = \Meal_Menu\DB::instance();
$upload_dir = wp_upload_dir();
$meal_dir   = $upload_dir['basedir'] . '/meal-menu';
$meal_url   = $upload_dir['baseurl'] . '/meal-menu';
$departments = $db->get_enabled_departments();
$nonce = wp_create_nonce( 'meal_menu_nonce' );
$all_files = glob( $meal_dir . '/*.xlsx' ) ?: array();

$daily_files = array();
$tmkp_files  = array();

foreach ( $all_files as $filepath ) {
	$name = basename( $filepath );
	if ( str_starts_with( $name, 'kp' ) || str_starts_with( $name, 'tm' ) || str_starts_with( $name, 'findex' ) ) {
		$tmkp_files[] = $filepath;
	} else {
		$daily_files[] = $filepath;
	}
}

rsort( $daily_files );

function meal_match_dept( $filename, $departments ) {
	$name_no_ext = basename( $filename, '.xlsx' );
	foreach ( $departments as $dep ) {
		$sfx = $dep['file_suffix'];
		if ( $sfx !== '' && str_ends_with( $name_no_ext, $sfx ) ) {
			return $dep;
		}
		if ( $sfx === '' && ! preg_match( '/-[a-z]+$/', $name_no_ext ) ) {
			$has_other = false;
			foreach ( $departments as $d2 ) {
				if ( $d2['file_suffix'] !== '' && str_ends_with( $name_no_ext, $d2['file_suffix'] ) ) {
					$has_other = true; break;
				}
			}
			if ( ! $has_other ) return $dep;
		}
	}
	return null;
}

function meal_match_tmkp_dept( $filename, $departments ) {
	if ( preg_match( '/^(?:kp|tm)\d+-([a-z]+)\.xlsx$/', basename( $filename ), $m ) ) {
		$code = $m[1];
		foreach ( $departments as $dep ) {
			if ( $dep['code'] === $code ) return $dep;
		}
	}
	return null;
}

function meal_group_by_dept( $files, $departments, $matcher = 'meal_match_dept' ) {
	$groups = array();
	foreach ( $departments as $dep ) {
		$groups[ $dep['code'] ] = array( 'label' => $dep['label'], 'files' => array() );
	}
	$groups['_unknown'] = array( 'label' => '', 'files' => array() );
	foreach ( $files as $f ) {
		$dep = call_user_func( $matcher, basename( $f ), $departments );
		$key = $dep ? $dep['code'] : '_unknown';
		$groups[ $key ]['files'][] = $f;
	}
	return array_filter( $groups, function( $g ) { return ! empty( $g['files'] ); } );
}

$daily_groups  = meal_group_by_dept( $daily_files, $departments, 'meal_match_dept' );
$tmkp_groups   = meal_group_by_dept( $tmkp_files, $departments, 'meal_match_tmkp_dept' );
?>
<div class="wrap meal-menu-wrap">
	<h1 class="page-title"><?php _e( 'Опубликованные файлы меню', 'meal-menu' ); ?></h1>

	<div class="files-columns">
	<div class="files-col">

	<?php if ( ! empty( $daily_files ) ): ?>
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
		<h2 style="margin:0;font-size:1rem"><?php _e( 'Ежедневное меню', 'meal-menu' ); ?></h2>
		<div style="display:flex;gap:6px">
			<button type="button" id="btn-del-selected" class="btn btn-danger btn-sm"><?php _e( 'Удалить выбранные', 'meal-menu' ); ?></button>
			<button type="button" id="btn-cleanup-files" class="btn btn-outline btn-sm"><?php _e( 'Удалить устаревшие (&gt;15 сут.)', 'meal-menu' ); ?></button>
		</div>
	</div>
	<div class="panel">
		<table class="menu-table">
			<thead>
				<tr>
					<th style="width:32px"><input type="checkbox" class="cb-select-all"></th>
					<th><?php _e( 'Файл', 'meal-menu' ); ?></th>
					<th style="width:80px"><?php _e( 'Размер', 'meal-menu' ); ?></th>
					<th style="width:100px"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $daily_files as $filepath ): ?>
				<tr>
					<td><input type="checkbox" class="cb-file" value="<?php echo esc_attr( basename( $filepath ) ); ?>"></td>
					<td><?php echo esc_html( basename( $filepath ) ); ?></td>
					<td><?php echo esc_html( size_format( filesize( $filepath ) ) ); ?></td>
					<td style="white-space:nowrap">
						<a href="<?php echo esc_url( $meal_url . '/' . basename( $filepath ) ); ?>" class="btn btn-outline btn-sm" download>&#x2193;</a>
						<button type="button" class="btn btn-danger btn-sm btn-del-file" data-file="<?php echo esc_attr( basename( $filepath ) ); ?>"><?php _e( 'Удалить', 'meal-menu' ); ?></button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	</div>
	<div class="files-col">

	<?php if ( ! empty( $tmkp_groups ) ): ?>
	<h2 style="margin:0 0 12px;font-size:1rem"><?php _e( 'Типовое меню и календарное планирование', 'meal-menu' ); ?></h2>
	<?php foreach ( $tmkp_groups as $code => $group ): ?>
	<div class="panel" style="margin-bottom:16px">
		<div class="panel-title"><?php echo esc_html( $group['label'] ?: __( 'Без отделения', 'meal-menu' ) ); ?></div>
		<table class="menu-table">
			<thead>
				<tr>
					<th style="width:32px"><input type="checkbox" class="cb-select-all"></th>
					<th><?php _e( 'Файл', 'meal-menu' ); ?></th>
					<th style="width:80px"><?php _e( 'Размер', 'meal-menu' ); ?></th>
					<th style="width:140px"><?php _e( 'Дата', 'meal-menu' ); ?></th>
					<th style="width:100px"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $group['files'] as $filepath ): ?>
				<tr>
					<td><input type="checkbox" class="cb-file" value="<?php echo esc_attr( basename( $filepath ) ); ?>"></td>
					<td><?php echo esc_html( basename( $filepath ) ); ?></td>
					<td><?php echo esc_html( size_format( filesize( $filepath ) ) ); ?></td>
					<td><?php echo esc_html( gmdate( 'Y-m-d H:i', filemtime( $filepath ) ) ); ?></td>
					<td style="white-space:nowrap">
						<a href="<?php echo esc_url( $meal_url . '/' . basename( $filepath ) ); ?>" class="btn btn-outline btn-sm" download>&#x2193;</a>
						<button type="button" class="btn btn-danger btn-sm btn-del-file" data-file="<?php echo esc_attr( basename( $filepath ) ); ?>"><?php _e( 'Удалить', 'meal-menu' ); ?></button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endforeach; ?>
	<?php endif; ?>

	</div>
	</div>

	<style>
	@media (min-width: 1200px) {
		.files-columns { display: flex; gap: 24px; align-items: flex-start; }
		.files-col { flex: 1; min-width: 0; }
	}
	</style>

	<?php if ( empty( $daily_files ) && empty( $tmkp_groups ) ): ?>
	<div class="alert alert-error"><?php _e( 'Нет сгенерированных файлов.', 'meal-menu' ); ?></div>
	<?php endif; ?>
</div>

<script>
(function() {
	var nonce = <?php echo json_encode( $nonce ); ?>;
	var ajaxUrl = <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

	function apiPost(payload, cb) {
		payload.nonce = nonce;
		jQuery.post(ajaxUrl + '?action=meal_' + payload.action, JSON.stringify(payload), function(r) {
			try { cb(typeof r === 'object' ? r : JSON.parse(r)); }
			catch(e) { cb({ ok: false, error: 'Ошибка ответа' }); }
		}).fail(function() { cb({ ok: false, error: 'Сетевая ошибка' }); });
	}

	document.addEventListener('change', function(e) {
		if (e.target.classList.contains('cb-select-all')) {
			var checked = e.target.checked;
			var table = e.target.closest('table');
			table.querySelectorAll('.cb-file').forEach(function(cb) { cb.checked = checked; });
		}
	});

	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.btn-del-file');
		if (!btn) return;
		if (!confirm('Удалить файл «' + btn.dataset.file + '»?')) return;
		apiPost({ action: 'delete_file', file: btn.dataset.file }, function(r) {
			if (r.ok) { location.reload(); }
			else { alert(r.error || 'Ошибка'); }
		});
	});

	var delSelectedBtn = document.getElementById('btn-del-selected');
	if (delSelectedBtn) {
		delSelectedBtn.addEventListener('click', function() {
			var files = [];
			document.querySelectorAll('.cb-file:checked').forEach(function(cb) { files.push(cb.value); });
			if (!files.length) { alert('Не выбрано ни одного файла.'); return; }
			if (!confirm('Удалить ' + files.length + ' файл(ов)?')) return;
			apiPost({ action: 'delete_file', files: files }, function(r) {
				if (r.ok) {
					var msg = 'Удалено файлов: ' + r.deleted;
					if (r.errors && r.errors.length) msg += '\nОшибки:\n' + r.errors.join('\n');
					alert(msg);
					location.reload();
				} else { alert(r.error || 'Ошибка'); }
			});
		});
	}

	var cleanupBtn = document.getElementById('btn-cleanup-files');
	if (cleanupBtn) {
		cleanupBtn.addEventListener('click', function() {
			if (!confirm('Удалить все файлы ежедневного меню старше 15 суток?')) return;
			apiPost({ action: 'cleanup_files', days: 15 }, function(r) {
				if (r.ok) { alert('Удалено файлов: ' + r.deleted); location.reload(); }
				else { alert(r.error || 'Ошибка'); }
			});
		});
	}
})();
</script>