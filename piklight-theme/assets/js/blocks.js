(function (blocks, element, blockEditor, components, i18n) {
	const el = element.createElement;
	const { RichText, InspectorControls, MediaUpload, MediaUploadCheck } = blockEditor;
	const { PanelBody, TextControl, TextareaControl, Button } = components;
	const __ = i18n.__;

	const attrs = {
		title: { type: 'string', default: '' },
		accent: { type: 'string', default: '' },
		text: { type: 'string', default: '' },
		buttonText: { type: 'string', default: '' },
		buttonUrl: { type: 'string', default: '#' },
		imageUrl: { type: 'string', default: '' },
		items: { type: 'array', default: [] },
		catalogText: { type: 'string', default: '' },
		catalogUrl: { type: 'string', default: '#' }
	};

	function mediaControl(props) {
		return el(MediaUploadCheck, {}, el(MediaUpload, {
			onSelect: (media) => props.setAttributes({ imageUrl: media.url }),
			allowedTypes: ['image'],
			value: props.attributes.imageUrl,
			render: ({ open }) => el(Button, { onClick: open, isSecondary: true }, props.attributes.imageUrl ? __('Zmień obraz', 'piklight') : __('Wybierz obraz', 'piklight'))
		}));
	}

	function itemEditor(props, defaults) {
		const items = props.attributes.items.length ? props.attributes.items : defaults;
		return el(PanelBody, { title: __('Elementy sekcji', 'piklight'), initialOpen: false },
			items.map((item, index) => el('div', { className: 'piklight-item-editor', key: index },
				el(TextControl, {
					label: __('Tytuł', 'piklight'),
					value: item.title || '',
					onChange: (value) => {
						const next = [...items];
						next[index] = { ...next[index], title: value };
						props.setAttributes({ items: next });
					}
				}),
				el(TextareaControl, {
					label: __('Opis', 'piklight'),
					value: item.text || '',
					onChange: (value) => {
						const next = [...items];
						next[index] = { ...next[index], text: value };
						props.setAttributes({ items: next });
					}
				}),
				el(TextControl, {
					label: __('URL', 'piklight'),
					value: item.url || '',
					onChange: (value) => {
						const next = [...items];
						next[index] = { ...next[index], url: value };
						props.setAttributes({ items: next });
					}
				}),
				el(MediaUploadCheck, {}, el(MediaUpload, {
					onSelect: (media) => {
						const next = [...items];
						next[index] = { ...next[index], imageUrl: media.url };
						props.setAttributes({ items: next });
					},
					allowedTypes: ['image'],
					render: ({ open }) => el(Button, { onClick: open, isSmall: true, isSecondary: true }, __('Obraz elementu', 'piklight'))
				}))
			))
		);
	}

	function sectionEdit(props, defaults, hasItems) {
		return el('div', { className: 'piklight-editor-card' },
			el(InspectorControls, {},
				el(PanelBody, { title: __('Ustawienia sekcji', 'piklight') },
					el(TextControl, { label: __('Link przycisku', 'piklight'), value: props.attributes.buttonUrl, onChange: (value) => props.setAttributes({ buttonUrl: value }) }),
					el(TextControl, { label: __('Link katalogu', 'piklight'), value: props.attributes.catalogUrl, onChange: (value) => props.setAttributes({ catalogUrl: value }) }),
					mediaControl(props)
				),
				hasItems ? itemEditor(props, defaults.items) : null
			),
			el(RichText, { tagName: 'h2', placeholder: defaults.title, value: props.attributes.title, onChange: (value) => props.setAttributes({ title: value }) }),
			el(RichText, { tagName: 'p', placeholder: defaults.text, value: props.attributes.text, onChange: (value) => props.setAttributes({ text: value }) }),
			el(RichText, { tagName: 'strong', placeholder: defaults.buttonText, value: props.attributes.buttonText, onChange: (value) => props.setAttributes({ buttonText: value }) }),
			hasItems ? el('p', {}, __('Elementy kart edytujesz w panelu bocznym bloku.', 'piklight')) : null
		);
	}

	const blocksConfig = [
		['piklight/hero', 'Hero Pik-Light', { title: 'Producent zniczy i wkładów do zniczy', text: 'Wysoka jakość, estetyka i długi czas palenia.', buttonText: 'Zobacz ofertę', items: [] }, false],
		['piklight/features', 'Atuty Pik-Light', { title: 'Atuty', text: '', buttonText: '', items: [{ title: 'Polska produkcja', text: 'Wszystkie produkty wytwarzamy w Polsce.' }, { title: 'Wysoka jakość', text: 'Sprawdzone surowce i technologie.' }, { title: 'Terminowe dostawy', text: 'Szybka realizacja zamówień.' }] }, true],
		['piklight/offer', 'Oferta Pik-Light', { title: 'Nasza oferta', text: '', buttonText: '', items: [{ title: 'Znicze ozdobne' }, { title: 'Znicze zalewane' }, { title: 'Wkłady do zniczy' }, { title: 'Nowości' }, { title: 'Katalog produktów' }] }, true],
		['piklight/about', 'O firmie Pik-Light', { title: 'O firmie Pik-Light', text: 'Jesteśmy polskim producentem zniczy i wkładów do zniczy.', buttonText: 'Dowiedz się więcej', items: [] }, false],
		['piklight/products', 'Polecane produkty Pik-Light', { title: 'Polecane produkty', text: '', buttonText: 'Zobacz wszystkie produkty', items: [{ title: 'V1-50' }, { title: '42-O-A' }, { title: '45-O-R' }, { title: 'Anioł-A' }, { title: 'Z-401' }, { title: 'Z-115' }] }, true],
		['piklight/wholesale', 'Współpraca hurtowa Pik-Light', { title: 'Współpraca hurtowa', text: 'Zapraszamy hurtownie i dystrybutorów do współpracy.', buttonText: 'Zapytaj o ofertę', items: [] }, false]
	];

	blocksConfig.forEach(([name, title, defaults, hasItems]) => {
		blocks.registerBlockType(name, {
			title,
			icon: 'store',
			category: 'design',
			attributes: attrs,
			edit: (props) => sectionEdit(props, defaults, hasItems),
			save: () => null
		});
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
