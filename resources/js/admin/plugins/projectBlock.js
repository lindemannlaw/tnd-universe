/**
 * SunEditor custom plugin: Projekt-Block links/rechts einfügen
 * Nur für Portfolio-Projekt-Beschreibung
 */
function createProjectBlock(core, align) {
	const el = core.util.createElement('DIV');
	el.className = 'project-block';
	el.setAttribute('data-align', align);
	const p = core.util.createElement('P');
	p.innerHTML = '<br>';
	el.appendChild(p);
	return el;
}

export const projectBlockLeft = {
	name: 'projectBlockLeft',
	display: 'command',
	title: 'Block links',
	innerHTML: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" width="16" height="16"><path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/></svg>`,
	add: function () {},
	action: function () {
		this.focus();
		const block = createProjectBlock(this, 'left');
		this.insertNode(block, null, false);
		this.setRange(block.firstChild, 0, block.firstChild, 0);
		this.history.push(false);
		this.context.element.wysiwyg.dispatchEvent(new Event('input', { bubbles: true }));
	},
};

export const projectBlockRight = {
	name: 'projectBlockRight',
	display: 'command',
	title: 'Block rechts',
	innerHTML: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" width="16" height="16"><path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-4-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m4-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-4-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/></svg>`,
	add: function () {},
	action: function () {
		this.focus();
		const block = createProjectBlock(this, 'right');
		this.insertNode(block, null, false);
		this.setRange(block.firstChild, 0, block.firstChild, 0);
		this.history.push(false);
		this.context.element.wysiwyg.dispatchEvent(new Event('input', { bubbles: true }));
	},
};
