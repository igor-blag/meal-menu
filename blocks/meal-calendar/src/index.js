import { registerBlockType } from '@wordpress/blocks';
import { Placeholder, Spinner, SelectControl, ToggleControl, PanelBody } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useEffect, useState, RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

const PALETTES = [
	{ value: '', label: 'По умолчанию' },
	{ value: 'retro', label: 'Оригинальная' },
	{ value: 'futuristic', label: 'Футуристическая' },
	{ value: 'minimal', label: 'Минималистичная' },
	{ value: 'nature', label: 'Природная' },
	{ value: 'school', label: 'Школьная' },
	{ value: 'warm', label: 'Тёплая' },
	{ value: 'ocean', label: 'Океанская' },
];

const LAYOUTS = [
	{ value: '', label: 'По умолчанию' },
	{ value: 'classic', label: 'Классическая' },
	{ value: 'modern', label: 'Современная' },
	{ value: 'air', label: 'Воздушная' },
];

registerBlockType( 'meal-menu/calendar', {
	edit: ( { attributes, setAttributes } ) => {
		const { type = '', palette = '', layout = '', show_oc = true } = attributes;
		const blockProps = useBlockProps();
		const [ html, setHtml ] = useState( '' );
		const [ loading, setLoading ] = useState( false );
		const [ error, setError ] = useState( false );

		const data = window.mealBlockData || {};
		const departments = data.departments || [];
		const ajaxUrl = data.ajaxUrl || '';
		const year = data.currentYear || new Date().getFullYear();
		const month = data.currentMonth || new Date().getMonth() + 1;

		useEffect( () => {
			if ( ! type || ! ajaxUrl ) {
				setHtml( '' );
				return;
			}

			setLoading( true );
			setError( false );

			const url = addQueryArgs( ajaxUrl, {
				action: 'meal_get_calendar',
				meal_type: type,
				meal_y: year,
				meal_m: month,
			} );

			fetch( url )
				.then( ( r ) => r.json() )
				.then( ( responseData ) => {
					if ( responseData.ok ) {
						setHtml( responseData.html );
					} else {
						setError( true );
					}
				} )
				.catch( () => setError( true ) )
				.finally( () => setLoading( false ) );
		}, [ type, year, month, ajaxUrl ] );

		const typeOptions = [
			{ value: '', label: __( 'Выберите тип школы', 'meal-menu' ) },
			...departments.map( ( d ) => ( {
				value: d.code,
				label: d.label,
			} ) ),
		];

		const controls = (
			<InspectorControls>
				<PanelBody title={ __( 'Параметры календаря', 'meal-menu' ) }>
					<SelectControl
						label={ __( 'Тип школы', 'meal-menu' ) }
						value={ type }
						options={ typeOptions }
						onChange={ ( v ) => setAttributes( { type: v } ) }
					/>
					<SelectControl
						label={ __( 'Палитра', 'meal-menu' ) }
						value={ palette }
						options={ PALETTES }
						onChange={ ( v ) => setAttributes( { palette: v } ) }
					/>
					<SelectControl
						label={ __( 'Макет', 'meal-menu' ) }
						value={ layout }
						options={ LAYOUTS }
						onChange={ ( v ) => setAttributes( { layout: v } ) }
					/>
					<ToggleControl
						label={ __( 'Общественный контроль питания', 'meal-menu' ) }
						help={
							show_oc
								? __( 'Спойлер отображается на сайте.', 'meal-menu' )
								: __( 'Спойлер скрыт на сайте.', 'meal-menu' )
						}
						checked={ show_oc }
						onChange={ ( v ) => setAttributes( { show_oc: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
		);

		if ( departments.length === 0 ) {
			return (
				<Placeholder
					icon="calendar-alt"
					label={ __( 'Календарь питания', 'meal-menu' ) }
				>
					<p>
						{ __(
							'Нет доступных типов школ. Настройте их в разделе "Питание".',
							'meal-menu'
						) }
					</p>
				</Placeholder>
			);
		}

		if ( ! type ) {
			return (
				<div {...blockProps}>
					{ controls }
					<Placeholder
						icon="calendar-alt"
						label={ __( 'Календарь питания', 'meal-menu' ) }
						instructions={ __(
							'Выберите тип школы для отображения календаря.',
							'meal-menu'
						) }
					>
						<SelectControl
							value={ type }
							options={ typeOptions }
							onChange={ ( v ) => setAttributes( { type: v } ) }
						/>
					</Placeholder>
				</div>
			);
		}

		if ( loading ) {
			return (
				<div {...blockProps}>
					{ controls }
					<Placeholder
						icon="calendar-alt"
						label={ __( 'Календарь питания', 'meal-menu' ) }
					>
						<Spinner />
					</Placeholder>
				</div>
			);
		}

		if ( error ) {
			return (
				<div {...blockProps}>
					{ controls }
					<Placeholder
						icon="calendar-alt"
						label={ __( 'Календарь питания', 'meal-menu' ) }
					>
						<p>
							{ __(
								'Не удалось загрузить календарь.',
								'meal-menu'
							) }
						</p>
					</Placeholder>
				</div>
			);
		}

		return (
			<div {...blockProps}>
				{ controls }
				<div className="meal-wrapper">
					<RawHTML>{ html }</RawHTML>
				</div>
			</div>
		);
	},
	save: () => null,
} );
