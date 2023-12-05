import * as UI from "@ckeditor/ckeditor5-ui";
import * as Core from "@ckeditor/ckeditor5-core";
import { default as modalObject } from "@typo3/backend/modal.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
const VISUAL_SELECTION_MARKER_NAME = "openai-ui";
const aiIcon = '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="M14.5 1.2c-.3.7-.6 6.2-.7 12.3l-.3 11-4.5.3c-5 .4-6 1.3-6 5.9 0 4.6.9 6.3 3.5 6.3 1.3 0 2.6.4 2.9.9.3.5-1.7 4-4.4 7.9-4.1 5.9-5 7.9-5 11.5 0 2.3.5 4.8 1.2 5.5 1.7 1.7 59.9 1.7 61.6 0 .7-.7 1.2-3.2 1.2-5.5 0-3.6-.9-5.6-5-11.5-2.7-3.9-4.7-7.4-4.4-7.9.3-.5 1.6-.9 2.9-.9 2.6 0 3.5-1.7 3.5-6.3s-1-5.5-6-5.9l-4.5-.3-.3-6c-.3-5.9-.4-6.1-6.5-12.3L37.6 0H26.2c-8.1 0-11.4.4-11.7 1.2zM37 5.6c0 5.8 1.4 7.4 6.5 7.4H48v12H16V2h21v3.6zm6.1 3l2.4 2.6-3-.4c-2.2-.2-3.1-.9-3.3-2.6-.4-2.9.9-2.8 3.9.4zM59 31c0 3.6-.3 4-2.4 4-1.8 0-2.5-.6-2.8-2.3l-.3-2.2h-43l-.3 2.2c-.3 1.7-1 2.3-2.8 2.3-2.1 0-2.4-.4-2.4-4v-4h54v4zm-7 4v3H12v-6h40v3zm5.4 12.4c2.6 3.5 4.6 6.6 4.6 7 0 .3-13.5.6-30 .6s-30-.3-30-.6c0-.4 2-3.5 4.6-7l4.5-6.4h41.8l4.5 6.4zm4.4 12.3c-.3 1.7-2.5 1.8-29.8 1.8s-29.5-.1-29.8-1.8C1.9 58.1 3.8 58 32 58s30.1.1 29.8 1.7z"/><path d="M21.5 7.1c-.7 1-3.4 11.4-3.5 13.1 0 1.7 1.9.7 2.5-1.2.3-1.1 1.2-2 2-2 .7 0 1.5.9 1.8 2 .3 1.1 1.1 2 1.7 2 .7 0 1-.6.7-1.3-.2-.7-1-3.8-1.6-7-1.3-5.8-2.4-7.6-3.6-5.6zm1.2 7.1c-.3.8-.6.5-.6-.6-.1-1.1.2-1.7.5-1.3.3.3.4 1.2.1 1.9zM29.5 6.9c-.3.5-.1 1.2.5 1.6 1.3.8 1.3 10.5 0 10.5-.5 0-1 .4-1 1 0 .5 1.4 1 3 1 1.7 0 3-.5 3-1 0-.6-.4-1-1-1-1.3 0-1.3-9.7 0-10.5 1.5-.9.2-2.5-2-2.5-1 0-2.1.4-2.5.9zM14 44c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM20.5 44c.3.5 1.3 1 2.1 1s1.4-.5 1.4-1c0-.6-.9-1-2.1-1-1.1 0-1.7.4-1.4 1zM27 44c0 .5.6 1 1.4 1 .8 0 1.8-.5 2.1-1 .3-.6-.3-1-1.4-1-1.2 0-2.1.4-2.1 1zM33.5 44c.3.5 1.3 1 2.1 1s1.4-.5 1.4-1c0-.6-.9-1-2.1-1-1.1 0-1.7.4-1.4 1zM40 44c0 .5.6 1 1.4 1 .8 0 1.8-.5 2.1-1 .3-.6-.3-1-1.4-1-1.2 0-2.1.4-2.1 1zM46 44c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM11 48c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM17 48c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM24 48c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM30 48c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM36 48c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM43 48c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM49 48c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM8 52c0 .5.9 1 2.1 1 1.1 0 1.7-.5 1.4-1-.3-.6-1.3-1-2.1-1S8 51.4 8 52zM14 52c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM20.5 52c-.3.5.3 1 1.4 1 1.2 0 2.1-.5 2.1-1 0-.6-.6-1-1.4-1-.8 0-1.8.4-2.1 1zM27 52c0 .5.9 1 2.1 1 1.1 0 1.7-.5 1.4-1-.3-.6-1.3-1-2.1-1s-1.4.4-1.4 1zM33.5 52c-.3.5.3 1 1.4 1 1.2 0 2.1-.5 2.1-1 0-.6-.6-1-1.4-1-.8 0-1.8.4-2.1 1zM40 52c0 .5.9 1 2.1 1 1.1 0 1.7-.5 1.4-1-.3-.6-1.3-1-2.1-1s-1.4.4-1.4 1zM46 52c0 .5.9 1 2 1s2-.5 2-1c0-.6-.9-1-2-1s-2 .4-2 1zM52.5 52c-.3.5.3 1 1.4 1 1.2 0 2.1-.5 2.1-1 0-.6-.6-1-1.4-1-.8 0-1.8.4-2.1 1z"/></svg>';
let select_model = "text-davinci-003", select_temperature = 0.5, select_max_tokens = 4e3, select_amount = 1;
const escapeHtml = (unsafe) => {
    return unsafe.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
};
/*export class OpenaiCommand extends Core.Command {
    constructor() {
        super(...arguments), (this.attrs = {});
    }
    execute(e, t = {}) {
        const i = this.editor.model,
            n = i.document.selection;
            console.log('click',i);
    }
}*/

export class Openai extends Core.Plugin {
    static pluginName = 'Openai';

    init() {
        const e = this.editor;
            this.createToolbarAIButtons();
          /*  e.conversion
                .for("editingDowncast")
                .markerToHighlight({
                    model: "openai-ui",
                    view: { classes: ["ck-fake-link-selection"] },
                }),
            e.conversion
                .for("editingDowncast")
                .markerToElement({
                    model: "openai-ui",
                    view: {
                        name: "span",
                        classes: [
                            "ck-fake-link-selection",
                            "ck-fake-link-selection_collapsed",
                        ],
                    },
                });*/
        // e.commands.add("openai", new OpenaiCommand(e));
    }
    createToolbarAIButtons() {
        const e = this.editor;
            e.ui.componentFactory.add(Openai.pluginName, (e) => {
                const n = new UI.ButtonView(e);
                n.set({
                    label: 'OpenAi',
                    withText: false,
                    tooltip: 'OpenAI Content Assistance',
                    icon: aiIcon,
                });
                this.listenTo(n, 'execute', () => this.showUI());
                return n;
            });
    }
    showUI() {
        this.openLinkBrowser(this.editor)
    }
    openLinkBrowser(e) {
        var self = this;
        const url = new URL('dialogs/index.html', import.meta.url).pathname;

        var bc = new BroadcastChannel('fontawesome:selected');
        var modal = modalObject.advanced({
            type: modalObject.types.iframe,
            title: 'Fontawesome',
            content: url,
            size: modalObject.sizes.medium,
            // severity: SeverityEnum.info,
            callback: (t) => {
                var iframe = t.querySelector('iframe');
                iframe.addEventListener('load', (e) => {
                    self._addFontawesomeCss(iframe.contentDocument);
                }, true);

                bc.onmessage = function (message) {
                    self.insertIcon(message.data);
                    t.hideModal();
                };
            }
        });

        modal.addEventListener('typo3-modal-hide', e => {
            bc.close();
        });
/*        let content = '<div><h2>Modal Content</h2>';
        var b = new BroadcastChannel('fontawesome:selected');
        var modal =  modalObject.advanced({
            type: modalObject.types.iframe,
            title: 'OpenAI Content Assistance',
            content: '<div><h2>Modal Content</h2>',
            size: {
              width: 400,
              height: 800
            },
            severity: SeverityEnum.info,

            callback: (t) => {
                var iframe = t.querySelector('iframe');
                iframe.setAttribute('id', 'customOurIframe');
                
                if(iframe.getAttribute('id').value === 'customOurIframe'){
                    iframe.addEventListener('load', (e) => {
                        this.addFontawesomeCss(content);
                        alert('test');
                    }, true);
                }
            }
*/
         /*   callback: (t) => {
                (t.userData.editor = e),
                  (t.userData.selectionStartPosition =
                    e.model.document.selection.getFirstPosition()),
                  (t.userData.selectionEndPosition =
                    e.model.document.selection.getLastPosition()),
                  t.querySelector(".t3js-modal-body")?.setAttribute("id", "123");
              },

            // renderModalBody(){},
          });*/
    }
    _addFontawesomeCss(HTMLDocumentNode){
        console.log(HTMLDocumentNode);
        let head = document.getElementsByTagName('HEAD')[0];
        // Create new link Element
        let link = document.createElement('link');
        // set the attributes for link element
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = '';
        // Append link element to HTML head
        console.log(head, link, HTMLDocumentNode);
        head.appendChild(link);
    }

    insertIcon(iconClass) {
        if (typeof iconClass == 'object') iconClass = iconClass.join(' ');
        const content = `<i class="${iconClass}" aria-hidden="true">&nbsp;</i> `;

        const viewFragment = this.editor.data.processor.toView(content);
        const modelFragment = this.editor.data.toModel(viewFragment);

        this.editor.model.insertContent(modelFragment, this.editor.model.document.selection.getFirstPosition());
    }
}
