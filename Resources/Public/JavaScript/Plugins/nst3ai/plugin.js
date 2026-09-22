CKEDITOR.dialog.add("nsT3AiContentDialog", function (editor) {
    let select_temperature = 0.5,
        select_max_tokens = 4e3,
        select_amount = 1;
    return {
        title: "T3AI Content Assistance",
        minWidth: 400,
        minHeight: 70,
        contents: [
            {
                id: "ns-tab-basic",
                label: editor.lang.nst3ai_content.tabGeneral,
                accessKey: "C",
                elements: [
                    {
                        type: "text",
                        id: "ns-t3ai-prompt",
                        label: editor.lang.nst3ai_content.writeAbout,
                        rows: 2,
                        validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.nst3ai_content.errorNotEmpty),
                        setup: function (element) {
                            this.setValue(element.getText());
                        },
                        commit: function (element) {
                            let generatedContent = this.getDialog().getContentElement('ns-tab-basic', 'ns-t3ai-generated-content').getValue();
                            element.setHtml(generatedContent);
                        }
                    },
                    {
                        type: "button",
                        id: "ns-t3ai-generate",
                        label: editor.lang.nst3ai_content.generate,
                        onClick: function (element) {
                            let promptElement = this.getDialog().getContentElement('ns-tab-basic', 'ns-t3ai-prompt');
                            let promptValue = promptElement && promptElement.getValue().trim();
                            if (!promptValue) {
                                alert(editor.lang.nst3ai_content.errorNotEmpty);
                            } else {
                                select_temperature = parseFloat(this.getDialog().getValueOf("tab-advanced", "temperature"));
                                select_amount = parseInt(this.getDialog().getValueOf("tab-advanced", "amount"));
                                let resElement = this.getDialog().getContentElement('ns-tab-basic', 'ns-t3ai-generated-content');
                                var buttonElement = this.getDialog().getContentElement('ns-tab-basic', 'ns-t3ai-generate');
                                buttonElement.disable();
                                resElement.setValue('Loading...');

                                var ajaxUrl = (typeof TYPO3 !== 'undefined'
                                    && TYPO3.settings
                                    && TYPO3.settings.ajaxUrls
                                    && TYPO3.settings.ajaxUrls.rte_content_generation)
                                    ? TYPO3.settings.ajaxUrls.rte_content_generation
                                    : null;

                                if (!ajaxUrl) {
                                    resElement.setValue('Error: Backend AJAX unavailable');
                                    buttonElement.enable();
                                    return;
                                }

                                var params = new URLSearchParams();
                                params.append('prompt', promptValue);
                                params.append('max_tokens', String(select_max_tokens));
                                params.append('temperature', String(select_temperature));
                                params.append('top_p', '1');
                                params.append('n', String(select_amount));
                                params.append('frequency_penalty', '0');
                                params.append('presence_penalty', '0');

                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', ajaxUrl, true);
                                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                xhr.withCredentials = true;
                                xhr.onreadystatechange = function () {
                                    if (this.readyState !== 4) {
                                        return;
                                    }
                                    buttonElement.enable();
                                    if (this.status === 200) {
                                        try {
                                            var responseBody = JSON.parse(this.responseText);
                                            if (responseBody.success && responseBody.generatedContent) {
                                                resElement.setValue(String(responseBody.generatedContent).trim());
                                            } else {
                                                resElement.setValue('Error: ' + (responseBody.error || 'generation failed'));
                                            }
                                        } catch (parseError) {
                                            resElement.setValue('Error: invalid response');
                                        }
                                    } else {
                                        resElement.setValue('Error: ' + this.status);
                                    }
                                };
                                xhr.onerror = function () {
                                    resElement.setValue('Error: network failure');
                                    buttonElement.enable();
                                };
                                xhr.send(params.toString());
                            }
                        }
                    },
                    {
                        type: "textarea",
                        id: "ns-t3ai-generated-content",
                        rows: 5,
                        validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.nst3ai_content.errorNotEmpty),
                    }
                ]
            },
            {
                id: "tab-advanced",
                label: editor.lang.nst3ai_content.tabAdvanced,
                elements: [
                    {
                        type: "select",
                        id: "temperature",
                        title: editor.lang.nst3ai_content.temperature,
                        label: editor.lang.nst3ai_content.temperatureLabel,
                        default: 0.5,
                        items: [
                            ["0.0", 0.01],
                            ["0.25", 0.25],
                            ["0.5", 0.5],
                            ["0.75", 0.75],
                            ["1.0", 1],
                            ["1.25", 1.25],
                            ["1.5", 1.5],
                            ["1.75", 1.75],
                            ["2.0", 2]
                        ],
                        setup: function (element) {
                            element.setAttribute("type", "number");
                            this.setValue(element.getText());
                        },
                        commit: function (element) {
                        }
                    },
                    {
                        type: "select",
                        id: "amount",
                        title: editor.lang.nst3ai_content.amount,
                        label: editor.lang.nst3ai_content.amountLabel,
                        default: 1,
                        items: [
                            ["1", 1],
                            ["2", 2],
                            ["3", 3],
                            ["4", 4]
                        ],
                        setup: function (element) {
                            element.setAttribute("type", "number");
                            this.setValue(element.getText());
                        },
                        commit: function (element) {
                        }
                    }
                ]
            }
        ],
        onOk: function () {
            let dialog = this, nst3ai = editor.document.createElement("div");
            select_temperature = parseFloat(dialog.getValueOf("tab-advanced", "temperature"));
            select_amount = parseInt(dialog.getValueOf("tab-advanced", "amount"));
            select_max_tokens = 4e3;
            dialog.commitContent(nst3ai);
            editor.insertElement(nst3ai);
        }
    };
});
CKEDITOR.plugins.add("nst3ai_content", {
    icons: "ns-t3ai",
    lang: ["en", "de"],
    init: function (editor) {
        editor.addCommand("nst3ai_content", new CKEDITOR.dialogCommand("nsT3AiContentDialog"));
        editor.ui.addButton("ns_t3ai", {
            label: "T3AI Content Assistance",
            command: "nst3ai_content",
            toolbar: "insert",
            icon: this.path + "icons/ns-copywriter.png"
        });
    }
});
CKEDITOR.config.keystrokes = [
    [CKEDITOR.ALT + 67, "nst3ai_content"]
];
