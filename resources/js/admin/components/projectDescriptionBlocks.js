import Sortable from 'sortablejs';
import { fields } from '../fields/fields.js';
import { wysiwyg } from './wysiwyg.js';

// Field name suffixes that are text-only (per-language, not synced to other languages)
const TEXT_ONLY_NAMES = new Set(['content', 'headline', 'link_text', 'link_url', 'subhead']);

export function projectDescriptionBlocks() {
    const builders = document.querySelectorAll('[data-project-description-builder]');

    builders.forEach((builder) => {
        if (builder.dataset.inited) return;

        initBuilder(builder);
        builder.dataset.inited = 'true';
    });
}

// ─── Sibling helpers ─────────────────────────────────────────────────────────

function getSiblingBuilder(builder) {
    // Search within the nearest .tab-content ancestor so we only look at
    // language siblings of THIS description builder, not any other builders
    // that might exist elsewhere in the page.
    const tabContent = builder.closest('.tab-content') || document;
    const all = tabContent.querySelectorAll('[data-project-description-builder]');
    for (const b of all) {
        if (b !== builder) return b;
    }
    return null;
}

function isLayoutField(name) {
    if (!name) return false;
    const match = name.match(/\[([^\]]+)\]$/);
    return match ? !TEXT_ONLY_NAMES.has(match[1]) : false;
}

function getBlockIndex(block) {
    const wrapper = block.closest('[data-blocks-wrapper]');
    if (!wrapper) return -1;
    return [...wrapper.querySelectorAll(':scope > [data-block]')].indexOf(block);
}

function getBlockAtIndex(builder, index) {
    const blocks = builder.querySelectorAll('[data-blocks-wrapper] > [data-block]');
    return blocks[index] ?? null;
}

function getItemIndex(item, itemSelector) {
    const wrapper = item.closest('[data-tc-items-wrapper], [data-gallery-items-wrapper]');
    if (!wrapper) return -1;
    return [...wrapper.querySelectorAll(`:scope > ${itemSelector}`)].indexOf(item);
}

function findFieldByName(container, name) {
    for (const f of container.querySelectorAll('[name]')) {
        if (f.getAttribute('name') === name) return f;
    }
    return null;
}

function syncLayoutFieldToSibling(field, builder) {
    const name = field.getAttribute('name');
    if (!name || !isLayoutField(name) || field.type === 'file') return;

    const sibling = getSiblingBuilder(builder);
    if (!sibling) return;

    const locale       = builder.dataset.locale;
    const siblingLocale = sibling.dataset.locale;
    const siblingName  = name.replace(`[${locale}]`, `[${siblingLocale}]`);
    const siblingField = findFieldByName(sibling, siblingName);
    if (!siblingField) return;

    if (field.type === 'checkbox' || field.type === 'radio') {
        siblingField.checked = field.checked;
    } else {
        siblingField.value = field.value;
    }
}

function mirrorBlockMove(builder, oldIndex, newIndex) {
    const blocksWrapper = builder.querySelector('[data-blocks-wrapper]');
    if (!blocksWrapper) return;

    const blocks = [...blocksWrapper.querySelectorAll(':scope > [data-block]')];
    if (oldIndex < 0 || oldIndex >= blocks.length) return;

    const movedBlock = blocks[oldIndex];
    movedBlock.remove();

    const updated = [...blocksWrapper.querySelectorAll(':scope > [data-block]')];
    if (newIndex >= updated.length) {
        blocksWrapper.appendChild(movedBlock);
    } else {
        blocksWrapper.insertBefore(movedBlock, updated[newIndex]);
    }
}

function mirrorItemMove(wrapper, itemSelector, oldIndex, newIndex) {
    if (!wrapper) return;

    const items = [...wrapper.querySelectorAll(`:scope > ${itemSelector}`)];
    if (oldIndex < 0 || oldIndex >= items.length) return;

    const movedItem = items[oldIndex];
    movedItem.remove();

    const updated = [...wrapper.querySelectorAll(`:scope > ${itemSelector}`)];
    if (newIndex >= updated.length) {
        wrapper.appendChild(movedItem);
    } else {
        wrapper.insertBefore(movedItem, updated[newIndex]);
    }
}

// ─── Core init ───────────────────────────────────────────────────────────────

function initBuilder(builder) {
    const blocksWrapper = builder.querySelector('[data-blocks-wrapper]');
    if (!blocksWrapper) return;

    Sortable.create(blocksWrapper, {
        draggable: '[data-block]',
        handle: '[data-block-move]',
        onEnd: (evt) => {
            reindexBuilder(builder);
            const sibling = getSiblingBuilder(builder);
            if (sibling) {
                mirrorBlockMove(sibling, evt.oldIndex, evt.newIndex);
                reindexBuilder(sibling);
            }
        },
    });

    builder.addEventListener('click', (event) => {
        handleClick(event, builder, blocksWrapper);
    });

    builder.addEventListener('change', (event) => {
        // Block type select
        const typeSelect = event.target.closest('[data-block-type-select]');
        if (typeSelect) {
            handleBlockTypeChange(typeSelect, builder);
            return;
        }

        // All other layout fields (skip file inputs)
        const field = event.target.closest('[name]');
        if (field && field.type !== 'file') {
            syncLayoutFieldToSibling(field, builder);
        }
    });

    // Real-time sync for number inputs
    builder.addEventListener('input', (event) => {
        const field = event.target.closest('[name]');
        if (field && field.type === 'number') {
            syncLayoutFieldToSibling(field, builder);
        }
    });

    builder.querySelectorAll('[data-block]').forEach((block) => {
        const typeInput = block.querySelector('[data-block-type-input]');
        const type = typeInput?.value || 'text';
        setBlockType(block, type);
        setBlockCollapsed(block, true);

        initGalleryItemsSortable(block, builder);
        initTcItemsSortable(block, builder);
    });

    reindexBuilder(builder);
}

function handleBlockTypeChange(typeSelect, builder) {
    const block = typeSelect.closest('[data-block]');
    if (!block) return;

    setBlockType(block, typeSelect.value);

    if (typeSelect.value === 'text') {
        ensureWysiwygForBlock(block);
    }

    if (typeSelect.value === 'floating_gallery') {
        ensureGalleryItem(block, builder);
    }

    if (typeSelect.value === 'text_column_row') {
        ensureTcItem(block, builder);
    }

    reindexBuilder(builder);

    // Mirror type change to sibling
    const sibling = getSiblingBuilder(builder);
    if (sibling) {
        const blockIdx   = getBlockIndex(block);
        const siblingBlock = getBlockAtIndex(sibling, blockIdx);
        if (siblingBlock) {
            const siblingSelect = siblingBlock.querySelector('[data-block-type-select]');
            if (siblingSelect) siblingSelect.value = typeSelect.value;

            setBlockType(siblingBlock, typeSelect.value);

            if (typeSelect.value === 'floating_gallery') ensureGalleryItem(siblingBlock, sibling);
            if (typeSelect.value === 'text_column_row')  ensureTcItem(siblingBlock, sibling);

            reindexBuilder(sibling);
        }
    }
}

function handleClick(event, builder, blocksWrapper) {
    const sibling = getSiblingBuilder(builder);

    const toggleBlockButton      = event.target.closest('[data-block-toggle]');
    const addBlockButton         = event.target.closest('[data-block-add]');
    const addAfterButton         = event.target.closest('[data-block-add-after]');
    const removeBlockButton      = event.target.closest('[data-block-remove]');
    const addGalleryItemButton   = event.target.closest('[data-gallery-item-add]');
    const addGalleryItemAfter    = event.target.closest('[data-gallery-item-add-after]');
    const removeGalleryItem      = event.target.closest('[data-gallery-item-remove]');
    const addTcItemButton        = event.target.closest('[data-tc-item-add]');
    const addTcItemAfter         = event.target.closest('[data-tc-item-add-after]');
    const removeTcItem           = event.target.closest('[data-tc-item-remove]');

    if (toggleBlockButton) {
        const block      = toggleBlockButton.closest('[data-block]');
        const isExpanded = toggleBlockButton.getAttribute('aria-expanded') === 'true';
        setBlockCollapsed(block, isExpanded);
        if (!isExpanded) ensureWysiwygForBlock(block);
        return;
    }

    // ── Add block at end ───────────────────────────────────────────────────
    if (addBlockButton) {
        const newBlock = createBlock(builder, 'text');
        blocksWrapper.appendChild(newBlock);
        reindexBuilder(builder);
        fields();
        ensureWysiwygForBlock(newBlock);

        if (sibling) {
            try {
                const siblingWrapper = sibling.querySelector('[data-blocks-wrapper]');
                if (siblingWrapper) {
                    siblingWrapper.appendChild(createBlock(sibling, 'text'));
                    reindexBuilder(sibling);
                    fields();
                }
            } catch (e) { console.error('[descBlocks] addBlock sibling sync failed', e); }
        }
        return;
    }

    // ── Add block after current ────────────────────────────────────────────
    if (addAfterButton) {
        const currentBlock = addAfterButton.closest('[data-block]');
        if (!currentBlock) return;
        const blockIdx = getBlockIndex(currentBlock);

        const newBlock = createBlock(builder, 'text');
        currentBlock.insertAdjacentElement('afterend', newBlock);
        reindexBuilder(builder);
        fields();
        ensureWysiwygForBlock(newBlock);

        if (sibling) {
            try {
                const siblingWrapper = sibling.querySelector('[data-blocks-wrapper]');
                if (siblingWrapper) {
                    const siblingRef   = getBlockAtIndex(sibling, blockIdx);
                    const siblingBlock = createBlock(sibling, 'text');
                    if (siblingRef) {
                        siblingRef.insertAdjacentElement('afterend', siblingBlock);
                    } else {
                        siblingWrapper.appendChild(siblingBlock);
                    }
                    reindexBuilder(sibling);
                    fields();
                }
            } catch (e) { console.error('[descBlocks] addAfter sibling sync failed', e); }
        }
        return;
    }

    // ── Remove block ───────────────────────────────────────────────────────
    if (removeBlockButton) {
        const currentBlock = removeBlockButton.closest('[data-block]');
        if (!currentBlock) return;

        const allBlocks = blocksWrapper.querySelectorAll('[data-block]');
        if (allBlocks.length <= 1) return;

        const blockIdx = getBlockIndex(currentBlock);
        currentBlock.remove();
        reindexBuilder(builder);

        if (sibling) {
            try {
                const siblingWrapper = sibling.querySelector('[data-blocks-wrapper]');
                if (siblingWrapper) {
                    const siblingBlocks = siblingWrapper.querySelectorAll(':scope > [data-block]');
                    if (siblingBlocks.length > 1 && siblingBlocks[blockIdx]) {
                        siblingBlocks[blockIdx].remove();
                        reindexBuilder(sibling);
                    }
                }
            } catch (e) { console.error('[descBlocks] removeBlock sibling sync failed', e); }
        }
        return;
    }

    // ── Gallery item: add at end ───────────────────────────────────────────
    if (addGalleryItemButton) {
        const currentBlock = addGalleryItemButton.closest('[data-block]');
        if (!currentBlock) return;
        const blockIdx    = getBlockIndex(currentBlock);
        const itemsWrapper = currentBlock.querySelector('[data-gallery-items-wrapper]');
        if (!itemsWrapper) return;

        itemsWrapper.appendChild(createGalleryItem(builder));
        reindexBuilder(builder);
        fields();

        if (sibling) {
            const siblingBlock   = getBlockAtIndex(sibling, blockIdx);
            const siblingWrapper = siblingBlock?.querySelector('[data-gallery-items-wrapper]');
            if (siblingWrapper) {
                siblingWrapper.appendChild(createGalleryItem(sibling));
                reindexBuilder(sibling);
                fields();
            }
        }
        return;
    }

    // ── Gallery item: add after ────────────────────────────────────────────
    if (addGalleryItemAfter) {
        const currentItem  = addGalleryItemAfter.closest('[data-gallery-item]');
        if (!currentItem) return;
        const currentBlock = currentItem.closest('[data-block]');
        const blockIdx     = getBlockIndex(currentBlock);
        const itemIdx      = getItemIndex(currentItem, '[data-gallery-item]');

        currentItem.insertAdjacentElement('afterend', createGalleryItem(builder));
        reindexBuilder(builder);
        fields();

        if (sibling) {
            const siblingBlock = getBlockAtIndex(sibling, blockIdx);
            const siblingItems = siblingBlock?.querySelectorAll('[data-gallery-items-wrapper] > [data-gallery-item]');
            const siblingRef   = siblingItems?.[itemIdx];
            if (siblingRef) {
                siblingRef.insertAdjacentElement('afterend', createGalleryItem(sibling));
                reindexBuilder(sibling);
                fields();
            }
        }
        return;
    }

    // ── Gallery item: remove ───────────────────────────────────────────────
    if (removeGalleryItem) {
        const currentItem  = removeGalleryItem.closest('[data-gallery-item]');
        const itemsWrapper = removeGalleryItem.closest('[data-gallery-items-wrapper]');
        if (!currentItem || !itemsWrapper) return;
        if (itemsWrapper.querySelectorAll('[data-gallery-item]').length <= 1) return;

        const currentBlock = currentItem.closest('[data-block]');
        const blockIdx     = getBlockIndex(currentBlock);
        const itemIdx      = getItemIndex(currentItem, '[data-gallery-item]');

        currentItem.remove();
        reindexBuilder(builder);

        if (sibling) {
            const siblingBlock = getBlockAtIndex(sibling, blockIdx);
            const siblingItems = siblingBlock?.querySelectorAll('[data-gallery-items-wrapper] > [data-gallery-item]');
            if (siblingItems?.length > 1 && siblingItems[itemIdx]) {
                siblingItems[itemIdx].remove();
                reindexBuilder(sibling);
            }
        }
        return;
    }

    // ── TC item: add at end ────────────────────────────────────────────────
    if (addTcItemButton) {
        const currentBlock = addTcItemButton.closest('[data-block]');
        if (!currentBlock) return;
        const blockIdx    = getBlockIndex(currentBlock);
        const itemsWrapper = currentBlock.querySelector('[data-tc-items-wrapper]');
        if (!itemsWrapper) return;

        itemsWrapper.appendChild(createTcItem(builder));
        reindexBuilder(builder);
        fields();
        wysiwyg();

        if (sibling) {
            const siblingBlock   = getBlockAtIndex(sibling, blockIdx);
            const siblingWrapper = siblingBlock?.querySelector('[data-tc-items-wrapper]');
            if (siblingWrapper) {
                siblingWrapper.appendChild(createTcItem(sibling));
                reindexBuilder(sibling);
                fields();
            }
        }
        return;
    }

    // ── TC item: add after ─────────────────────────────────────────────────
    if (addTcItemAfter) {
        const currentItem  = addTcItemAfter.closest('[data-tc-item]');
        if (!currentItem) return;
        const currentBlock = currentItem.closest('[data-block]');
        const blockIdx     = getBlockIndex(currentBlock);
        const itemIdx      = getItemIndex(currentItem, '[data-tc-item]');

        currentItem.insertAdjacentElement('afterend', createTcItem(builder));
        reindexBuilder(builder);
        fields();
        wysiwyg();

        if (sibling) {
            const siblingBlock = getBlockAtIndex(sibling, blockIdx);
            const siblingItems = siblingBlock?.querySelectorAll('[data-tc-items-wrapper] > [data-tc-item]');
            const siblingRef   = siblingItems?.[itemIdx];
            if (siblingRef) {
                siblingRef.insertAdjacentElement('afterend', createTcItem(sibling));
                reindexBuilder(sibling);
                fields();
            }
        }
        return;
    }

    // ── TC item: remove ────────────────────────────────────────────────────
    if (removeTcItem) {
        const currentItem  = removeTcItem.closest('[data-tc-item]');
        const itemsWrapper = removeTcItem.closest('[data-tc-items-wrapper]');
        if (!currentItem || !itemsWrapper) return;
        if (itemsWrapper.querySelectorAll('[data-tc-item]').length <= 1) return;

        const currentBlock = currentItem.closest('[data-block]');
        const blockIdx     = getBlockIndex(currentBlock);
        const itemIdx      = getItemIndex(currentItem, '[data-tc-item]');

        currentItem.remove();
        reindexBuilder(builder);

        if (sibling) {
            const siblingBlock = getBlockAtIndex(sibling, blockIdx);
            const siblingItems = siblingBlock?.querySelectorAll('[data-tc-items-wrapper] > [data-tc-item]');
            if (siblingItems?.length > 1 && siblingItems[itemIdx]) {
                siblingItems[itemIdx].remove();
                reindexBuilder(sibling);
            }
        }
        return;
    }
}

// ─── Sortable init ───────────────────────────────────────────────────────────

function initGalleryItemsSortable(block, builder) {
    const itemsWrapper = block.querySelector('[data-gallery-items-wrapper]');
    if (!itemsWrapper || itemsWrapper.dataset.sortableInited) return;

    Sortable.create(itemsWrapper, {
        draggable: '[data-gallery-item]',
        handle: '[data-gallery-item-move]',
        onEnd: (evt) => {
            reindexBuilder(builder);
            const sibling = getSiblingBuilder(builder);
            if (sibling) {
                const blockIdx     = getBlockIndex(block);
                const siblingBlock = getBlockAtIndex(sibling, blockIdx);
                const siblingWrapper = siblingBlock?.querySelector('[data-gallery-items-wrapper]');
                mirrorItemMove(siblingWrapper, '[data-gallery-item]', evt.oldIndex, evt.newIndex);
                reindexBuilder(sibling);
            }
        },
    });

    itemsWrapper.dataset.sortableInited = 'true';
}

function initTcItemsSortable(block, builder) {
    const itemsWrapper = block.querySelector('[data-tc-items-wrapper]');
    if (!itemsWrapper || itemsWrapper.dataset.sortableInited) return;

    Sortable.create(itemsWrapper, {
        draggable: '[data-tc-item]',
        handle: '[data-tc-item-move]',
        onEnd: (evt) => {
            reindexBuilder(builder);
            const sibling = getSiblingBuilder(builder);
            if (sibling) {
                const blockIdx     = getBlockIndex(block);
                const siblingBlock = getBlockAtIndex(sibling, blockIdx);
                const siblingWrapper = siblingBlock?.querySelector('[data-tc-items-wrapper]');
                mirrorItemMove(siblingWrapper, '[data-tc-item]', evt.oldIndex, evt.newIndex);
                reindexBuilder(sibling);
            }
        },
    });

    itemsWrapper.dataset.sortableInited = 'true';
}

// ─── Factory helpers ─────────────────────────────────────────────────────────

function createBlock(builder, type = 'text') {
    const blockTemplate = getTemplateFromPane(builder, '[data-block-template="text"]');
    if (!blockTemplate) return document.createElement('div');

    const block      = blockTemplate.content.firstElementChild.cloneNode(true);
    const typeSelect = block.querySelector('[data-block-type-select]');
    if (typeSelect) typeSelect.value = type;

    setBlockType(block, type);
    setBlockCollapsed(block, true);

    initGalleryItemsSortable(block, builder);
    initTcItemsSortable(block, builder);

    return block;
}

function createGalleryItem(builder) {
    const itemTemplate = getTemplateFromPane(builder, '[data-gallery-item-template]');
    if (!itemTemplate) return document.createElement('div');
    return itemTemplate.content.firstElementChild.cloneNode(true);
}

function createTcItem(builder) {
    const itemTemplate = getTemplateFromPane(builder, '[data-tc-item-template]');
    if (!itemTemplate) return document.createElement('div');
    return itemTemplate.content.firstElementChild.cloneNode(true);
}

function ensureGalleryItem(block, builder) {
    const itemsWrapper = block.querySelector('[data-gallery-items-wrapper]');
    if (itemsWrapper && itemsWrapper.querySelectorAll('[data-gallery-item]').length === 0) {
        itemsWrapper.appendChild(createGalleryItem(builder));
        fields();
    }
}

function ensureTcItem(block, builder) {
    const itemsWrapper = block.querySelector('[data-tc-items-wrapper]');
    if (itemsWrapper && itemsWrapper.querySelectorAll('[data-tc-item]').length === 0) {
        itemsWrapper.appendChild(createTcItem(builder));
        fields();
    }
}

function getTemplateFromPane(builder, selector) {
    const pane = builder.closest('.tab-pane');
    return pane ? pane.querySelector(selector) : document.querySelector(selector);
}

// ─── Block state helpers ──────────────────────────────────────────────────────

function setBlockType(block, type) {
    const typeInput  = block.querySelector('[data-block-type-input]');
    const textPanel  = block.querySelector('[data-block-type-panel="text"]');
    const galleryPanel = block.querySelector('[data-block-type-panel="floating_gallery"]');
    const tcRowPanel = block.querySelector('[data-block-type-panel="text_column_row"]');

    if (typeInput) typeInput.value = type;

    textPanel?.classList.toggle('d-none', type !== 'text');
    galleryPanel?.classList.toggle('d-none', type !== 'floating_gallery');
    tcRowPanel?.classList.toggle('d-none', type !== 'text_column_row');

    togglePanelRequired(textPanel,    type === 'text');
    togglePanelRequired(galleryPanel, type === 'floating_gallery');
    togglePanelRequired(tcRowPanel,   type === 'text_column_row');
}

function ensureWysiwygForBlock(block) {
    const body = block.querySelector('[data-block-body]');
    if (body?.classList.contains('d-none')) return;
    wysiwyg();
}

function setBlockCollapsed(block, collapsed) {
    if (!block) return;

    const toggleButton = block.querySelector('[data-block-toggle]');
    const icon         = block.querySelector('[data-block-toggle-icon]');
    const body         = block.querySelector('[data-block-body]');

    if (!toggleButton || !body) return;

    toggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    body.classList.toggle('d-none', collapsed);

    if (icon) icon.textContent = collapsed ? '>' : 'v';
}

function togglePanelRequired(panel, shouldBeRequired) {
    if (!panel) return;

    panel.querySelectorAll('input, textarea, select').forEach((field) => {
        if (field.dataset.requiredOriginal === undefined) {
            field.dataset.requiredOriginal = field.hasAttribute('required') ? '1' : '0';
        }

        if (shouldBeRequired && field.dataset.requiredOriginal === '1') {
            field.setAttribute('required', 'required');
        } else {
            field.removeAttribute('required');
        }
    });
}

// ─── Reindex ─────────────────────────────────────────────────────────────────

function reindexBuilder(builder) {
    const locale = builder.dataset.locale;
    const blocks = builder.querySelectorAll('[data-blocks-wrapper] > [data-block]');

    blocks.forEach((block, blockIndex) => {
        updateBlockLabel(block, blockIndex);

        block.querySelectorAll('[name]').forEach((field) => {
            const originalName = field.getAttribute('name');
            if (!originalName) return;
            field.setAttribute(
                'name',
                originalName.replace(
                    new RegExp(`description_blocks\\[${locale}\\]\\[(?:\\d+|__block__)\\]`, 'g'),
                    `description_blocks[${locale}][${blockIndex}]`
                )
            );
        });

        // Gallery items
        block.querySelectorAll('[data-gallery-items-wrapper] > [data-gallery-item]').forEach((item, itemIndex) => {
            item.querySelectorAll('[name]').forEach((field) => {
                const name = field.getAttribute('name');
                if (!name) return;
                field.setAttribute('name', name.replace(/\[items\]\[(?:\d+|__item__)\]/g, `[items][${itemIndex}]`));
            });
        });

        // TC items
        block.querySelectorAll('[data-tc-items-wrapper] > [data-tc-item]').forEach((item, itemIndex) => {
            item.querySelectorAll('[name]').forEach((field) => {
                const name = field.getAttribute('name');
                if (!name) return;
                field.setAttribute('name', name.replace(/\[items\]\[(?:\d+|__item__)\]/g, `[items][${itemIndex}]`));
            });
        });
    });
}

function updateBlockLabel(block, blockIndex) {
    const labelEl = block.querySelector('[data-block-label]');
    if (!labelEl) return;

    const type = block.querySelector('[data-block-type-input]')?.value || 'text';
    const typeLabel = type === 'floating_gallery' ? 'Floating Gallery'
        : type === 'text_column_row' ? 'Text Column Row'
        : 'Content';

    labelEl.textContent = `Block ${blockIndex + 1} - ${typeLabel}`;
}
