import suneditor from 'suneditor';
import plugins from 'suneditor/src/plugins/index.js';
import { projectBlockLeft, projectBlockRight } from '../plugins/projectBlock.js';
//import langUa from 'suneditor/src/lang/ua.js';
import langDe from 'suneditor/src/lang/de.js';

const html = document.documentElement;

export function wysiwyg() {
	const elements = document.querySelectorAll('[data-wysiwyg]');

	elements.forEach((element) => {
		if (element.wysiwygInited) return;

		const editor = element.nextElementSibling;

		if (editor?.classList?.contains('sun-editor')) {
			editor.remove();
		}

		init(element);
	});
}

function init(element) {
	const scrollWrapper = element.closest('[data-overlayscrollbars-viewport]');
	const form = element.closest('form');
	const wrapper = element.closest('[data-wysiwyg-wrapper]');
	const langs = {
		//'uk': langUa,
		'de': langDe,
	};
	const currentLang = html.lang;

	if (form) {
		form.addEventListener('submit', (event) => {
			if (form.waitWysiwygEditotSaving) {
				event.preventDefault();
				console.error('wait for the wysiwyg editor to process and save the data');
				return;
			}
		});
	}

	const dataButtonList = element.dataset.buttonList;
	const charLimit = +element.dataset.charLimit || null;
	const hasProjectBlocks = element.dataset.projectBlocks === 'true';
	let buttonList = [
		['undo', 'redo'],
		[/*'font', 'fontSize', */'formatBlock'],
		//['paragraphStyle', 'blockquote'],
		['bold', 'underline', 'italic', 'strike', 'subscript', 'superscript'],
		//['fontColor', 'hiliteColor', 'textStyle'],
		//'/', // Line break
		//['outdent', 'indent'],
		['align', /*'horizontalRule', */'list', /*'lineHeight'*/],
		[/*'table', */'link', 'image', /*'video', 'audio'*/ /* ,'math' */], // You must add the 'katex' library at options to use the 'math' plugin.
		///** ['imageGallery'] */ // You must add the "imageGalleryUrl".
		['fullScreen', 'showBlocks', 'codeView'],
		//['preview', 'print'],
		//['save', 'template'],
		///** ['dir', 'dir_ltr', 'dir_rtl'] */ // "dir": Toggle text direction, "dir_ltr": Right to Left, "dir_rtl": Left to Right
		['removeFormat'],
	];

	if (dataButtonList) {
		buttonList = [];

		let parseButtonList = dataButtonList.split('|');

		parseButtonList.forEach(buttons => {
			buttonList.push(buttons.split(','));
		});
	}

	if (hasProjectBlocks) {
		buttonList.splice(buttonList.length - 2, 0, ['projectBlockLeft', 'projectBlockRight']);
	}

	const editorPlugins = { ...plugins };
	if (hasProjectBlocks) {
		editorPlugins.projectBlockLeft = projectBlockLeft;
		editorPlugins.projectBlockRight = projectBlockRight;
	}

	const options = {
		plugins: editorPlugins,
		buttonList: buttonList,
		formats: ['p', ...(hasProjectBlocks ? ['div'] : []), 'h2', 'h3', 'h4', 'h5', 'h6'],
		maxCharCount: charLimit,
		width: 'auto',
		height: 'auto',
		minHeight: element.dataset.height || '200px',
		tagsBlacklist: 'span',
		attributesBlacklist: {
			all: 'style',
		},
		attributesWhitelist: {
			img: 'src|srcset|alt|style|width|height|data-.+',
			div: 'class|data-align',
		},
		icons: icons(),
		placeholder: element.getAttribute('placeholder') || null,
		//imageUrlInput: false,
        linkProtocol: '',
		lang: langs[currentLang],
	};

	const editor = suneditor.create(element, options);

	element.wysiwygInited = true;

	editor.onImageUploadBefore = function (files, info, core, uploadHandler) {
		const formData = new FormData();

		formData.append('image', files[0]);

		axios.post('/admin/upload-image', formData, {
			headers: {
				'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
			}
		})
			.then(response => {
				uploadHandler({
					'result': response.data.result
				});
			})
			.catch(error => {
				uploadHandler({
					'errorMessage': error,
				});
			});
	}

	editor.toggleFullScreen = function (isFullScreen, core) {
		html.classList.toggle('wysiwyg-full-screen', isFullScreen);

		if (isFullScreen) {
			clearToolbarSticky(editor);
		} else if (scrollWrapper) {
			setEditorScrollProps(scrollWrapper, editor, wrapper);
			updateToolbarPosition(scrollWrapper, editor, wrapper);
		}
	};

	editor.onPaste = function (e, cleanData, maxCharCount, core) {
        const domain = window.location.origin;
        const domainRegex = new RegExp(domain, 'g');

		cleanData = cleanData.replace(/&nbsp;/g, ' ');
        cleanData = cleanData.replace(domainRegex, '');

		return cleanData;
	};

	editor.onImageUpload = function (targetElement, index, state, info, remainingFilesCount, core) {
		core.context.element.wysiwyg.dispatchEvent(new Event('input', { bubbles: true }));
	};

	editor.onChange = function (contents, core) {
		if (form) {
			form.waitWysiwygEditotSaving = true;
			form.classList.add('waiting');
		}

		const parser = new DOMParser();
		const parseDocument = parser.parseFromString(contents, 'text/html');
        const imageContainers = parseDocument.querySelectorAll('.se-image-container');
        const videoContainers = parseDocument.querySelectorAll('.se-video-container');

        imageContainers.forEach((container) => {
            const image = container.querySelector('img');
            const figure = container.querySelector('figure');

            if (!image || !figure) {
                console.warn('Missing <img> or <figure> in a container. Skipping.');
                return;
            }

            const picture = parseDocument.createElement('picture');
            const float = container.classList.contains('__se__float-left') ? 'left' : (container.classList.contains('__se__float-right') ? 'right' : null);

            if (float) {
                figure.classList.add(`float-${float}`);
            }

            figure.insertBefore(picture, image);
            picture.appendChild(image);
            parseDocument.body.insertBefore(figure, container);
            container.remove();
        });

        videoContainers.forEach((container) => {
            const iframe = container.querySelector('iframe');
            const figure = container.querySelector('figure');

            if (!iframe || !figure) {
                console.warn('Missing <iframe> or <figure> in a container. Skipping.');
                return;
            }

            const float = container.classList.contains('__se__float-left') ? 'left' : (container.classList.contains('__se__float-right') ? 'right' : null);

            if (float) {
                figure.classList.add(`float-${float}`);
            }

            parseDocument.body.insertBefore(figure, container);
            container.remove();
        });

		element.value = trimTrailingEmptyParagraphs(parseDocument.getElementsByTagName('body')[0].innerHTML);

		if (form) {
			form.waitWysiwygEditotSaving = false;
			form.classList.remove('waiting');
		}
	};

	if (scrollWrapper) {
		let resizeTimer = null;

		setEditorScrollProps(scrollWrapper, editor, wrapper);

		scrollWrapper.addEventListener('scroll', () => {
			updateToolbarPosition(scrollWrapper, editor, wrapper);
		});

		window.addEventListener('resize', () => {
			clearTimeout(resizeTimer);

			resizeTimer = setTimeout(() => {
				setEditorScrollProps(scrollWrapper, editor, wrapper);
				updateToolbarPosition(scrollWrapper, editor, wrapper);
			}, 150);
		});
	}

	document.addEventListener('togglesidebar', () => {
		setEditorScrollProps(scrollWrapper, editor, wrapper);
		updateToolbarPosition(scrollWrapper, editor, wrapper);
	});
}

function trimTrailingEmptyParagraphs(htmlContent) {
    let cleanedContent = htmlContent.trim();
    const pattern = /(?:<p\s*>\s*(?:<br\s*>)?\s*<\/p>\s*)+$/i;

    cleanedContent = cleanedContent.replace(pattern, '');

    return cleanedContent.trim();
}

function updateToolbarPosition(scrollWrapper, editor, wrapper) {
    if (!scrollWrapper) return;

	const scrollProps = editor.scrollProps || setEditorScrollProps(scrollWrapper, editor, wrapper);
	const scrollTop = scrollWrapper.scrollTop;
	const toolbar = scrollProps.toolbar;
	const container = editor.core.context.element.relative;

	//console.log(scrollTop > scrollProps.maxFixedScroll);

	if (scrollProps.editor.height > scrollProps.scrWrap.height) {
		if (scrollTop >= scrollProps.editor.top && !html.classList.contains('wysiwyg-full-screen')) {
			const topPos = scrollTop > scrollProps.maxFixedScroll ? (scrollProps.maxFixedScroll + scrollProps.scrWrap.top) - scrollTop + 'px' : scrollProps.scrWrap.top + 'px';

			toolbar.classList.add('se-toolbar-sticky');
			toolbar.style.top = topPos;
			toolbar.style.width = (scrollProps.editor.width - 2) + 'px';
			container.style.setProperty('--toolbar-height', toolbar.offsetHeight + 'px');
		} else {
			clearToolbarSticky(editor);
		}
	}
}

function clearToolbarSticky(editor) {
	const toolbar = editor.core.context.element.toolbar;

	toolbar.classList.remove('se-toolbar-sticky');
	toolbar.style = null;
}

function setEditorScrollProps(scrWrap, editor, wrapper) {
	if (!scrWrap) return;

	const scrWrapRect = scrWrap.getBoundingClientRect();

	const editorArea = editor.core.context.element.topArea;
	const editorToolbar = editor.core.context.element.toolbar;

	const scrWrapTop = scrWrapRect.top;
	const scrWrapHeight = scrWrap.offsetHeight;

	const editorTop = wrapper.offsetTop;
	const editorWidth = editorArea.offsetWidth;
	const editorHeight = editorArea.offsetHeight;

	const maxFixedScroll = editorTop + editorHeight - 300;

	editor.scrollProps = {
		area: editorArea,
		toolbar: editorToolbar,
		maxFixedScroll: maxFixedScroll,

		scrWrap: {
			top: scrWrapTop,
			height: scrWrapHeight,
		},

		editor: {
			top: editorTop,
			width: editorWidth,
			height: editorHeight,
		},
	};

	return editor.scrollProps;
}

function icons() {
	return {
		undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466"/></svg>`,
		redo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/></svg>`,
		bold: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8.21 13c2.106 0 3.412-1.087 3.412-2.823 0-1.306-.984-2.283-2.324-2.386v-.055a2.176 2.176 0 0 0 1.852-2.14c0-1.51-1.162-2.46-3.014-2.46H3.843V13zM5.908 4.674h1.696c.963 0 1.517.451 1.517 1.244 0 .834-.629 1.32-1.73 1.32H5.908V4.673zm0 6.788V8.598h1.73c1.217 0 1.88.492 1.88 1.415 0 .943-.643 1.449-1.832 1.449H5.907z"/></svg>`,
		underline: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M5.313 3.136h-1.23V9.54c0 2.105 1.47 3.623 3.917 3.623s3.917-1.518 3.917-3.623V3.136h-1.23v6.323c0 1.49-.978 2.57-2.687 2.57s-2.687-1.08-2.687-2.57zM12.5 15h-9v-1h9z"/></svg>`,
		italic: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M7.991 11.674 9.53 4.455c.123-.595.246-.71 1.347-.807l.11-.52H7.211l-.11.52c1.06.096 1.128.212 1.005.807L6.57 11.674c-.123.595-.246.71-1.346.806l-.11.52h3.774l.11-.52c-1.06-.095-1.129-.211-1.006-.806z"/></svg>`,
		strike: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M6.333 5.686c0 .31.083.581.27.814H5.166a2.8 2.8 0 0 1-.099-.76c0-1.627 1.436-2.768 3.48-2.768 1.969 0 3.39 1.175 3.445 2.85h-1.23c-.11-1.08-.964-1.743-2.25-1.743-1.23 0-2.18.602-2.18 1.607zm2.194 7.478c-2.153 0-3.589-1.107-3.705-2.81h1.23c.144 1.06 1.129 1.703 2.544 1.703 1.34 0 2.31-.705 2.31-1.675 0-.827-.547-1.374-1.914-1.675L8.046 8.5H1v-1h14v1h-3.504c.468.437.675.994.675 1.697 0 1.826-1.436 2.967-3.644 2.967"/></svg>`,
		subscript: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="m3.266 12.496.96-2.853H7.76l.96 2.853H10L6.62 3H5.38L2 12.496zm2.748-8.063 1.419 4.23h-2.88l1.426-4.23zm6.132 7.203v-.075c0-.332.234-.618.619-.618.354 0 .618.256.618.58 0 .362-.271.649-.52.898l-1.788 1.832V15h3.59v-.958h-1.923v-.045l.973-1.04c.415-.438.867-.845.867-1.547 0-.8-.701-1.41-1.787-1.41-1.23 0-1.795.8-1.795 1.576v.06z"/></svg>`,
		superscript: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="m4.266 12.496.96-2.853H8.76l.96 2.853H11L7.62 3H6.38L3 12.496zm2.748-8.063 1.419 4.23h-2.88l1.426-4.23zm5.132-1.797v-.075c0-.332.234-.618.619-.618.354 0 .618.256.618.58 0 .362-.271.649-.52.898l-1.788 1.832V6h3.59v-.958h-1.923v-.045l.973-1.04c.415-.438.867-.845.867-1.547 0-.8-.701-1.41-1.787-1.41C11.565 1 11 1.8 11 2.576v.06z"/></svg>`,
		align_left: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/></svg>`,
		align_right: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-4-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m4-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-4-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/></svg>`,
		align_center: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/></svg>`,
		align_justify: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/></svg>`,
		list_bullets: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/></svg>`,
		list_number: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5"/><path d="M1.713 11.865v-.474H2c.217 0 .363-.137.363-.317 0-.185-.158-.31-.361-.31-.223 0-.367.152-.373.31h-.59c.016-.467.373-.787.986-.787.588-.002.954.291.957.703a.595.595 0 0 1-.492.594v.033a.615.615 0 0 1 .569.631c.003.533-.502.8-1.051.8-.656 0-1-.37-1.008-.794h.582c.008.178.186.306.422.309.254 0 .424-.145.422-.35-.002-.195-.155-.348-.414-.348h-.3zm-.004-4.699h-.604v-.035c0-.408.295-.844.958-.844.583 0 .96.326.96.756 0 .389-.257.617-.476.848l-.537.572v.03h1.054V9H1.143v-.395l.957-.99c.138-.142.293-.304.293-.508 0-.18-.147-.32-.342-.32a.33.33 0 0 0-.342.338zM2.564 5h-.635V2.924h-.031l-.598.42v-.567l.629-.443h.635z"/></svg>`,
		link: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z"/><path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243z"/></svg>`,
		image: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/></svg>`,
		expansion: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M5.828 10.172a.5.5 0 0 0-.707 0l-4.096 4.096V11.5a.5.5 0 0 0-1 0v3.975a.5.5 0 0 0 .5.5H4.5a.5.5 0 0 0 0-1H1.732l4.096-4.096a.5.5 0 0 0 0-.707m4.344-4.344a.5.5 0 0 0 .707 0l4.096-4.096V4.5a.5.5 0 1 0 1 0V.525a.5.5 0 0 0-.5-.5H11.5a.5.5 0 0 0 0 1h2.768l-4.096 4.096a.5.5 0 0 0 0 .707"/></svg>`,
		reduction: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M.172 15.828a.5.5 0 0 0 .707 0l4.096-4.096V14.5a.5.5 0 1 0 1 0v-3.975a.5.5 0 0 0-.5-.5H1.5a.5.5 0 0 0 0 1h2.768L.172 15.121a.5.5 0 0 0 0 .707M15.828.172a.5.5 0 0 0-.707 0l-4.096 4.096V1.5a.5.5 0 1 0-1 0v3.975a.5.5 0 0 0 .5.5H14.5a.5.5 0 0 0 0-1h-2.768L15.828.879a.5.5 0 0 0 0-.707"/></svg>`,
		show_blocks: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M6 1v3H1V1zM1 0a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1zm14 12v3h-5v-3zm-5-1a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1zM6 8v7H1V8zM1 7a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1zm14-6v7h-5V1zm-5-1a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1z"/></svg>`,
		code_view: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0m6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0"/></svg>`,
		erase: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8.086 2.207a2 2 0 0 1 2.828 0l3.879 3.879a2 2 0 0 1 0 2.828l-5.5 5.5A2 2 0 0 1 7.879 15H5.12a2 2 0 0 1-1.414-.586l-2.5-2.5a2 2 0 0 1 0-2.828zm2.121.707a1 1 0 0 0-1.414 0L4.16 7.547l5.293 5.293 4.633-4.633a1 1 0 0 0 0-1.414zM8.746 13.547 3.453 8.254 1.914 9.793a1 1 0 0 0 0 1.414l2.5 2.5a1 1 0 0 0 .707.293H7.88a1 1 0 0 0 .707-.293z"/></svg>`,
		cancel: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/></svg>`,
		revert: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5m14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5"/></svg>`,
		bookmark: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1z"/></svg>`,
		download: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
		//undo: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"></svg>`,
	};
}
