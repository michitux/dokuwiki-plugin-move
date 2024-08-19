/**
 * The Tree Move Manager
 *
 * This script handles the move tree and all its interactions.
 *
 * The script supports combined and separate page/media trees. Items have their orignal ID in data-orig and their
 * current ID in data-id.
 *
 * This is pure vanilla JavaScript without any dependencies to jQuery. It is lazy loaded by the main script.
 */
class PluginMoveTree {
    #ENDPOINT = DOKU_BASE + 'lib/exe/ajax.php?call=plugin_move_tree';

    icons = {
        'close': 'M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z',
        'open': 'M19,20H4C2.89,20 2,19.1 2,18V6C2,4.89 2.89,4 4,4H10L12,6H19A2,2 0 0,1 21,8H21L4,8V18L6.14,10H23.21L20.93,18.5C20.7,19.37 19.92,20 19,20Z',
        'page': 'M6,2A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6M6,4H13V9H18V20H6V4M8,12V14H16V12H8M8,16V18H13V16H8Z',
        'media': 'M13,9V3.5L18.5,9M6,2C4.89,2 4,2.89 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6Z',
        'rename': 'M18,4V3A1,1 0 0,0 17,2H5A1,1 0 0,0 4,3V7A1,1 0 0,0 5,8H17A1,1 0 0,0 18,7V6H19V10H9V21A1,1 0 0,0 10,22H12A1,1 0 0,0 13,21V12H21V4H18Z',
        'drag': 'M4 4V22H20V24H4C2.9 24 2 23.1 2 22V4H4M15 7H20.5L15 1.5V7M8 0H16L22 6V18C22 19.11 21.11 20 20 20H8C6.89 20 6 19.1 6 18V2C6 .89 6.89 0 8 0M17 16V14H8V16H17M20 12V10H8V12H20Z',
    };

    #mainElement;
    #mediaTree;
    #pageTree;
    #dragTarget;
    #dragIcon;

    /**
     * Initialize the base tree and attach all event handlers
     *
     * @param {HTMLElement} main
     */
    constructor(main) {
        this.#mainElement = main;
        this.#mediaTree = this.#mainElement.querySelector('.move-media');
        this.#pageTree = this.#mainElement.querySelector('.move-pages');


        this.#dragIcon = this.icon('drag');
        this.#dragIcon.classList.add('drag-icon');
        this.#mainElement.appendChild(this.#dragIcon);

        this.#mainElement.addEventListener('click', this.clickHandler.bind(this));
        this.#mainElement.addEventListener('dragstart', this.dragStartHandler.bind(this));
        this.#mainElement.addEventListener('dragover', this.dragOverHandler.bind(this));
        this.#mainElement.addEventListener('drop', this.dragDropHandler.bind(this));
        this.#mainElement.addEventListener('dragend', this.dragEndHandler.bind(this));
        this.#mainElement.querySelector('form').addEventListener('submit', this.submitHandler.bind(this));

        // load and open the initial tree
        this.#init();

        // make tree visible
        this.#mainElement.style.display = 'block';
    }

    /**
     * Initialize the tree
     *
     * @returns {Promise<void>}
     */
    async #init() {
        await Promise.all([
            this.loadSubTree('', 'pages'),
            this.loadSubTree('', 'media'),
        ]);

        await this.openNamespace(JSINFO.namespace);
    }

    /**
     * Handle all item clicks
     *
     * @param {MouseEvent} ev
     */
    clickHandler(ev) {
        const target = ev.target;
        const li = target.closest('li');
        if (!li) return;

        // we want to handle clicks on these elements only
        const clicked = target.closest('i,button,span');
        if (!clicked) return;

        // icon click selects the item
        if (clicked.tagName.toLowerCase() === 'i') {
            ev.stopPropagation();
            li.classList.toggle('selected');
            return;
        }

        // button click opens rename dialog
        if (clicked.tagName.toLowerCase() === 'button') {
            ev.stopPropagation();
            this.renameGui(li);
            return;
        }

        // click on name opens/closes namespace
        if (clicked.tagName.toLowerCase() === 'span' && li.classList.contains('move-ns')) {
            ev.stopPropagation();
            this.toggleNamespace(li);
        }
    }

    /**
     * Submit the data for the move operation
     *
     * @param {FormDataEvent} ev
     */
    submitHandler(ev) {
        // gather all changed items
        const data = [];
        this.#mainElement.querySelectorAll('.changed').forEach(li => {
            let entry = {
                src: li.dataset.orig,
                dst: li.dataset.id,
                type: this.isItemMedia(li) ? 'media' : 'page',
                class: this.isItemNamespace(li) ? 'ns' : 'doc',
            };
            data.push(entry);

            // if this is a namspace that is shared between media and pages, add a second entry
            if (entry.class === 'ns' && entry.type === 'media' && this.isItemPage(li)) {
                entry = {...entry}; // clone
                entry.type = 'page';
                data.push(entry);
            }
        });

        // add JSON data to form, then let the event continue
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'json';
        input.value = JSON.stringify(data);
        ev.target.appendChild(input);
    }

    /**
     * Begin drag operation
     *
     * @param {DragEvent} ev
     */
    dragStartHandler(ev) {
        if (!ev.target) return;
        const li = ev.target.closest('li');
        if (!li) return;

        ev.dataTransfer.setData('text/plain', li.dataset.id); // FIXME needed?
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setDragImage(this.#dragIcon, -12, -12);

        // the dragged element is always selected
        li.classList.add('selected');
    }

    /**
     * Higlight drop zone and allow dropping
     *
     * @param {DragEvent} ev
     */
    dragOverHandler(ev) {
        if (!ev.target) return;  // the element the mouse is over
        const ul = ev.target.closest('ul');
        if (!ul) return;
        ev.preventDefault(); // allow drop

        if (this.#dragTarget && this.#dragTarget !== ul) {
            this.#dragTarget.classList.remove('drop-zone');
        }
        this.#dragTarget = ul;
        this.#dragTarget.classList.add('drop-zone');
    }

    /**
     * Handle the Drop operation
     *
     * @param {DragEvent} ev
     */
    dragDropHandler(ev) {
        if (!ev.target) return;
        const dst = ev.target.closest('ul');
        if (!dst) return;

        // move all selected items to the drop target
        const elements = this.#mainElement.querySelectorAll('.selected');
        elements.forEach(src => {
            const newID = this.getNewId(src.dataset.id, dst.dataset.id);
            console.log('move started', src.dataset.id + ' → ' + newID);

            // ensure that item stays in its own tree, ignore cross-tree moves
            if (this.itemTree(src).contains(dst) === false) {
                return;
            }

            // same ID? we consider this an abort
            if (newID === src.dataset.id) {
                src.classList.remove('selected');
                return;
            }

            // check if item with same ID already exists FIXME this also needs to check the type!
            if (this.itemTree(src).querySelector(`li[data-id="${newID}"]`)) {
                alert(LANG.plugins.move.duplicate.replace('%s', newID));
                return;
            }

            try {
                dst.append(src);
            } catch (e) {
                console.log('move aborted', e.message); // moved into itself
                src.classList.remove('selected');
                return;
            }
            this.updateMovedItem(src, newID);
        });
        this.updatePassiveSubNamespaces(dst);
        this.sortList(dst);
    }

    /**
     * Clean up after drag'n'drop operation
     *
     * @param {DragEvent} ev
     */
    dragEndHandler(ev) {
        if (this.#dragTarget) {
            this.#dragTarget.classList.remove('drop-zone');
        }
    }

    /**
     * Open the given namespace and all its parents
     *
     * @param {string} namespace
     * @returns {Promise<void>}
     */
    async openNamespace(namespace) {
        const namespaces = namespace.split(':');

        for (let i = 0; i < namespaces.length; i++) {
            const ns = namespaces.slice(0, i + 1).join(':');
            const li = this.#mainElement.querySelectorAll(`li[data-orig="${ns}"].move-ns`);
            if (!li.length) return;

            // we might have multiple namespaces with the same ID (media and pages)
            // we open both in parallel and wait for them
            const promises = [];
            for (const el of li) {
                const ul = el.querySelector('ul');
                if (!ul) {
                    promises.push(this.toggleNamespace(el));
                }
            }
            await Promise.all(promises);
        }
    }

    /**
     * Rename an item via a prompt dialog
     *
     * @param li
     */
    renameGui(li) {
        const newname = window.prompt(LANG.plugins.move.renameitem, this.getBase(li.dataset.id));
        const clean = this.cleanID(newname);

        if (!clean) {
            return;
        }

        // avoid extension changes for media items
        if (!this.isItemNamespace(li) && this.isItemMedia(li)) {
            if (this.getExtension(li.dataset.id) !== this.getExtension(clean)) {
                alert(LANG.plugins.move.extchange);
                return;
            }
        }

        // construct new ID and check for duplicate
        const ns = this.getNamespace(li.dataset.id);
        const newID = ns ? ns + ':' + clean : clean;
        if (this.itemTree(li).querySelector(`li[data-id="${newID}"]`)) {
            alert(LANG.plugins.move.duplicate.replace('%s', newID));
            return;
        }

        // update the item
        this.updateMovedItem(li, newID);

        // if this was a namespace, update sub namespaces
        if (this.isItemNamespace(li)) {
            this.updatePassiveSubNamespaces(li.querySelector('ul'));
        }
    }


    /**
     * Open or close a namespace
     *
     * @param li
     * @returns {Promise<void>}
     */
    async toggleNamespace(li) {
        const isOpen = li.classList.toggle('open');

        // swap icon
        const icon = li.querySelector('i');
        icon.parentNode.insertBefore(this.icon(isOpen ? 'open' : 'close'), icon);
        icon.remove();

        if (isOpen) {
            // check if UL already exists and reuse it
            let ul = li.querySelector('ul');
            if (ul) {
                ul.style.display = '';
                return;
            }

            // create new UL
            ul = document.createElement('ul');
            ul.classList = li.classList;
            ul.dataset.id = li.dataset.id;
            ul.dataset.orig = li.dataset.orig;
            li.appendChild(ul);

            const promises = [];

            if (li.classList.contains('move-pages')) {
                promises.push(this.loadSubTree(li.dataset.orig, 'pages'));
            }
            if (li.classList.contains('move-media')) {
                promises.push(this.loadSubTree(li.dataset.orig, 'media'));
            }
            await Promise.all(promises);
        } else {
            const ul = li.querySelector('ul');
            if (ul) {
                ul.style.display = 'none';
            }
        }
    }

    /**
     * Load the data for a namespace
     *
     * @param {string} namespace
     * @param {string} type
     * @returns {Promise<void>}
     */
    async loadSubTree(namespace, type) {

        const data = new FormData;
        data.append('ns', namespace);
        data.append('is_media', type === 'media' ? 1 : 0);

        const response = await fetch(this.#ENDPOINT, {
            method: 'POST',
            body: data
        });
        const result = await response.json();

        this.renderSubTree(namespace, result, type);
    }

    /**
     * Render the data for a namespace
     *
     * @param {string} namespace
     * @param {object[]} data
     * @param {string} type
     */
    renderSubTree(namespace, data, type) {
        const selector = `ul[data-orig="${namespace}"].move-${type}.move-ns`;
        const parent = this.#mainElement.querySelector(selector);

        for (const item of data) {
            let li;
            // reuse namespace
            if (item.type === 'd') {
                li = parent.querySelector(`li[data-orig="${item.id}"].move-ns`);
            }
            // create new item
            if (!li) {
                li = this.createListItem(item, type);
                parent.appendChild(li);
            }
            // ensure class is added to reused namespaces
            li.classList.add(`move-${type}`);
        }

        this.sortList(parent);
        this.updatePassiveSubNamespaces(parent); // subtree might have been loaded into a renamed namespace
    }

    /**
     * Sort the children of the given element
     *
     * namespaces are sorted first, then by ID
     *
     * @param {HTMLUListElement} parent
     */
    sortList(parent) {
        [...parent.children]
            .sort((a, b) => {
                // sort namespaces first
                if (a.classList.contains('move-ns') && !b.classList.contains('move-ns')) {
                    return -1;
                }
                if (!a.classList.contains('move-ns') && b.classList.contains('move-ns')) {
                    return 1;
                }
                // sort by ID
                return a.dataset.id.localeCompare(b.dataset.id);
            })
            .forEach(node => parent.appendChild(node));
    }

    /**
     * Update the IDs of all sub-namespaces without marking them as moved
     *
     * The update is not marked as a change, because it will be covered in the move of an upper namespace.
     * But updating the ID ensures that all drags that go into this namespace will already reflect the new namespace.
     *
     * @param {HTMLUListElement} parent
     */
    updatePassiveSubNamespaces(parent) {
        const ns = parent.dataset.id; // parent is the namespace

        for (const li of parent.children) {
            if (!this.isItemNamespace(li)) continue;

            const newID = this.getNewId(li.dataset.id, ns);
            li.dataset.id = newID;

            const sub = li.getElementsByTagName('ul');
            if (sub.length) {
                sub[0].dataset.id = newID;
                this.updatePassiveSubNamespaces(sub[0]);
            }
        }
    }

    /**
     * Get the new ID when moving an item to a new namespace
     *
     * @param oldId
     * @param newNS
     * @returns {string}
     */
    getNewId(oldId, newNS) {
        const base = this.getBase(oldId);
        return newNS ? newNS + ':' + base : base;
    }

    /**
     * Adjust the ID of a moved item
     *
     * @param {HTMLLIElement} li The item to rename
     * @param {string} newID The new ID
     */
    updateMovedItem(li, newID) {
        const name = li.querySelector('span');

        if (li.dataset.id !== newID) {
            li.dataset.id = newID;
            li.classList.add('changed');
            name.textContent = this.getBase(newID);
            name.title = li.dataset.orig + ' → ' + newID;

            const ul = li.querySelector('ul');
            if (ul) {
                ul.dataset.id = newID;
            }
        } else {
            li.classList.remove('changed');
            name.title = '';
        }
    }

    /**
     * Check if an item is a namespace item
     *
     * @param {HTMLLIElement} li
     * @returns {boolean}
     */
    isItemNamespace(li) {
        return li.classList.contains('move-ns');
    }

    /**
     * Check if an item is a media item
     *
     * @param {HTMLLIElement} li
     * @returns {boolean}
     */
    isItemMedia(li) {
        return li.classList.contains('move-media');
    }

    /**
     * Check if an item is a page item
     *
     * @param {HTMLLIElement} li
     * @returns {boolean}
     */
    isItemPage(li) {
        return li.classList.contains('move-pages');
    }

    /**
     * Get the tree for the given item
     *
     * @param li
     * @returns {HTMLUListElement}
     */
    itemTree(li) {
        if (this.isItemMedia(li)) {
            return this.#mediaTree;
        } else {
            return this.#pageTree;
        }
    }

    /**
     * Create a list item
     *
     * @param {object} item
     * @param {string} type
     * @returns {HTMLLIElement}
     */
    createListItem(item, type) {
        const li = document.createElement('li');
        li.dataset.id = item.id;
        li.dataset.orig = item.id; // track the original ID
        li.classList.add(`move-${type}`);
        li.draggable = true;

        const wrapper = document.createElement('div');
        wrapper.classList.add('li');
        li.appendChild(wrapper);

        let icon;
        if (item.type === 'd') {
            li.classList.add('move-ns');
            icon = this.icon('close');
        } else if (type === 'media') {
            icon = this.icon('media');
        } else {
            icon = this.icon('page');
        }
        icon.title = LANG.plugins.move.select;
        wrapper.appendChild(icon);

        const name = document.createElement('span');
        name.textContent = this.getBase(item.id);
        wrapper.appendChild(name);

        const renameBtn = document.createElement('button');
        this.icon('rename', renameBtn);
        renameBtn.title = LANG.plugins.move.renameitem;
        wrapper.appendChild(renameBtn);

        return li;
    }

    /**
     * Create an icon element
     *
     * @param {string} type
     * @param {HTMLElement} element The element to insert the SVG into, a new <i> if not given
     * @returns {HTMLElement}
     */
    icon(type, element = null) {
        if (!element) {
            element = document.createElement('i');
        }

        element.classList.add('icon');
        element.innerHTML = `<svg viewBox="0 0 24 24"><path d="${this.icons[type]}" /></svg>`;
        return element;
    }

    /**
     * Get the base part (filename) of an ID
     *
     * @param {string} id
     * @returns {string}
     */
    getBase(id) {
        return id.split(':').slice(-1)[0];
    }

    /**
     * Get the extension part of an ID
     *
     * This isn't perfect, but adds some safety
     *
     * @param {string} id
     * @returns {string}
     */
    getExtension(id) {
        const parts = id.split('.');
        return parts.length > 1 ? parts.pop() : '';
    }

    /**
     * Get the namespace part of an ID
     *
     * @param {string} id
     * @returns {string}
     */
    getNamespace(id) {
        if (id.includes(':') === false) {
            return '';
        }
        return id.split(':').slice(0, -1).join(':');
    }

    /**
     * Very simplistic cleanID() in JavaScript
     *
     * Strips out namespaces
     *
     * @param {string} id
     */
    cleanID(id) {
        if (!id) return '';

        id = id.replace(/[!"#$%§&'()+,\/;<=>?@\[\]^`{|}~\\:*\s]+/g, '_');
        id = id.replace(/^_+/, '');
        id = id.replace(/_+$/, '');
        id = id.toLowerCase();

        return id;
    };
}
